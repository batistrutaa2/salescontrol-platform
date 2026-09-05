<?php

namespace Tests\Feature\Backoffice;

use App\Enums\TabulationCode;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Services\TabulationCatalog;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PosVendaSettingsTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::BACKOFFICE, 'tipo_usuario' => 'BACKOFFICE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_master_configura_janela_somente_na_empresa_ativa(): void
    {
        [$empresaA, $empresaB, $master] = $this->cenarioMaster();

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $empresaB->id])
            ->patch(route('backoffice.posVenda.settings'), [
                'pos_venda_aniversarios_janela_dias' => 45,
            ])
            ->assertRedirect(route('backoffice.posVenda'));

        $this->assertSame(30, $empresaA->fresh()->pos_venda_aniversarios_janela_dias);
        $this->assertSame(45, $empresaB->fresh()->pos_venda_aniversarios_janela_dias);
        $this->assertNull($master->fresh()->empresa_id);
    }

    public function test_backoffice_visualiza_a_janela_mas_nao_pode_altera_la(): void
    {
        $empresa = Empresa::factory()->create(['pos_venda_aniversarios_janela_dias' => 20]);
        app(TabulationCatalog::class)->provision($empresa->id);
        $backoffice = User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::BACKOFFICE,
            'ativo' => 'Y',
        ]);

        $this->actingAs($backoffice)
            ->get(route('backoffice.posVenda'))
            ->assertOk()
            ->assertSee('Próximos 20 Dias')
            ->assertDontSee('Salvar janela');

        $this->patch(route('backoffice.posVenda.settings'), [
            'pos_venda_aniversarios_janela_dias' => 10,
        ])->assertForbidden();

        $this->assertSame(20, $empresa->fresh()->pos_venda_aniversarios_janela_dias);
    }

    public function test_kpi_respeita_janela_e_empresa_ativa(): void
    {
        [$empresaA, $empresaB, $master] = $this->cenarioMaster();
        $empresaB->update(['pos_venda_aniversarios_janela_dias' => 15]);

        $this->criarVendaImplantada($empresaB, today()->addDays(10)->subYears(2), 'Dentro da janela');
        $this->criarVendaImplantada($empresaB, today()->addDays(20)->subYears(2), 'Fora da janela');
        $this->criarVendaImplantada($empresaA, today()->addDays(5)->subYears(2), 'Outra corretora');

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $empresaB->id])
            ->getJson(route('backoffice.getPosVendaData'))
            ->assertOk()
            ->assertJsonPath('kpis.total_implantados', 2)
            ->assertJsonPath('kpis.proximos_aniversarios', 1)
            ->assertJsonPath('kpis.aniversarios_janela_dias', 15)
            ->assertDontSee('Outra corretora');
    }

    public function test_janela_invalida_e_rejeitada(): void
    {
        [, $empresa, $master] = $this->cenarioMaster();

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $empresa->id])
            ->patch(route('backoffice.posVenda.settings'), [
                'pos_venda_aniversarios_janela_dias' => 366,
            ])
            ->assertSessionHasErrors('pos_venda_aniversarios_janela_dias');
    }

    /** @return array{Empresa, Empresa, User} */
    private function cenarioMaster(): array
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        app(TabulationCatalog::class)->provision($empresaA->id);
        app(TabulationCatalog::class)->provision($empresaB->id);
        $master = User::factory()->create([
            'empresa_id' => null,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        return [$empresaA, $empresaB, $master];
    }

    private function criarVendaImplantada(Empresa $empresa, \DateTimeInterface $data, string $nome): void
    {
        $vendedor = User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $empresa->id,
            'user_import_id' => $vendedor->id,
            'nome_cliente' => $nome,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vendas')->insert([
            'empresa_id' => $empresa->id,
            'user_id' => $vendedor->id,
            'contato_id' => $contatoId,
            'tabulacao_id' => app(TabulationCatalog::class)->id($empresa->id, TabulationCode::IMPLANTADO),
            'nome_contrato' => $nome,
            'data_vigencia' => $data,
            'data_implantacao' => $data,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
