<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinanceiroAccessTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $financeiro;

    private User $admin;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->financeiro = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::FINANCEIRO,
            'ativo' => 'Y',
        ]);
        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
        $this->vendedor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
    }

    public function test_login_do_financeiro_redireciona_para_recebiveis(): void
    {
        $this->post(route('login.autentication'), [
            'email' => $this->financeiro->email,
            'password' => 'password',
        ])->assertRedirect(route('financeiro.recebiveis.index'));
    }

    public function test_financeiro_acessa_modulo_e_ve_somente_menu_financeiro(): void
    {
        $this->actingAs($this->financeiro)
            ->get(route('financeiro.recebiveis.index'))
            ->assertOk()
            ->assertSee('Regras de Comissão')
            ->assertSee('Recebíveis')
            ->assertDontSee('Dashboard')
            ->assertDontSee('Escola LK Brokers');
    }

    public function test_financeiro_nao_acessa_rotas_fora_do_modulo(): void
    {
        $this->actingAs($this->financeiro)
            ->get(route('home.dashboard'))
            ->assertRedirect(route('financeiro.recebiveis.index'));

        $this->actingAs($this->financeiro)
            ->getJson(route('home.dashboard'))
            ->assertForbidden();
    }

    public function test_outros_perfis_sem_permissao_nao_acessam_financeiro(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('financeiro.recebiveis.index'))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('financeiro.recebiveis.index'))
            ->assertOk();
    }

    public function test_cadastro_de_usuario_oferece_e_aceita_perfil_financeiro(): void
    {
        $this->actingAs($this->admin)
            ->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('<option value="8">FINANCEIRO</option>', false);

        $this->postJson(route('usuarios.createUser'), [
            'name' => 'Equipe Financeira',
            'email' => 'financeiro@example.com',
            'whatsapp' => null,
            'user_role_id' => UserRole::FINANCEIRO,
            'empresa_id' => (string) $this->empresa->id,
            'password' => 'senha-segura',
        ])->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'financeiro@example.com',
            'user_role_id' => UserRole::FINANCEIRO,
            'empresa_id' => $this->empresa->id,
        ]);
    }
}
