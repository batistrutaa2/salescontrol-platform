<?php

namespace Tests\Feature\Tenancy;

use App\Enums\TabulationCode;
use App\Enums\UserRole;
use App\Models\Contatos;
use App\Models\ContatosCorretores;
use App\Models\Empresa;
use App\Models\TenantIntegrationCredential;
use App\Models\User;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Services\TabulationCatalog;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            [
                'id' => UserRole::ADMINISTRATIVO,
                'tipo_usuario' => 'ADMINISTRATIVO',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => UserRole::DEVELOPER,
                'tipo_usuario' => 'DEVELOPER',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Route::middleware(['web', 'auth'])->get('/_test/tenant-context', function (TenantContext $tenantContext) {
            return response()->json([
                'context_empresa_id' => $tenantContext->id(),
                'auth_empresa_id' => auth()->user()->empresa_id,
            ]);
        });
    }

    public function test_runtime_nao_le_empresa_diretamente_do_usuario_autenticado(): void
    {
        $violations = collect(File::allFiles(app_path()))
            ->filter(function ($file) {
                return preg_match(
                    '/(?:(?:Auth::user\(\)|auth\(\)\s*->\s*user\(\)|\$[A-Za-z_][A-Za-z0-9_]*\s*->\s*user\(\))\s*\??->|\$(?:user|seller)\s*->\s*)empresa_id/',
                    File::get($file->getPathname()),
                ) === 1;
            })
            ->map(fn ($file) => $file->getRelativePathname())
            ->values()
            ->all();

        $this->assertSame([], $violations, 'O runtime deve resolver a empresa ativa por TenantContext.');
    }

    public function test_platform_admin_can_switch_context_without_changing_home_company(): void
    {
        $home = Empresa::query()->create(['nome_fantasia' => 'Empresa A']);
        $target = Empresa::query()->create(['nome_fantasia' => 'Empresa B']);
        $admin = User::factory()->create([
            'empresa_id' => $home->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        $this->actingAs($admin)
            ->post(route('manager.changeCompany'), ['empresa_id' => $target->id])
            ->assertRedirect(route('home.dashboard'))
            ->assertSessionHas(TenantContext::SESSION_KEY, $target->id);

        $this->actingAs($admin)
            ->withSession([TenantContext::SESSION_KEY => $target->id])
            ->get('/_test/tenant-context')
            ->assertOk()
            ->assertJson([
                'context_empresa_id' => $target->id,
                'auth_empresa_id' => $home->id,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'empresa_id' => $home->id,
        ]);
        $this->assertDatabaseHas('tenant_context_switches', [
            'user_id' => $admin->id,
            'from_empresa_id' => $home->id,
            'to_empresa_id' => $target->id,
        ]);
    }

    public function test_regular_user_cannot_switch_or_inherit_another_company_context(): void
    {
        $home = Empresa::query()->create(['nome_fantasia' => 'Empresa A']);
        $other = Empresa::query()->create(['nome_fantasia' => 'Empresa B']);
        $user = User::factory()->create([
            'empresa_id' => $home->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'is_platform_admin' => false,
            'ativo' => 'Y',
        ]);

        $this->actingAs($user)
            ->post(route('manager.changeCompany'), ['empresa_id' => $other->id])
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession([TenantContext::SESSION_KEY => $other->id])
            ->get('/_test/tenant-context')
            ->assertOk()
            ->assertJson([
                'context_empresa_id' => $home->id,
                'auth_empresa_id' => $home->id,
            ])
            ->assertSessionMissing(TenantContext::SESSION_KEY);
    }

    public function test_platform_admin_without_home_company_can_bootstrap_and_must_select_a_tenant(): void
    {
        $admin = User::factory()->create([
            'empresa_id' => null,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        $this->actingAs($admin)
            ->get(route('empresa.empresa'))
            ->assertOk()
            ->assertSee('Nenhuma empresa cadastrada')
            ->assertSee('Cadastrar empresa');

        $this->get(route('home.dashboard'))
            ->assertRedirect(route('empresa.empresa'));
        $this->getJson(route('usuarios.getStats'))
            ->assertStatus(409)
            ->assertJson([
                'message' => 'Selecione uma empresa ativa antes de continuar.',
                'code' => 'tenant_required',
            ]);

        $response = $this->postJson(route('empresa.createCompanies'), [
            'nome_fantasia' => 'Primeira Corretora',
            'cpf_cnpj' => '11.444.777/0001-61',
            'telefone' => '(11) 99999-0001',
            'email' => 'primeira@example.test',
        ])->assertCreated();
        $empresaId = (int) $response->json('empresa_id');

        $this->post(route('manager.changeCompany'), ['empresa_id' => $empresaId])
            ->assertRedirect(route('home.dashboard'))
            ->assertSessionHas(TenantContext::SESSION_KEY, $empresaId);

        $this->withSession([TenantContext::SESSION_KEY => $empresaId])
            ->get(route('home.dashboard'))
            ->assertOk();
        $this->assertDatabaseHas('tenant_context_switches', [
            'user_id' => $admin->id,
            'from_empresa_id' => null,
            'to_empresa_id' => $empresaId,
        ]);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'empresa_id' => null]);
    }

    public function test_platform_admin_without_tenant_can_always_log_out(): void
    {
        $admin = User::factory()->create([
            'empresa_id' => null,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        $this->actingAs($admin)->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_login_sends_tenantless_platform_admin_to_company_bootstrap(): void
    {
        $admin = User::factory()->create([
            'empresa_id' => null,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        $this->post(route('login.autentication'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('empresa.empresa'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_alias_renders_the_application_login_screen(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Acesse sua operação');
    }

    public function test_platform_has_no_runtime_access_to_the_single_company_legacy_database(): void
    {
        $this->assertFalse(Route::has('mailing.viewLeadslegacy'));
        $this->assertFalse(Route::has('comercial.getCommentsLegacy'));
        $this->assertNull(config('database.connections.mysql2'));
    }

    public function test_user_creation_uses_server_tenant_instead_of_payload_company(): void
    {
        $home = Empresa::query()->create(['nome_fantasia' => 'Empresa A']);
        $active = Empresa::query()->create(['nome_fantasia' => 'Empresa B']);
        $admin = User::factory()->create([
            'empresa_id' => $home->id,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        $this->actingAs($admin)
            ->withSession([TenantContext::SESSION_KEY => $active->id])
            ->postJson(route('usuarios.createUser'), [
                'name' => 'Usuário Empresa B',
                'email' => 'empresa-b@example.test',
                'user_role_id' => UserRole::ADMINISTRATIVO,
                'empresa_id' => $home->id,
                'password' => 'senha-segura-de-teste',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'empresa-b@example.test',
            'empresa_id' => $active->id,
            'is_platform_admin' => false,
        ]);
    }

    public function test_user_management_cannot_change_or_reset_user_from_another_tenant(): void
    {
        $home = Empresa::query()->create(['nome_fantasia' => 'Empresa A']);
        $other = Empresa::query()->create(['nome_fantasia' => 'Empresa B']);
        $admin = User::factory()->create([
            'empresa_id' => $home->id,
            'user_role_id' => UserRole::DEVELOPER,
            'ativo' => 'Y',
        ]);
        $foreignUser = User::factory()->create([
            'empresa_id' => $other->id,
            'user_role_id' => UserRole::DEVELOPER,
            'name' => 'Nome original',
            'ativo' => 'Y',
        ]);

        $this->actingAs($admin)->post(route('usuarios.updateUser'), [
            'user_id' => $foreignUser->id,
            'name' => 'Nome indevido',
            'ativo' => 'N',
        ])->assertNotFound();

        $this->actingAs($admin)->post(route('usuarios.resetPassword'), [
            'user_id' => $foreignUser->id,
            'senha' => 'nova-senha-indevida',
            'senhaConfirma' => 'nova-senha-indevida',
        ])->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $foreignUser->id,
            'name' => 'Nome original',
            'ativo' => 'Y',
        ]);
    }

    public function test_ads_webhook_resolves_tenant_and_import_user_from_hashed_bearer_token(): void
    {
        $empresa = Empresa::query()->create(['nome_fantasia' => 'Empresa integrada']);
        $other = Empresa::query()->create(['nome_fantasia' => 'Outra empresa']);
        app(TabulationCatalog::class)->provision($empresa->id);
        $importUser = User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::DEVELOPER,
            'ativo' => 'Y',
        ]);
        $plainToken = 'token-de-integracao-usado-apenas-no-teste';

        TenantIntegrationCredential::query()->create([
            'empresa_id' => $empresa->id,
            'user_id' => $importUser->id,
            'name' => 'Anúncios',
            'token_hash' => hash('sha256', $plainToken),
            'abilities' => ['leads.ads.import'],
            'active' => true,
        ]);

        $this->postJson('/api/webhook/leads-ads', ['nome_cliente' => 'Lead integrado'], [
            'Authorization' => "Bearer {$plainToken}",
        ])->assertCreated();

        $this->assertDatabaseHas('contatos', [
            'nome_cliente' => 'Lead integrado',
            'empresa_id' => $empresa->id,
            'user_import_id' => $importUser->id,
        ]);
        $this->assertDatabaseMissing('contatos', [
            'nome_cliente' => 'Lead integrado',
            'empresa_id' => $other->id,
        ]);
        $this->assertDatabaseHas('lead_reservatorio_itens', [
            'empresa_id' => $empresa->id,
            'entrou_por' => $importUser->id,
        ]);
    }

    public function test_ads_webhook_fails_closed_without_tenant_credential(): void
    {
        $this->postJson('/api/webhook/leads-ads', ['nome_cliente' => 'Lead sem tenant'])
            ->assertUnauthorized();

        $this->assertDatabaseMissing('contatos', ['nome_cliente' => 'Lead sem tenant']);
    }

    public function test_company_onboarding_provisions_independent_semantic_funnels(): void
    {
        $home = Empresa::query()->create(['nome_fantasia' => 'Empresa base']);
        $admin = User::factory()->create([
            'empresa_id' => $home->id,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        foreach ([
            ['nome_fantasia' => 'Corretora Alfa', 'cpf_cnpj' => '11444777000161', 'telefone' => '11999990001', 'email' => 'alfa@example.test'],
            ['nome_fantasia' => 'Corretora Beta', 'cpf_cnpj' => '52998224000138', 'telefone' => '11999990002', 'email' => 'beta@example.test'],
        ] as $company) {
            $this->actingAs($admin)->postJson(route('empresa.createCompanies'), $company)->assertCreated();
        }

        $alfa = Empresa::query()->where('email', 'alfa@example.test')->firstOrFail();
        $beta = Empresa::query()->where('email', 'beta@example.test')->firstOrFail();
        $catalog = app(TabulationCatalog::class);
        $alfaProspeccao = $catalog->id($alfa->id, TabulationCode::PROSPECCAO);
        $betaProspeccao = $catalog->id($beta->id, TabulationCode::PROSPECCAO);

        $this->assertNotSame($alfaProspeccao, $betaProspeccao);
        $this->assertDatabaseCount('tabulacoes', count(TabulationCode::defaults()) * 2);

        DB::table('tabulacoes')->where('id', $alfaProspeccao)->update(['descricao' => 'PRIMEIRO CONTATO']);
        $this->assertSame($alfaProspeccao, $catalog->id($alfa->id, TabulationCode::PROSPECCAO));
        $this->assertSame('PROSPECÇÃO', DB::table('tabulacoes')->where('id', $betaProspeccao)->value('descricao'));
    }

    public function test_developer_commercial_board_does_not_mix_companies(): void
    {
        $empresaA = Empresa::query()->create(['nome_fantasia' => 'Empresa A']);
        $empresaB = Empresa::query()->create(['nome_fantasia' => 'Empresa B']);
        $catalog = app(TabulationCatalog::class);
        $catalog->provision($empresaA->id);
        $catalog->provision($empresaB->id);
        $developer = User::factory()->create([
            'empresa_id' => $empresaA->id,
            'user_role_id' => UserRole::DEVELOPER,
            'ativo' => 'Y',
        ]);
        $ownerA = User::factory()->create([
            'empresa_id' => $empresaA->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
        $ownerB = User::factory()->create([
            'empresa_id' => $empresaB->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
        $leadA = Contatos::query()->create(['empresa_id' => $empresaA->id, 'user_import_id' => $developer->id, 'nome_cliente' => 'Lead A']);
        $leadB = Contatos::query()->create(['empresa_id' => $empresaB->id, 'user_import_id' => $developer->id, 'nome_cliente' => 'Lead B']);
        ContatosCorretores::query()->create([
            'empresa_id' => $empresaA->id,
            'contato_id' => $leadA->id,
            'user_id' => $ownerA->id,
            'tabulacao_id' => $catalog->id($empresaA->id, TabulationCode::PROSPECCAO),
        ]);
        ContatosCorretores::query()->create([
            'empresa_id' => $empresaB->id,
            'contato_id' => $leadB->id,
            'user_id' => $ownerB->id,
            'tabulacao_id' => $catalog->id($empresaB->id, TabulationCode::PROSPECCAO),
        ]);

        $this->actingAs($developer);
        $rows = app(TenantContext::class)->run(
            $empresaA->id,
            fn () => app(ContatosCorretoresRepository::class)->getClientComercial(
                (string) UserRole::DEVELOPER,
                (string) $empresaA->id,
            ),
        );

        $this->assertSame(['Lead A'], $rows->pluck('nome_cliente')->all());
    }
}
