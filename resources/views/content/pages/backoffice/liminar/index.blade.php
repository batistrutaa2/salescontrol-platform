@extends('layouts/layoutMaster')

@section('title', 'Cancelamento via Liminar')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/scss/pages/dashboard-analytics.scss',
        'resources/assets/vendor/scss/pages/pos-venda.scss',
        'resources/assets/vendor/scss/pages/liminar.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/sortablejs/sortable.js',
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/liminar.js'])
@endsection

@section('content')
<script>
    window.limConfig = {
        colunas: @json($colunas),
        isAdvogada: @json($isAdvogada),
    };
</script>

<div class="liminar-wrapper" data-advogada="{{ $isAdvogada ? '1' : '0' }}">

    {{-- Header --}}
    <div class="lim-header">
        <div class="lim-header-content">
            <div class="lim-header-text">
                <span class="lim-greeting-label">{{ $isAdvogada ? 'Jurídico' : 'Backoffice' }}</span>
                <h1 class="lim-main-title">Cancelamento via Liminar</h1>
                <p class="lim-subtitle">Acompanhe os processos de cancelamento judicial de planos de saúde</p>
            </div>
            @unless ($isAdvogada)
                <div class="lim-header-actions">
                    <button class="lim-btn-novo" id="btnNovoProcesso">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Novo Processo
                    </button>
                </div>
            @endunless
        </div>
    </div>

    {{-- Termômetro da esteira: em aberto + distribuição por pipeline --}}
    <div class="lim-dash">
        <div class="lim-dash-hero">
            <span class="lim-dash-hero-label">Em aberto</span>
            <span class="lim-dash-hero-value" id="dashEmAberto">0</span>
            <span class="lim-dash-hero-sub">de <span id="dashTotal">0</span> processos</span>
        </div>
        <div class="lim-dash-pipeline">
            <div class="lim-dash-bar" id="dashBar">
                @foreach ($colunas as $coluna)
                    <span class="lim-dash-seg" data-seg="{{ $coluna['id'] }}"
                          style="background: linear-gradient(90deg, {{ $coluna['gradiente']['start'] }}, {{ $coluna['gradiente']['end'] }});"></span>
                @endforeach
            </div>
            <div class="lim-dash-legend">
                @foreach ($colunas as $coluna)
                    <span class="lim-dash-leg">
                        <span class="lim-dash-dot" style="background: linear-gradient(135deg, {{ $coluna['gradiente']['start'] }}, {{ $coluna['gradiente']['end'] }});"></span>
                        <span class="lim-dash-leg-label">{{ $coluna['curto'] }}</span>
                        <strong class="lim-dash-leg-count" data-legcount="{{ $coluna['id'] }}">0</strong>
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Kanban Board --}}
    <div class="lim-kanban-board" id="kanbanBoard">
        @foreach ($colunas as $coluna)
            <div class="lim-kanban-column" data-status="{{ $coluna['id'] }}"
                 style="--col-start: {{ $coluna['gradiente']['start'] }}; --col-end: {{ $coluna['gradiente']['end'] }};">
                <div class="lim-kanban-column-header">
                    <span class="lim-kanban-column-dot"></span>
                    <span class="lim-kanban-column-title">{{ $coluna['label'] }}</span>
                    <span class="lim-kanban-column-count" data-count="{{ $coluna['id'] }}">0</span>
                </div>
                <div class="lim-kanban-column-body" data-column="{{ $coluna['id'] }}">
                    {{-- cards renderizados via JS --}}
                </div>
            </div>
        @endforeach
    </div>

</div>

