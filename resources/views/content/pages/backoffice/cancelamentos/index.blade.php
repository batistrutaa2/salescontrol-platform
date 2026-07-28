@extends('layouts/layoutMaster')

@section('title', 'Painel de Cancelamentos')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/scss/pages/dashboard-analytics.scss',
        'resources/assets/vendor/scss/pages/pos-venda.scss',
        'resources/assets/vendor/scss/pages/painel-cancelamentos.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/sortablejs/sortable.js',
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/painel-cancelamentos.js'])
@endsection

@section('content')
<script>
    window.pcxConfig = {
        colunas: @json($colunas),
        desistido: @json($etapaDesistido),
        vias: @json($vias),
        responsaveis: @json($responsaveis),
    };
</script>

<div class="pcx-wrapper">

    {{-- Header --}}
    <div class="pcx-header">
        <div class="pcx-header-content">
            <div class="pcx-header-text">
                <span class="pcx-greeting-label">Pós-venda · Concierge</span>
                <h1 class="pcx-main-title">Painel de Cancelamentos</h1>
                <p class="pcx-subtitle">Acompanhe o cancelamento do plano anterior de cada cliente na operadora antiga</p>
            </div>
            <div class="pcx-header-actions">
                <div class="pcx-view-toggle" role="tablist" aria-label="Modo de visualização">
                    <button type="button" class="pcx-view-btn is-active" data-view="kanban" role="tab">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="5" height="18" rx="1"/><rect x="10" y="3" width="5" height="12" rx="1"/><rect x="17" y="3" width="5" height="15" rx="1"/></svg>
                        Kanban
                    </button>
                    <button type="button" class="pcx-view-btn" data-view="tabela" role="tab">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="7" x2="21" y2="7"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="17" x2="21" y2="17"/></svg>
                        Tabela
                    </button>
                </div>
                <button class="pcx-btn-novo" id="btnNovoCancelamento">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Novo Cancelamento
                </button>
            </div>
        </div>
    </div>

    {{-- Esteira: cada segmento é uma etapa — contagem grande, clicável --}}
    <div class="pcx-esteira" id="pcxEsteira" role="group" aria-label="Contagem por etapa">
        @foreach ($colunas as $coluna)
            <button type="button" class="pcx-esteira-seg" data-etapa="{{ $coluna['id'] }}"
                    style="--seg-start: {{ $coluna['gradiente']['start'] }}; --seg-end: {{ $coluna['gradiente']['end'] }};">
                <span class="pcx-esteira-count" data-kpi="{{ $coluna['id'] }}">0</span>
                <span class="pcx-esteira-label">{{ $coluna['label'] }}</span>
            </button>
            @unless ($loop->last)
                <span class="pcx-esteira-arrow" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </span>
            @endunless
        @endforeach
        <span class="pcx-esteira-divider" aria-hidden="true"></span>
        <button type="button" class="pcx-esteira-seg pcx-esteira-seg-off" data-etapa="{{ $etapaDesistido['id'] }}"
                style="--seg-start: {{ $etapaDesistido['gradiente']['start'] }}; --seg-end: {{ $etapaDesistido['gradiente']['end'] }};">
            <span class="pcx-esteira-count" data-kpi="{{ $etapaDesistido['id'] }}">0</span>
            <span class="pcx-esteira-label">{{ $etapaDesistido['label'] }}s</span>
        </button>
    </div>

    {{-- Kanban --}}
    <div class="pcx-kanban-board" id="pcxKanban" data-view-pane="kanban">
        @foreach ($colunas as $coluna)
            <div class="pcx-kanban-column" data-status="{{ $coluna['id'] }}"
                 style="--col-start: {{ $coluna['gradiente']['start'] }}; --col-end: {{ $coluna['gradiente']['end'] }};">
                <div class="pcx-kanban-column-header">
                    <span class="pcx-kanban-column-dot"></span>
                    <span class="pcx-kanban-column-title">{{ $coluna['label'] }}</span>
                    <span class="pcx-kanban-column-count" data-count="{{ $coluna['id'] }}">0</span>
                </div>
                <div class="pcx-kanban-column-body" data-column="{{ $coluna['id'] }}">
                    {{-- cards renderizados via JS --}}
                </div>
            </div>
        @endforeach
    </div>

    {{-- Tabela --}}
    <div class="pcx-table-card" data-view-pane="tabela" hidden>
        <div class="pcx-table-toolbar">
            <div class="pcx-search">
                <span class="pcx-search-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input type="text" id="pcxFiltroTexto" class="pcx-search-input"
                       placeholder="Filtrar por contrato, CPF/CNPJ, operadora ou responsável" autocomplete="off">
            </div>
            <label class="pcx-check">
                <input type="checkbox" id="pcxMostrarDesistidos">
                <span>Mostrar desistidos</span>
            </label>
        </div>
        <div class="pcx-table-scroll">
            <table class="pcx-table">
                <thead>
                    <tr>
                        <th>Contrato</th>
                        <th>CPF/CNPJ</th>
                        <th>Escopo</th>
                        <th>Operadora anterior</th>
                        <th>Via</th>
                        <th>Etapa</th>
                        <th>Protocolo</th>
                        <th>Responsável</th>
                        <th>Criado em</th>
                    </tr>
                </thead>
                <tbody id="pcxTabelaBody"></tbody>
            </table>
            <p class="pcx-empty" id="pcxTabelaVazia" hidden>Nenhum cancelamento encontrado com esses filtros.</p>
        </div>
    </div>

