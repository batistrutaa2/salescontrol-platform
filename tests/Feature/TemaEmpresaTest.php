<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * O tema Licas (DESIGN-LICAS.md) vale só para a empresa marcada em
 * empresas.tema; as outras corretoras continuam no visual padrão.
 */
class TemaEmpresaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        DB::table('user_roles')->insert([
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function adminDe(Empresa $empresa): User
    {
        return User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
    }

    public function test_empresa_com_tema_licas_recebe_classe_e_marca(): void
    {
        $empresa = Empresa::factory()->create();
        DB::table('empresas')->where('id', $empresa->id)->update(['tema' => 'licas']);

        $this->actingAs($this->adminDe($empresa))
            ->get(route('home.dashboard'))
            ->assertOk()
            ->assertSee('theme-licas', false)
            ->assertSee('licas-wordmark', false)
            ->assertSee('data-licas-theme-toggle', false);
    }

    public function test_login_usa_tema_da_config_antes_de_autenticar(): void
    {
        config(['custom.tema_login' => 'licas']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('theme-licas', false)
            ->assertSee('licas-login', false)
            ->assertSee('formAuthentication', false);

        config(['custom.tema_login' => null]);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('theme-licas', false)
            ->assertSee('auth-modern-cover', false);
    }

    public function test_empresa_sem_tema_mantem_visual_padrao(): void
    {
        $empresa = Empresa::factory()->create();

        $this->actingAs($this->adminDe($empresa))
            ->get(route('home.dashboard'))
            ->assertOk()
            ->assertDontSee('theme-licas', false)
            ->assertDontSee('licas-wordmark', false)
            ->assertDontSee('data-licas-theme-toggle', false);
    }
}
