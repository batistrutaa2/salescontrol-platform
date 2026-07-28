<?php

namespace Tests\Feature\Backoffice;

use App\Enums\EtapaCancelamento;
use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Enums\ViaCancelamento;
use App\Models\CancelamentoPosVenda;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Vendas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Painel de Cancelamentos do pós-venda: entrada manual vinculada à venda,
 * etapa inicial derivada da implantação, kanban com histórico auditável,
 * KPIs por etapa e escopo multi-tenant. Gate para ADM/BACKOFFICE/DEV/SUPERVISOR.
 */
class PainelCancelamentosTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $backoffice;

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
        $this->backoffice = $this->criarUsuario(UserRole::BACKOFFICE);

        DB::table('tabulacoes')->insert([
            'id' => Tabulations::IMPLANTADO, 'empresa_id' => $this->empresa->id, 'descricao' => 'IMPLANTADO',
            'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'status' => 'Y', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('tabulacoes')->insert([
            'id' => Tabulations::VENDA, 'empresa_id' => $this->empresa->id, 'descricao' => 'VENDA',
            'tipo_tabulacao' => 'A', 'efetivo' => 'N', 'status' => 'Y', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function criarUsuario(int $role, ?Empresa $empresa = null): User
    {
        return User::factory()->create([
            'empresa_id' => ($empresa ?? $this->empresa)->id,
            'user_role_id' => $role,
            'ativo' => 'Y',
        ]);
    }

    private function criarContrato(?Empresa $empresa = null, bool $implantado = false, ?string $nome = null): Vendas
    {
        $empresa = $empresa ?? $this->empresa;
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $empresa->id, 'user_import_id' => $this->backoffice->id, 'nome_cliente' => 'C '.uniqid(),
            'cpf' => (string) random_int(10000000000, 99999999999), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return Vendas::create([
            'empresa_id' => $empresa->id, 'user_id' => $this->backoffice->id, 'contato_id' => $contatoId,
            'tabulacao_id' => $implantado ? Tabulations::IMPLANTADO : Tabulations::VENDA,
            'nome_contrato' => $nome ?? 'CONTRATO '.uniqid(),
            'cpf_cnpj' => (string) random_int(10000000000000, 99999999999999), 'operadora' => 'AMIL',
            'valor_contrato' => 500, 'vidas' => 1, 'data_vigencia' => now(),
            'data_implantacao' => $implantado ? now()->format('Y-m-d') : null,
        ]);
    }

    private function payloadStore(Vendas $venda, array $override = []): array
    {
        return array_merge([
            'venda_id' => $venda->id,
            'escopo' => 'APOLICE',
            'via' => ViaCancelamento::LIMINAR->value,
            'operadora_anterior' => 'UNIMED',
        ], $override);
    }

    private function criarCancelamento(Vendas $venda, string $etapa = 'PRONTO_PARA_SOLICITAR'): CancelamentoPosVenda
    {
        return CancelamentoPosVenda::create([
            'venda_id' => $venda->id,
            'empresa_id' => $venda->empresa_id,
            'escopo' => 'APOLICE',
            'via' => ViaCancelamento::DIRETO_OPERADORA->value,
            'operadora_anterior' => 'BRADESCO',
            'etapa' => $etapa,
            'created_by' => $this->backoffice->id,
        ]);
    }

    // ---------------------------------------------------------------
    // Gate
    // ---------------------------------------------------------------

    public function test_acesso_liberado_para_roles_do_backoffice(): void
    {
        foreach ([UserRole::ADMINISTRATIVO, UserRole::BACKOFFICE, UserRole::DEVELOPER, UserRole::SUPERVISOR] as $role) {
            $user = $this->criarUsuario($role);
            $this->actingAs($user)->get(route('backoffice.cancelamentos.index'))->assertOk();
            $this->actingAs($user)->getJson(route('backoffice.cancelamentos.dados'))->assertOk();
        }
    }

    public function test_vendedor_recebe_403(): void
    {
        $vendedor = $this->criarUsuario(UserRole::VENDEDOR);

        $this->actingAs($vendedor)->get(route('backoffice.cancelamentos.index'))->assertForbidden();
        $this->actingAs($vendedor)->getJson(route('backoffice.cancelamentos.dados'))->assertForbidden();
        $this->actingAs($vendedor)->postJson(route('backoffice.cancelamentos.store'), [])->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Store
    // ---------------------------------------------------------------

    public function test_store_venda_nao_implantada_nasce_aguardando_implantacao(): void
    {
        $venda = $this->criarContrato(implantado: false);

        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.cancelamentos.store'), $this->payloadStore($venda))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('cancelamentos_pos_venda', [
            'venda_id' => $venda->id,
            'etapa' => EtapaCancelamento::AGUARDANDO_IMPLANTACAO->value,
        ]);
        $this->assertDatabaseHas('cancelamentos_pos_venda_historico', [
            'campo_alterado' => 'etapa',
            'valor_novo' => EtapaCancelamento::AGUARDANDO_IMPLANTACAO->label(),
        ]);
    }

    public function test_store_venda_implantada_nasce_pronto_para_solicitar(): void
    {
        $venda = $this->criarContrato(implantado: true);

        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.cancelamentos.store'), $this->payloadStore($venda))
            ->assertOk();

        $this->assertDatabaseHas('cancelamentos_pos_venda', [
            'venda_id' => $venda->id,
            'etapa' => EtapaCancelamento::PRONTO_PARA_SOLICITAR->value,
        ]);
    }

    public function test_store_beneficiario_especifico_guarda_nome(): void
    {
        $venda = $this->criarContrato();

        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.cancelamentos.store'), $this->payloadStore($venda, [
                'escopo' => 'BENEFICIARIO',
                'beneficiario_nome' => 'JOSE DA SILVA',
            ]))
            ->assertOk();

        $this->assertDatabaseHas('cancelamentos_pos_venda', [
            'venda_id' => $venda->id,
            'escopo' => 'BENEFICIARIO',
            'beneficiario_nome' => 'JOSE DA SILVA',
        ]);
    }

    public function test_store_validacao(): void
    {
        $venda = $this->criarContrato();
        $acting = $this->actingAs($this->backoffice);

        $acting->postJson(route('backoffice.cancelamentos.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['venda_id', 'escopo', 'via', 'operadora_anterior']);

        $acting->postJson(route('backoffice.cancelamentos.store'), $this->payloadStore($venda, [
            'escopo' => 'BENEFICIARIO',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['beneficiario_nome']);

        $acting->postJson(route('backoffice.cancelamentos.store'), $this->payloadStore($venda, [
            'via' => 'FAX',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['via']);
    }

    // ---------------------------------------------------------------
    // Multi-tenant
    // ---------------------------------------------------------------

    public function test_store_nao_aceita_venda_de_outra_empresa(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $vendaAlheia = $this->criarContrato($outraEmpresa);

        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.cancelamentos.store'), $this->payloadStore($vendaAlheia))
            ->assertUnprocessable();

        $this->assertDatabaseMissing('cancelamentos_pos_venda', ['venda_id' => $vendaAlheia->id]);
    }

    public function test_dados_nao_vaza_outra_empresa_e_acoes_dao_404(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $alheio = $this->criarCancelamento($this->criarContrato($outraEmpresa));
        $meu = $this->criarCancelamento($this->criarContrato());

        $registros = $this->actingAs($this->backoffice)
            ->getJson(route('backoffice.cancelamentos.dados'))
            ->assertOk()
            ->json('registros');

        $this->assertCount(1, $registros);
        $this->assertSame($meu->id, $registros[0]['id']);

        $acting = $this->actingAs($this->backoffice);
        $acting->getJson(route('backoffice.cancelamentos.show', $alheio->id))->assertNotFound();
        $acting->postJson(route('backoffice.cancelamentos.mover', $alheio->id), ['etapa' => 'SOLICITADO'])->assertNotFound();
        $acting->postJson(route('backoffice.cancelamentos.atribuir', $alheio->id), ['responsavel_id' => null])->assertNotFound();
        $acting->postJson(route('backoffice.cancelamentos.desistir', $alheio->id), [])->assertNotFound();
    }

    // ---------------------------------------------------------------
    // Mover / desistir / atribuir / atualizar
    // ---------------------------------------------------------------

    public function test_mover_para_solicitado_seta_data_e_protocolo(): void
    {
        $c = $this->criarCancelamento($this->criarContrato(implantado: true));

        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.cancelamentos.mover', $c->id), [
                'etapa' => 'SOLICITADO',
                'protocolo' => 'PROT-123',
            ])
            ->assertOk();

        $c->refresh();
        $this->assertSame('SOLICITADO', $c->etapa);
        $this->assertNotNull($c->solicitado_em);
        $this->assertSame('PROT-123', $c->protocolo);
        $this->assertDatabaseHas('cancelamentos_pos_venda_historico', [
            'cancelamento_pos_venda_id' => $c->id,
            'campo_alterado' => 'etapa',
            'valor_anterior' => EtapaCancelamento::PRONTO_PARA_SOLICITAR->label(),
            'valor_novo' => EtapaCancelamento::SOLICITADO->label(),
        ]);
    }

    public function test_mover_para_concluido_seta_concluido_em(): void
    {
        $c = $this->criarCancelamento($this->criarContrato(), etapa: 'SOLICITADO');

        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.cancelamentos.mover', $c->id), ['etapa' => 'CONCLUIDO'])
            ->assertOk();

        $this->assertNotNull($c->refresh()->concluido_em);
    }

    public function test_mover_etapa_invalida_da_422(): void
    {
        $c = $this->criarCancelamento($this->criarContrato());

        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.cancelamentos.mover', $c->id), ['etapa' => 'INEXISTENTE'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['etapa']);
    }

    public function test_desistir_registra_motivo_no_historico(): void
    {
        $c = $this->criarCancelamento($this->criarContrato());

        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.cancelamentos.desistir', $c->id), ['motivo' => 'Cliente mudou de ideia'])
            ->assertOk();

        $this->assertSame(EtapaCancelamento::DESISTIDO->value, $c->refresh()->etapa);
        $this->assertDatabaseHas('cancelamentos_pos_venda_historico', [
            'cancelamento_pos_venda_id' => $c->id,
            'valor_novo' => EtapaCancelamento::DESISTIDO->label(),
            'observacao' => 'Motivo: Cliente mudou de ideia',
        ]);
    }

    public function test_atribuir_e_limpar_responsavel(): void
    {
        $c = $this->criarCancelamento($this->criarContrato());
        $responsavel = $this->criarUsuario(UserRole::BACKOFFICE);

        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.cancelamentos.atribuir', $c->id), ['responsavel_id' => $responsavel->id])
            ->assertOk();
        $this->assertSame($responsavel->id, $c->refresh()->responsavel_id);
        $this->assertDatabaseHas('cancelamentos_pos_venda_historico', [
            'cancelamento_pos_venda_id' => $c->id,
            'campo_alterado' => 'responsavel',
            'valor_novo' => $responsavel->name,
        ]);

        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.cancelamentos.atribuir', $c->id), ['responsavel_id' => null])
            ->assertOk();
        $this->assertNull($c->refresh()->responsavel_id);
    }

    public function test_update_protocolo_e_observacoes_com_historico(): void
    {
        $c = $this->criarCancelamento($this->criarContrato());

        $this->actingAs($this->backoffice)
            ->patchJson(route('backoffice.cancelamentos.update', $c->id), [
                'protocolo' => 'ABC-999',
                'observacoes' => 'Operadora pediu carta assinada.',
            ])
            ->assertOk();

        $c->refresh();
        $this->assertSame('ABC-999', $c->protocolo);
        $this->assertSame('Operadora pediu carta assinada.', $c->observacoes);
        $this->assertDatabaseHas('cancelamentos_pos_venda_historico', [
            'cancelamento_pos_venda_id' => $c->id,
            'campo_alterado' => 'protocolo',
            'valor_novo' => 'ABC-999',
        ]);
    }

    // ---------------------------------------------------------------
    // Dados / KPIs / detalhe / busca
    // ---------------------------------------------------------------

    public function test_kpis_contam_por_etapa_e_desistido_sai_do_fluxo(): void
    {
        $this->criarCancelamento($this->criarContrato(), etapa: 'AGUARDANDO_IMPLANTACAO');
        $this->criarCancelamento($this->criarContrato(), etapa: 'SOLICITADO');
        $this->criarCancelamento($this->criarContrato(), etapa: 'SOLICITADO');
        $this->criarCancelamento($this->criarContrato(), etapa: 'DESISTIDO');

        $kpis = $this->actingAs($this->backoffice)
            ->getJson(route('backoffice.cancelamentos.dados'))
            ->assertOk()
            ->json('kpis');

        $this->assertSame(1, $kpis['AGUARDANDO_IMPLANTACAO']);
        $this->assertSame(0, $kpis['PRONTO_PARA_SOLICITAR']);
        $this->assertSame(2, $kpis['SOLICITADO']);
        $this->assertSame(0, $kpis['EM_EXIGENCIA']);
        $this->assertSame(0, $kpis['CONCLUIDO']);
        $this->assertSame(1, $kpis['DESISTIDO']);
    }

    public function test_registros_derivam_flag_implantado(): void
    {
        $this->criarCancelamento($this->criarContrato(implantado: true), etapa: 'AGUARDANDO_IMPLANTACAO');

        $registros = $this->actingAs($this->backoffice)
            ->getJson(route('backoffice.cancelamentos.dados'))
            ->json('registros');

        $this->assertTrue($registros[0]['implantado']);
    }

    public function test_show_traz_detalhe_e_timeline(): void
    {
        $c = $this->criarCancelamento($this->criarContrato());
        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.cancelamentos.mover', $c->id), ['etapa' => 'SOLICITADO'])
            ->assertOk();

        $detalhe = $this->actingAs($this->backoffice)
            ->getJson(route('backoffice.cancelamentos.show', $c->id))
            ->assertOk()
            ->json();

        $this->assertSame($c->id, $detalhe['registro']['id']);
        $this->assertSame('Solicitado', $detalhe['registro']['etapa_label']);
        $this->assertNotEmpty($detalhe['historico']);
        $this->assertSame('etapa', $detalhe['historico'][0]['campo_alterado']);
    }

    public function test_buscar_contratos_por_nome_e_digitos_sem_vazar_empresa(): void
    {
        $venda = $this->criarContrato(nome: 'PADARIA DO ZE LTDA');
        $outraEmpresa = Empresa::factory()->create();
        $this->criarContrato($outraEmpresa, nome: 'PADARIA DO ZE FILIAL');

        $porNome = $this->actingAs($this->backoffice)
            ->getJson(route('backoffice.cancelamentos.buscarContratos', ['q' => 'padaria']))
            ->assertOk()
            ->json('contratos');

        $this->assertCount(1, $porNome);
        $this->assertSame($venda->id, $porNome[0]['id']);

        $digitos = substr(preg_replace('/\D+/', '', $venda->cpf_cnpj), 0, 8);
        $porCnpj = $this->actingAs($this->backoffice)
            ->getJson(route('backoffice.cancelamentos.buscarContratos', ['q' => $digitos]))
            ->json('contratos');

        $this->assertNotEmpty($porCnpj);
        $this->assertContains($venda->id, array_column($porCnpj, 'id'));
    }
}
