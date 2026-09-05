<?php

namespace Tests\Feature\Tenancy;

use App\Enums\TabulationCode;
use App\Enums\UserRole;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_e_configuravel_sem_ids_ou_identidade_de_cliente_fixos(): void
    {
        config()->set('tenancy.bootstrap', [
            'company_name' => 'Corretora de Homologação',
            'company_document' => '00999999000100',
            'company_phone' => '11999999999',
            'company_email' => 'homologacao@example.test',
            'admin_name' => 'Admin de Homologação',
            'admin_email' => 'admin-homologacao@example.test',
            'admin_password' => 'senha-exclusiva-do-teste',
        ]);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $empresaId = (int) DB::table('empresas')
            ->where('email', 'homologacao@example.test')
            ->value('id');

        $this->assertDatabaseCount('empresas', 1);
        $this->assertDatabaseHas('users', [
            'email' => 'admin-homologacao@example.test',
            'empresa_id' => $empresaId,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
        ]);
        $this->assertTrue(Hash::check(
            'senha-exclusiva-do-teste',
            (string) DB::table('users')->value('password')
        ));
        $this->assertDatabaseCount('tabulacoes', count(TabulationCode::defaults()));
        $this->assertDatabaseMissing('empresas', ['nome_fantasia' => 'LKBROKERS']);
    }
}
