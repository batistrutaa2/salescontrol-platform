<?php

namespace Tests\Feature\Comercial;

use App\Enums\TabulationCode;
use App\Enums\UserRole;
use App\Models\Contatos;
use App\Models\Empresa;
use App\Models\Preditiva;
use App\Models\User;
use App\Models\Vendas;
use App\Services\TabulationCatalog;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Exclusão em massa da fila preditiva: mesma regra do "Excluir Permanentemente"
 * de cada linha, aplicada aos leads selecionados.
 */
class PreditivaExclusaoMassaTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
        app(TabulationCatalog::class)->provision($this->empresa->id);
        $this->admin = $this->usuario($this->empresa, UserRole::ADMINISTRATIVO);
        app(TenantContext::class)->set($this->empresa->id);
    }

    public function test_exclui_os_leads_selecionados_e_registra_no_log(): void
    {
        $leads = collect(['Lead A', 'Lead B'])->map(fn ($nome) => $this->leadNaFila($nome));
        $preservado = $this->leadNaFila('Lead nao selecionado');

        $this->actingAs($this->admin)
            ->postJson(route('preditiva.excluirMultiplos'), ['ids' => $leads->pluck('id')->all()])
            ->assertOk()
            ->assertJson(['success' => true, 'excluidos' => 2, 'ignorados' => 0]);

        foreach ($leads as $lead) {
            $this->assertDatabaseHas('contatos', ['id' => $lead->id, 'status' => 'N']);
            $this->assertDatabaseMissing('preditiva', ['contato_id' => $lead->id]);
            $this->assertDatabaseHas('log_preditiva', [
                'empresa_id' => $this->empresa->id,
                'contato_id' => $lead->id,
                'tabulacao' => 'EXCLUIDO',
                'acao' => 'DESCARTE',
                'user_id' => $this->admin->id,
            ]);
        }

        // Quem não foi selecionado continua ativo e na fila.
        $this->assertDatabaseHas('contatos', ['id' => $preservado->id, 'status' => 'Y']);
        $this->assertDatabaseHas('preditiva', ['contato_id' => $preservado->id, 'status' => 'Y']);
    }

    public function test_lead_com_venda_cadastrada_e_mantido_e_o_resto_segue(): void
    {
        $comVenda = $this->leadNaFila('Lead com venda');
        $semVenda = $this->leadNaFila('Lead sem venda');
        $this->criarVenda($comVenda);

        $this->actingAs($this->admin)
            ->postJson(route('preditiva.excluirMultiplos'), ['ids' => [$comVenda->id, $semVenda->id]])
            ->assertOk()
            ->assertJson(['success' => true, 'excluidos' => 1, 'ignorados' => 1]);

        $this->assertDatabaseHas('contatos', ['id' => $comVenda->id, 'status' => 'Y']);
        $this->assertDatabaseHas('preditiva', ['contato_id' => $comVenda->id]);
        $this->assertDatabaseMissing('log_preditiva', ['contato_id' => $comVenda->id]);

        $this->assertDatabaseHas('contatos', ['id' => $semVenda->id, 'status' => 'N']);
        $this->assertDatabaseMissing('preditiva', ['contato_id' => $semVenda->id]);
    }

    public function test_selecao_inteira_com_venda_cancela_a_exclusao(): void
    {
        $lead = $this->leadNaFila('Lead com venda');
        $this->criarVenda($lead);

        $this->actingAs($this->admin)
            ->postJson(route('preditiva.excluirMultiplos'), ['ids' => [$lead->id]])
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'ignorados' => 1]);

        $this->assertDatabaseHas('contatos', ['id' => $lead->id, 'status' => 'Y']);
        $this->assertDatabaseHas('preditiva', ['contato_id' => $lead->id]);
    }

    public function test_lead_de_outra_empresa_e_rejeitado_na_validacao(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        app(TabulationCatalog::class)->provision($outraEmpresa->id);
        $leadExterno = app(TenantContext::class)->run(
            $outraEmpresa->id,
            fn () => $this->leadNaFila('Lead externo', $outraEmpresa),
        );
        $leadLocal = $this->leadNaFila('Lead local');

        $this->actingAs($this->admin)
            ->postJson(route('preditiva.excluirMultiplos'), ['ids' => [$leadLocal->id, $leadExterno->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids.1');

        // Nenhum dos dois pode ter sido tocado.
        $this->assertDatabaseHas('contatos', ['id' => $leadLocal->id, 'status' => 'Y']);
        $this->assertDatabaseHas('contatos', ['id' => $leadExterno->id, 'status' => 'Y']);
        $this->assertDatabaseHas('preditiva', ['contato_id' => $leadLocal->id]);
    }

    public function test_vendedor_nao_pode_excluir_em_massa(): void
    {
        $lead = $this->leadNaFila('Lead');
        $vendedor = $this->usuario($this->empresa, UserRole::VENDEDOR);

        $this->actingAs($vendedor)
            ->postJson(route('preditiva.excluirMultiplos'), ['ids' => [$lead->id]])
            ->assertForbidden();

        $this->assertDatabaseHas('contatos', ['id' => $lead->id, 'status' => 'Y']);
    }

    private function usuario(Empresa $empresa, int $role): User
    {
        return User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => $role,
            'ativo' => 'Y',
        ]);
    }

    private function leadNaFila(string $nome, ?Empresa $empresa = null): Contatos
    {
        $empresa = $empresa ?? $this->empresa;
        $importador = $empresa->is($this->empresa) ? $this->admin : $this->usuario($empresa, UserRole::ADMINISTRATIVO);

        $contato = Contatos::create([
            'empresa_id' => $empresa->id,
            'user_import_id' => $importador->id,
            'nome_cliente' => $nome,
            'status' => 'Y',
        ]);

        Preditiva::create([
            'empresa_id' => $empresa->id,
            'contato_id' => $contato->id,
            'status' => 'Y',
        ]);

        return $contato;
    }

    private function criarVenda(Contatos $contato): Vendas
    {
        return Vendas::create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->admin->id,
            'contato_id' => $contato->id,
            'tabulacao_id' => app(TabulationCatalog::class)->id($this->empresa->id, TabulationCode::IMPLANTADO),
            'nome_contrato' => 'Contrato '.$contato->id,
            'cpf_cnpj' => '12345678000190',
            'operadora' => 'AMIL',
            'valor_contrato' => 500.00,
            'vidas' => 1,
            'data_vigencia' => now(),
            'data_implantacao' => now(),
        ]);
    }
}
