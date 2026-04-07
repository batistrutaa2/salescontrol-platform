@extends('layouts/layoutMaster')

@section('title', 'Gerenciar Leads')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/scss/pages/dashboard-analytics.scss',
        'resources/assets/vendor/scss/pages/gerenciar-leads.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/visualizar-leads.js'])
    <script>
        window.leadHubConfig = {
            kpis: @json($kpis),
            importDates: @json($importDates),
            currentMonth: {{ $currentMonth }},
            currentYear: {{ $currentYear }},
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
@endsection

@section('content')
<div class="gerenciar-leads-wrapper">

    {{-- Page Header --}}
    <div class="gl-page-header">
        <div class="gl-page-title-group">
            <div class="gl-page-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div>
                <h1 class="gl-page-title">Gerenciar Leads</h1>
                <p class="gl-page-subtitle">Central de controle de todos os leads do sistema</p>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="gl-kpi-grid">
        <div class="gl-kpi-card kpi-total" data-filter="">
            <div class="gl-kpi-top">
                <div class="gl-kpi-icon-ring">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
            </div>
            <span class="gl-kpi-label">Total de Leads</span>
            <h2 class="gl-kpi-value" id="kpi-total">{{ number_format($kpis['total'], 0, ',', '.') }}</h2>
        </div>

        <div class="gl-kpi-card kpi-vendedor" data-filter="COM VENDEDOR">
            <div class="gl-kpi-top">
                <div class="gl-kpi-icon-ring">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
            </div>
            <span class="gl-kpi-label">Com Vendedor</span>
            <h2 class="gl-kpi-value" id="kpi-vendedor">{{ number_format($kpis['com_vendedor'], 0, ',', '.') }}</h2>
        </div>

        <div class="gl-kpi-card kpi-preditiva" data-filter="PREDITIVA">
            <div class="gl-kpi-top">
                <div class="gl-kpi-icon-ring">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                </div>
            </div>
            <span class="gl-kpi-label">Preditiva</span>
            <h2 class="gl-kpi-value" id="kpi-preditiva">{{ number_format($kpis['preditiva'], 0, ',', '.') }}</h2>
        </div>

        <div class="gl-kpi-card kpi-descartados" data-filter="DESCARTADO">
            <div class="gl-kpi-top">
                <div class="gl-kpi-icon-ring">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                </div>
            </div>
            <span class="gl-kpi-label">Descartados</span>
            <h2 class="gl-kpi-value" id="kpi-descartados">{{ number_format($kpis['descartados'], 0, ',', '.') }}</h2>
        </div>

        <div class="gl-kpi-card kpi-remarketing" data-filter="REMARKETING">
            <div class="gl-kpi-top">
                <div class="gl-kpi-icon-ring">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10"/>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                    </svg>
                </div>
            </div>
            <span class="gl-kpi-label">Remarketing</span>
            <h2 class="gl-kpi-value" id="kpi-remarketing">{{ number_format($kpis['remarketing'], 0, ',', '.') }}</h2>
        </div>
    </div>

    {{-- Main Layout --}}
    <div class="gl-main-layout">

        {{-- Sidebar: Import Dates --}}
        <div class="gl-imports-panel">
            <div class="gl-imports-header">
                <h6>Importacoes</h6>
                <div class="gl-month-nav">
                    <button type="button" id="btnPrevMonth">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <span class="gl-month-label" id="monthLabel"></span>
                    <button type="button" id="btnNextMonth">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
            </div>
            <div class="gl-imports-list" id="importsList">
                {{-- Rendered via JS --}}
            </div>
        </div>

        {{-- Table Area --}}
        <div class="gl-table-area">
            <div class="gl-table-card">

                {{-- Filter Bar --}}
                <div class="gl-filter-bar">
                    <div class="gl-filter-item">
                        <select id="filterSituacao" class="form-select form-select-sm">
                            <option value="">Todas Situacoes</option>
                            <option value="COM VENDEDOR">Com Vendedor</option>
                            <option value="PREDITIVA">Preditiva</option>
                            <option value="DESCARTADO">Descartado</option>
                            <option value="REMARKETING">Remarketing</option>
                            <option value="SEM ATRIBUICAO">Sem Atribuicao</option>
                        </select>
                    </div>
                    <div class="gl-filter-item">
                        <select id="filterCorretor" class="form-select form-select-sm">
                            <option value="">Todos Corretores</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ strtoupper($user->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gl-filter-item gl-filter-base">
                        <input type="text" id="filterBase" class="form-control form-control-sm" placeholder="Filtrar por base...">
                    </div>
                    <div class="gl-filter-item gl-filter-date-range">
                        <div class="gl-date-range-wrapper">
                            <svg class="gl-date-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <input type="text" id="filterDateRange" class="form-control form-control-sm" placeholder="Periodo de importacao" readonly>
                        </div>
                    </div>
                    <button type="button" class="gl-filter-clear" id="btnClearFilters">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Limpar
                    </button>
                </div>

                {{-- Bulk Toolbar --}}
                <div class="gl-bulk-toolbar" id="bulkToolbar">
                    <span class="gl-selected-count"><span id="selectedCount">0</span> selecionados</span>
                    <button type="button" class="gl-bulk-btn gl-btn-transfer" data-action="transfer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5"/><path d="M4 20L21 3"/><path d="M21 16v5h-5"/><path d="M15 15l6 6"/><path d="M4 4l5 5"/></svg>
                        Transferir
                    </button>
                    <button type="button" class="gl-bulk-btn gl-btn-preditiva" data-action="preditiva">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3"/></svg>
                        Enviar Preditiva
                    </button>
                    <button type="button" class="gl-bulk-btn gl-btn-discard" data-action="discard">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        Descartar
                    </button>
                    <button type="button" class="gl-bulk-btn gl-btn-reactivate" data-action="reactivate">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                        Reativar
                    </button>
                    <button type="button" class="gl-bulk-btn gl-btn-delete" data-action="delete">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        Excluir
                    </button>
                </div>

                {{-- DataTable --}}
                <div class="card-datatable table-responsive">
                    <table id="leadsHubTable" class="table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>CPF</th>
                                <th>Corretor</th>
                                <th>Situacao</th>
                                <th>Tabulacao</th>
                                <th>Base</th>
                                <th>Importado em</th>
                                <th>Acoes</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Transfer --}}
<div class="modal fade" id="modalTransfer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="gl-modal-header modal-header">
                <h5 class="modal-title">Transferir Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4" id="transferSubtitle">Selecione o corretor que recebera o lead</p>
                <div class="mb-3">
                    <label class="form-label">Corretor</label>
                    <select class="select2 form-select" id="transferUserId">
                        <option value="">Selecione um corretor</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ strtoupper($user->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status/Tabulacao</label>
                    <select class="select2 form-select" id="transferTabulationId">
                        <option value="">Selecione o Status</option>
                        @foreach ($tabulations as $tabulation)
                            <option value="{{ $tabulation->id }}">{{ strtoupper($tabulation->descricao) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmTransfer">Transferir</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Comments --}}
<div class="modal fade" id="modalComentarios" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="gl-modal-header modal-header">
                <h5 class="modal-title">Historico do Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="comentariosBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Confirm Action --}}
<div class="modal fade" id="modalConfirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="gl-modal-header modal-header">
                <h5 class="modal-title" id="confirmTitle">Confirmar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">Tem certeza que deseja realizar esta acao?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnConfirmAction">Confirmar</button>
            </div>
        </div>
    </div>
</div>

@endsection
