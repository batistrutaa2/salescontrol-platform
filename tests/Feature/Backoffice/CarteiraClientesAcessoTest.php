<?php

namespace Tests\Feature\Backoffice;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Carteira de Clientes contém valores/faturamento: só ADMINISTRATIVO e DEVELOPER
 * acessam — e o bloqueio é na rota (não só no menu).
 */
class CarteiraClientesAcessoTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::BACKOFFICE, 'tipo_usuario' => 'BACKOFFICE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::SUPERVISOR, 'tipo_usuario' => 'SUPERVISOR', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
    }

    private function user(int $roleId): User
    {
        return User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => $roleId,
            'ativo' => 'Y',
        ]);
    }

    public function test_administrador_e_developer_acessam(): void
    {
        foreach ([UserRole::ADMINISTRATIVO, UserRole::DEVELOPER] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('backoffice.carteiraClientes'))
                ->assertOk();
        }
    }

    public function test_demais_papeis_recebem_403(): void
    {
        foreach ([UserRole::VENDEDOR, UserRole::BACKOFFICE, UserRole::SUPERVISOR] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('backoffice.carteiraClientes'))
                ->assertStatus(403);

            // Endpoints de dados (com os valores) também bloqueados.
            $this->actingAs($this->user($role))
                ->getJson(route('backoffice.getCarteiraClientesData'))
                ->assertStatus(403);
        }
    }
}
