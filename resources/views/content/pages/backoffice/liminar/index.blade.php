@extends('layouts/layoutMaster')

@section('title', 'Cancelamento via Liminar')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
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
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/liminar.js'])
@endsection

@section('content')
<div class="liminar-wrapper">

    {{-- Header --}}
    <div class="lim-header">
        <div class="lim-header-content">
            <div class="lim-header-text">
                <span class="lim-greeting-label">Backoffice</span>
                <h1 class="lim-main-title">Cancelamento via Liminar</h1>
                <p class="lim-subtitle">Gerencie os processos de cancelamento judicial de planos de saúde</p>
            </div>
            <div class="lim-header-actions">
                <button class="lim-btn-novo" id="btnNovoProcesso">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Novo Processo
                </button>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="lim-kpi-grid">
        <div class="kpi-card kpi-info">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total de Processos</span>
                <h2 class="kpi-value" id="kpi-total">—</h2>
                <span class="kpi-subtitle">registrados</span>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-warning">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Em Execução</span>
                <h2 class="kpi-value" id="kpi-execucao">—</h2>
                <span class="kpi-subtitle">em andamento</span>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-success">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Liminares Concedidas</span>
                <h2 class="kpi-value" id="kpi-concedidas">—</h2>
                <span class="kpi-subtitle">fase atual</span>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-primary">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Concluídos</span>
                <h2 class="kpi-value" id="kpi-concluidos">—</h2>
                <span class="kpi-subtitle">finalizados</span>
            </div>
            <div class="kpi-glow"></div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="lim-filter-card">
        <div class="lim-filter-row">
            <div class="lim-filter-group">
                <label>Status</label>
                <select id="filtroStatus" class="lim-select">
                    <option value="">Todos</option>
                    <option value="NAO_INICIADA">Não Iniciada</option>
                    <option value="EM_EXECUCAO">Em Execução</option>
                    <option value="BLOQUEADA">Bloqueada</option>
                    <option value="CONCLUIDA">Concluída</option>
                </select>
            </div>
            <div class="lim-filter-group">
                <label>Fase</label>
                <select id="filtroFase" class="lim-select">
                    <option value="">Todas</option>
                    <option value="ASSINATURA_PROCURACAO">Assinatura da Procuração</option>
                    <option value="ENVIO_DOCUMENTOS">Envio de Documentos</option>
                    <option value="AGUARDANDO_LIMINAR">Aguardando Liminar</option>
                    <option value="AGUARDANDO_RETORNO_OPERADORA">Aguardando Retorno da Operadora</option>
                    <option value="LIMINAR_CONCEDIDA">Liminar Concedida</option>
                </select>
            </div>
            <div class="lim-filter-group">
                <label>Período (criação)</label>
                <input type="text" id="filtroDataInicio" class="lim-input flatpickr-date" placeholder="Data Início">
            </div>
            <div class="lim-filter-group">
                <label>&nbsp;</label>
                <input type="text" id="filtroDataFim" class="lim-input flatpickr-date" placeholder="Data Fim">
            </div>
            <div class="lim-filter-group lim-filter-btn-group">
                <label>&nbsp;</label>
                <button id="btnFiltrar" class="lim-btn-filtrar">Filtrar</button>
            </div>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="table-card">
        <div class="table-header">
            <div class="table-title-group">
                <div class="table-icon" style="background: linear-gradient(135deg, var(--dash-primary), var(--dash-primary-light))">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                    </svg>
                </div>
                <div>
                    <h3 class="table-title">Processos de Liminar</h3>
                    <span class="table-subtitle">Cancelamentos via processo judicial</span>
                </div>
            </div>
        </div>
        <div class="table-body">
            <table id="tabelaLiminares" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>Contrato</th>
                        <th>Beneficiário</th>
                        <th>Tipo</th>
                        <th>Fase</th>
                        <th>Status</th>
                        <th>Honorários</th>
                        <th>Recebimento</th>
                        <th>Corretor</th>
                        <th>Responsável</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

</div>

