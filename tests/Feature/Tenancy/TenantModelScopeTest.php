<?php

namespace Tests\Feature\Tenancy;

use App\Models\Empresa;
use App\Models\Operadora;
use App\Models\User;
use App\Models\VendaDependente;
use App\Models\VendaTitular;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class TenantModelScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(TenantContext::class)->clear();
    }

    public function test_modelo_com_escopo_retorna_apenas_recursos_da_empresa_ativa(): void
    {
        [$empresaA, $empresaB] = $this->empresas();
        $operadoraA = Operadora::query()->create(['empresa_id' => $empresaA->id, 'nome' => 'Operadora A']);
        $operadoraB = Operadora::query()->create(['empresa_id' => $empresaB->id, 'nome' => 'Operadora B']);

        app(TenantContext::class)->set($empresaA->id);

        $this->assertSame([$operadoraA->id], Operadora::query()->pluck('id')->all());
        $this->assertNull(Operadora::query()->find($operadoraB->id));
    }

    public function test_modelo_com_escopo_falha_fechado_sem_empresa_ativa(): void
    {
        [$empresaA, $empresaB] = $this->empresas();
        Operadora::query()->create(['empresa_id' => $empresaA->id, 'nome' => 'Operadora A']);
        Operadora::query()->create(['empresa_id' => $empresaB->id, 'nome' => 'Operadora B']);

        app(TenantContext::class)->clear();

        $this->assertSame(0, Operadora::query()->count());
        $this->assertNull(Operadora::query()->first());
        $this->assertSame(2, Operadora::withoutGlobalScope('tenant')->count());
    }

    public function test_modelo_nao_grava_sem_contexto_nem_empresa_explicita(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Não é permitido gravar este recurso sem uma empresa ativa.');

        Operadora::query()->create(['nome' => 'Sem empresa']);
    }

    public function test_modelo_preenche_empresa_ativa_quando_ela_nao_e_informada(): void
    {
        [$empresaA] = $this->empresas();
        app(TenantContext::class)->set($empresaA->id);

        $operadora = Operadora::query()->create(['nome' => 'Operadora segura']);

        $this->assertSame($empresaA->id, $operadora->empresa_id);
        $this->assertDatabaseHas('operadoras', ['id' => $operadora->id, 'empresa_id' => $empresaA->id]);
    }

    public function test_modelo_rejeita_empresa_divergente_na_criacao_e_alteracao(): void
    {
        [$empresaA, $empresaB] = $this->empresas();
        $foreignOperator = Operadora::query()->create(['empresa_id' => $empresaB->id, 'nome' => 'Operadora externa']);
        app(TenantContext::class)->set($empresaA->id);

        try {
            Operadora::query()->create(['empresa_id' => $empresaB->id, 'nome' => 'Invasão']);
            $this->fail('A criação com empresa divergente deveria falhar.');
        } catch (LogicException $exception) {
            $this->assertSame('O recurso não pertence à empresa ativa.', $exception->getMessage());
        }

        $this->expectException(LogicException::class);
        $foreignOperator->nome = 'Alteração indevida';
        $foreignOperator->save();
    }

    public function test_modelos_herdados_filtram_e_validam_o_tenant_ate_o_pai_direto(): void
    {
        // Cadeia de dois níveis: vendas -> vendas_titulares -> vendas_dependentes.
        [$empresaA, $empresaB] = $this->empresas();
        $userA = User::factory()->create(['empresa_id' => $empresaA->id]);
        $userB = User::factory()->create(['empresa_id' => $empresaB->id]);
        $vendaA = $this->venda($empresaA->id, $userA->id);
        $vendaB = $this->venda($empresaB->id, $userB->id);
        $titularA = VendaTitular::query()->create(['venda_id' => $vendaA, 'nome' => 'Titular A']);
        $titularB = VendaTitular::query()->create(['venda_id' => $vendaB, 'nome' => 'Titular B']);
        VendaDependente::query()->create(['venda_id' => $vendaA, 'titular_id' => $titularA->id, 'nome' => 'Dependente A']);
        VendaDependente::query()->create(['venda_id' => $vendaB, 'titular_id' => $titularB->id, 'nome' => 'Dependente B']);

        app(TenantContext::class)->set($empresaA->id);

        $this->assertSame([$titularA->id], VendaTitular::query()->pluck('id')->all());
        $this->assertSame(['DEPENDENTE A'], VendaDependente::query()->pluck('nome')->all());

        try {
            VendaTitular::query()->create(['venda_id' => $vendaB, 'nome' => 'Tentativa externa']);
            $this->fail('O titular de uma venda externa deveria ser rejeitado.');
        } catch (LogicException $exception) {
            $this->assertSame('O recurso pai não pertence à empresa ativa.', $exception->getMessage());
        }

        $this->expectException(LogicException::class);
        VendaDependente::query()->create([
            'venda_id' => $vendaB,
            'titular_id' => $titularB->id,
            'nome' => 'Dependente externo',
        ]);
    }

    private function venda(int $empresaId, int $userId): int
    {
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $empresaId,
            'user_import_id' => $userId,
            'nome_cliente' => 'Cliente '.$empresaId,
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('vendas')->insertGetId([
            'empresa_id' => $empresaId,
            'user_id' => $userId,
            'contato_id' => $contatoId,
            'nome_contrato' => 'Contrato '.$empresaId,
            'data_vigencia' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_visibilidade_de_usuario_distingue_membro_de_autor_da_plataforma(): void
    {
        [$empresaA, $empresaB] = $this->empresas();
        $membroA = User::factory()->create(['empresa_id' => $empresaA->id]);
        $membroB = User::factory()->create(['empresa_id' => $empresaB->id]);
        $masterDaEmpresaA = User::factory()->create([
            'empresa_id' => $empresaA->id,
            'is_platform_admin' => true,
        ]);
        $master = User::factory()->create([
            'empresa_id' => $empresaB->id,
            'is_platform_admin' => true,
        ]);

        $this->assertSame(
            [$membroA->id],
            User::query()->tenantMember($empresaA->id)->orderBy('id')->pluck('id')->all(),
        );
        $this->assertSame(
            [$membroA->id, $masterDaEmpresaA->id, $master->id],
            User::query()->tenantActor($empresaA->id)->orderBy('id')->pluck('id')->all(),
        );
        $this->assertNotContains($membroB->id, User::query()->tenantActor($empresaA->id)->pluck('id')->all());
    }

    /** @return array{Empresa, Empresa} */
    private function empresas(): array
    {
        return [
            Empresa::factory()->create(),
            Empresa::factory()->create(),
        ];
    }
}
