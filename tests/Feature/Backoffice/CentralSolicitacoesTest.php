<?php

namespace Tests\Feature\Backoffice;

use App\Enums\NaturezaEtapaSolicitacao;
use App\Enums\Tabulations;
use App\Enums\TipoSolicitacaoPosVenda;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\PosVendaFluxoEtapa;
use App\Models\PosVendaSolicitacao;
use App\Models\User;
use App\Models\Vendas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Central de Solicitações do Pós-Venda: fila por prazo + kanban com fluxo de
 * etapas configurável por tipo de solicitação.
 */
class CentralSolicitacoesTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $backoffice;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::BACKOFFICE, 'tipo_usuario' => 'BACKOFFICE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::SUPERVISOR, 'tipo_usuario' => 'SUPERVISOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->backoffice = $this->criarUsuario(UserRole::BACKOFFICE);
        $this->vendedor = $this->criarUsuario(UserRole::VENDEDOR);

        DB::table('tabulacoes')->insert([
            'id' => Tabulations::IMPLANTADO, 'empresa_id' => $this->empresa->id, 'descricao' => 'IMPLANTADO',
            'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'status' => 'Y', 'created_at' => now(), 'updated_at' => now(),
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

    private function criarContrato(?Empresa $empresa = null): Vendas
    {
        $empresa = $empresa ?? $this->empresa;

        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $empresa->id, 'user_import_id' => $this->backoffice->id,
            'nome_cliente' => 'Cliente '.uniqid(), 'cpf' => (string) random_int(10000000000, 99999999999),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return Vendas::create([
            'empresa_id' => $empresa->id, 'user_id' => $this->backoffice->id,
            'contato_id' => $contatoId, 'tabulacao_id' => Tabulations::IMPLANTADO,
            'nome_contrato' => 'Contrato '.uniqid(), 'cpf_cnpj' => (string) random_int(10000000000000, 99999999999999),
            'operadora' => 'AMIL', 'valor_contrato' => 500.00, 'vidas' => 1,
            'data_vigencia' => now(), 'data_implantacao' => now(),
        ]);
    }

    private function criarSolicitacao(Vendas $venda, array $overrides = []): PosVendaSolicitacao
    {
        PosVendaFluxoEtapa::seedDefaults($venda->empresa_id);

        $tipo = $overrides['tipo'] ?? TipoSolicitacaoPosVenda::ENVIO_BOLETO->value;
        $etapaId = $overrides['etapa_id'] ?? PosVendaFluxoEtapa::where('empresa_id', $venda->empresa_id)
            ->where('tipo', $tipo)->orderBy('ordem')->value('id');

        return PosVendaSolicitacao::create(array_merge([
            'venda_id' => $venda->id,
            'empresa_id' => $venda->empresa_id,
            'tipo' => $tipo,
            'etapa_id' => $etapaId,
            'titulo' => 'Solicitação teste',
            'status' => PosVendaSolicitacao::STATUS_ABERTA,
            'origem' => PosVendaSolicitacao::ORIGEM_BACKOFFICE,
            'created_by' => $this->backoffice->id,
        ], $overrides));
    }

    private function etapaDoTipo(string $tipo, string $natureza): PosVendaFluxoEtapa
    {
        return PosVendaFluxoEtapa::where('empresa_id', $this->empresa->id)
            ->where('tipo', $tipo)->where('natureza', $natureza)
            ->orderBy('ordem')->firstOrFail();
    }

    // ---------------------------------------------------------------
    // Autorização
    // ---------------------------------------------------------------

    public function test_dados_acessivel_para_os_quatro_perfis(): void
    {
        foreach ([UserRole::ADMINISTRATIVO, UserRole::BACKOFFICE, UserRole::DEVELOPER, UserRole::SUPERVISOR] as $role) {
            $this->actingAs($this->criarUsuario($role))
                ->getJson(route('backoffice.solicitacoes.dados'))
                ->assertOk();
        }
    }

    public function test_index_renderiza_para_backoffice(): void
    {
        $this->withoutVite();

        $this->actingAs($this->backoffice)
            ->get(route('backoffice.solicitacoes.index'))
            ->assertOk()
            ->assertSee('Central de Solicitações', false);
    }

    public function test_vendedor_recebe_403(): void
    {
        $this->actingAs($this->vendedor)
            ->getJson(route('backoffice.solicitacoes.dados'))
            ->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Seed de etapas
    // ---------------------------------------------------------------

    public function test_store_semeia_etapas_default_sem_duplicar(): void
    {
        $venda = $this->criarContrato();

        $this->actingAs($this->backoffice)->postJson(route('backoffice.solicitacoes.store'), [
            'venda_id' => $venda->id, 'tipo' => TipoSolicitacaoPosVenda::CANCELAMENTO->value,
        ])->assertOk();

        $total = PosVendaFluxoEtapa::where('empresa_id', $this->empresa->id)->count();
        $tipos = PosVendaFluxoEtapa::where('empresa_id', $this->empresa->id)->distinct()->count('tipo');
        $this->assertSame(9, $tipos, 'Todos os tipos devem ganhar etapas default.');

        $this->actingAs($this->backoffice)->postJson(route('backoffice.solicitacoes.store'), [
            'venda_id' => $venda->id, 'tipo' => TipoSolicitacaoPosVenda::REEMBOLSO->value,
        ])->assertOk();

        $this->assertSame($total, PosVendaFluxoEtapa::where('empresa_id', $this->empresa->id)->count(), 'Seed não pode duplicar.');
    }

    public function test_buscar_contratos_por_nome_cpf_e_cnpj(): void
    {
        $venda = $this->criarContrato();

        // Por nome — payload traz a visão rápida da venda (plano, valor, vendedor...).
        $resp = $this->actingAs($this->backoffice)
            ->getJson(route('backoffice.solicitacoes.buscarContratos', ['q' => substr($venda->nome_contrato, 0, 10)]))
            ->assertOk()->assertJsonPath('contratos.0.id', $venda->id);
        $this->assertSame('R$ 500,00', $resp->json('contratos.0.valor_contrato'));
        $this->assertSame($this->backoffice->name, $resp->json('contratos.0.vendedor_nome'));
        $this->assertSame('IMPLANTADO', $resp->json('contratos.0.status'));

        // Por CPF do titular/beneficiário.
        DB::table('vendas_titulares')->insert([
            'venda_id' => $venda->id, 'nome' => 'MARIA BENEFICIARIA', 'cpf' => '98765432100',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($this->backoffice)
            ->getJson(route('backoffice.solicitacoes.buscarContratos', ['q' => '987.654.321-00']))
            ->assertOk()->assertJsonPath('contratos.0.id', $venda->id);

        // Por CNPJ com máscara (a busca ignora a formatação).
        $doc = $venda->cpf_cnpj;
        $formatado = substr($doc, 0, 2).'.'.substr($doc, 2, 3).'.'.substr($doc, 5, 3).'/'.substr($doc, 8, 4).'-'.substr($doc, 12, 2);
        $this->actingAs($this->backoffice)
            ->getJson(route('backoffice.solicitacoes.buscarContratos', ['q' => $formatado]))
            ->assertOk()->assertJsonPath('contratos.0.id', $venda->id);

        // Multi-tenant: outra empresa não enxerga.
        $outra = Empresa::factory()->create();
        $userOutra = $this->criarUsuario(UserRole::ADMINISTRATIVO, $outra);
        $this->actingAs($userOutra)
            ->getJson(route('backoffice.solicitacoes.buscarContratos', ['q' => substr($venda->nome_contrato, 0, 10)]))
            ->assertOk()->assertJsonCount(0, 'contratos');
    }

    // ---------------------------------------------------------------
    // Store
    // ---------------------------------------------------------------

    public function test_store_cria_na_etapa_inicial_com_historico(): void
    {
        $venda = $this->criarContrato();
        $responsavel = $this->criarUsuario(UserRole::BACKOFFICE);

        $resp = $this->actingAs($this->backoffice)->postJson(route('backoffice.solicitacoes.store'), [
            'venda_id' => $venda->id,
            'tipo' => TipoSolicitacaoPosVenda::CANCELAMENTO->value,
            'data_limite' => now()->addDays(10)->format('Y-m-d'),
            'responsavel_id' => $responsavel->id,
            'descricao' => 'Cancelar plano anterior na Unimed.',
        ]);

        $resp->assertOk()->assertJson(['success' => true]);

        $solicitacao = PosVendaSolicitacao::findOrFail($resp->json('id'));
        $this->assertSame(PosVendaSolicitacao::STATUS_ABERTA, $solicitacao->status);
        $this->assertSame('Cancelamento', $solicitacao->titulo, 'Sem título informado, usa o label do tipo.');
        $this->assertSame($responsavel->id, $solicitacao->responsavel_id);
        $this->assertSame('Aberta', $solicitacao->etapa->nome);
        $this->assertSame(NaturezaEtapaSolicitacao::EM_ANDAMENTO->value, $solicitacao->etapa->natureza);

        $this->assertDatabaseHas('pos_venda_solicitacao_historico', [
            'solicitacao_id' => $solicitacao->id,
            'campo_alterado' => 'abertura',
        ]);
    }

    public function test_store_validacoes(): void
    {
        $venda = $this->criarContrato();

        // Tipo inválido.
        $this->actingAs($this->backoffice)->postJson(route('backoffice.solicitacoes.store'), [
            'venda_id' => $venda->id, 'tipo' => 'INEXISTENTE',
        ])->assertStatus(422);

        // Prazo em formato inválido.
        $this->actingAs($this->backoffice)->postJson(route('backoffice.solicitacoes.store'), [
            'venda_id' => $venda->id, 'tipo' => TipoSolicitacaoPosVenda::OUTROS->value, 'data_limite' => '30/07/2026',
        ])->assertStatus(422);

        // Venda de outra empresa.
        $outraEmpresa = Empresa::factory()->create();
        $vendaOutra = $this->criarContrato($outraEmpresa);
        $this->actingAs($this->backoffice)->postJson(route('backoffice.solicitacoes.store'), [
            'venda_id' => $vendaOutra->id, 'tipo' => TipoSolicitacaoPosVenda::OUTROS->value,
        ])->assertStatus(422);
    }

    // ---------------------------------------------------------------
    // Fila, KPIs e filtros
    // ---------------------------------------------------------------

    public function test_fila_ordena_por_urgencia_e_kpis_batem(): void
    {
        $venda = $this->criarContrato();

        $semPrazo = $this->criarSolicitacao($venda, ['titulo' => 'SEM PRAZO']);
        $vencida = $this->criarSolicitacao($venda, ['titulo' => 'VENCIDA', 'data_limite' => now()->subDays(3)->toDateString()]);
        $hoje = $this->criarSolicitacao($venda, ['titulo' => 'HOJE', 'data_limite' => now()->toDateString()]);
        $futura = $this->criarSolicitacao($venda, ['titulo' => 'FUTURA', 'data_limite' => now()->addDays(10)->toDateString()]);

        $etapaConcluida = $this->etapaDoTipo(TipoSolicitacaoPosVenda::ENVIO_BOLETO->value, NaturezaEtapaSolicitacao::CONCLUIDA->value);
        $concluida = $this->criarSolicitacao($venda, [
            'titulo' => 'CONCLUIDA', 'etapa_id' => $etapaConcluida->id,
            'status' => PosVendaSolicitacao::STATUS_CONCLUIDA, 'concluida_em' => now(),
        ]);

        $resp = $this->actingAs($this->backoffice)->getJson(route('backoffice.solicitacoes.dados'))->assertOk();

        $titulos = collect($resp->json('registros'))->pluck('titulo')->all();
        $this->assertSame(['SEM PRAZO', 'VENCIDA', 'HOJE', 'FUTURA', 'CONCLUIDA'], $titulos);

        $resp->assertJson(['kpis' => [
            'atrasadas' => 1,
            'vencem_hoje' => 1,
            'abertas' => 4,
            'concluidas_mes' => 1,
        ]]);

        $porTitulo = collect($resp->json('registros'))->keyBy('titulo');
        $this->assertSame($this->backoffice->name, $porTitulo['SEM PRAZO']['criado_por_nome'], 'A fila expõe quem abriu a solicitação.');
        $this->assertLessThan(0, $porTitulo['VENCIDA']['dias_restantes']);
        $this->assertSame(0, $porTitulo['HOJE']['dias_restantes']);
        $this->assertSame(10, $porTitulo['FUTURA']['dias_restantes']);
        $this->assertNull($porTitulo['SEM PRAZO']['dias_restantes']);
        $this->assertNull($porTitulo['CONCLUIDA']['dias_restantes']);
    }

    public function test_dados_filtra_por_tipo_responsavel_e_busca(): void
    {
        $venda = $this->criarContrato();
        $responsavel = $this->criarUsuario(UserRole::BACKOFFICE);

        $this->criarSolicitacao($venda, ['tipo' => TipoSolicitacaoPosVenda::REEMBOLSO->value, 'titulo' => 'REEMBOLSO MARIA', 'responsavel_id' => $responsavel->id]);
        $this->criarSolicitacao($venda, ['titulo' => 'BOLETO ZE']);

        $this->actingAs($this->backoffice)
            ->getJson(route('backoffice.solicitacoes.dados', ['tipo' => TipoSolicitacaoPosVenda::REEMBOLSO->value]))
            ->assertOk()->assertJsonCount(1, 'registros')->assertJsonPath('registros.0.titulo', 'REEMBOLSO MARIA');

        $this->actingAs($this->backoffice)
            ->getJson(route('backoffice.solicitacoes.dados', ['responsavel_id' => $responsavel->id]))
            ->assertOk()->assertJsonCount(1, 'registros');

        $this->actingAs($this->backoffice)
            ->getJson(route('backoffice.solicitacoes.dados', ['busca' => 'BOLETO ZE']))
            ->assertOk()->assertJsonCount(1, 'registros')->assertJsonPath('registros.0.titulo', 'BOLETO ZE');
    }

    // ---------------------------------------------------------------
    // Mover etapa (encerramento e reabertura)
    // ---------------------------------------------------------------

    public function test_mover_para_etapa_concluida_encerra_e_reabrir_limpa(): void
    {
        $venda = $this->criarContrato();
        $solicitacao = $this->criarSolicitacao($venda);
        $tipo = $solicitacao->tipo;

        $concluida = $this->etapaDoTipo($tipo, NaturezaEtapaSolicitacao::CONCLUIDA->value);
        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.solicitacoes.mover', $solicitacao->id), ['etapa_id' => $concluida->id])
            ->assertOk();

        $fresh = $solicitacao->fresh();
        $this->assertSame(PosVendaSolicitacao::STATUS_CONCLUIDA, $fresh->status);
        $this->assertNotNull($fresh->concluida_em);
        $this->assertDatabaseHas('pos_venda_solicitacao_historico', [
            'solicitacao_id' => $solicitacao->id, 'campo_alterado' => 'etapa', 'valor_novo' => $concluida->nome,
        ]);

        // Cancelada também encerra.
        $cancelada = $this->etapaDoTipo($tipo, NaturezaEtapaSolicitacao::CANCELADA->value);
        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.solicitacoes.mover', $solicitacao->id), ['etapa_id' => $cancelada->id])
            ->assertOk();
        $this->assertSame(PosVendaSolicitacao::STATUS_CANCELADA, $solicitacao->fresh()->status);

        // Voltar para EM_ANDAMENTO reabre.
        $aberta = $this->etapaDoTipo($tipo, NaturezaEtapaSolicitacao::EM_ANDAMENTO->value);
        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.solicitacoes.mover', $solicitacao->id), ['etapa_id' => $aberta->id])
            ->assertOk();

        $fresh = $solicitacao->fresh();
        $this->assertSame(PosVendaSolicitacao::STATUS_ABERTA, $fresh->status);
        $this->assertNull($fresh->concluida_em);
    }

    public function test_mover_para_etapa_de_outro_tipo_e_422(): void
    {
        $venda = $this->criarContrato();
        $solicitacao = $this->criarSolicitacao($venda); // ENVIO_BOLETO

        $etapaOutroTipo = $this->etapaDoTipo(TipoSolicitacaoPosVenda::CANCELAMENTO->value, NaturezaEtapaSolicitacao::EM_ANDAMENTO->value);

        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.solicitacoes.mover', $solicitacao->id), ['etapa_id' => $etapaOutroTipo->id])
            ->assertStatus(422);

        $this->assertSame($solicitacao->etapa_id, $solicitacao->fresh()->etapa_id);
    }

    // ---------------------------------------------------------------
    // Update com histórico
    // ---------------------------------------------------------------

    public function test_update_de_prazo_e_responsavel_gera_historico(): void
    {
        $venda = $this->criarContrato();
        $solicitacao = $this->criarSolicitacao($venda);
        $responsavel = $this->criarUsuario(UserRole::BACKOFFICE);

        $this->actingAs($this->backoffice)
            ->patchJson(route('backoffice.solicitacoes.update', $solicitacao->id), [
                'data_limite' => now()->addDays(5)->format('Y-m-d'),
                'responsavel_id' => $responsavel->id,
            ])
            ->assertOk();

        $fresh = $solicitacao->fresh();
        $this->assertSame(now()->addDays(5)->toDateString(), $fresh->data_limite->toDateString());
        $this->assertSame($responsavel->id, $fresh->responsavel_id);

        $this->assertDatabaseHas('pos_venda_solicitacao_historico', [
            'solicitacao_id' => $solicitacao->id, 'campo_alterado' => 'prazo',
        ]);
        $this->assertDatabaseHas('pos_venda_solicitacao_historico', [
            'solicitacao_id' => $solicitacao->id, 'campo_alterado' => 'responsavel', 'valor_novo' => $responsavel->name,
        ]);
    }

    public function test_show_retorna_registro_e_timeline(): void
    {
        $venda = $this->criarContrato();
        $solicitacao = $this->criarSolicitacao($venda);

        $resp = $this->actingAs($this->backoffice)
            ->getJson(route('backoffice.solicitacoes.show', $solicitacao->id))
            ->assertOk();

        $this->assertSame($solicitacao->id, $resp->json('registro.id'));
        $this->assertIsArray($resp->json('historico'));
    }

    // ---------------------------------------------------------------
    // Multi-tenant
    // ---------------------------------------------------------------

    public function test_multi_tenant_nao_vaza_entre_empresas(): void
    {
        $venda = $this->criarContrato();
        $solicitacao = $this->criarSolicitacao($venda);

        $outraEmpresa = Empresa::factory()->create();
        $userOutra = $this->criarUsuario(UserRole::ADMINISTRATIVO, $outraEmpresa);

        $this->actingAs($userOutra)->getJson(route('backoffice.solicitacoes.show', $solicitacao->id))->assertStatus(404);
        $this->actingAs($userOutra)->patchJson(route('backoffice.solicitacoes.update', $solicitacao->id), ['titulo' => 'X'])->assertStatus(404);
        $this->actingAs($userOutra)
            ->postJson(route('backoffice.solicitacoes.mover', $solicitacao->id), ['etapa_id' => $solicitacao->etapa_id])
            ->assertStatus(404);

        $this->actingAs($userOutra)->getJson(route('backoffice.solicitacoes.dados'))
            ->assertOk()->assertJsonCount(0, 'registros');
    }

    // ---------------------------------------------------------------
    // CRUD de etapas do fluxo
    // ---------------------------------------------------------------

    public function test_crud_de_etapas(): void
    {
        PosVendaFluxoEtapa::seedDefaults($this->empresa->id);
        $tipo = TipoSolicitacaoPosVenda::ENVIO_BOLETO->value;

        // Criar: entra no fim do fluxo.
        $resp = $this->actingAs($this->backoffice)->postJson(route('backoffice.solicitacoes.etapas.store'), [
            'tipo' => $tipo, 'nome' => 'Boleto enviado ao cliente', 'cor' => '#22D3EE',
        ])->assertOk();

        $nova = PosVendaFluxoEtapa::findOrFail($resp->json('id'));
        $maiorOrdem = PosVendaFluxoEtapa::where('empresa_id', $this->empresa->id)->where('tipo', $tipo)->max('ordem');
        $this->assertSame($maiorOrdem, $nova->ordem);

        // Renomear.
        $this->actingAs($this->backoffice)
            ->putJson(route('backoffice.solicitacoes.etapas.update', $nova->id), ['nome' => 'Enviado'])
            ->assertOk();
        $this->assertSame('Enviado', $nova->fresh()->nome);

        // Reordenar (inverte a ordem atual).
        $ids = PosVendaFluxoEtapa::where('empresa_id', $this->empresa->id)->where('tipo', $tipo)
            ->orderBy('ordem')->pluck('id')->all();
        $this->actingAs($this->backoffice)->postJson(route('backoffice.solicitacoes.etapas.reordenar'), [
            'tipo' => $tipo, 'ordem' => array_reverse($ids),
        ])->assertOk();
        $this->assertSame(0, $nova->fresh()->ordem, 'A última virou primeira após inverter.');

        // Reordenar com lista incompleta é rejeitado.
        $this->actingAs($this->backoffice)->postJson(route('backoffice.solicitacoes.etapas.reordenar'), [
            'tipo' => $tipo, 'ordem' => [$nova->id],
        ])->assertStatus(422);

        // Excluir etapa vazia OK.
        $this->actingAs($this->backoffice)
            ->deleteJson(route('backoffice.solicitacoes.etapas.destroy', $nova->id))
            ->assertOk();
        $this->assertDatabaseMissing('pos_venda_fluxo_etapas', ['id' => $nova->id]);
    }

    public function test_excluir_etapa_com_solicitacoes_ou_ultima_concluida_e_422(): void
    {
        $venda = $this->criarContrato();
        $solicitacao = $this->criarSolicitacao($venda);

        // Etapa ocupada.
        $this->actingAs($this->backoffice)
            ->deleteJson(route('backoffice.solicitacoes.etapas.destroy', $solicitacao->etapa_id))
            ->assertStatus(422);

        // Última etapa CONCLUIDA do tipo.
        $concluida = $this->etapaDoTipo($solicitacao->tipo, NaturezaEtapaSolicitacao::CONCLUIDA->value);
        $this->actingAs($this->backoffice)
            ->deleteJson(route('backoffice.solicitacoes.etapas.destroy', $concluida->id))
            ->assertStatus(422);
    }

    public function test_etapas_multi_tenant(): void
    {
        PosVendaFluxoEtapa::seedDefaults($this->empresa->id);
        $etapa = PosVendaFluxoEtapa::where('empresa_id', $this->empresa->id)->firstOrFail();

        $outraEmpresa = Empresa::factory()->create();
        $userOutra = $this->criarUsuario(UserRole::ADMINISTRATIVO, $outraEmpresa);

        $this->actingAs($userOutra)
            ->putJson(route('backoffice.solicitacoes.etapas.update', $etapa->id), ['nome' => 'Invadida'])
            ->assertStatus(404);
        $this->actingAs($userOutra)
            ->deleteJson(route('backoffice.solicitacoes.etapas.destroy', $etapa->id))
            ->assertStatus(404);
    }
}
