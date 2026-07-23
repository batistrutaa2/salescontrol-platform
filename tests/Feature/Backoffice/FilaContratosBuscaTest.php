<?php

namespace Tests\Feature\Backoffice;

use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Vendas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Busca da Fila de Contratos.
 *
 * Dois defeitos cobertos aqui: o LIKE exigia a frase inteira contígua (procurar
 * "empresa x10" não achava "X10 COMERCIO E AUTOMACAO LTDA") e a fila não tem
 * raia para status finalizados, então contrato implantado sumia sem explicação.
 */
class FilaContratosBuscaTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);

        // VENDA tem raia na fila; IMPLANTADO não (contrato já concluído).
        DB::table('tabulacoes')->insert([
            [
                'id' => Tabulations::VENDA, 'empresa_id' => $this->empresa->id, 'descricao' => 'VENDA',
                'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'status' => 'Y', 'ordem_kanban' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'id' => Tabulations::IMPLANTADO, 'empresa_id' => $this->empresa->id, 'descricao' => 'IMPLANTADO',
                'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'status' => 'Y', 'ordem_kanban' => 9,
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
    }

    private function criarContrato(string $nome, int $tabulacaoId, ?Empresa $empresa = null): Vendas
    {
        $empresa = $empresa ?? $this->empresa;

        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $empresa->id,
            'user_import_id' => $this->admin->id,
            'nome_cliente' => 'Cliente '.uniqid(),
            'cpf' => (string) random_int(10000000000, 99999999999),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Vendas::create([
            'empresa_id' => $empresa->id,
            'user_id' => $this->admin->id,
            'contato_id' => $contatoId,
            'tabulacao_id' => $tabulacaoId,
            'nome_contrato' => $nome,
            'cpf_cnpj' => (string) random_int(10000000000000, 99999999999999),
            'operadora' => 'AMIL',
            'valor_contrato' => 500.00,
            'vidas' => 1,
            'data_vigencia' => now(),
        ]);
    }

    private function pipeline(array $params = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->getJson(route('backoffice.pipelineData', $params));
    }

    private function idsNaFila(\Illuminate\Testing\TestResponse $resp): \Illuminate\Support\Collection
    {
        return collect($resp->json('pipeline'))
            ->flatMap(fn ($coluna) => collect($coluna['contratos'])->pluck('id'));
    }

    public function test_filtro_da_fila_casa_palavras_soltas_fora_de_ordem(): void
    {
        $x10 = $this->criarContrato('X10 COMERCIO E AUTOMACAO LTDA', Tabulations::VENDA);

        foreach (['empresa x10', 'x10', 'automacao comercio'] as $termo) {
            $this->assertTrue(
                $this->idsNaFila($this->pipeline(['busca' => $termo]))->contains($x10->id),
                "Busca por \"{$termo}\" deveria achar o contrato na fila."
            );
        }
    }

    public function test_contrato_fora_da_fila_e_sinalizado_em_vez_de_sumir(): void
    {
        $implantado = $this->criarContrato('X10 COMERCIO E AUTOMACAO LTDA', Tabulations::IMPLANTADO);

        $resp = $this->pipeline(['busca' => 'empresa x10'])->assertOk();

        // Não aparece nas raias — IMPLANTADO não é estágio da fila.
        $this->assertFalse($this->idsNaFila($resp)->contains($implantado->id));

        // Mas o usuário é avisado de onde ele está.
        $fora = collect($resp->json('fora_da_fila'));
        $this->assertCount(1, $fora);
        $this->assertSame($implantado->id, $fora->first()['id']);
        $this->assertSame('IMPLANTADO', $fora->first()['status_atual']);
    }

    public function test_sem_busca_nao_sinaliza_contratos_fora_da_fila(): void
    {
        $this->criarContrato('X10 COMERCIO E AUTOMACAO LTDA', Tabulations::IMPLANTADO);

        $this->pipeline()->assertOk()->assertJsonPath('fora_da_fila', []);
    }

    public function test_fora_da_fila_nao_vaza_contrato_de_outra_empresa(): void
    {
        $outra = Empresa::factory()->create();
        $this->criarContrato('X10 COMERCIO E AUTOMACAO LTDA', Tabulations::IMPLANTADO, $outra);

        $this->pipeline(['busca' => 'x10'])->assertOk()->assertJsonPath('fora_da_fila', []);
    }

    public function test_curinga_digitado_nao_vira_wildcard_no_filtro(): void
    {
        $this->criarContrato('CLINICA POPULAR', Tabulations::VENDA);

        $resp = $this->pipeline(['busca' => '%%'])->assertOk();

        $this->assertTrue($this->idsNaFila($resp)->isEmpty(), 'Curinga digitado não pode devolver a fila inteira.');
    }
}
