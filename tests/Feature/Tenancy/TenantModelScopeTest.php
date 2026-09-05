<?php

namespace Tests\Feature\Tenancy;

use App\Models\Empresa;
use App\Models\EstudoItens;
use App\Models\Estudos;
use App\Models\EstudoVidas;
use App\Models\Operadora;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        [$empresaA, $empresaB] = $this->empresas();
        $userA = User::factory()->create(['empresa_id' => $empresaA->id]);
        $userB = User::factory()->create(['empresa_id' => $empresaB->id]);
        $estudoA = Estudos::query()->create([
            'empresa_id' => $empresaA->id,
            'user_id' => $userA->id,
            'titulo' => 'Estudo A',
            'link_unico' => 'estudo-tenant-a',
        ]);
        $estudoB = Estudos::query()->create([
            'empresa_id' => $empresaB->id,
            'user_id' => $userB->id,
            'titulo' => 'Estudo B',
            'link_unico' => 'estudo-tenant-b',
        ]);
        $itemA = EstudoItens::query()->create([
            'estudo_id' => $estudoA->id,
            'operadora_plano' => 'Plano A',
            'coparticipacao' => 'Não',
            'categoria' => 'Individual',
        ]);
        $itemB = EstudoItens::query()->create([
            'estudo_id' => $estudoB->id,
            'operadora_plano' => 'Plano B',
            'coparticipacao' => 'Não',
            'categoria' => 'Individual',
        ]);
        EstudoVidas::query()->create([
            'estudo_item_id' => $itemA->id,
            'faixa' => '00-18',
            'qtde' => 1,
        ]);
        EstudoVidas::query()->create([
            'estudo_item_id' => $itemB->id,
            'faixa' => '19-23',
            'qtde' => 2,
        ]);

        app(TenantContext::class)->set($empresaA->id);

        $this->assertSame([$itemA->id], EstudoItens::query()->pluck('id')->all());
        $this->assertSame(['00-18'], EstudoVidas::query()->pluck('faixa')->all());

        try {
            EstudoItens::query()->create([
                'estudo_id' => $estudoB->id,
                'operadora_plano' => 'Tentativa externa',
                'coparticipacao' => 'Não',
                'categoria' => 'Individual',
            ]);
            $this->fail('O filho de um estudo externo deveria ser rejeitado.');
        } catch (LogicException $exception) {
            $this->assertSame('O recurso pai não pertence à empresa ativa.', $exception->getMessage());
        }

        $this->expectException(LogicException::class);
        EstudoVidas::query()->create([
            'estudo_item_id' => $itemB->id,
            'faixa' => '24-28',
            'qtde' => 1,
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
