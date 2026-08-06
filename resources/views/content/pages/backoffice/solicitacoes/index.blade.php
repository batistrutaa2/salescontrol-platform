@extends('layouts/layoutMaster')

@section('title', 'Central de Solicitações')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/scss/pages/dashboard-analytics.scss',
        'resources/assets/vendor/scss/pages/pos-venda.scss',
        'resources/assets/vendor/scss/pages/central-solicitacoes.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/sortablejs/sortable.js',
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/central-solicitacoes.js'])
@endsection

@section('content')
<script>
    window.spvConfig = {
        tipos: @json($tipos),
        naturezas: @json($naturezas),
        etapasPorTipo: @json($etapasPorTipo),
        responsaveis: @json($responsaveis),
    };
</script>

<div class="spv-wrapper">

    {{-- Header --}}
    <div class="spv-header">
        <div class="spv-header-content">
            <div class="spv-header-text">
                <span class="spv-greeting-label">Pós-venda · Gestão</span>
                <h1 class="spv-main-title">Central de Solicitações</h1>
                <p class="spv-subtitle">Todas as demandas do pós-venda mapeadas, com prazo e fluxo por tipo de solicitação</p>
            </div>
            <div class="spv-header-actions">
                <div class="spv-view-toggle" role="tablist" aria-label="Modo de visualização">
                    <button type="button" class="spv-view-btn is-active" data-view="fila" role="tab">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="7" x2="21" y2="7"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="17" x2="21" y2="17"/></svg>
                        Fila
                    </button>
                    <button type="button" class="spv-view-btn" data-view="kanban" role="tab">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="5" height="18" rx="1"/><rect x="10" y="3" width="5" height="12" rx="1"/><rect x="17" y="3" width="5" height="15" rx="1"/></svg>
                        Kanban
                    </button>
                </div>
                <button class="spv-btn-ghost" id="btnGerenciarEtapas" title="Configurar as etapas do fluxo de cada tipo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Gerenciar Etapas
                </button>
                <button class="spv-btn-novo" id="btnNovaSolicitacao">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Nova Solicitação
                </button>
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="spv-kpi-row" role="group" aria-label="Indicadores">
        <div class="spv-kpi spv-kpi-danger">
            <span class="spv-kpi-count" data-kpi="atrasadas">0</span>
            <span class="spv-kpi-label">Atrasadas</span>
        </div>
        <div class="spv-kpi spv-kpi-warning">
            <span class="spv-kpi-count" data-kpi="vencem_hoje">0</span>
            <span class="spv-kpi-label">Vencem hoje</span>
        </div>
        <div class="spv-kpi spv-kpi-primary">
            <span class="spv-kpi-count" data-kpi="abertas">0</span>
            <span class="spv-kpi-label">Em aberto</span>
        </div>
        <div class="spv-kpi spv-kpi-success">
            <span class="spv-kpi-count" data-kpi="concluidas_mes">0</span>
            <span class="spv-kpi-label">Concluídas no mês</span>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="spv-filtros">
        <div class="spv-search">
            <span class="spv-search-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <input type="text" id="spvFiltroBusca" class="spv-search-input"
                   placeholder="Buscar por título, contrato ou CPF/CNPJ" autocomplete="off">
        </div>
        <select id="spvFiltroTipo" class="spv-filtro-select">
            <option value="">Todos os tipos</option>
            @foreach ($tipos as $valor => $label)
                <option value="{{ $valor }}">{{ $label }}</option>
            @endforeach
        </select>
        <select id="spvFiltroResponsavel" class="spv-filtro-select">
            <option value="">Todos os responsáveis</option>
            <option value="0">Sem responsável</option>
            @foreach ($responsaveis as $resp)
                <option value="{{ $resp->id }}">{{ $resp->name }}</option>
            @endforeach
        </select>
        <div class="spv-chips" role="radiogroup" aria-label="Filtro de status">
            <button type="button" class="spv-chip is-active" data-status="ABERTA">Abertas</button>
            <button type="button" class="spv-chip" data-status="ADIADOS">Adiados</button>
            <button type="button" class="spv-chip" data-status="ENCERRADAS">Encerradas</button>
            <button type="button" class="spv-chip" data-status="TODAS">Todas</button>
        </div>
    </div>

    {{-- Fila por prazo --}}
    <div class="spv-fila-card" data-view-pane="fila">
        <div id="spvFila" class="spv-fila"></div>
        <p class="spv-empty" id="spvFilaVazia" hidden>Nenhuma solicitação encontrada com esses filtros.</p>
    </div>

    {{-- Kanban por tipo --}}
    <div data-view-pane="kanban" hidden>
        <div class="spv-kanban-tipos" id="spvKanbanTipos" role="tablist" aria-label="Tipo de solicitação"></div>
        <div class="spv-kanban-board" id="spvKanban"></div>
    </div>

