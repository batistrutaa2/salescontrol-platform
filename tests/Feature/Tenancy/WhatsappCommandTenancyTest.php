<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Events\Whatsapp\StatusInstanciaAtualizado;
use App\Jobs\Whatsapp\EnviarMensagemWhatsapp;
use App\Jobs\Whatsapp\ProcessarMensagemRecebida;
use App\Models\Empresa;
use App\Models\User;
use App\Models\WhatsappConversa;
use App\Models\WhatsappInstancia;
use App\Models\WhatsappMensagem;
use App\Services\Evolution\EvolutionApiService;
use App\Services\Whatsapp\MediaStorageService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class WhatsappCommandTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            'id' => UserRole::VENDEDOR,
            'tipo_usuario' => 'VENDEDOR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_monitor_processa_instancias_em_contextos_separados(): void
    {
        Event::fake([StatusInstanciaAtualizado::class]);
        [$empresaA, $instanciaA] = $this->criarInstancia('monitor-a', 'QRCODE');
        [$empresaB, $instanciaB] = $this->criarInstancia('monitor-b', 'QRCODE');
        $ids = ['monitor-a' => $empresaA->id, 'monitor-b' => $empresaB->id];

        $evolution = \Mockery::mock(EvolutionApiService::class);
        $evolution->shouldReceive('connectionState')->twice()->andReturnUsing(function (string $nome) use ($ids): array {
            $this->assertSame($ids[$nome], app(TenantContext::class)->id());

            return ['instance' => ['state' => 'open']];
        });
        $this->app->instance(EvolutionApiService::class, $evolution);

        app(TenantContext::class)->set($empresaB->id);
        $this->artisan('whatsapp:monitor')->assertSuccessful();

        $this->assertDatabaseHas('whatsapp_instancias', ['id' => $instanciaA->id, 'status' => 'CONECTADA']);
        $this->assertDatabaseHas('whatsapp_instancias', ['id' => $instanciaB->id, 'status' => 'CONECTADA']);
        $this->assertFalse(app(TenantContext::class)->isResolved());
    }

    public function test_resync_busca_conversas_por_empresa_e_enfileira_com_a_instancia_correta(): void
    {
        Queue::fake();
        [$empresaA, $instanciaA, $conversaA] = $this->criarConversa('resync-a');
        [$empresaB, $instanciaB, $conversaB] = $this->criarConversa('resync-b');
        $ids = ['resync-a' => $empresaA->id, 'resync-b' => $empresaB->id];

        $evolution = \Mockery::mock(EvolutionApiService::class);
        $evolution->shouldReceive('findMessages')->twice()->andReturnUsing(
            function (string $nome, string $remoteJid, int $limite) use ($ids): array {
                $this->assertSame($ids[$nome], app(TenantContext::class)->id());
                $this->assertSame(30, $limite);

                return ['messages' => ['records' => [[
                    'key' => ['id' => 'nova-'.$nome, 'remoteJid' => $remoteJid],
                    'message' => ['conversation' => 'Mensagem recuperada'],
                ]]]];
            }
        );
        $this->app->instance(EvolutionApiService::class, $evolution);

        $this->artisan('whatsapp:resync')->assertSuccessful();

        Queue::assertPushed(ProcessarMensagemRecebida::class, 2);
        Queue::assertPushed(ProcessarMensagemRecebida::class, fn (ProcessarMensagemRecebida $job) => $job->instanciaId === $instanciaA->id
            && data_get($job->payload, 'data.key.id') === 'nova-resync-a');
        Queue::assertPushed(ProcessarMensagemRecebida::class, fn (ProcessarMensagemRecebida $job) => $job->instanciaId === $instanciaB->id
            && data_get($job->payload, 'data.key.id') === 'nova-resync-b');
        $this->assertNotSame($conversaA->empresa_id, $conversaB->empresa_id);
        $this->assertFalse(app(TenantContext::class)->isResolved());
    }

    public function test_recuperacao_de_midia_preserva_limite_global_entre_empresas(): void
    {
        [$empresaA, , $conversaA] = $this->criarConversa('media-a');
        [$empresaB, , $conversaB] = $this->criarConversa('media-b');
        $mensagemA = $this->criarMensagem($empresaA, $conversaA, 'msg-a');
        $mensagemB = $this->criarMensagem($empresaB, $conversaB, 'msg-b');

        $evolution = \Mockery::mock(EvolutionApiService::class);
        $evolution->shouldReceive('getBase64FromMediaMessage')->once()->with('media-a', 'msg-a')
            ->andReturn(['base64' => base64_encode('arquivo'), 'mimetype' => 'image/jpeg']);
        $this->app->instance(EvolutionApiService::class, $evolution);

        $storage = \Mockery::mock(MediaStorageService::class);
        $storage->shouldReceive('salvarBase64')->once()->andReturnUsing(
            function (string $base64, string $mime, int $empresaId, int $conversaId) use ($empresaA, $conversaA): array {
                $this->assertSame($empresaA->id, app(TenantContext::class)->id());
                $this->assertSame($empresaA->id, $empresaId);
                $this->assertSame($conversaA->id, $conversaId);

                return ['whatsapp/teste.jpg', strlen(base64_decode($base64))];
            }
        );
        $this->app->instance(MediaStorageService::class, $storage);

        $this->artisan('whatsapp:redownload-media', ['--limite' => 1])
            ->expectsOutput('Mídias recuperadas: 1 de 1 pendentes.')
            ->assertSuccessful();

        $this->assertDatabaseHas('whatsapp_mensagens', ['id' => $mensagemA->id, 'media_path' => 'whatsapp/teste.jpg']);
        $this->assertDatabaseHas('whatsapp_mensagens', ['id' => $mensagemB->id, 'media_path' => null]);
        $this->assertFalse(app(TenantContext::class)->isResolved());
    }

    public function test_comandos_rejeitam_empresa_e_limites_invalidos(): void
    {
        $this->artisan('whatsapp:monitor', ['--empresa' => 999999])
            ->expectsOutput('Empresa inválida.')
            ->assertFailed();
        $this->artisan('whatsapp:resync', ['--chats' => 0])
            ->expectsOutput('Limites inválidos. Use chats entre 1 e 200 e mensagens entre 1 e 500.')
            ->assertFailed();
        $this->artisan('whatsapp:redownload-media', ['--limite' => 0])
            ->expectsOutput('Limite inválido. Informe um valor entre 1 e 1000.')
            ->assertFailed();
    }

    public function test_falha_definitiva_de_envio_atualiza_somente_a_mensagem_do_tenant(): void
    {
        [$empresaA, , $conversaA] = $this->criarConversa('falha-a');
        [$empresaB, , $conversaB] = $this->criarConversa('falha-b');
        $mensagemA = $this->criarMensagemSaida($empresaA, $conversaA, 'saida-a');
        $mensagemB = $this->criarMensagemSaida($empresaB, $conversaB, 'saida-b');
        Event::fake();

        app(TenantContext::class)->set($empresaB->id);
        (new EnviarMensagemWhatsapp($mensagemA->id))->failed(new RuntimeException('credencial sensível do provedor'));

        $this->assertDatabaseHas('whatsapp_mensagens', [
            'id' => $mensagemA->id,
            'empresa_id' => $empresaA->id,
            'status_envio' => 'ERRO',
            'erro_envio' => 'Não foi possível enviar a mensagem pelo provedor.',
        ]);
        $this->assertDatabaseHas('whatsapp_mensagens', [
            'id' => $mensagemB->id,
            'empresa_id' => $empresaB->id,
            'status_envio' => 'PENDENTE',
            'erro_envio' => null,
        ]);
        $this->assertFalse(app(TenantContext::class)->isResolved());
    }

    /** @return array{Empresa, WhatsappInstancia} */
    private function criarInstancia(string $nome, string $status = 'CONECTADA'): array
    {
        $empresa = Empresa::factory()->create();
        $usuario = User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
        $instancia = app(TenantContext::class)->run($empresa->id, fn () => WhatsappInstancia::create([
            'empresa_id' => $empresa->id,
            'user_id' => $usuario->id,
            'instance_name' => $nome,
            'status' => $status,
            'webhook_token' => 'token-'.$nome,
        ]));

        return [$empresa, $instancia];
    }

    /** @return array{Empresa, WhatsappInstancia, WhatsappConversa} */
    private function criarConversa(string $nome): array
    {
        [$empresa, $instancia] = $this->criarInstancia($nome);
        $conversa = app(TenantContext::class)->run($empresa->id, fn () => WhatsappConversa::create([
            'empresa_id' => $empresa->id,
            'instancia_id' => $instancia->id,
            'user_id' => $instancia->user_id,
            'remote_jid' => '551199999'.str_pad((string) $empresa->id, 4, '0', STR_PAD_LEFT).'@s.whatsapp.net',
            'numero' => '1199999999',
            'numero_normalizado' => '1199999999',
            'last_message_at' => now(),
        ]));

        return [$empresa, $instancia, $conversa];
    }

    private function criarMensagem(Empresa $empresa, WhatsappConversa $conversa, string $messageId): WhatsappMensagem
    {
        return app(TenantContext::class)->run($empresa->id, fn () => WhatsappMensagem::create([
            'empresa_id' => $empresa->id,
            'conversa_id' => $conversa->id,
            'message_id' => $messageId,
            'direcao' => 'IN',
            'tipo' => 'image',
            'media_path' => null,
            'media_mime' => 'image/jpeg',
            'message_timestamp' => now(),
        ]));
    }

    private function criarMensagemSaida(Empresa $empresa, WhatsappConversa $conversa, string $messageId): WhatsappMensagem
    {
        return app(TenantContext::class)->run($empresa->id, fn () => WhatsappMensagem::create([
            'empresa_id' => $empresa->id,
            'conversa_id' => $conversa->id,
            'message_id' => $messageId,
            'direcao' => 'OUT',
            'tipo' => 'text',
            'body' => 'Mensagem de teste',
            'status_envio' => 'PENDENTE',
            'message_timestamp' => now(),
        ]));
    }
}