{{-- ============================================================ --}}
{{-- Modal: Detalhes do Processo                                 --}}
{{-- ============================================================ --}}
<div class="modal fade" id="modalDetalhes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content pv-modal-modern">

            {{-- Header status-aware: accent e stepper espelham as colunas do kanban --}}
            <div class="lim-detail-header" id="detalheHeader">
                <div class="lim-detail-head-row">
                    <div class="lim-detail-titlewrap">
                        <span class="lim-detail-eyebrow" id="detalheFasePill">—</span>
                        <h5 class="lim-detail-title" id="detalheModalTitulo">Processo de Liminar</h5>
                        <span class="lim-detail-sub" id="detalheModalSubtitulo">Carregando…</span>
                    </div>
                    <button type="button" class="lim-detail-close" data-bs-dismiss="modal" aria-label="Fechar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <ol class="lim-stepper" id="detalheStepper" aria-label="Andamento do processo"></ol>

                {{-- Mover processo pelo fluxo do kanban (backoffice e advogada) --}}
                <div class="lim-move-bar">
                    <span class="lim-move-label">Mover para</span>
                    <select id="detalheFaseSelect" class="lim-move-select">
                        @foreach ($colunas as $col)
                            <option value="{{ $col['id'] }}">{{ $col['label'] }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="lim-move-btn" id="btnMoverFase">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                        </svg>
                        Mover
                    </button>
                </div>
            </div>

            {{-- Abas --}}
            <div class="lim-tabs" role="tablist">
                <button type="button" class="lim-tab is-active" data-tab="tabGeral" role="tab">Visão geral</button>
                <button type="button" class="lim-tab" data-tab="tabDatas" role="tab">Datas</button>
                <button type="button" class="lim-tab" data-tab="tabDocs" role="tab">Documentos<span class="lim-tab-badge" id="tabDocsCount">0</span></button>
                <button type="button" class="lim-tab" data-tab="tabHist" role="tab">Histórico</button>
                @unless ($isAdvogada)
                    <button type="button" class="lim-tab" data-tab="tabFin" role="tab">Andamento</button>
                @endunless
            </div>

            {{-- Body --}}
            <div class="pv-modal-body lim-detail-body">
                <input type="hidden" id="detalheId">

                {{-- Visão geral (titular, contrato, empresa, procuração) --}}
                <div class="lim-tabpane is-active" id="tabGeral" role="tabpanel"></div>

                {{-- Datas (agrupadas por etapa) --}}
                <div class="lim-tabpane" id="tabDatas" role="tabpanel"></div>

                {{-- Documentos --}}
                <div class="lim-tabpane" id="tabDocs" role="tabpanel">
                    @unless ($isAdvogada)
                        <div class="lim-doc-upload">
                            <select id="uploadTipoDoc" class="lim-modal-select w-100 mb-2">
                                <option value="">— Selecione o tipo —</option>
                                <option value="CARTEIRINHA">Carteirinha</option>
                                <option value="CARTAO_CNPJ">Cartão do CNPJ</option>
                                <option value="COMPROVANTE_PAGAMENTO">Comprovante de Pagamento</option>
                                <option value="CONTRATO_SOCIAL">Contrato Social</option>
                                <option value="RETORNO_CANCELAMENTO">Retorno do Cancelamento</option>
                                <option value="RG_CLIENTE">RG/CPF do Responsável</option>
                                <option value="PRINT_PROTOCOLO">Print do Protocolo</option>
                                <option value="AUDIO_HAPVIDA">Áudio (Hapvida)</option>
                            </select>
                            <div class="lim-file-drop" id="fileDropZone">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/>
                                    <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/>
                                </svg>
                                <p>Arraste ou <label for="inputArquivo" class="lim-file-link">selecione</label></p>
                                <small>PDF, JPG, PNG — máx. 10MB</small>
                                <input type="file" id="inputArquivo" accept=".pdf,.jpg,.jpeg,.png,audio/*" hidden>
                            </div>
                        </div>
                    @endunless
                    <div id="listaDocumentos" class="lim-doc-list"></div>
                </div>

                {{-- Histórico --}}
                <div class="lim-tabpane" id="tabHist" role="tabpanel">
                    <div class="lim-timeline-container">
                        <div id="timelineHistorico" class="lim-timeline"></div>
                    </div>
                </div>

                @unless ($isAdvogada)
                {{-- Andamento / financeiro --}}
                <div class="lim-tabpane" id="tabFin" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="pv-form-label">Honorários</label>
                            <select id="detalheHonorarios" class="lim-modal-select w-100">
                                <option value="PENDENTE">Pendente</option>
                                <option value="PAGO">Pago</option>
                                <option value="NAO_SE_APLICA">N/A</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="pv-form-label">Pagamento cliente</label>
                            <select id="detalheRecebimento" class="lim-modal-select w-100">
                                <option value="PENDENTE">Pendente</option>
                                <option value="BOLETO_GERADO">Boleto Gerado</option>
                                <option value="PAGO">Pago</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="pv-form-label">Valor (R$)</label>
                            <input type="number" id="detalheValorRecebimento" class="pv-form-input" step="0.01" min="0" placeholder="0,00">
                        </div>
                        <div class="col-12">
                            <label class="pv-form-label">Observações</label>
                            <textarea id="detalheObservacoes" class="pv-form-textarea" rows="3" maxlength="2000"></textarea>
                        </div>
                    </div>
                </div>
                @endunless
            </div>

            {{-- Footer --}}
            <div class="lim-modal-footer-bar">
                @unless ($isAdvogada)
                    <button type="button" class="pv-btn lim-btn-excluir" id="btnExcluirProcesso">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                        Excluir processo
                    </button>
                @endunless
                <button type="button" class="pv-btn pv-btn-ghost" data-bs-dismiss="modal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    Fechar
                </button>
                @unless ($isAdvogada)
                    <button type="button" class="pv-btn pv-btn-primary" id="btnSalvarDetalhes">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Salvar Alterações
                    </button>
                @endunless
            </div>

        </div>
    </div>
</div>

@unless ($isAdvogada)
{{-- ============================================================ --}}
{{-- Modal: Novo Processo — Contrato → Beneficiário → Procuração --}}
{{-- ============================================================ --}}
<div class="modal fade" id="modalNovoProcesso" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content pv-modal-modern">

            {{-- Header --}}
            <div class="pv-modal-header pv-modal-header-info">
                <div class="pv-modal-header-content">
                    <div class="pv-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
                        </svg>
                    </div>
                    <div class="pv-modal-title-group">
                        <h5 class="pv-modal-title">Abrir Processo de Liminar</h5>
                        <span class="pv-modal-subtitle" id="novoSubtitulo">Selecione o contrato</span>
                    </div>
                </div>
                <button type="button" class="pv-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="pv-modal-body pv-modal-body-flush">

                {{-- Passo 1: busca de contrato --}}
                <div id="passo1">
                    <div class="lim-modal-section lim-modal-section-last">
                        <div class="lim-modal-section-header lim-section-info">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                            Buscar Contrato
                        </div>
                        <div class="lim-modal-section-body">
                            <label class="pv-form-label">Nome do contrato, CPF/CNPJ ou número da proposta</label>
                            <div class="lim-search-row mb-3">
                                <input type="text" id="buscaContrato" class="pv-form-input flex-grow-1" placeholder="Digite para buscar...">
                                <button id="btnBuscarContrato" class="pv-btn pv-btn-primary" style="white-space:nowrap">Buscar</button>
                            </div>
                            <div id="resultadosContratos" class="lim-resultados"></div>
                        </div>
                    </div>
                </div>

                {{-- Passo 2: seleção de beneficiário --}}
                <div id="passo2" style="display:none">
                    <div class="lim-modal-section lim-modal-section-last">
                        <div class="lim-modal-section-header lim-section-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                            </svg>
                            Selecionar Beneficiário
                        </div>
                        <div class="lim-modal-section-body">
                            <div class="lim-contrato-selecionado mb-3" id="infoContratoSelecionado"></div>
                            <div id="listaBeneficiarios" class="lim-beneficiarios-list"></div>
                        </div>
                    </div>
                </div>

                {{-- Passo 3: dados da procuração (todos obrigatórios) --}}
                <div id="passo3" style="display:none">
                    <form id="formProcuracao">
                        {{-- Dados da empresa --}}
                        <div class="lim-modal-section">
                            <div class="lim-modal-section-header lim-section-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/>
                                </svg>
                                Dados da Empresa
                            </div>
                            <div class="lim-modal-section-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="pv-form-label">Nome da Empresa *</label>
                                        <input type="text" name="nome_empresa" class="pv-form-input" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="pv-form-label">CNPJ *</label>
                                        <input type="text" name="cnpj" class="pv-form-input" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="pv-form-label">Protocolo de Cancelamento *</label>
                                        <input type="text" name="protocolo_cancelamento" class="pv-form-input" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="pv-form-label">E-mail para envio da procuração *</label>
                                        <input type="email" name="email_procuracao" class="pv-form-input" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Datas --}}
                        <div class="lim-modal-section">
                            <div class="lim-modal-section-header lim-section-info">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                Datas
                            </div>
                            <div class="lim-modal-section-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="pv-form-label">Fim do plano *</label>
                                        <input type="text" name="data_fim_plano" class="pv-form-input flatpickr-date" placeholder="DD/MM/AAAA" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="pv-form-label">Contratação *</label>
                                        <input type="text" name="data_contratacao" class="pv-form-input flatpickr-date" placeholder="DD/MM/AAAA" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="pv-form-label">Solicitação do cancelamento *</label>
                                        <input type="text" name="data_solicitacao_cancelamento" class="pv-form-input flatpickr-date" placeholder="DD/MM/AAAA" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="pv-form-label">Último pagamento do boleto *</label>
                                        <input type="text" name="data_ultimo_pagamento_boleto" class="pv-form-input flatpickr-date" placeholder="DD/MM/AAAA" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="pv-form-label">Cobertura comprovante — início *</label>
                                        <input type="text" name="cobertura_comprovante_inicio" class="pv-form-input flatpickr-date" placeholder="DD/MM/AAAA" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="pv-form-label">Cobertura comprovante — fim *</label>
                                        <input type="text" name="cobertura_comprovante_fim" class="pv-form-input flatpickr-date" placeholder="DD/MM/AAAA" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="pv-form-label">Venc. 1º boleto inelegível *</label>
                                        <input type="text" name="data_vencimento_boleto_1" class="pv-form-input flatpickr-date" placeholder="DD/MM/AAAA" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="pv-form-label">Venc. 2º boleto inelegível *</label>
                                        <input type="text" name="data_vencimento_boleto_2" class="pv-form-input flatpickr-date" placeholder="DD/MM/AAAA" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Documentos --}}
                        <div class="lim-modal-section lim-modal-section-last">
                            <div class="lim-modal-section-header lim-section-warning">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/>
                                </svg>
                                Documentos do processo
                            </div>
                            <div class="lim-modal-section-body">
                                @php
                                    $slots = [
                                        ['name' => 'doc_contrato_social',       'label' => 'Contrato Social',                 'req' => true,  'accept' => '.pdf,.jpg,.jpeg,.png', 'icon' => 'doc',   'hint' => 'PDF, JPG ou PNG · até 10MB'],
                                        ['name' => 'doc_cartao_cnpj',            'label' => 'Cartão do CNPJ',                  'req' => true,  'accept' => '.pdf,.jpg,.jpeg,.png', 'icon' => 'doc',   'hint' => 'PDF, JPG ou PNG · até 10MB'],
                                        ['name' => 'doc_rg_cliente',             'label' => 'RG/CPF do Responsável',           'req' => true,  'accept' => '.pdf,.jpg,.jpeg,.png', 'icon' => 'doc',   'hint' => 'PDF, JPG ou PNG · até 10MB'],
                                        ['name' => 'doc_comprovante_pagamento',  'label' => 'Último Comprovante de Pagamento',  'req' => true,  'accept' => '.pdf,.jpg,.jpeg,.png', 'icon' => 'doc',   'hint' => 'PDF, JPG ou PNG · até 10MB'],
                                        ['name' => 'doc_print_protocolo',        'label' => 'Print do Protocolo',              'req' => false, 'accept' => '.pdf,.jpg,.jpeg,.png', 'icon' => 'doc',   'hint' => 'Foto ou print do protocolo'],
                                        ['name' => 'doc_audio_hapvida',          'label' => 'Áudio — Hapvida',                 'req' => false, 'accept' => 'audio/*',              'icon' => 'audio', 'hint' => 'MP3, WAV ou M4A · até 25MB'],
                                    ];
                                @endphp
                                <div class="lim-upload-grid">
                                    @foreach ($slots as $s)
                                        <div class="lim-upload-slot" data-slot>
                                            <input type="file" id="slot_{{ $s['name'] }}" name="{{ $s['name'] }}" accept="{{ $s['accept'] }}" @if($s['req']) required @endif hidden>
                                            <label for="slot_{{ $s['name'] }}" class="lim-slot-click">
                                                <span class="lim-slot-icon">
                                                    @if ($s['icon'] === 'audio')
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 10v4M7 6v12M11 3v18M15 7v10M19 10v4"/></svg>
                                                    @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                    @endif
                                                </span>
                                                <span class="lim-slot-body">
                                                    <span class="lim-slot-head">
                                                        <span class="lim-slot-title">{{ $s['label'] }}</span>
                                                        <span class="lim-slot-badge {{ $s['req'] ? 'is-req' : 'is-opt' }}">{{ $s['req'] ? 'Obrigatório' : 'Opcional' }}</span>
                                                    </span>
                                                    <span class="lim-slot-hint">Arraste ou <u>selecione</u> · {{ $s['hint'] }}</span>
                                                    <span class="lim-slot-file"><span class="lim-slot-filename"></span><span class="lim-slot-size"></span></span>
                                                </span>
                                                <span class="lim-slot-check" aria-hidden="true">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                </span>
                                            </label>
                                            <button type="button" class="lim-slot-remove" aria-label="Remover documento">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

            </div>

            {{-- Footer --}}
            <div class="lim-modal-footer-bar">
                <button type="button" id="btnVoltarPasso" class="pv-btn pv-btn-ghost" style="display:none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                    Voltar
                </button>
                <button type="button" class="pv-btn pv-btn-ghost" data-bs-dismiss="modal" id="btnCancelarNovo">Cancelar</button>
                <button type="button" class="pv-btn pv-btn-success" id="btnConfirmarNovo" style="display:none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Abrir Processo
                </button>
            </div>

        </div>
    </div>
</div>
@endunless

@endsection
