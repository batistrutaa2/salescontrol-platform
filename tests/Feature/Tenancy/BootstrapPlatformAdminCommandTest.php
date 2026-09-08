<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\UserRolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BootstrapPlatformAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_tenantless_platform_admin_without_creating_a_company(): void
    {
        config()->set('tenancy.bootstrap.admin_name', 'Master Licas');
        config()->set('tenancy.bootstrap.admin_email', 'master@licas.test');
        config()->set('tenancy.bootstrap.admin_password', 'senha-forte-inicial');

        $this->artisan('platform:bootstrap-admin')->assertSuccessful();

        $admin = User::query()->where('email', 'master@licas.test')->firstOrFail();
        $this->assertNull($admin->empresa_id);
        $this->assertSame(UserRole::DEVELOPER, (int) $admin->user_role_id);
        $this->assertTrue($admin->is_platform_admin);
        $this->assertTrue(Hash::check('senha-forte-inicial', $admin->password));
        $this->assertDatabaseCount('empresas', 0);
        $this->assertDatabaseHas('user_roles', ['id' => UserRole::DEVELOPER]);
    }

    public function test_it_is_idempotent_and_does_not_reset_the_existing_password(): void
    {
        config()->set('tenancy.bootstrap.admin_email', 'master@licas.test');
        config()->set('tenancy.bootstrap.admin_password', 'senha-forte-inicial');
        $this->artisan('platform:bootstrap-admin')->assertSuccessful();

        $originalPassword = User::query()->where('email', 'master@licas.test')->value('password');
        config()->set('tenancy.bootstrap.admin_password', 'outra-senha-que-nao-deve-entrar');

        $this->artisan('platform:bootstrap-admin')->assertSuccessful();

        $this->assertSame(
            $originalPassword,
            User::query()->where('email', 'master@licas.test')->value('password')
        );
        $this->assertDatabaseCount('users', 1);
    }

    public function test_it_never_promotes_an_existing_regular_user(): void
    {
        $this->seed(UserRolesSeeder::class);

        $user = User::factory()->create([
            'email' => 'master@licas.test',
            'user_role_id' => UserRole::VENDEDOR,
            'is_platform_admin' => false,
        ]);

        config()->set('tenancy.bootstrap.admin_email', $user->email);
        config()->set('tenancy.bootstrap.admin_password', 'senha-forte-inicial');

        $this->artisan('platform:bootstrap-admin')->assertFailed();

        $this->assertFalse($user->fresh()->is_platform_admin);
        $this->assertSame(UserRole::VENDEDOR, (int) $user->fresh()->user_role_id);
    }
}
