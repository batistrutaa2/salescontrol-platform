<?php

namespace Tests\Feature\Backoffice;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Boas-vindas: a mensagem/e-mail agora aceita VÁRIOS acessos ao aplicativo
 * (login/senha), com fallback para o par único legado (login_app/senha_app).
 */
class BoasVindasTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $empresa = Empresa::factory()->create();
        $this->user = User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
    }

    public function test_preview_email_renderiza_varios_acessos_do_app(): void
    {
        $resp = $this->actingAs($this->user)->post(route('backoffice.previewEmailBoasVindas'), [
            'tipo_envio' => 'padrao',
            'operadora' => 'AMIL',
            'nome_contrato' => 'ACME LTDA',
            'acessos_app' => [
                ['rotulo' => 'Titular', 'login' => 'maria@ex.com', 'senha' => 'Saude2026'],
                ['rotulo' => 'Dependente', 'login' => 'joao@ex.com', 'senha' => 'Saude2027'],
            ],
        ]);

        $resp->assertOk()
            ->assertSee('maria@ex.com')->assertSee('Saude2026')
            ->assertSee('joao@ex.com')->assertSee('Saude2027')
            ->assertSee('TITULAR')->assertSee('DEPENDENTE'); // rótulo em maiúsculas
    }

    public function test_preview_email_fallback_para_login_unico_legado(): void
    {
        $resp = $this->actingAs($this->user)->post(route('backoffice.previewEmailBoasVindas'), [
            'tipo_envio' => 'padrao',
            'operadora' => 'AMIL',
            'login_app' => 'unico@ex.com',
            'senha_app' => 'Senha123',
        ]);

        $resp->assertOk()->assertSee('unico@ex.com')->assertSee('Senha123');
    }
}
