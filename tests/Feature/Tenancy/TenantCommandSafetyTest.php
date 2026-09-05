<?php

namespace Tests\Feature\Tenancy;

use App\Enums\TabulationCode;
use App\Models\Agendamento;
use App\Models\Empresa;
use App\Models\PreditivaConfiguracao;
use App\Models\User;
use App\Notifications\AgendamentoNotificacao;
use App\Notifications\BirthdayGreetingNotification;
use App\Services\TabulationCatalog;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TenantCommandSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_limpeza_da_preditiva_remove_somente_registros_da_empresa_explicita(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $preditivaA = $this->criarLeadComVendaNaPreditiva($empresaA);
        $preditivaB = $this->criarLeadComVendaNaPreditiva($empresaB);

        $this->artisan('preditiva:limpar-com-venda', [
            'empresa_id' => $empresaA->id,
            '--confirmar' => true,
        ])
            ->expectsConfirmation('Confirma a remoção de 1 registros da preditiva?', 'yes')
            ->assertSuccessful();

        $this->assertDatabaseMissing('preditiva', ['id' => $preditivaA]);
        $this->assertDatabaseHas('preditiva', ['id' => $preditivaB, 'empresa_id' => $empresaB->id]);
    }

    public function test_limpeza_rejeita_empresa_inexistente(): void
    {
        $this->artisan('preditiva:limpar-com-venda', ['empresa_id' => 999999])
            ->expectsOutput('Empresa inválida.')
            ->assertFailed();
    }

    public function test_backfill_de_tabulacao_altera_somente_a_empresa_explicita(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $this->criarLeadComVendaNaPreditiva($empresaA);
        $this->criarLeadComVendaNaPreditiva($empresaB);
        DB::table('vendas')->update(['data_implantacao' => now()->toDateString()]);
        $catalog = app(TabulationCatalog::class);
        $catalog->provision($empresaA->id);
        $catalog->provision($empresaB->id);

        $this->artisan('vendas:backfill-tabulacao', ['empresa_id' => $empresaA->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('vendas', [
            'empresa_id' => $empresaA->id,
            'tabulacao_id' => $catalog->id($empresaA->id, TabulationCode::IMPLANTADO),
        ]);
        $this->assertDatabaseHas('vendas', [
            'empresa_id' => $empresaB->id,
            'tabulacao_id' => null,
        ]);
    }

    public function test_backfill_rejeita_empresa_inexistente(): void
    {
        $this->artisan('vendas:backfill-tabulacao', ['empresa_id' => 999999])
            ->expectsOutput('Empresa inválida.')
            ->assertFailed();
    }

    public function test_comandos_operacionais_rejeitam_empresa_inexistente(): void
    {
        config(['documentos.processamento_ativo' => true]);

        $this->artisan('documentos:processar-pendentes', ['empresa_id' => 999999])
            ->expectsOutput('Empresa inválida.')
            ->assertFailed();
        $this->artisan('documentos:reparar-permissoes', ['empresa_id' => 999999])
            ->expectsOutput('Empresa inválida.')
            ->assertFailed();
        $this->artisan('recebiveis:retroativos', ['empresa_id' => 999999])
            ->expectsOutput('Empresa inválida.')
            ->assertFailed();
        $this->artisan('recebiveis:importacao-sys', ['empresa_id' => 999999])
            ->expectsOutput('Empresa inválida.')
            ->assertFailed();
    }

    public function test_verificacao_de_agendamentos_processa_cada_empresa_sem_notificar_usuario_externo(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-09-03 14:30:00');
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $userA = User::factory()->create(['empresa_id' => $empresaA->id]);
        $userB = User::factory()->create(['empresa_id' => $empresaB->id]);
        $contatoA = $this->criarContato($empresaA, $userA);
        $contatoB = $this->criarContato($empresaB, $userB);

        $agendamentoA = app(TenantContext::class)->run($empresaA->id, fn () => Agendamento::create([
            'empresa_id' => $empresaA->id,
            'user_id' => $userA->id,
            'contato_id' => $contatoA,
            'horario_agendamento' => now(),
            'notificado' => 'N',
        ]));
        $agendamentoB = app(TenantContext::class)->run($empresaB->id, fn () => Agendamento::create([
            'empresa_id' => $empresaB->id,
            'user_id' => $userB->id,
            'contato_id' => $contatoB,
            'horario_agendamento' => now(),
            'notificado' => 'N',
        ]));
        $corrompido = DB::table('agendamentos')->insertGetId([
            'empresa_id' => $empresaA->id,
            'user_id' => $userB->id,
            'contato_id' => $contatoA,
            'horario_agendamento' => now(),
            'notificado' => 'N',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('verificar:agendamentos')->assertSuccessful();

        $this->assertDatabaseHas('agendamentos', ['id' => $agendamentoA->id, 'notificado' => 'Y']);
        $this->assertDatabaseHas('agendamentos', ['id' => $agendamentoB->id, 'notificado' => 'Y']);
        $this->assertDatabaseHas('agendamentos', ['id' => $corrompido, 'notificado' => 'N']);
        Notification::assertSentTo($userA, AgendamentoNotificacao::class);
        $this->assertCount(1, Notification::sent($userB, AgendamentoNotificacao::class));
    }

    public function test_reciclagem_agendada_instala_contexto_para_cada_empresa(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        app(TabulationCatalog::class)->provision($empresaA->id);
        app(TabulationCatalog::class)->provision($empresaB->id);

        foreach ([$empresaA, $empresaB] as $empresa) {
            PreditivaConfiguracao::query()->create([
                'empresa_id' => $empresa->id,
                'dias_sem_contato_reenvio' => 30,
                'limite_envio_diario' => 10,
                'indicadores_janela_dias' => 15,
                'envio_automatico_ativo' => true,
            ]);
        }

        $this->artisan('preditiva:reciclar-frios', ['--dry-run' => true])
            ->expectsOutput("Empresa {$empresaA->id} | dias=30 | limite=10 | elegiveis agora: 0")
            ->expectsOutput("Empresa {$empresaB->id} | dias=30 | limite=10 | elegiveis agora: 0")
            ->assertSuccessful();

        $this->assertFalse(app(TenantContext::class)->isResolved());
    }

    public function test_aniversarios_percorrem_empresas_sem_notificar_master_global_ou_usuario_inativo(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-09-05 09:00:00');
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $aniversarianteA = User::factory()->create([
            'empresa_id' => $empresaA->id,
            'data_nascimento' => '1990-09-05',
            'ativo' => 'Y',
        ]);
        $aniversarianteB = User::factory()->create([
            'empresa_id' => $empresaB->id,
            'data_nascimento' => '1985-09-05',
            'ativo' => 'Y',
        ]);
        $inativo = User::factory()->create([
            'empresa_id' => $empresaA->id,
            'data_nascimento' => '1995-09-05',
            'ativo' => 'N',
        ]);
        $master = User::factory()->create([
            'empresa_id' => null,
            'data_nascimento' => '1980-09-05',
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        app(TenantContext::class)->set($empresaB->id);
        $this->artisan('birthdays:send')
            ->expectsOutput('Parabéns enviados para 2 usuário(s).')
            ->assertSuccessful();

        Notification::assertSentTo($aniversarianteA, BirthdayGreetingNotification::class);
        Notification::assertSentTo($aniversarianteB, BirthdayGreetingNotification::class);
        Notification::assertNotSentTo($inativo, BirthdayGreetingNotification::class);
        Notification::assertNotSentTo($master, BirthdayGreetingNotification::class);
        $this->assertDatabaseHas('users', ['id' => $aniversarianteA->id, 'data_nascimento_notified_at' => '2026-09-05']);
        $this->assertDatabaseHas('users', ['id' => $aniversarianteB->id, 'data_nascimento_notified_at' => '2026-09-05']);
        $this->assertFalse(app(TenantContext::class)->isResolved());
    }

    public function test_diagnostico_de_renovacoes_separa_empresas_e_rejeita_filtro_invalido(): void
    {
        $empresaA = Empresa::factory()->create(['nome_fantasia' => 'Corretora Alfa']);
        $empresaB = Empresa::factory()->create(['nome_fantasia' => 'Corretora Beta']);

        app(TenantContext::class)->set($empresaB->id);
        $this->artisan('renovacoes:diagnosticar')
            ->expectsOutput("Empresa {$empresaA->id}: Corretora Alfa")
            ->expectsOutput("Empresa {$empresaB->id}: Corretora Beta")
            ->assertSuccessful();
        $this->assertFalse(app(TenantContext::class)->isResolved());

        $this->artisan('renovacoes:diagnosticar', ['--empresa' => 999999])
            ->expectsOutput('Empresa inválida.')
            ->assertFailed();
    }

    private function criarLeadComVendaNaPreditiva(Empresa $empresa): int
    {
        $user = User::factory()->create(['empresa_id' => $empresa->id]);
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $empresa->id,
            'user_import_id' => $user->id,
            'nome_cliente' => "Lead {$empresa->id}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vendas')->insert([
            'empresa_id' => $empresa->id,
            'user_id' => $user->id,
            'contato_id' => $contatoId,
            'data_vigencia' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('preditiva')->insertGetId([
            'empresa_id' => $empresa->id,
            'contato_id' => $contatoId,
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function criarContato(Empresa $empresa, User $user): int
    {
        return DB::table('contatos')->insertGetId([
            'empresa_id' => $empresa->id,
            'user_import_id' => $user->id,
            'nome_cliente' => "Contato {$empresa->id}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
