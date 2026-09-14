<?php

namespace Tests\Feature\Backoffice;

use App\Enums\TabulationCode;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Vendas;
use App\Services\BoletoLembreteService;
use App\Services\TabulationCatalog;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class BoletoLembreteTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private Empresa $outraEmpresa;

    private User $admin;

    private User $backoffice;

    private User $externo;

    private Vendas $venda;

    private Vendas $outraVenda;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-09-14 09:00:00', 'America/Sao_Paulo'));
        foreach ([1 => 'VENDEDOR', 2 => 'ADMINISTRATIVO', 3 => 'BACKOFFICE', 4 => 'DEVELOPER', 5 => 'SUPERVISOR', 7 => 'ADVOGADA', 8 => 'FINANCEIRO'] as $id => $nome) {
            DB::table('user_roles')->updateOrInsert(['id' => $id], ['tipo_usuario' => $nome, 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->empresa = Empresa::factory()->create();
        $this->outraEmpresa = Empresa::factory()->create();
        $this->admin = $this->usuario($this->empresa, UserRole::ADMINISTRATIVO);
        $this->backoffice = $this->usuario($this->empresa, UserRole::BACKOFFICE);
        $this->externo = $this->usuario($this->outraEmpresa, UserRole::ADMINISTRATIVO);
        foreach ([$this->empresa, $this->outraEmpresa] as $empresa) {
            app(TabulationCatalog::class)->provision($empresa->id);
        }
        $this->venda = $this->contrato($this->empresa, $this->admin);
        $this->outraVenda = $this->contrato($this->outraEmpresa, $this->externo);
    }

    public function test_cadastro_gera_aviso_no_dia_apenas_para_admin_e_backoffice_da_empresa(): void
    {
        $this->usuario($this->empresa, UserRole::VENDEDOR);
        $this->usuario($this->empresa, UserRole::SUPERVISOR);
        $this->usuario($this->empresa, UserRole::DEVELOPER);
        $this->usuario($this->empresa, UserRole::ADMINISTRATIVO, ['ativo' => 'N']);
        $this->usuario($this->empresa, UserRole::DEVELOPER, ['is_platform_admin' => true]);

        $this->configurar('2026-09-14', 14)->assertRedirect();
        $this->assertDatabaseHas('boleto_agendas', ['venda_id' => $this->venda->id, 'proximo_vencimento' => '2026-10-14']);
        $this->assertDatabaseHas('boleto_lembretes', ['venda_id' => $this->venda->id, 'vencimento' => '2026-09-14', 'tratado_em' => null]);
        $this->assertSame([$this->admin->id, $this->backoffice->id], DB::table('notifications')->orderBy('notifiable_id')->pluck('notifiable_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(0, app(BoletoLembreteService::class)->sincronizar());
        $this->assertDatabaseCount('notifications', 2);
        $this->getJson(route('backoffice.boletos.resumo'))->assertOk()->assertJson(['total' => 1, 'hoje' => 1]);
        $this->getJson(route('notificacoes.novas'))->assertOk()->assertJsonPath('0.data.tipo', 'boleto_vencimento');
        $this->get(route('backoffice.boletos.index'))->assertOk()->assertSee('Vence hoje')->assertSee($this->venda->nome_contrato);
        $this->get(route('home.dashboard'))->assertOk()->assertSee('1 lembrete(s) aguardando acompanhamento.');
    }

    public function test_recorre_mensalmente_e_respeita_fim_de_mes_inclusive_ano_bissexto(): void
    {
        $this->travelTo(CarbonImmutable::parse('2028-01-31 09:00', 'America/Sao_Paulo'));
        $this->configurar('2028-01-31', 31)->assertRedirect();
        $this->assertDatabaseHas('boleto_agendas', ['venda_id' => $this->venda->id, 'proximo_vencimento' => '2028-02-29']);
        $this->travelTo(CarbonImmutable::parse('2028-03-31 09:00', 'America/Sao_Paulo'));
        $this->assertSame(2, app(BoletoLembreteService::class)->sincronizar());
        $this->assertDatabaseHas('boleto_lembretes', ['venda_id' => $this->venda->id, 'vencimento' => '2028-02-29']);
        $this->assertDatabaseHas('boleto_lembretes', ['venda_id' => $this->venda->id, 'vencimento' => '2028-03-31']);
        $this->assertDatabaseHas('boleto_agendas', ['venda_id' => $this->venda->id, 'proximo_vencimento' => '2028-04-30']);
        $this->assertDatabaseCount('boleto_lembretes', 3);
        $this->assertSame(0, app(BoletoLembreteService::class)->sincronizar());
    }

    public function test_aviso_permanece_pendente_ate_tratativa_sem_marcar_pagamento(): void
    {
        $this->configurar('2026-09-14', 14);
        $id = DB::table('boleto_lembretes')->value('id');
        $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00', 'America/Sao_Paulo'));
        $this->getJson(route('backoffice.boletos.resumo'))->assertJson(['total' => 1, 'hoje' => 0]);
        $this->actingAs($this->backoffice)->post(route('backoffice.boletos.tratar', $id), ['observacao' => 'Cliente orientado.'])->assertRedirect();
        $this->assertDatabaseHas('boleto_lembretes', ['id' => $id, 'tratado_por' => $this->backoffice->id, 'observacao' => 'Cliente orientado.']);
        $this->assertSame(0, DB::table('notifications')->whereNull('read_at')->count());
        $this->getJson(route('backoffice.boletos.resumo'))->assertJson(['total' => 0, 'hoje' => 0]);
        $this->actingAs($this->admin)->post(route('backoffice.boletos.tratar', $id), ['observacao' => 'Duplicado.'])->assertRedirect();
        $this->assertDatabaseHas('boleto_lembretes', ['id' => $id, 'tratado_por' => $this->backoffice->id, 'observacao' => 'Cliente orientado.']);
        $this->assertDatabaseHas('vendas', ['id' => $this->venda->id, 'valor_contrato' => 500, 'tabulacao_id' => $this->venda->tabulacao_id]);
    }

    public function test_rejeita_contratos_e_lembretes_de_outra_empresa(): void
    {
        $this->configurar('2026-09-14', 14);
        $id = DB::table('boleto_lembretes')->value('id');
        $this->actingAs($this->externo)->putJson(route('backoffice.boletos.configurar', $this->venda), $this->dados('2026-10-14', 14))->assertNotFound();
        $this->postJson(route('backoffice.boletos.tratar', $id), [])->assertNotFound();
        $this->getJson(route('backoffice.boletos.resumo'))->assertJson(['total' => 0]);
        $this->get(route('backoffice.boletos.index'))->assertOk()->assertDontSee($this->venda->nome_contrato)->assertSee($this->outraVenda->nome_contrato);
        $this->assertDatabaseHas('boleto_lembretes', ['id' => $id, 'tratado_em' => null]);
    }

    public function test_notificacoes_antigas_nao_vazam_apos_mudanca_de_empresa_ou_papel(): void
    {
        $this->configurar('2026-09-14', 14);
        $this->admin->update(['empresa_id' => $this->outraEmpresa->id]);
        $this->actingAs($this->admin->fresh())->getJson(route('notificacoes.novas'))->assertOk()->assertExactJson([]);
        $this->backoffice->update(['user_role_id' => UserRole::VENDEDOR]);
        $this->actingAs($this->backoffice->fresh())->getJson(route('notificacoes.novas'))->assertOk()->assertExactJson([]);
    }

    public function test_vendedor_supervisor_financeiro_e_advogada_nao_acessam_nem_configuram(): void
    {
        $this->configurar('2026-09-14', 14);
        $lembreteId = DB::table('boleto_lembretes')->value('id');
        foreach ([UserRole::VENDEDOR, UserRole::SUPERVISOR, UserRole::FINANCEIRO, UserRole::ADVOGADA] as $role) {
            $user = $this->usuario($this->empresa, $role);
            $this->actingAs($user)->getJson(route('backoffice.boletos.index'))->assertForbidden();
            $this->getJson(route('backoffice.boletos.resumo'))->assertForbidden();
            $this->putJson(route('backoffice.boletos.configurar', $this->venda), $this->dados('2026-09-14', 14))->assertForbidden();
            $this->postJson(route('backoffice.boletos.tratar', $lembreteId), [])->assertForbidden();
        }
        $this->assertDatabaseCount('boleto_agendas', 1);
    }

    public function test_sem_data_futuro_pausado_e_contrato_que_saiu_de_implantado_nao_geram_avisos(): void
    {
        $this->actingAs($this->admin)->get(route('backoffice.boletos.index'))->assertOk()->assertSee('1 sem vencimento cadastrado');
        $this->assertSame(0, app(BoletoLembreteService::class)->sincronizar());
        $this->configurar('2026-09-15', 15)->assertRedirect();
        $this->assertDatabaseCount('boleto_lembretes', 0);
        $this->configurar('2026-09-14', 14, false)->assertRedirect();
        $this->assertDatabaseCount('boleto_lembretes', 0);
        DB::table('boleto_agendas')->update(['ativo' => true]);
        DB::table('vendas')->where('id', $this->venda->id)->update(['tabulacao_id' => app(TabulationCatalog::class)->id($this->empresa->id, TabulationCode::ESTORNO)]);
        $this->assertSame(0, app(BoletoLembreteService::class)->sincronizar());
        $this->assertDatabaseCount('notifications', 0);
        $this->configurar('2026-09-14', 14)->assertNotFound();
    }

    public function test_valida_datas_e_impede_segundo_lembrete_na_mesma_competencia(): void
    {
        $this->actingAs($this->admin)->putJson(route('backoffice.boletos.configurar', $this->venda), $this->dados('2026-09-13', 13))->assertUnprocessable();
        $this->putJson(route('backoffice.boletos.configurar', $this->venda), $this->dados('2026-09-15', 32))->assertUnprocessable();
        $this->putJson(route('backoffice.boletos.configurar', $this->venda), $this->dados('2026-09-15', 16))->assertUnprocessable();
        $this->configurar('2026-09-14', 14)->assertRedirect();
        $this->putJson(route('backoffice.boletos.configurar', $this->venda), $this->dados('2026-09-20', 20))->assertUnprocessable();
        $this->assertDatabaseCount('boleto_lembretes', 1);
    }

    public function test_formulario_recupera_tentativa_rejeitada_no_contrato_da_rota(): void
    {
        $this->actingAs($this->admin)->from(route('backoffice.boletos.index'))
            ->put(route('backoffice.boletos.configurar', $this->venda), [
                ...$this->dados('2026-09-15', 16, false),
                'boleto_venda' => 999999,
            ])->assertSessionHasErrors('proximo_vencimento');
        $this->get(route('backoffice.boletos.index'))->assertOk()
            ->assertSee('id="boleto-restaurar"', false)
            ->assertSee('data-dia="16"', false)
            ->assertSee('data-proximo="2026-09-15"', false)
            ->assertViewHas('contratoAnterior', fn ($venda) => $venda->id === $this->venda->id);
    }

    public function test_falha_na_notificacao_desfaz_lembrete_e_preserva_proximo_vencimento(): void
    {
        $this->configurar('2026-09-15', 15)->assertRedirect();
        $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00', 'America/Sao_Paulo'));
        Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('Falha simulada.'));
        try {
            app(BoletoLembreteService::class)->sincronizar();
            $this->fail('A falha de notificação deveria impedir a conclusão.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falha simulada.', $exception->getMessage());
        }
        $this->assertDatabaseCount('boleto_lembretes', 0);
        $this->assertDatabaseHas('boleto_agendas', ['venda_id' => $this->venda->id, 'proximo_vencimento' => '2026-09-15']);
    }

    public function test_comando_respeita_empresa_e_fuso_horario(): void
    {
        $this->configurar('2026-09-15', 15)->assertRedirect();
        $this->actingAs($this->externo)->put(route('backoffice.boletos.configurar', $this->outraVenda), $this->dados('2026-09-15', 15))->assertRedirect();
        $this->travelTo(CarbonImmutable::parse('2026-09-15 02:59:00', 'UTC'));
        $this->artisan('boletos:lembrar')->assertSuccessful();
        $this->assertDatabaseCount('boleto_lembretes', 0);
        $this->travelTo(CarbonImmutable::parse('2026-09-15 03:00:00', 'UTC'));
        $this->artisan('boletos:lembrar', ['--empresa' => $this->empresa->id])->assertSuccessful();
        $this->assertDatabaseCount('boleto_lembretes', 1);
        $this->assertDatabaseHas('boleto_lembretes', ['venda_id' => $this->venda->id]);
        $this->assertDatabaseMissing('boleto_lembretes', ['venda_id' => $this->outraVenda->id]);
        $this->assertSame($this->outraEmpresa->id, app(TenantContext::class)->id());
    }

    public function test_cliente_sem_contrato_recebe_aviso_recorrente_e_preserva_historico(): void
    {
        $this->actingAs($this->admin)->post(route('backoffice.boletos.manual.store'), [
            ...$this->dados('2026-09-14', 14),
            'nome_cliente' => 'Cliente avulso',
            'referencia' => 'Plano familiar',
            'empresa_id' => $this->outraEmpresa->id,
            'venda_id' => $this->outraVenda->id,
        ])->assertRedirect();
        $agenda = DB::table('boleto_agendas')->first();
        $this->assertNull($agenda->venda_id);
        $this->assertSame($this->empresa->id, $agenda->empresa_id);
        $this->assertDatabaseCount('vendas', 2);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('boleto_lembretes', ['nome_cliente' => 'Cliente avulso', 'referencia' => 'Plano familiar', 'venda_id' => null]);
        $this->get(route('backoffice.boletos.index'))->assertOk()->assertSee('Cliente avulso')->assertSee('1 sem vencimento cadastrado');
        $this->getJson(route('backoffice.boletos.resumo'))->assertJson(['total' => 1]);
        $this->put(route('backoffice.boletos.manual.update', $agenda->id), [
            ...$this->dados('2026-10-14', 14), 'nome_cliente' => 'Cliente renomeado', 'referencia' => 'Nova referência',
        ])->assertRedirect();
        $this->assertDatabaseHas('boleto_lembretes', ['nome_cliente' => 'Cliente avulso', 'referencia' => 'Plano familiar']);
        $this->travelTo(CarbonImmutable::parse('2026-10-14 09:00', 'America/Sao_Paulo'));
        $this->assertSame(1, app(BoletoLembreteService::class)->sincronizar());
        $this->assertSame(0, app(BoletoLembreteService::class)->sincronizar());
        $this->assertDatabaseHas('boleto_lembretes', ['nome_cliente' => 'Cliente renomeado', 'vencimento' => '2026-10-14']);
        $id = DB::table('boleto_lembretes')->orderBy('id')->value('id');
        $this->actingAs($this->backoffice)->post(route('backoffice.boletos.tratar', $id), ['observacao' => 'Cliente orientado'])->assertRedirect();
        $this->assertDatabaseHas('boleto_lembretes', ['id' => $id, 'tratado_por' => $this->backoffice->id]);
        $this->getJson(route('backoffice.boletos.resumo'))->assertJson(['total' => 1]);
    }

    public function test_cadastro_manual_valida_dados_recupera_formulario_e_restringe_acesso(): void
    {
        $this->actingAs($this->admin)->from(route('backoffice.boletos.index'))->post(route('backoffice.boletos.manual.store'), [
            ...$this->dados('2026-09-15', 16), 'nome_cliente' => 'Tentativa preservada',
        ])->assertSessionHasErrors('proximo_vencimento');
        $this->get(route('backoffice.boletos.index'))->assertOk()->assertSee('data-nome="Tentativa preservada"', false)->assertSee('data-manual="1"', false);
        $this->postJson(route('backoffice.boletos.manual.store'), $this->dados('2026-09-15', 15))->assertUnprocessable();
        $this->post(route('backoffice.boletos.manual.store'), [
            ...$this->dados('2026-09-15', 15, false), 'nome_cliente' => 'Somente empresa A',
        ])->assertRedirect();
        $agenda = DB::table('boleto_agendas')->first();
        $this->actingAs($this->externo)->putJson(route('backoffice.boletos.manual.update', $agenda->id), [
            ...$this->dados('2026-09-15', 15), 'nome_cliente' => 'Não permitido',
        ])->assertNotFound();
        $this->get(route('backoffice.boletos.index'))->assertOk()->assertDontSee('Somente empresa A');
        $this->actingAs($this->usuario($this->empresa, UserRole::VENDEDOR))
            ->postJson(route('backoffice.boletos.manual.store'), [
                ...$this->dados('2026-09-15', 15), 'nome_cliente' => 'Não permitido',
            ])->assertForbidden();
        $this->putJson(route('backoffice.boletos.manual.update', $agenda->id), [
            ...$this->dados('2026-09-15', 15), 'nome_cliente' => 'Não permitido',
        ])->assertForbidden();
        $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00', 'America/Sao_Paulo'));
        $this->assertSame(0, app(BoletoLembreteService::class)->sincronizar());
        $this->assertDatabaseCount('boleto_lembretes', 0);
        $this->assertDatabaseHas('boleto_agendas', ['id' => $agenda->id, 'nome_cliente' => 'Somente empresa A', 'ativo' => false]);
    }

    private function usuario(Empresa $empresa, int $role, array $extra = []): User
    {
        return User::factory()->create(array_merge(['empresa_id' => $empresa->id, 'user_role_id' => $role, 'ativo' => 'Y'], $extra));
    }

    private function contrato(Empresa $empresa, User $user): Vendas
    {
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $empresa->id, 'user_import_id' => $user->id,
            'nome_cliente' => 'Cliente teste', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $id = DB::table('vendas')->insertGetId([
            'empresa_id' => $empresa->id, 'user_id' => $user->id, 'contato_id' => $contatoId,
            'tabulacao_id' => app(TabulationCatalog::class)->id($empresa->id, TabulationCode::IMPLANTADO),
            'nome_contrato' => 'Cliente Empresa '.$empresa->id, 'cpf_cnpj' => '52998224725',
            'operadora' => 'Operadora teste', 'valor_contrato' => 500, 'vidas' => 1,
            'data_implantacao' => '2026-09-01', 'data_vigencia' => '2026-09-01', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return Vendas::withoutGlobalScope('tenant')->findOrFail($id);
    }

    private function dados(string $data, int $dia, bool $ativo = true): array
    {
        return ['dia_vencimento' => $dia, 'proximo_vencimento' => $data, 'ativo' => $ativo];
    }

    private function configurar(string $data, int $dia, bool $ativo = true)
    {
        return $this->actingAs($this->admin)->from(route('backoffice.boletos.index'))
            ->put(route('backoffice.boletos.configurar', $this->venda), $this->dados($data, $dia, $ativo));
    }
}