{{-- ============================================================ --}}
{{-- Modal: Detalhes / Edição do Processo                        --}}
{{-- ============================================================ --}}
<div class="modal fade" id="modalDetalhes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content pv-modal-modern">

            {{-- Header --}}
            <div class="pv-modal-header pv-modal-header-primary">
                <div class="pv-modal-header-content">
                    <div class="pv-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                    </div>
                    <div class="pv-modal-title-group">
                        <h5 class="pv-modal-title" id="detalheModalTitulo">Processo de Liminar</h5>
                        <span class="pv-modal-subtitle" id="detalheModalSubtitulo">Carregando...</span>
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
                <input type="hidden" id="detalheId">

                {{-- Seção: Processo --}}
                <div class="lim-modal-section">
                    <div class="lim-modal-section-header lim-section-primary" id="detalheSecaoHeader">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="detalheSecaoIcone">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <span id="detalheSecaoTexto">Andamento do Processo</span>
                    </div>
                    <div class="lim-modal-section-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="pv-form-label">Status</label>
                                <select id="detalheStatus" class="lim-modal-select">
                                    <option value="NAO_INICIADA">Não Iniciada</option>
                                    <option value="EM_EXECUCAO">Em Execução</option>
                                    <option value="BLOQUEADA">Bloqueada</option>
                                    <option value="CONCLUIDA">Concluída</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="pv-form-label">Fase Atual</label>
                                <select id="detalheFase" class="lim-modal-select">
                                    <option value="">— Selecione —</option>
                                    <option value="ASSINATURA_PROCURACAO">Assinatura da Procuração</option>
                                    <option value="ENVIO_DOCUMENTOS">Envio de Documentos</option>
                                    <option value="AGUARDANDO_LIMINAR">Aguardando Liminar</option>
                                    <option value="AGUARDANDO_RETORNO_OPERADORA">Aguardando Retorno da Operadora</option>
                                    <option value="LIMINAR_CONCEDIDA">Liminar Concedida</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="pv-form-label">Data de Envio</label>
                                <input type="text" id="detalheDataEnvio" class="pv-form-input flatpickr-date" placeholder="DD/MM/AAAA">
                            </div>
                            <div class="col-md-3">
                                <label class="pv-form-label">Responsável</label>
                                <input type="text" id="detalheResponsavelNome" class="pv-form-input" readonly>
                                <input type="hidden" id="detalheResponsavelId">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Seção: Financeiro --}}
                <div class="lim-modal-section">
                    <div class="lim-modal-section-header lim-section-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                        Financeiro
                    </div>
                    <div class="lim-modal-section-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="pv-form-label">Honorários</label>
                                <select id="detalheHonorarios" class="lim-modal-select">
                                    <option value="PENDENTE">Pendente</option>
                                    <option value="PAGO">Pago</option>
                                    <option value="NAO_SE_APLICA">N/A</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="pv-form-label">Recebimento (Operadora)</label>
                                <select id="detalheRecebimento" class="lim-modal-select">
                                    <option value="PENDENTE">Pendente</option>
                                    <option value="BOLETO_GERADO">Boleto Gerado</option>
                                    <option value="PAGO">Pago</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="pv-form-label">Valor Recebido (R$)</label>
                                <input type="number" id="detalheValorRecebimento" class="pv-form-input" step="0.01" min="0" placeholder="0,00">
                            </div>
                            <div class="col-md-4">
                                <label class="pv-form-label">Observações</label>
                                <textarea id="detalheObservacoes" class="pv-form-textarea" rows="2" maxlength="2000" style="height:44px;resize:none"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Seção: Documentos + Histórico --}}
                <div class="lim-modal-section lim-modal-section-last">
                    <div class="row g-0">
                        {{-- Documentos --}}
                        <div class="col-md-6 lim-modal-col-left">
                            <div class="lim-modal-section-header lim-section-warning">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/>
                                </svg>
                                Documentos
                            </div>
                            <div class="lim-modal-section-body">
                                <select id="uploadTipoDoc" class="lim-modal-select w-100 mb-2">
                                    <option value="">— Selecione o tipo —</option>
                                    <option value="CARTEIRINHA">Carteirinha</option>
                                    <option value="CARTAO_CNPJ">Cartão do CNPJ</option>
                                    <option value="COMPROVANTE_PAGAMENTO">Comprovante de Pagamento</option>
                                    <option value="CONTRATO_SOCIAL">Contrato Social</option>
                                    <option value="RETORNO_CANCELAMENTO">Retorno do Cancelamento</option>
                                    <option value="RG_CLIENTE">RG da Cliente</option>
                                </select>
                                <div class="lim-file-drop" id="fileDropZone">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/>
                                        <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/>
                                    </svg>
                                    <p>Arraste ou <label for="inputArquivo" class="lim-file-link">selecione</label></p>
                                    <small>PDF, JPG, PNG — máx. 10MB</small>
                                    <input type="file" id="inputArquivo" accept=".pdf,.jpg,.jpeg,.png" hidden>
                                </div>
                                <div id="listaDocumentos" class="lim-doc-list mt-2"></div>
                            </div>
                        </div>

                        {{-- Histórico --}}
                        <div class="col-md-6 lim-modal-col-right">
                            <div class="lim-modal-section-header lim-section-info">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                </svg>
                                Histórico de Alterações
                            </div>
                            <div class="lim-modal-section-body lim-timeline-container">
                                <div id="timelineHistorico" class="lim-timeline"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="lim-modal-footer-bar">
                <button type="button" class="pv-btn pv-btn-ghost" data-bs-dismiss="modal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    Fechar
                </button>
                <button type="button" class="pv-btn pv-btn-primary" id="btnSalvarDetalhes">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Salvar Alterações
                </button>
            </div>

        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- Modal: Novo Processo — Seleção de Contrato + Beneficiário   --}}
{{-- ============================================================ --}}
<div class="modal fade" id="modalNovoProcesso" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content pv-modal-modern">

            {{-- Header --}}
            <div class="pv-modal-header pv-modal-header-info">
                <div class="pv-modal-header-content">
                    <div class="pv-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="16"/>
                            <line x1="8" y1="12" x2="16" y2="12"/>
                        </svg>
                    </div>
                    <div class="pv-modal-title-group">
                        <h5 class="pv-modal-title">Iniciar Processo de Liminar</h5>
                        <span class="pv-modal-subtitle">Selecione o contrato e o beneficiário</span>
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
                                <button id="btnBuscarContrato" class="pv-btn pv-btn-primary" style="white-space:nowrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                    </svg>
                                    Buscar
                                </button>
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
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                            </svg>
                            Selecionar Beneficiário
                        </div>
                        <div class="lim-modal-section-body">
                            <div class="lim-contrato-selecionado mb-3" id="infoContratoSelecionado"></div>
                            <div id="listaBeneficiarios" class="lim-beneficiarios-list"></div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="lim-modal-footer-bar">
                <button type="button" id="btnVoltarPasso1" class="pv-btn pv-btn-ghost" style="display:none">
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
                    Iniciar Processo
                </button>
            </div>

        </div>
    </div>
</div>

@endsection
