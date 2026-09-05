<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UsuarioManagementSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $admin;

    private User $developer;

    private User $master;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->admin = $this->user(UserRole::ADMINISTRATIVO);
        $this->developer = $this->user(UserRole::DEVELOPER);
        $this->master = $this->user(UserRole::DEVELOPER, true);
    }

    public function test_administrativo_nao_cria_nem_lista_developer_ou_master_global(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('usuarios.createUser'), [
                'name' => 'Escalada indevida',
                'email' => 'escalada@example.test',
                'user_role_id' => UserRole::DEVELOPER,
                'password' => 'senha-segura',
            ])
            ->assertUnprocessable();

        $response = $this->getJson(route('usuarios.getUsers'))->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id);

        $this->assertNotContains($this->developer->id, $ids);
        $this->assertNotContains($this->master->id, $ids);
        $this->assertDatabaseMissing('users', ['email' => 'escalada@example.test']);
    }

    public function test_developer_cria_outro_developer_com_acesso_global(): void
    {
        $this->actingAs($this->developer)
            ->postJson(route('usuarios.createUser'), [
                'name' => 'Developer do tenant',
                'email' => 'developer-tenant@example.test',
                'user_role_id' => UserRole::DEVELOPER,
                'empresa_id' => 999999,
                'password' => 'senha-segura',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'developer-tenant@example.test',
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
        ]);
    }

    public function test_administrativo_nao_altera_developer_e_nenhum_tenant_manager_altera_master(): void
    {
        $this->actingAs($this->admin)
            ->get(route('usuarios.editUser', $this->developer->id))
            ->assertNotFound();
        $this->post(route('usuarios.updateUser'), [
            'user_id' => $this->developer->id,
            'name' => 'Nome indevido',
            'ativo' => 'N',
        ])->assertNotFound();
        $this->post(route('usuarios.resetPassword'), [
            'user_id' => $this->developer->id,
            'senha' => 'nova-senha-segura',
            'senhaConfirma' => 'nova-senha-segura',
        ])->assertNotFound();
        $this->post(route('usuarios.toggleStatus', $this->developer->id))->assertNotFound();
        $this->post(route('contasPagamento.save', $this->developer->id), [
            'chave_pix' => 'indevida@example.test',
        ])->assertNotFound();

        $this->actingAs($this->developer)
            ->get(route('usuarios.editUser', $this->master->id))
            ->assertNotFound();
        $this->post(route('usuarios.toggleStatus', $this->master->id))->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $this->developer->id,
            'ativo' => 'Y',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->master->id,
            'ativo' => 'Y',
            'is_platform_admin' => true,
        ]);
        $this->assertDatabaseMissing('contas_pagamento', [
            'user_id' => $this->developer->id,
            'chave_pix' => 'indevida@example.test',
        ]);
    }

    public function test_estatisticas_contam_membros_do_tenant_sem_incluir_master_global(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('usuarios.getStats'))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('ativos', 1);
    }

    private function user(int $role, bool $platformAdmin = false): User
    {
        return User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => $role,
            'is_platform_admin' => $platformAdmin || $role === UserRole::DEVELOPER,
            'ativo' => 'Y',
        ]);
    }
}
