<?php

namespace Tests\Feature\Comercial;

use App\Enums\UserRole;
use App\Jobs\EnviarReuniaoAgendadaWhatsappJob;
use App\Models\ComercialReunioes;
use App\Models\Contatos;
use App\Models\ContatosCorretores;
use App\Models\Empresa;
use App\Models\User;
use App\Services\ReuniaoAgendadaFormatter;
use App\Services\TabulationCatalog;
use App\Services\WhatsappService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AgendarReuniaoWhatsappTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $vendedor;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::SUPERVISOR, 'tipo_usuario' => 'SUPERVISOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::BACKOFFICE, 'tipo_usuario' => 'BACKOFFICE', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();

        $this->vendedor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);

        $this->manager = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::SUPERVISOR,
            'ativo' => 'Y',
            'whatsapp' => '(11) 99999-8888',
        ]);
    }

    public function test_agendar_reuniao_dispara_job_de_whatsapp_para_o_manager_selecionado(): void
    {
        Bus::fake();

        $payload = [
            'titulo' => 'Reunião com lead premium',
            'manager_id' => $this->manager->id,
            'data_inicio' => now()->addDay()->format('Y-m-d H:i:s'),
            'data_final' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'location' => 'https://meet.google.com/abc-defg-hij',
            'observacao' => 'Cliente quer plano empresarial',
        ];

        $response = $this->actingAs($this->vendedor)->postJson('/reunioes', $payload);

        $response->assertStatus(200)->assertJson(['status' => 'success']);

        $reuniao = ComercialReunioes::where('empresa_id', $this->empresa->id)->latest('id')->first();
        $this->assertNotNull($reuniao);
        $this->assertSame($this->vendedor->id, $reuniao->user_id);
        $this->assertSame($this->manager->id, $reuniao->manager_id);

        Bus::assertDispatched(
            EnviarReuniaoAgendadaWhatsappJob::class,
            fn (EnviarReuniaoAgendadaWhatsappJob $job) => $job->reuniaoId === $reuniao->id,
        );
    }

    public function test_request_invalido_nao_dispara_job(): void
    {
        Bus::fake();

        $response = $this->actingAs($this->vendedor)->postJson('/reunioes', [
            // titulo ausente, manager_id ausente
            'data_inicio' => now()->addDay()->format('Y-m-d H:i:s'),
            'data_final' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
        ]);

        $this->assertNotEquals(200, $response->status(), 'request inválido não deveria retornar 200');
        $this->assertSame(0, ComercialReunioes::count(), 'nenhuma reunião deveria ter sido persistida');
        Bus::assertNotDispatched(EnviarReuniaoAgendadaWhatsappJob::class);
    }

    public function test_manager_de_outra_empresa_nao_dispara_job(): void
    {
        Bus::fake();

        $outraEmpresa = Empresa::factory()->create();
        $managerOutraEmpresa = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
            'whatsapp' => '(11) 88888-7777',
        ]);

        $response = $this->actingAs($this->vendedor)->postJson('/reunioes', [
            'titulo' => 'Tentativa cross-empresa',
            'manager_id' => $managerOutraEmpresa->id,
            'data_inicio' => now()->addDay()->format('Y-m-d H:i:s'),
            'data_final' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'location' => 'Sala 1',
        ]);

        $response->assertStatus(422);
        Bus::assertNotDispatched(EnviarReuniaoAgendadaWhatsappJob::class);
    }

    public function test_master_global_nao_pode_ser_selecionado_como_gestor_operacional(): void
    {
        Bus::fake();

        $master = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::SUPERVISOR,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        $this->actingAs($this->vendedor)->postJson('/reunioes', [
            'titulo' => 'Tentativa de atribuir o master',
            'manager_id' => $master->id,
            'data_inicio' => now()->addDay()->format('Y-m-d H:i:s'),
            'data_final' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
        ])->assertUnprocessable()->assertJsonValidationErrors('manager_id');

        $this->assertDatabaseMissing('comercial_reunioes', ['manager_id' => $master->id]);
        Bus::assertNotDispatched(EnviarReuniaoAgendadaWhatsappJob::class);
    }

    public function test_job_aceita_master_como_autor_mas_mantem_gestor_no_tenant_da_reuniao(): void
    {
        $master = User::factory()->create([
            'empresa_id' => null,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'name' => 'Master da plataforma',
            'ativo' => 'Y',
        ]);
        $this->empresa->update(['whatsapp_token' => 'token-empresa-ativa']);

        $reuniao = app(TenantContext::class)->run($this->empresa->id, fn () => ComercialReunioes::create([
            'user_id' => $master->id,
            'manager_id' => $this->manager->id,
            'titulo' => 'Reunião criada pelo master',
            'data_inicio' => now()->addDay(),
            'data_final' => now()->addDay()->addHour(),
            'status' => 'scheduled',
        ]));

        $whatsapp = $this->mock(WhatsappService::class);
        $whatsapp->shouldReceive('send')
            ->once()
            ->withArgs(fn ($token, $telefone, $body) => $token === 'token-empresa-ativa'
                && $telefone === $this->manager->whatsapp
                && str_contains($body, 'Master da plataforma'))
            ->andReturn(['success' => true]);

        (new EnviarReuniaoAgendadaWhatsappJob($reuniao->id))->handle(
            $whatsapp,
            app(ReuniaoAgendadaFormatter::class),
        );

        $this->assertFalse(app(TenantContext::class)->isResolved());
    }

    public function test_master_sem_empresa_configura_agenda_e_vincula_contato_do_tenant_ativo(): void
    {
        Bus::fake();
        $master = User::factory()->create([
            'empresa_id' => null,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'name' => 'Master global',
            'ativo' => 'Y',
        ]);
        app(TabulationCatalog::class)->provision($this->empresa->id);
        $contato = Contatos::query()->create([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->vendedor->id,
            'nome_cliente' => 'Lead do vendedor',
            'status' => 'Y',
        ]);
        ContatosCorretores::query()->create([
            'empresa_id' => $this->empresa->id,
            'contato_id' => $contato->id,
            'user_id' => $this->vendedor->id,
            'tabulacao_id' => DB::table('tabulacoes')->where('empresa_id', $this->empresa->id)->value('id'),
        ]);
        $session = [TenantContext::SESSION_KEY => $this->empresa->id];
        $this->actingAs($master)->withSession($session);

        $this->getJson(route('comercialReunioes.contacts', ['search' => 'Lead do vendedor']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $contato->id);

        $this->putJson(route('comercialReunioes.settings.update'), [
            'reuniao_horario_inicio' => '09:00',
            'reuniao_horario_fim' => '11:00',
            'reuniao_duracao_minutos' => 30,
        ])->assertOk()->assertJsonPath('settings.reuniao_duracao_minutos', 30);

        $slots = $this->withSession($session)->getJson(route('comercialReunioes.slots', [
            'managerId' => $this->manager->id,
            'date' => now()->addDay()->toDateString(),
        ]))->assertOk();
        $slots->assertJsonCount(4, 'available_slots')
            ->assertJsonPath('available_slots.0.start_formatted', '09:00')
            ->assertJsonPath('available_slots.3.end_formatted', '11:00');

        $this->withSession($session)->postJson(route('comercialReunioes.store'), [
            'titulo' => 'Reunião administrada pelo master',
            'manager_id' => $this->manager->id,
            'contato_id' => $contato->id,
            'data_inicio' => now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
            'data_final' => now()->addDay()->setTime(9, 30)->format('Y-m-d H:i:s'),
        ])->assertOk()->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('empresas', [
            'id' => $this->empresa->id,
            'reuniao_horario_inicio' => '09:00:00',
            'reuniao_horario_fim' => '11:00:00',
            'reuniao_duracao_minutos' => 30,
        ]);
        $this->assertDatabaseHas('comercial_reunioes', [
            'empresa_id' => $this->empresa->id,
            'user_id' => $master->id,
            'manager_id' => $this->manager->id,
            'contato_id' => $contato->id,
        ]);
        $this->assertDatabaseHas('users', ['id' => $master->id, 'empresa_id' => null]);
    }

    public function test_vendedor_lista_e_altera_somente_as_proprias_reunioes(): void
    {
        $outroVendedor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
        [$propria, $externa] = app(TenantContext::class)->run($this->empresa->id, function () use ($outroVendedor) {
            $base = [
                'manager_id' => $this->manager->id,
                'data_inicio' => now()->addDay(),
                'data_final' => now()->addDay()->addHour(),
                'status' => 'scheduled',
            ];

            return [
                ComercialReunioes::query()->create([...$base, 'titulo' => 'Minha reunião', 'user_id' => $this->vendedor->id]),
                ComercialReunioes::query()->create([...$base, 'titulo' => 'Reunião de colega', 'user_id' => $outroVendedor->id]),
            ];
        });
        $this->actingAs($this->vendedor);

        $this->getJson(route('comercialReunioes.data'))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $propria->id)
            ->assertDontSee('Reunião de colega');
        $this->getJson(route('comercialReunioes.stats'))
            ->assertOk()
            ->assertJsonPath('total', 1);

        $payload = [
            'titulo' => 'Alteração indevida',
            'manager_id' => $this->manager->id,
            'data_inicio' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'data_final' => now()->addDays(2)->addHour()->format('Y-m-d H:i:s'),
        ];
        $this->putJson(route('comercialReunioes.update', $externa->id), $payload)->assertNotFound();
        $this->deleteJson(route('comercialReunioes.destroy', $externa->id))->assertNotFound();
        $this->assertDatabaseHas('comercial_reunioes', [
            'id' => $externa->id,
            'titulo' => 'Reunião de colega',
            'deleted_at' => null,
        ]);
    }

    public function test_backoffice_nao_acessa_calendario_comercial_por_url_direta(): void
    {
        $backoffice = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::BACKOFFICE,
            'ativo' => 'Y',
        ]);

        $this->actingAs($backoffice)
            ->getJson(route('comercialReunioes.data'))
            ->assertForbidden();
        $this->get(route('comercialReunioes.index'))->assertForbidden();
    }
}
