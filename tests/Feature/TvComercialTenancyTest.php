<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\MetaDiaria;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TvComercialTenancyTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private Empresa $outraEmpresa;

    private User $admin;

    private User $vendedor;

    private User $vendedorOutraEmpresa;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->outraEmpresa = Empresa::factory()->create();
        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
        $this->vendedor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
            'name' => 'Vendedor Empresa A',
        ]);
        $this->vendedorOutraEmpresa = User::factory()->create([
            'empresa_id' => $this->outraEmpresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
            'name' => 'Vendedor Empresa B',
        ]);
    }

    public function test_empresa_id_na_url_nao_abre_mais_o_painel_publico(): void
    {
        $this->get('/tv-comercial/painel?empresa_id='.$this->empresa->id)->assertNotFound();
        $this->get('/tv-comercial/dados?empresa_id='.$this->empresa->id)->assertNotFound();
    }

    public function test_token_publico_exibe_somente_dados_da_empresa_vinculada(): void
    {
        $this->empresa->update([
            'tv_percentual_atencao' => 20,
            'tv_percentual_bom' => 60,
        ]);
        MetaDiaria::create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->vendedor->id,
            'data' => today(),
            'meta_cotacoes' => 10,
            'cotacoes_realizadas' => 4,
        ]);
        MetaDiaria::create([
            'empresa_id' => $this->outraEmpresa->id,
            'user_id' => $this->vendedorOutraEmpresa->id,
            'data' => today(),
            'meta_cotacoes' => 99,
            'cotacoes_realizadas' => 88,
        ]);

        $url = $this->actingAs($this->admin)
            ->postJson(route('tv-comercial.regenerar-acesso'))
            ->assertOk()
            ->json('url');

        $this->get($url)
            ->assertOk()
            ->assertSee('if (accessToken)', false)
            ->assertDontSee('if (empresaId)', false);

        $token = basename(parse_url($url, PHP_URL_PATH));
        $this->getJson(route('tv-comercial.dados', ['token' => $token]))
            ->assertOk()
            ->assertJsonPath('metas.0.vendedor', 'Vendedor Empresa A')
            ->assertJsonPath('metas.0.status', 'atencao')
            ->assertJsonPath('configuracao.percentual_atencao', 20)
            ->assertJsonPath('configuracao.percentual_bom', 60)
            ->assertJsonCount(1, 'metas')
            ->assertJsonMissing(['vendedor' => 'Vendedor Empresa B']);
    }

    public function test_regenerar_acesso_revoga_imediatamente_o_token_anterior(): void
    {
        $primeiraUrl = $this->actingAs($this->admin)
            ->postJson(route('tv-comercial.regenerar-acesso'))
            ->json('url');
        $segundaUrl = $this->postJson(route('tv-comercial.regenerar-acesso'))->json('url');

        $this->get($primeiraUrl)->assertNotFound();
        $this->get($segundaUrl)->assertOk();
    }

    public function test_nao_cadastra_meta_para_usuario_de_outra_empresa(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('tv-comercial.salvar-metas'), [
                'data' => today()->format('Y-m-d'),
                'metas' => [[
                    'user_id' => $this->vendedorOutraEmpresa->id,
                    'meta_cotacoes' => 10,
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('metas.0.user_id');

        $this->assertDatabaseMissing('metas_diarias', [
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->vendedorOutraEmpresa->id,
        ]);
    }

    public function test_lote_de_metas_rejeita_duplicidade_e_limites_antes_de_persistir(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('tv-comercial.salvar-metas'), [
                'data' => today()->format('Y-m-d'),
                'metas' => [
                    ['user_id' => $this->vendedor->id, 'meta_cotacoes' => 10],
                    ['user_id' => $this->vendedor->id, 'meta_cotacoes' => 20],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('metas.1.user_id');

        $this->assertDatabaseMissing('metas_diarias', [
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->vendedor->id,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('tv-comercial.salvar-metas'), [
                'data' => today()->format('Y-m-d'),
                'metas' => [[
                    'user_id' => $this->vendedor->id,
                    'meta_cotacoes' => 1000001,
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('metas.0.meta_cotacoes');
    }

    public function test_filtros_de_data_invalidos_sao_rejeitados(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('tv-comercial.listar-metas', ['data' => '05/09/2026']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('data');

        $this->actingAs($this->admin)
            ->getJson(route('tv-comercial.ranking-cotacoes', ['periodo' => 'personalizado']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['data_inicio', 'data_fim']);
    }

    public function test_nao_altera_meta_de_outra_empresa(): void
    {
        $meta = MetaDiaria::create([
            'empresa_id' => $this->outraEmpresa->id,
            'user_id' => $this->vendedorOutraEmpresa->id,
            'data' => today(),
            'meta_cotacoes' => 15,
            'cotacoes_realizadas' => 3,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('tv-comercial.atualizar-meta'), [
                'meta_id' => $meta->id,
                'meta_cotacoes' => 100,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('meta_id');

        $this->assertDatabaseHas('metas_diarias', [
            'id' => $meta->id,
            'meta_cotacoes' => 15,
        ]);
    }

    public function test_vendedor_nao_acessa_gerenciamento_da_tv(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('tv-comercial.gerenciar'))
            ->assertForbidden();
    }

    public function test_master_sem_empresa_de_origem_gerencia_somente_a_empresa_ativa(): void
    {
        $master = User::factory()->create([
            'empresa_id' => null,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);
        $session = [TenantContext::SESSION_KEY => $this->outraEmpresa->id];

        $this->actingAs($master)
            ->withSession($session)
            ->get(route('tv-comercial.gerenciar'))
            ->assertOk()
            ->assertSee('Vendedor Empresa B')
            ->assertDontSee('Vendedor Empresa A');

        $this->actingAs($master)
            ->withSession($session)
            ->postJson(route('tv-comercial.salvar-metas'), [
                'data' => today()->format('Y-m-d'),
                'metas' => [[
                    'user_id' => $this->vendedorOutraEmpresa->id,
                    'meta_cotacoes' => 12,
                ]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('metas_diarias', [
            'empresa_id' => $this->outraEmpresa->id,
            'user_id' => $this->vendedorOutraEmpresa->id,
            'meta_cotacoes' => 12,
        ]);
        $this->assertNull($master->fresh()->empresa_id);
    }

    public function test_faixas_de_progresso_sao_validadas_e_isoladas_por_empresa(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('tv-comercial.atualizar-configuracao'), [
                'percentual_atencao' => 70,
                'percentual_bom' => 60,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('percentual_bom');

        $this->postJson(route('tv-comercial.atualizar-configuracao'), [
            'percentual_atencao' => 30,
            'percentual_bom' => 80,
        ])
            ->assertOk()
            ->assertJsonPath('configuracao.percentual_atencao', 30)
            ->assertJsonPath('configuracao.percentual_bom', 80);

        $this->assertDatabaseHas('empresas', [
            'id' => $this->empresa->id,
            'tv_percentual_atencao' => 30,
            'tv_percentual_bom' => 80,
        ]);
        $this->assertDatabaseHas('empresas', [
            'id' => $this->outraEmpresa->id,
            'tv_percentual_atencao' => 50,
            'tv_percentual_bom' => 75,
        ]);
    }

    public function test_master_configura_faixas_somente_no_tenant_selecionado(): void
    {
        $master = User::factory()->create([
            'empresa_id' => null,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $this->outraEmpresa->id])
            ->postJson(route('tv-comercial.atualizar-configuracao'), [
                'percentual_atencao' => 25,
                'percentual_bom' => 70,
            ])
            ->assertOk();

        $this->assertSame(50, $this->empresa->fresh()->tv_percentual_atencao);
        $this->assertSame(25, $this->outraEmpresa->fresh()->tv_percentual_atencao);
        $this->assertNull($master->fresh()->empresa_id);
    }

    public function test_ranking_legado_preserva_urls_como_redirecionamentos_para_tv(): void
    {
        $this->actingAs($this->admin)
            ->get(route('ranking.index'))
            ->assertRedirect(route('tv-comercial.gerenciar'));

        $this->get(route('ranking.config'))
            ->assertRedirect(route('tv-comercial.gerenciar'));
        $this->get(route('ranking.edit', 123))
            ->assertRedirect(route('tv-comercial.gerenciar'));

        $this->assertTrue(Route::has('ranking.edit'));
        $this->assertTrue(Route::has('ranking.config'));
    }
}
