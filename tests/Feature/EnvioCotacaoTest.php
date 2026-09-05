<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class EnvioCotacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_falha_do_provedor_nao_expoe_excecao_ao_destinatario(): void
    {
        DB::table('user_roles')->insert([
            'id' => UserRole::VENDEDOR,
            'tipo_usuario' => 'VENDEDOR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $empresa = Empresa::factory()->create([
            'nome_fantasia' => 'Corretora Segura',
            'email' => 'contato@corretora-segura.test',
        ]);
        $vendedor = User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'email' => 'vendedor@corretora-segura.test',
            'ativo' => 'Y',
        ]);

        Mail::shouldReceive('to')
            ->once()
            ->with('cliente@example.com')
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->once()
            ->andThrow(new RuntimeException('SMTP senha_super_secreta host-interno'));

        $response = $this->actingAs($vendedor)
            ->postJson(route('comercial.envioCotacao.enviar'), [
                'destinatarios' => ['cliente@example.com'],
                'assunto' => 'Sua cotação',
                'mensagem' => '<p>Segue a proposta.</p>',
                'anexo' => UploadedFile::fake()->create('cotacao.pdf', 100, 'application/pdf'),
            ]);

        $response->assertStatus(502)
            ->assertJsonPath('message', 'Não foi possível enviar a cotação neste momento.')
            ->assertJsonPath('nao_enviados.0', 'cliente@example.com')
            ->assertDontSee('SMTP')
            ->assertDontSee('senha_super_secreta')
            ->assertDontSee('host-interno');
    }
}
