<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\TenantServiceCredential;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IntegrationSettingsTenancyTest extends TestCase
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

    public function test_somente_master_acessa_configuracoes_de_integracao(): void
    {
        $empresa = Empresa::factory()->create();
        $administrativo = User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'is_platform_admin' => false,
            'ativo' => 'Y',
        ]);

        $this->actingAs($administrativo)
            ->get(route('manager.integrations.index'))
            ->assertForbidden();
        $this->put(route('manager.integrations.voip.save'), [
            'endpoint' => 'https://voip.example.test/click-to-call',
            'token' => 'token-nao-autorizado-123',
            'active' => true,
        ])->assertForbidden();
    }

    public function test_master_visualiza_status_da_empresa_ativa_sem_receber_segredo(): void
    {
        [$empresaA, $empresaB, $master] = $this->cenarioMaster();
        $this->voip($empresaA, 'https://voip-a.example.test/call', 'token-secreto-empresa-a');
        $this->voip($empresaB, 'https://voip-b.example.test/call', 'token-secreto-empresa-b');

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $empresaB->id])
            ->get(route('manager.integrations.index'))
            ->assertOk()
            ->assertSee('Empresa B')
            ->assertSee('https://voip-b.example.test/call')
            ->assertDontSee('https://voip-a.example.test/call')
            ->assertDontSee('token-secreto-empresa-a')
            ->assertDontSee('token-secreto-empresa-b');
    }

    public function test_master_salva_e_remove_credencial_somente_na_empresa_ativa(): void
    {
        [$empresaA, $empresaB, $master] = $this->cenarioMaster();
        $configA = $this->voip($empresaA, 'https://voip-a.example.test/call', 'token-secreto-empresa-a');

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $empresaB->id])
            ->put(route('manager.integrations.voip.save'), [
                'empresa_id' => $empresaA->id,
                'endpoint' => 'https://voip-b.example.test/call/',
                'token' => 'token-secreto-empresa-b',
                'active' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'success');

        $configB = TenantServiceCredential::query()
            ->where('empresa_id', $empresaB->id)
            ->where('service', TenantServiceCredential::SERVICE_VOIP_MAIS)
            ->firstOrFail();
        $this->assertSame('https://voip-b.example.test/call', $configB->endpoint);
        $this->assertSame('token-secreto-empresa-b', $configB->credentials['token']);
        $this->assertStringNotContainsString(
            'token-secreto-empresa-b',
            (string) DB::table('tenant_service_credentials')->where('id', $configB->id)->value('credentials')
        );

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $empresaB->id])
            ->delete(route('manager.integrations.voip.delete'))
            ->assertRedirect();

        $this->assertDatabaseMissing('tenant_service_credentials', ['id' => $configB->id]);
        $this->assertDatabaseHas('tenant_service_credentials', ['id' => $configA->id]);
    }

    /** @return array{Empresa, Empresa, User} */
    private function cenarioMaster(): array
    {
        $empresaA = Empresa::factory()->create(['nome_fantasia' => 'Empresa A']);
        $empresaB = Empresa::factory()->create(['nome_fantasia' => 'Empresa B']);

        return [$empresaA, $empresaB, $this->developer($empresaA, true)];
    }

    private function developer(Empresa $empresa, bool $platformAdmin): User
    {
        return User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => $platformAdmin,
            'ativo' => 'Y',
        ]);
    }

    private function voip(Empresa $empresa, string $endpoint, string $token): TenantServiceCredential
    {
        return TenantServiceCredential::query()->create([
            'empresa_id' => $empresa->id,
            'service' => TenantServiceCredential::SERVICE_VOIP_MAIS,
            'endpoint' => $endpoint,
            'credentials' => ['token' => $token],
            'active' => true,
        ]);
    }
}
