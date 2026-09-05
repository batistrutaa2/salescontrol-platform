<?php

namespace Tests\Feature\Tenancy;

use App\Enums\TabulationCode;
use App\Enums\UserRole;
use App\Jobs\Middleware\UseWhatsappTenantContext;
use App\Jobs\Whatsapp\ProcessarMensagemRecebida;
use App\Models\Empresa;
use App\Models\User;
use App\Models\WhatsappMensagem;
use App\Repositories\Contracts\WhatsappConversaRepositoryInterface;
use App\Services\TabulationCatalog;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class WhatsappTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_vendedor_lista_e_manipula_somente_suas_conversas_da_empresa(): void
    {
        $cenario = $this->cenario();

        $this->actingAs($cenario['vendedorA'])
            ->getJson(route('whatsapp.conversas'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $cenario['conversaA'])
            ->assertJsonCount(1, 'data');

        $this->getJson(route('whatsapp.mensagens', $cenario['conversaB']))->assertNotFound();
        $this->postJson(route('whatsapp.descartarConversa', $cenario['conversaB']))->assertForbidden();
        $this->postJson(route('whatsapp.limparConversa', $cenario['conversaB']))->assertForbidden();
        $this->deleteJson(route('whatsapp.apagarConversa', $cenario['conversaB']))->assertForbidden();
        $this->postJson(route('whatsapp.kanban.changeStatus'), [
            'conversa_id' => $cenario['conversaB'],
            'tabulacao_id' => $cenario['tabulacaoA'],
        ])->assertNotFound();

        $this->assertDatabaseHas('whatsapp_conversas', [
            'id' => $cenario['conversaB'],
            'empresa_id' => $cenario['empresaB']->id,
            'arquivada' => 'N',
        ]);
        $this->assertDatabaseHas('whatsapp_mensagens', ['id' => $cenario['mensagemB']]);
    }

    public function test_busca_e_vinculo_de_lead_rejeitam_relacoes_cruzadas(): void
    {
        $cenario = $this->cenario();

        $this->actingAs($cenario['vendedorA'])
            ->getJson(route('whatsapp.leads'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $cenario['contatoA']);

        $this->postJson(route('whatsapp.vincularContato', $cenario['conversaA']), [
            'contato_id' => $cenario['contatoB'],
        ])->assertUnprocessable();

        $this->assertDatabaseHas('whatsapp_conversas', [
            'id' => $cenario['conversaA'],
            'contato_id' => $cenario['contatoA'],
        ]);
    }

    public function test_master_developer_pode_operar_a_empresa_ativa_sem_enxergar_as_demais(): void
    {
        $cenario = $this->cenario();
        $master = User::factory()->create([
            'empresa_id' => $cenario['empresaA']->id,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $cenario['empresaB']->id])
            ->getJson(route('whatsapp.conversas'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $cenario['conversaB']);

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $cenario['empresaB']->id])
            ->get(route('whatsapp.chat', $cenario['conversaB']))
            ->assertOk()
            ->assertViewHas('podeEnviar', true);

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $cenario['empresaB']->id])
            ->postJson(route('whatsapp.descartarConversa', $cenario['conversaB']))
            ->assertOk();

        $this->assertDatabaseHas('whatsapp_conversas', [
            'id' => $cenario['conversaB'],
            'empresa_id' => $cenario['empresaB']->id,
            'arquivada' => 'Y',
        ]);
        $this->assertDatabaseHas('whatsapp_conversas', [
            'id' => $cenario['conversaA'],
            'empresa_id' => $cenario['empresaA']->id,
            'arquivada' => 'N',
        ]);
    }

    public function test_master_global_nao_cria_instancia_pessoal_de_whatsapp_no_tenant(): void
    {
        $cenario = $this->cenario();
        $master = User::factory()->create([
            'empresa_id' => null,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $cenario['empresaB']->id])
            ->postJson(route('whatsapp.conexao.conectar'))
            ->assertForbidden();

        $this->assertDatabaseMissing('whatsapp_instancias', [
            'empresa_id' => $cenario['empresaB']->id,
            'user_id' => $master->id,
        ]);
    }

    public function test_modelos_whatsapp_bloqueiam_pais_de_outra_empresa(): void
    {
        $cenario = $this->cenario();
        app(TenantContext::class)->set($cenario['empresaA']->id);

        $this->expectException(LogicException::class);

        WhatsappMensagem::query()->create([
            'empresa_id' => $cenario['empresaA']->id,
            'conversa_id' => $cenario['conversaB'],
            'message_id' => 'tentativa-cruzada',
            'direcao' => 'IN',
            'tipo' => 'text',
            'body' => 'Não deve persistir',
            'message_timestamp' => now(),
        ]);
    }

    public function test_repositorio_nao_altera_conversa_quando_empresa_nao_corresponde(): void
    {
        $cenario = $this->cenario();
        $repository = app(WhatsappConversaRepositoryInterface::class);

        $this->assertFalse($repository->changeStatusConversa(
            $cenario['conversaB'],
            $cenario['empresaA']->id,
            $cenario['tabulacaoA'],
        ));
        $this->assertFalse($repository->vincularContato(
            $cenario['conversaB'],
            $cenario['empresaA']->id,
            $cenario['contatoA'],
        ));
        $repository->zerarNaoLidas($cenario['conversaB'], $cenario['empresaA']->id);
        $this->assertFalse($repository->setArquivada(
            $cenario['conversaB'],
            $cenario['empresaA']->id,
            true,
        ));

        $this->assertDatabaseHas('whatsapp_conversas', [
            'id' => $cenario['conversaB'],
            'empresa_id' => $cenario['empresaB']->id,
            'tabulacao_id' => $cenario['tabulacaoB'],
            'contato_id' => $cenario['contatoB'],
            'unread_count' => 1,
            'arquivada' => 'N',
        ]);
    }

    public function test_webhook_autentica_a_instancia_exata_e_job_carrega_contexto_do_tenant(): void
    {
        $cenario = $this->cenario();
        Bus::fake();

        $payload = [
            'event' => 'messages.upsert',
            'data' => ['key' => ['id' => 'wamid-1', 'remoteJid' => '5511999999999@s.whatsapp.net']],
        ];

        $this->postJson('/webhook/whatsapp/instancia-b/token-b-invalido', $payload)->assertUnauthorized();
        $this->postJson('/webhook/whatsapp/instancia-b/token-b', $payload)->assertOk();

        Bus::assertDispatched(ProcessarMensagemRecebida::class, function (ProcessarMensagemRecebida $job) use ($cenario) {
            $middleware = $job->middleware();

            $this->assertCount(1, $middleware);

            return $job->instanciaId === $cenario['instanciaB'];
        });
    }

    public function test_middleware_do_worker_substitui_e_limpa_contexto_entre_jobs(): void
    {
        $cenario = $this->cenario();
        $context = app(TenantContext::class);
        $context->set($cenario['empresaA']->id);
        $job = new class($cenario['instanciaB'])
        {
            public function __construct(public int $instanciaId) {}
        };

        (new UseWhatsappTenantContext)->handle($job, function () use ($cenario, $context): void {
            $this->assertSame($cenario['empresaB']->id, $context->id());
        });

        $this->assertFalse($context->isResolved());
    }

    public function test_middleware_do_worker_nao_executa_sem_referencia_de_tenant(): void
    {
        $context = app(TenantContext::class);
        $context->set(Empresa::factory()->create()->id);
        $executed = false;
        $job = new class
        {
            public int $mensagemId = 999999;
        };

        (new UseWhatsappTenantContext)->handle($job, function () use (&$executed): void {
            $executed = true;
        });

        $this->assertFalse($executed);
        $this->assertFalse($context->isResolved());
    }

    /** @return array<string, mixed> */
    private function cenario(): array
    {
        $empresaA = Empresa::factory()->create(['nome_fantasia' => 'Corretora A']);
        $empresaB = Empresa::factory()->create(['nome_fantasia' => 'Corretora B']);
        $catalog = app(TabulationCatalog::class);
        $catalog->provision($empresaA->id);
        $catalog->provision($empresaB->id);
        $tabulacaoA = $catalog->id($empresaA->id, TabulationCode::PROSPECCAO);
        $tabulacaoB = $catalog->id($empresaB->id, TabulationCode::PROSPECCAO);
        $vendedorA = User::factory()->create(['empresa_id' => $empresaA->id, 'user_role_id' => UserRole::VENDEDOR, 'ativo' => 'Y']);
        $vendedorB = User::factory()->create(['empresa_id' => $empresaB->id, 'user_role_id' => UserRole::VENDEDOR, 'ativo' => 'Y']);
        $contatoA = $this->contato($empresaA->id, $vendedorA->id, 'Lead A', '11911111111');
        $contatoB = $this->contato($empresaB->id, $vendedorB->id, 'Lead B', '11922222222');
        $this->atribuicao($empresaA->id, $vendedorA->id, $contatoA, $tabulacaoA);
        $this->atribuicao($empresaB->id, $vendedorB->id, $contatoB, $tabulacaoB);
        $instanciaA = $this->instancia($empresaA->id, $vendedorA->id, 'instancia-a', 'token-a');
        $instanciaB = $this->instancia($empresaB->id, $vendedorB->id, 'instancia-b', 'token-b');
        $conversaA = $this->conversa($empresaA->id, $vendedorA->id, $instanciaA, $contatoA, $tabulacaoA, 'Lead A');
        $conversaB = $this->conversa($empresaB->id, $vendedorB->id, $instanciaB, $contatoB, $tabulacaoB, 'Lead B');
        $mensagemB = $this->mensagem($empresaB->id, $conversaB, 'Mensagem privada B');

        return compact(
            'empresaA', 'empresaB', 'vendedorA', 'vendedorB', 'contatoA', 'contatoB',
            'tabulacaoA', 'tabulacaoB', 'instanciaA', 'instanciaB', 'conversaA', 'conversaB', 'mensagemB'
        );
    }

    private function contato(int $empresaId, int $userId, string $nome, string $telefone): int
    {
        return DB::table('contatos')->insertGetId([
            'empresa_id' => $empresaId,
            'user_import_id' => $userId,
            'nome_cliente' => $nome,
            'telefone1' => $telefone,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function atribuicao(int $empresaId, int $userId, int $contatoId, int $tabulacaoId): void
    {
        DB::table('contatos_corretores')->insert([
            'empresa_id' => $empresaId,
            'user_id' => $userId,
            'contato_id' => $contatoId,
            'tabulacao_id' => $tabulacaoId,
            'temperatura' => 'FRIO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function instancia(int $empresaId, int $userId, string $nome, string $token): int
    {
        return DB::table('whatsapp_instancias')->insertGetId([
            'empresa_id' => $empresaId,
            'user_id' => $userId,
            'instance_name' => $nome,
            'status' => 'CONECTADA',
            'webhook_token' => $token,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function conversa(int $empresaId, int $userId, int $instanciaId, int $contatoId, int $tabulacaoId, string $nome): int
    {
        $numeroNormalizado = sprintf('11%08d', $empresaId % 100000000);

        return DB::table('whatsapp_conversas')->insertGetId([
            'empresa_id' => $empresaId,
            'instancia_id' => $instanciaId,
            'user_id' => $userId,
            'remote_jid' => "55{$empresaId}999999999@s.whatsapp.net",
            'numero' => "55{$empresaId}999999999",
            'numero_normalizado' => $numeroNormalizado,
            'nome_whatsapp' => $nome,
            'contato_id' => $contatoId,
            'tabulacao_id' => $tabulacaoId,
            'last_message_at' => now(),
            'last_message_preview' => "Olá de {$nome}",
            'unread_count' => 1,
            'arquivada' => 'N',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function mensagem(int $empresaId, int $conversaId, string $body): int
    {
        return DB::table('whatsapp_mensagens')->insertGetId([
            'empresa_id' => $empresaId,
            'conversa_id' => $conversaId,
            'message_id' => "message-{$empresaId}",
            'direcao' => 'IN',
            'tipo' => 'text',
            'body' => $body,
            'message_timestamp' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