</div>

{{-- ============================================================ --}}
{{-- Modal: Nova Solicitação                                      --}}
{{-- ============================================================ --}}
<div class="modal fade" id="modalNovaSolicitacao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content pv-modal-modern">

            <div class="pv-modal-header pv-modal-header-info">
                <div class="pv-modal-header-content">
                    <div class="pv-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    </div>
                    <div class="pv-modal-title-group">
                        <h5 class="pv-modal-title">Nova Solicitação</h5>
                        <span class="pv-modal-subtitle">Registrar uma demanda do pós-venda</span>
                    </div>
                </div>
                <button type="button" class="pv-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="pv-modal-body">
                <form id="formNovaSolicitacao">
                    {{-- 1. Contrato --}}
                    <div class="spv-form-section">
                        <div class="spv-form-section-title">1 · Contrato do cliente</div>
                        <div class="spv-busca-contrato">
                            <span class="spv-search-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </span>
                            <input type="text" id="spvBuscaContrato" class="spv-search-input"
                                   placeholder="Buscar contrato — nome, proposta ou CPF/CNPJ" autocomplete="off">
                            <div id="spvResultadosContrato" class="spv-resultados" hidden></div>
                        </div>
                        <input type="hidden" name="venda_id" id="spvVendaId">
                        <div class="spv-contrato-resumo" id="spvContratoResumo" hidden>
                            <div class="spv-contrato-resumo-topo">
                                <div class="spv-contrato-resumo-main">
                                    <strong id="spvResumoNome">—</strong>
                                    <span id="spvResumoDocs">—</span>
                                </div>
                                <button type="button" class="spv-resumo-trocar" id="spvTrocarContrato">trocar</button>
                            </div>
                            {{-- Visão rápida do que foi vendido: plano, valor, vidas, vendedor... --}}
                            <div class="spv-resumo-grid" id="spvResumoGrid"></div>
                        </div>
                    </div>

                    {{-- 2. Solicitação --}}
                    <div class="spv-form-section">
                        <div class="spv-form-section-title">2 · Solicitação</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="pv-form-label" for="spvNovoTipo">Tipo *</label>
                                <select id="spvNovoTipo" name="tipo" class="pv-form-input" required>
                                    @foreach ($tipos as $valor => $label)
                                        <option value="{{ $valor }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="pv-form-label" for="spvNovoTitulo">Título</label>
                                <input type="text" id="spvNovoTitulo" name="titulo" class="pv-form-input" maxlength="255"
                                       placeholder="Opcional — sem título, usa o tipo">
                            </div>
                            <div class="col-md-6">
                                <label class="pv-form-label" for="spvNovoPrazo">Prazo (data-limite)</label>
                                <input type="date" id="spvNovoPrazo" name="data_limite" class="pv-form-input">
                                <small class="spv-hint">Até quando essa solicitação precisa ser resolvida</small>
                            </div>
                            <div class="col-md-6">
                                <label class="pv-form-label" for="spvNovoResponsavel">Responsável</label>
                                <select id="spvNovoResponsavel" name="responsavel_id" class="pv-form-input">
                                    <option value="">— Sem responsável —</option>
                                    @foreach ($responsaveis as $resp)
                                        <option value="{{ $resp->id }}">{{ $resp->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="pv-form-label" for="spvNovaDescricao">Descrição</label>
                                <textarea id="spvNovaDescricao" name="descricao" class="pv-form-textarea" rows="2" maxlength="2000"
                                          placeholder="Contexto útil para quem vai tocar a demanda"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="spv-modal-footer-bar">
                <button type="button" class="pv-btn pv-btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="pv-btn pv-btn-success" id="btnConfirmarNovaSolicitacao">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Criar Solicitação
                </button>
            </div>

        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- Modal: Detalhe da Solicitação                                --}}
{{-- ============================================================ --}}
<div class="modal fade" id="modalDetalheSolicitacao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content pv-modal-modern">

            <div class="spv-detail-header" id="spvDetalheHeader">
                <div class="spv-detail-head-row">
                    <div class="spv-detail-titlewrap">
                        <span class="spv-detail-eyebrow" id="spvDetalheEtapaPill">—</span>
                        <h5 class="spv-detail-title" id="spvDetalheTitulo">Solicitação</h5>
                        <span class="spv-detail-sub" id="spvDetalheSubtitulo">Carregando…</span>
                    </div>
                    <div class="spv-detail-head-actions">
                        <button type="button" class="spv-flag-btn" id="btnSpvPrioridadeDetalhe" title="Marcar como prioridade" aria-label="Marcar como prioridade" aria-pressed="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                            <span class="spv-flag-btn-label">Prioridade</span>
                        </button>
                        <button type="button" class="spv-detail-close" data-bs-dismiss="modal" aria-label="Fechar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>
                <ol class="spv-stepper" id="spvDetalheStepper" aria-label="Andamento da solicitação"></ol>
                <div class="spv-move-bar">
                    <span class="spv-move-label">Mover para</span>
                    <select id="spvDetalheEtapaSelect" class="spv-move-select"></select>
                    <button type="button" class="spv-move-btn" id="btnSpvMoverEtapa">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        Mover
                    </button>
                </div>
            </div>

            <div class="spv-tabs" role="tablist">
                <button type="button" class="spv-tab is-active" data-tab="spvTabGeral" role="tab">Visão geral</button>
                <button type="button" class="spv-tab" data-tab="spvTabHist" role="tab">Atualizações &amp; histórico</button>
            </div>

            <div class="pv-modal-body">
                <input type="hidden" id="spvDetalheId">

                <div class="spv-tabpane is-active" id="spvTabGeral" role="tabpanel">
                    <div id="spvDetalheInfo"></div>
                    <div class="row g-3 spv-detail-edit">
                        <div class="col-md-6">
                            <label class="pv-form-label" for="spvDetalheTituloInput">Título</label>
                            <input type="text" id="spvDetalheTituloInput" class="pv-form-input" maxlength="255">
                        </div>
                        <div class="col-md-3">
                            <label class="pv-form-label" for="spvDetalhePrazo">Prazo</label>
                            <input type="date" id="spvDetalhePrazo" class="pv-form-input">
                        </div>
                        <div class="col-md-3">
                            <label class="pv-form-label" for="spvDetalheResponsavel">Responsável</label>
                            <select id="spvDetalheResponsavel" class="pv-form-input">
                                <option value="">— Sem responsável —</option>
                                @foreach ($responsaveis as $resp)
                                    <option value="{{ $resp->id }}">{{ $resp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="pv-form-label" for="spvDetalheDescricao">Descrição</label>
                            <textarea id="spvDetalheDescricao" class="pv-form-textarea" rows="3" maxlength="2000"></textarea>
                        </div>
                    </div>
                </div>

                <div class="spv-tabpane" id="spvTabHist" role="tabpanel">
                    {{-- Registro de andamento: o texto entra na timeline e avisa o vendedor dono do contrato --}}
                    <div class="spv-atualizacao-composer">
                        <label class="pv-form-label" for="spvAtualizacaoTexto">Registrar atualização do processo</label>
                        <textarea id="spvAtualizacaoTexto" class="pv-form-textarea" rows="2" maxlength="500"
                                  placeholder='Ex.: "Acessei hoje e a portabilidade ainda não saiu"'></textarea>
                        <div class="spv-atualizacao-composer-bar">
                            <small class="spv-hint">O vendedor dono do contrato é avisado na hora.</small>
                            <button type="button" class="pv-btn pv-btn-primary" id="btnSpvRegistrarAtualizacao">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                Registrar
                            </button>
                        </div>
                    </div>
                    <div class="spv-timeline-container">
                        <div id="spvTimeline" class="spv-timeline"></div>
                    </div>
                </div>
            </div>

            <div class="spv-modal-footer-bar">
                <button type="button" class="pv-btn spv-btn-excluir" id="btnSpvExcluirDetalhe">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    Excluir
                </button>
                <button type="button" class="pv-btn spv-btn-adiar" id="btnSpvAbrirRetorno">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/><path d="M5 3v4h4"/></svg>
                    Adiar tratativa
                </button>
                <button type="button" class="pv-btn pv-btn-ghost" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="pv-btn pv-btn-primary" id="btnSpvSalvarDetalhe">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Salvar Alterações
                </button>
            </div>

        </div>
    </div>
</div>

{{-- Adiar: tira temporariamente o card da fila e o devolve na data escolhida. --}}
<div class="modal fade" id="modalProgramarRetorno" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content pv-modal-modern spv-retorno-modal">
            <div class="pv-modal-header pv-modal-header-info">
                <div class="pv-modal-header-content">
                    <div class="pv-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    </div>
                    <div class="pv-modal-title-group">
                        <h5 class="pv-modal-title">Quando retomar?</h5>
                        <span class="pv-modal-subtitle">O card ficará fora da fila até essa data</span>
                    </div>
                </div>
                <button type="button" class="pv-modal-close" data-bs-dismiss="modal" aria-label="Fechar">×</button>
            </div>
            <div class="pv-modal-body">
                <div class="spv-retorno-atual" id="spvRetornoAtual" hidden></div>
                <label class="pv-form-label" for="spvDataRetorno">Data de retorno</label>
                <input type="date" id="spvDataRetorno" class="pv-form-input">
                <div class="spv-retorno-atalhos" aria-label="Atalhos de data">
                    <button type="button" data-retorno-dias="7">7 dias</button>
                    <button type="button" data-retorno-dias="15">15 dias</button>
                    <button type="button" data-retorno-dias="30">30 dias</button>
                    <button type="button" data-retorno-dias="45">45 dias</button>
                </div>
                <p class="spv-retorno-preview" id="spvRetornoPreview">Escolha uma data futura.</p>
            </div>
            <div class="spv-retorno-actions">
                <button type="button" class="pv-btn pv-btn-ghost" id="btnSpvRetornarAgora" hidden>Voltar para fila agora</button>
                <button type="button" class="pv-btn pv-btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="pv-btn pv-btn-primary" id="btnSpvConfirmarRetorno">Programar retorno</button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- Modal: Gerenciar Etapas do Fluxo                             --}}
{{-- ============================================================ --}}
<div class="modal fade" id="modalGerenciarEtapas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content pv-modal-modern">

            <div class="pv-modal-header pv-modal-header-info">
                <div class="pv-modal-header-content">
                    <div class="pv-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="5" height="18" rx="1"/><rect x="10" y="3" width="5" height="12" rx="1"/><rect x="17" y="3" width="5" height="15" rx="1"/></svg>
                    </div>
                    <div class="pv-modal-title-group">
                        <h5 class="pv-modal-title">Gerenciar Etapas</h5>
                        <span class="pv-modal-subtitle">Monte o fluxo de cada tipo de solicitação do seu jeito</span>
                    </div>
                </div>
                <button type="button" class="pv-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="pv-modal-body">
                <div class="spv-etapas-toolbar">
                    <label class="pv-form-label" for="spvEtapasTipo">Fluxo do tipo</label>
                    <select id="spvEtapasTipo" class="pv-form-input">
                        @foreach ($tipos as $valor => $label)
                            <option value="{{ $valor }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <small class="spv-hint">Arraste para reordenar. A natureza define o que acontece com a solicitação ao entrar na etapa.</small>
                </div>

                <div id="spvEtapasLista" class="spv-etapas-lista"></div>

                <div class="spv-etapas-add">
                    <input type="text" id="spvNovaEtapaNome" class="pv-form-input" maxlength="60" placeholder="Nome da nova etapa">
                    <button type="button" class="pv-btn pv-btn-primary" id="btnSpvAddEtapa">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Adicionar
                    </button>
                </div>
            </div>

            <div class="spv-modal-footer-bar">
                <button type="button" class="pv-btn pv-btn-ghost" data-bs-dismiss="modal">Fechar</button>
            </div>

        </div>
    </div>
</div>

@endsection
