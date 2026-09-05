<?php

namespace Tests\Feature\Tenancy;

use App\Enums\TabulationCode;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanyOnboardingTest extends TestCase
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
    }

    public function test_only_platform_admin_lists_and_creates_companies(): void
    {
        $home = Empresa::query()->create(['nome_fantasia' => 'Empresa de origem']);
        $regular = User::factory()->create([
            'empresa_id' => $home->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'is_platform_admin' => false,
            'ativo' => 'Y',
        ]);

        $this->actingAs($regular)->get(route('empresa.getAllCompanies'))->assertForbidden();
        $this->actingAs($regular)->postJson(route('empresa.createCompanies'), $this->validCompany())->assertForbidden();
    }

    public function test_developer_is_the_global_role_even_without_legacy_flag(): void
    {
        $home = Empresa::query()->create(['nome_fantasia' => 'Empresa de origem']);
        $developer = User::factory()->create([
            'empresa_id' => $home->id,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => false,
            'ativo' => 'Y',
        ]);

        $this->assertTrue($developer->isPlatformAdmin());
        $this->actingAs($developer)
            ->get(route('empresa.empresa'))
            ->assertOk()
            ->assertSee('data-company-create-trigger', false)
            ->assertSee('Cadastrar empresa');
        $this->getJson(route('empresa.getAllCompanies'))->assertOk();
        $this->postJson(route('empresa.createCompanies'), $this->validCompany())->assertCreated();
    }

    public function test_onboarding_accepts_only_validated_fields_and_provisions_own_funnel(): void
    {
        [$home, $admin] = $this->platformAdmin();

        $response = $this->actingAs($admin)->postJson(route('empresa.createCompanies'), [
            ...$this->validCompany(),
            'id' => 999999,
            'whatsapp_token' => 'token-injetado-pelo-navegador',
            'empresa_id' => $home->id,
        ])->assertCreated()->assertJson([
            'error' => false,
            'message' => 'Empresa criada com sucesso.',
        ]);

        $empresaId = (int) $response->json('empresa_id');
        $this->assertNotSame(999999, $empresaId);
        $this->assertDatabaseHas('empresas', [
            'id' => $empresaId,
            'nome_fantasia' => 'Corretora Alfa',
            'cpf_cnpj' => '11144477735',
            'cpf_cnpj_normalizado' => '11144477735',
            'telefone' => '11999990001',
            'email' => 'contato@alfa.example',
            'whatsapp_token' => null,
        ]);
        $this->assertDatabaseCount('tabulacoes', count(TabulationCode::defaults()));
    }

    public function test_onboarding_rejects_invalid_or_duplicate_fiscal_document(): void
    {
        [, $admin] = $this->platformAdmin();

        $this->actingAs($admin)
            ->postJson(route('empresa.createCompanies'), [
                ...$this->validCompany(),
                'cpf_cnpj' => '111.111.111-11',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cpf_cnpj');

        $this->actingAs($admin)
            ->postJson(route('empresa.createCompanies'), $this->validCompany())
            ->assertCreated();

        $this->actingAs($admin)
            ->postJson(route('empresa.createCompanies'), [
                ...$this->validCompany(),
                'nome_fantasia' => 'Tentativa duplicada',
                'cpf_cnpj' => '111.444.777-35',
                'email' => 'duplicada@example.test',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cpf_cnpj_normalizado');

        $this->assertSame(1, Empresa::query()->where('cpf_cnpj_normalizado', '11144477735')->count());
    }

    public function test_whatsapp_token_is_encrypted_for_the_active_company_and_never_listed(): void
    {
        [$home, $admin] = $this->platformAdmin();
        $target = Empresa::query()->create(['nome_fantasia' => 'Empresa ativa']);
        $token = 'segredo-whatsapp-empresa-ativa';

        $this->actingAs($admin)
            ->withSession([TenantContext::SESSION_KEY => $target->id])
            ->postJson(route('backoffice.updateWhatsappToken'), ['whatsapp_token' => $token])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame($token, Empresa::query()->findOrFail($target->id)->whatsapp_token);
        $this->assertNotSame($token, DB::table('empresas')->where('id', $target->id)->value('whatsapp_token'));
        $this->assertNull(DB::table('empresas')->where('id', $home->id)->value('whatsapp_token'));

        $companies = $this->actingAs($admin)->getJson(route('empresa.getAllCompanies'))->assertOk();
        $companies->assertJsonMissingPath('0.whatsapp_token');
    }

    private function platformAdmin(): array
    {
        $home = Empresa::query()->create(['nome_fantasia' => 'Empresa de origem']);
        $admin = User::factory()->create([
            'empresa_id' => $home->id,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        return [$home, $admin];
    }

    private function validCompany(): array
    {
        return [
            'nome_fantasia' => '  Corretora Alfa  ',
            'cpf_cnpj' => '111.444.777-35',
            'telefone' => '(11) 99999-0001',
            'email' => 'CONTATO@ALFA.EXAMPLE',
        ];
    }
}
