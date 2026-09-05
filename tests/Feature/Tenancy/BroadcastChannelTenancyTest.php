<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BroadcastChannelTenancyTest extends TestCase
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

    public function test_master_autoriza_canal_apenas_da_empresa_ativa(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $master = User::factory()->create([
            'empresa_id' => $empresaA->id,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        $this->withTenant($empresaB, function (callable $authorize) use ($empresaA, $empresaB, $master): void {
            $this->assertTrue($authorize($master, (string) $empresaB->id));
            $this->assertFalse($authorize($master, (string) $empresaA->id));
        });
    }

    public function test_administrativo_autoriza_canal_apenas_da_propria_empresa(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $administrativo = User::factory()->create([
            'empresa_id' => $empresaA->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'is_platform_admin' => false,
            'ativo' => 'Y',
        ]);

        $this->withTenant($empresaA, function (callable $authorize) use ($administrativo, $empresaA, $empresaB): void {
            $this->assertTrue($authorize($administrativo, (string) $empresaA->id));
            $this->assertFalse($authorize($administrativo, (string) $empresaB->id));
        });
    }

    private function withTenant(Empresa $empresa, callable $assertions): void
    {
        $tenantContext = app(TenantContext::class);
        $authorize = Broadcast::getChannels()->get('contratos.administrativo.{empresaId}');

        $this->assertIsCallable($authorize);
        $tenantContext->set($empresa->id);

        try {
            $assertions($authorize);
        } finally {
            $tenantContext->clear();
        }
    }
}