</div>

{{-- ============================================================ --}}
{{-- Modal: Novo Cancelamento                                     --}}
{{-- ============================================================ --}}
<div class="modal fade" id="modalNovoCancelamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content pv-modal-modern">

            <div class="pv-modal-header pv-modal-header-info">
                <div class="pv-modal-header-content">
                    <div class="pv-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    </div>
                    <div class="pv-modal-title-group">
                        <h5 class="pv-modal-title">Novo Cancelamento</h5>
                        <span class="pv-modal-subtitle">Solicitação de cancelamento do plano anterior</span>
                    </div>
                </div>
                <button type="button" class="pv-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="pv-modal-body">
                <form id="formNovoCancelamento">
                    {{-- 1. Contrato --}}
                    <div class="pcx-form-section">
                        <div class="pcx-form-section-title">1 · Contrato da venda nova</div>
                        <div class="pcx-busca-contrato">
                            <span class="pcx-search-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </span>
                            <input type="text" id="pcxBuscaContrato" class="pcx-search-input"
                                   placeholder="Buscar contrato — nome, proposta ou CPF/CNPJ" autocomplete="off">
                            <div id="pcxResultadosContrato" class="pcx-resultados" hidden></div>
                        </div>
                        <input type="hidden" name="venda_id" id="pcxVendaId">
                        <div class="pcx-contrato-resumo" id="pcxContratoResumo" hidden>
                            <div class="pcx-contrato-resumo-main">
                                <strong id="pcxResumoNome">—</strong>
                                <span id="pcxResumoDocs">—</span>
                            </div>
                            <div class="pcx-contrato-resumo-side">
                                <span class="pcx-badge-implantado" id="pcxResumoImplantado" hidden>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                    Implantado
                                </span>
                                <span class="pcx-badge-aguardando" id="pcxResumoAguardando" hidden>Aguardando implantação</span>
                                <button type="button" class="pcx-resumo-trocar" id="pcxTrocarContrato">trocar</button>
                            </div>
                        </div>
                    </div>

                    {{-- 2. O que cancelar --}}
                    <div class="pcx-form-section">
                        <div class="pcx-form-section-title">2 · O que cancelar na operadora anterior</div>
                        <div class="pcx-escopo-toggle" role="radiogroup">
                            <label class="pcx-escopo-opt">
                                <input type="radio" name="escopo" value="APOLICE" checked>
                                <span class="pcx-escopo-card">
                                    <strong>Apólice inteira</strong>
                                    <small>Cancela o contrato antigo por completo</small>
                                </span>
                            </label>
                            <label class="pcx-escopo-opt">
                                <input type="radio" name="escopo" value="BENEFICIARIO">
                                <span class="pcx-escopo-card">
                                    <strong>Beneficiário específico</strong>
                                    <small>Remove apenas uma pessoa da apólice</small>
                                </span>
                            </label>
                        </div>
                        <div class="pcx-field" id="pcxCampoBeneficiario" hidden>
                            <label class="pv-form-label" for="pcxBeneficiarioNome">Nome do beneficiário *</label>
                            <input type="text" id="pcxBeneficiarioNome" name="beneficiario_nome" class="pv-form-input" maxlength="255"
                                   placeholder="Nome completo de quem sai da apólice">
                        </div>
                    </div>

                    {{-- 3. Como e onde --}}
                    <div class="pcx-form-section">
                        <div class="pcx-form-section-title">3 · Via e operadora</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="pv-form-label" for="pcxVia">Via de cancelamento *</label>
                                <select id="pcxVia" name="via" class="pv-form-input" required>
                                    @foreach ($vias as $valor => $label)
                                        <option value="{{ $valor }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <small class="pcx-hint">Bradesco, SulAmérica e Qualicorp: direto na operadora. Demais: via liminar.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="pv-form-label" for="pcxOperadoraAnterior">Operadora anterior *</label>
                                <input type="text" id="pcxOperadoraAnterior" name="operadora_anterior" class="pv-form-input" maxlength="255"
                                       placeholder="Onde o plano antigo será cancelado" required>
                            </div>
                            <div class="col-md-6">
                                <label class="pv-form-label" for="pcxResponsavel">Responsável</label>
                                <select id="pcxResponsavel" name="responsavel_id" class="pv-form-input">
                                    <option value="">— Sem responsável —</option>
                                    @foreach ($responsaveis as $resp)
                                        <option value="{{ $resp->id }}">{{ $resp->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="pv-form-label" for="pcxObservacoes">Observações</label>
                                <textarea id="pcxObservacoes" name="observacoes" class="pv-form-textarea" rows="2" maxlength="2000"
                                          placeholder="Contexto útil para quem vai tocar o processo"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="pcx-modal-footer-bar">
                <button type="button" class="pv-btn pv-btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="pv-btn pv-btn-success" id="btnConfirmarNovoCancelamento">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Criar Cancelamento
                </button>
            </div>

        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- Modal: Detalhe do Cancelamento                               --}}
{{-- ============================================================ --}}
<div class="modal fade" id="modalDetalheCancelamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content pv-modal-modern">

            {{-- Header status-aware --}}
            <div class="pcx-detail-header" id="pcxDetalheHeader">
                <div class="pcx-detail-head-row">
                    <div class="pcx-detail-titlewrap">
                        <span class="pcx-detail-eyebrow" id="pcxDetalheEtapaPill">—</span>
                        <h5 class="pcx-detail-title" id="pcxDetalheTitulo">Cancelamento</h5>
                        <span class="pcx-detail-sub" id="pcxDetalheSubtitulo">Carregando…</span>
                    </div>
                    <button type="button" class="pcx-detail-close" data-bs-dismiss="modal" aria-label="Fechar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <ol class="pcx-stepper" id="pcxDetalheStepper" aria-label="Andamento do cancelamento"></ol>
                <div class="pcx-move-bar">
                    <span class="pcx-move-label">Mover para</span>
                    <select id="pcxDetalheEtapaSelect" class="pcx-move-select">
                        @foreach ($colunas as $col)
                            <option value="{{ $col['id'] }}">{{ $col['label'] }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="pcx-move-btn" id="btnPcxMoverEtapa">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        Mover
                    </button>
                </div>
            </div>

            {{-- Abas --}}
            <div class="pcx-tabs" role="tablist">
                <button type="button" class="pcx-tab is-active" data-tab="pcxTabGeral" role="tab">Visão geral</button>
                <button type="button" class="pcx-tab" data-tab="pcxTabHist" role="tab">Histórico</button>
            </div>

            <div class="pv-modal-body">
                <input type="hidden" id="pcxDetalheId">

                <div class="pcx-tabpane is-active" id="pcxTabGeral" role="tabpanel">
                    <div id="pcxDetalheInfo"></div>
                    <div class="row g-3 pcx-detail-edit">
                        <div class="col-md-4">
                            <label class="pv-form-label" for="pcxDetalheProtocolo">Protocolo</label>
                            <input type="text" id="pcxDetalheProtocolo" class="pv-form-input" maxlength="255" placeholder="Protocolo da operadora">
                        </div>
                        <div class="col-md-4">
                            <label class="pv-form-label" for="pcxDetalheResponsavel">Responsável</label>
                            <select id="pcxDetalheResponsavel" class="pv-form-input">
                                <option value="">— Sem responsável —</option>
                                @foreach ($responsaveis as $resp)
                                    <option value="{{ $resp->id }}">{{ $resp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="pv-form-label">Solicitado em</label>
                            <input type="text" id="pcxDetalheSolicitadoEm" class="pv-form-input" disabled>
                        </div>
                        <div class="col-12">
                            <label class="pv-form-label" for="pcxDetalheObservacoes">Observações</label>
                            <textarea id="pcxDetalheObservacoes" class="pv-form-textarea" rows="3" maxlength="2000"></textarea>
                        </div>
                    </div>
                </div>

                <div class="pcx-tabpane" id="pcxTabHist" role="tabpanel">
                    <div class="pcx-timeline-container">
                        <div id="pcxTimeline" class="pcx-timeline"></div>
                    </div>
                </div>
            </div>

            <div class="pcx-modal-footer-bar">
                <button type="button" class="pv-btn pcx-btn-desistir" id="btnPcxDesistir">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    Desistir do cancelamento
                </button>
                <button type="button" class="pv-btn pv-btn-ghost" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="pv-btn pv-btn-primary" id="btnPcxSalvarDetalhe">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Salvar Alterações
                </button>
            </div>

        </div>
    </div>
</div>

@endsection
