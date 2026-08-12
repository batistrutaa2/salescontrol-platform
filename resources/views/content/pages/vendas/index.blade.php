@extends('layouts/layoutMaster')

@section('title', 'Relatorio de Vendas | ' . auth()->user()->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/lista-vendas.scss', 'resources/assets/vendor/scss/pages/venda-documentos.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/vendas.js', 'resources/assets/js/venda-documentos.js'])
@endsection

@section('content')
<div class="lv-wrapper">

    <!-- Page Header -->
    <div class="lv-page-header">
        <div class="lv-header-content">
            <div class="lv-header-text">
                <span class="lv-greeting-label">Vendas</span>
                <h1 class="lv-main-title">Minha carteira de clientes</h1>
                <p class="lv-subtitle">Acompanhe contratos implantados e propostas que ainda estão em implantação</p>
            </div>
            <div class="lv-header-filters">
                <fieldset class="lv-filter-group lv-period-filter">
                    <legend class="lv-filter-legend">Período de cadastro</legend>
                    <div class="lv-filter-item">
                        <label class="lv-filter-label" for="filtro-data-inicio">Data inicial</label>
                        <div class="lv-date-control">
                            <i class="ri-calendar-2-line" aria-hidden="true"></i>
                            <input type="text" class="lv-filter-select js-date-mask" id="filtro-data-inicio" placeholder="DD/MM/AAAA" inputmode="numeric" maxlength="10" autocomplete="off" aria-describedby="lv-periodo-erro">
                            <button type="button" class="lv-date-trigger" data-calendar-target="inicio" aria-label="Abrir calendário da data inicial"><i class="ri-arrow-down-s-line" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <div class="lv-filter-item">
                        <label class="lv-filter-label" for="filtro-data-fim">Data final</label>
                        <div class="lv-date-control">
                            <i class="ri-calendar-2-line" aria-hidden="true"></i>
                            <input type="text" class="lv-filter-select js-date-mask" id="filtro-data-fim" placeholder="DD/MM/AAAA" inputmode="numeric" maxlength="10" autocomplete="off" aria-describedby="lv-periodo-erro">
                            <button type="button" class="lv-date-trigger" data-calendar-target="fim" aria-label="Abrir calendário da data final"><i class="ri-arrow-down-s-line" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <span class="lv-filter-error" id="lv-periodo-erro" role="alert" aria-live="polite"></span>
                </fieldset>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="lv-kpi-grid">
        <!-- Contratos Cadastrados -->
        <div class="lv-kpi-card lv-kpi-primary">
            <div class="lv-kpi-icon-wrapper">
                <div class="lv-kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="lv-kpi-pulse"></div>
            </div>
            <div class="lv-kpi-content">
                <span class="lv-kpi-label">Contratos Cadastrados</span>
                <h2 class="lv-kpi-value js-valorCadastrado">R$ 0,00</h2>
            </div>
            <div class="lv-kpi-glow"></div>
        </div>

        <!-- Contratos Implantados -->
        <div class="lv-kpi-card lv-kpi-success">
            <div class="lv-kpi-icon-wrapper">
                <div class="lv-kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="lv-kpi-pulse"></div>
            </div>
            <div class="lv-kpi-content">
                <span class="lv-kpi-label">Contratos Implantados</span>
                <h2 class="lv-kpi-value js-implantado">R$ 0,00</h2>
            </div>
            <div class="lv-kpi-glow"></div>
        </div>

        <!-- Contatos Importados -->
        <div class="lv-kpi-card lv-kpi-info">
            <div class="lv-kpi-icon-wrapper">
                <div class="lv-kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="lv-kpi-pulse"></div>
            </div>
            <div class="lv-kpi-content">
                <span class="lv-kpi-label">Contatos Importados</span>
                <h2 class="lv-kpi-value js-quantidadeContatosImportados">0</h2>
            </div>
            <div class="lv-kpi-glow"></div>
        </div>

        <!-- Conversão Mensal -->
        <div class="lv-kpi-card lv-kpi-warning">
            <div class="lv-kpi-icon-wrapper">
                <div class="lv-kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"/>
                    </svg>
                </div>
                <div class="lv-kpi-pulse"></div>
            </div>
            <div class="lv-kpi-content">
                <span class="lv-kpi-label">Conversão Mensal</span>
                <h2 class="lv-kpi-value js-conversao">0%</h2>
            </div>
            <div class="lv-kpi-glow"></div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="lv-table-card">
        <div class="lv-table-header">
            <div class="lv-table-title-group">
                <div class="lv-table-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </div>
                <div>
                    <h3 class="lv-table-title">Vendas Detalhadas</h3>
                    <span class="lv-table-subtitle">Lista completa de vendas</span>
                </div>
            </div>
            <div class="lv-portfolio-filter" role="group" aria-label="Filtrar contratos por fase">
                <button type="button" class="lv-portfolio-option is-active" data-fase="todos" aria-pressed="true">Todos</button>
                <button type="button" class="lv-portfolio-option" data-fase="implantados" aria-pressed="false"><span class="lv-filter-dot is-success"></span>Implantados</button>
                <button type="button" class="lv-portfolio-option" data-fase="processo" aria-pressed="false"><span class="lv-filter-dot is-warning"></span>Em implantação</button>
            </div>
        </div>
        <div class="lv-table-body">
            <div class="table-responsive">
                <table id="tabela-vendas-detalhadas" class="table">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Nome do Contrato</th>
                            <th scope="col">Status</th>
                            <th scope="col">Documentos</th>
                            <th scope="col">Backoffice</th>
                            <th scope="col">Valor</th>
                            <th scope="col">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Preenchido via DataTables --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Visualizar Venda -->
<div class="modal fade" id="modalVisualizarVenda" tabindex="-1" aria-labelledby="venda-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <!-- Header -->
            <div class="lv-modal-header">
                <div class="lv-modal-heading">
                    <span class="lv-modal-title-icon">
                        <i class="ri-file-text-line"></i>
                    </span>
                    <div>
                        <span class="lv-modal-eyebrow">Detalhes da venda</span>
                        <h2 class="lv-modal-title" id="venda-modal-title">Proposta #<span id="venda-modal-id">-</span></h2>
                    </div>
                </div>
                <button type="button" class="lv-btn-close" data-bs-dismiss="modal" aria-label="Fechar detalhes do contrato">
                    <i class="ri-close-line" aria-hidden="true"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="modal-body lv-modal-body" id="venda-modal-scroll">
                <!-- Loading State -->
                <div id="venda-loading" class="lv-loading">
                    <div class="lv-spinner"></div>
                    <p class="lv-loading-text">Carregando detalhes...</p>
                </div>

                <!-- Content -->
                <div id="venda-content" class="d-none">

                    <!-- Contract summary -->
                    <div class="lv-contract-summary">
                        <div class="lv-contract-identity">
                            <span class="lv-contract-kicker">Contrato</span>
                            <h4 class="lv-contract-name" id="venda-nome-contrato">-</h4>
                            <div class="lv-contract-meta">
                                <span><i class="ri-id-card-line"></i> <span id="venda-cpf-cnpj">-</span></span>
                                <span><i class="ri-phone-line"></i> <span id="venda-telefone">-</span></span>
                            </div>
                        </div>
                        <div class="lv-contract-aside">
                            <span class="lv-status-badge primary" id="venda-status-badge">-</span>
                            <div class="lv-backoffice-card" id="backoffice-card">
                                <div class="lv-avatar" id="backoffice-avatar">-</div>
                                <div>
                                    <div class="lv-bo-label">Responsável</div>
                                    <div class="lv-bo-name" id="venda-backoffice-nome">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs lv-nav-tabs" id="vendaDetailsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-contrato" data-bs-toggle="tab" data-bs-target="#panel-contrato" type="button" role="tab" aria-controls="panel-contrato" aria-selected="true">
                                <i class="ri-building-line me-2"></i>Visão geral
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-documentos" data-bs-toggle="tab" data-bs-target="#panel-documentos" type="button" role="tab" aria-controls="panel-documentos" aria-selected="false">
                                <i class="ri-attachment-2 me-2"></i>Documentos
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-beneficiarios" data-bs-toggle="tab" data-bs-target="#panel-beneficiarios" type="button" role="tab" aria-controls="panel-beneficiarios" aria-selected="false">
                                <i class="ri-group-line me-2"></i>Beneficiários <span class="lv-tab-count" id="venda-beneficiarios-count">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-plano" data-bs-toggle="tab" data-bs-target="#panel-plano" type="button" role="tab" aria-controls="panel-plano" aria-selected="false">
                                <i class="ri-shield-check-line me-2"></i>Plano vendido
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-historico" data-bs-toggle="tab" data-bs-target="#panel-historico" type="button" role="tab" aria-controls="panel-historico" aria-selected="false">
                                <i class="ri-history-line me-2"></i>Andamento
                            </button>
                        </li>
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content" id="vendaDetailsTabsContent">

                        <div class="tab-pane fade" id="panel-documentos" role="tabpanel" aria-labelledby="tab-documentos" tabindex="0">
                            <x-venda-documentos />
                        </div>

                        <!-- Tab: Contrato -->
                        <div class="tab-pane fade show active" id="panel-contrato" role="tabpanel" aria-labelledby="tab-contrato" tabindex="0">

                            <div class="lv-info-section">
                                <div class="lv-section-title">Dados da empresa e contato</div>
                                <div class="lv-info-grid">
                                    <div class="lv-info-item"><span class="lv-label">Razão social / contrato</span><span class="lv-value" id="venda-empresa-nome">-</span></div>
                                    <div class="lv-info-item"><span class="lv-label">CPF/CNPJ</span><span class="lv-value" id="venda-empresa-documento">-</span></div>
                                    <div class="lv-info-item"><span class="lv-label">E-mail</span><span class="lv-value" id="venda-empresa-email">-</span></div>
                                    <div class="lv-info-item"><span class="lv-label">Telefone principal</span><span class="lv-value" id="venda-empresa-telefone1">-</span></div>
                                    <div class="lv-info-item"><span class="lv-label">Telefone alternativo</span><span class="lv-value" id="venda-empresa-telefone2">-</span></div>
                                    <div class="lv-info-item"><span class="lv-label">Tipo de empresa</span><span class="lv-value" id="venda-tipo-empresa">-</span></div>
                                    <div class="lv-info-item"><span class="lv-label">Tipo de contrato</span><span class="lv-value" id="venda-tipo-contrato">-</span></div>
                                    <div class="lv-info-item lv-info-item-wide"><span class="lv-label">Observações</span><span class="lv-value" id="venda-observacoes">-</span></div>
                                </div>
                            </div>

                            <div class="lv-info-section">
                                <div class="lv-section-title">Valores</div>
                                <div class="lv-info-grid">
                                    <div class="lv-info-item">
                                        <span class="lv-label">Valor do Contrato</span>
                                        <span class="lv-value highlight" id="venda-valor">-</span>
                                    </div>
                                    <div class="lv-info-item">
                                        <span class="lv-label">Valor de angariação</span>
                                        <span class="lv-value" id="venda-angariacao-valor">-</span>
                                    </div>
                                    <div class="lv-info-item">
                                        <span class="lv-label">Modalidade</span>
                                        <span class="lv-value" id="venda-angariacao">-</span>
                                    </div>
                                </div>
                            </div>

                            <div class="lv-info-section">
                                <div class="lv-section-title">Datas e Detalhes</div>
                                <div class="lv-info-grid">
                                    <div class="lv-info-item">
                                        <span class="lv-label">Data Vigência</span>
                                        <span class="lv-value" id="venda-data-vigencia">-</span>
                                    </div>
                                    <div class="lv-info-item">
                                        <span class="lv-label">Data Implantação</span>
                                        <span class="lv-value" id="venda-data-implantacao">-</span>
                                    </div>
                                    <div class="lv-info-item">
                                        <span class="lv-label">Nº Proposta</span>
                                        <span class="lv-value" id="venda-numero-proposta">-</span>
                                    </div>
                                    <div class="lv-info-item">
                                        <span class="lv-label">Vendedor</span>
                                        <span class="lv-value" id="venda-vendedor">-</span>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="tab-pane fade" id="panel-beneficiarios" role="tabpanel" aria-labelledby="tab-beneficiarios" tabindex="0">
                            <div id="venda-beneficiarios" class="lv-beneficiary-list"></div>
                            <div id="venda-beneficiarios-vazio" class="lv-empty-state d-none"><i class="ri-group-line lv-empty-symbol" aria-hidden="true"></i><p class="lv-empty-text">Nenhum titular foi cadastrado neste contrato.</p></div>
                        </div>

                        <div class="tab-pane fade" id="panel-plano" role="tabpanel" aria-labelledby="tab-plano" tabindex="0">
                            <div class="lv-plan-hero">
                                <div class="lv-plan-icon"><i class="ri-shield-check-line" aria-hidden="true"></i></div>
                                <div><span class="lv-plan-operator" id="venda-plano-operadora">-</span><h3 class="lv-plan-name" id="venda-plano-nome">-</h3></div>
                                <span class="lv-plan-value" id="venda-plano-valor">-</span>
                            </div>
                            <div class="lv-info-grid lv-plan-grid">
                                <div class="lv-info-item"><span class="lv-label">Coparticipação</span><span class="lv-value" id="venda-plano-coparticipacao">-</span></div>
                                <div class="lv-info-item"><span class="lv-label">Total de vidas</span><span class="lv-value" id="venda-plano-vidas">-</span></div>
                                <div class="lv-info-item"><span class="lv-label">Vigência</span><span class="lv-value" id="venda-plano-vigencia">-</span></div>
                                <div class="lv-info-item"><span class="lv-label">Implantação</span><span class="lv-value" id="venda-plano-implantacao">-</span></div>
                                <div class="lv-info-item"><span class="lv-label">Número da proposta</span><span class="lv-value" id="venda-plano-proposta">-</span></div>
                                <div class="lv-info-item"><span class="lv-label">Modalidade</span><span class="lv-value" id="venda-plano-modalidade">-</span></div>
                            </div>
                        </div>

                        <!-- Tab: Histórico -->
                        <div class="tab-pane fade" id="panel-historico" role="tabpanel" aria-labelledby="tab-historico" tabindex="0">

                            <div id="historico-loading" class="text-center py-4">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                                <span class="ms-2" style="color: var(--lv-text-muted);">Carregando histórico...</span>
                            </div>

                            <div id="historico-content" class="d-none">
                                <div id="historico-timeline" class="lv-timeline"></div>
                                <div id="historico-vazio" class="lv-empty-state d-none">
                                    <div class="lv-empty-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/>
                                            <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>
                                        </svg>
                                    </div>
                                    <p class="lv-empty-text">Nenhum histórico encontrado.</p>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>
            </div>

            <!-- Footer -->
            <div class="lv-modal-footer">
                <button type="button" class="lv-btn-close-modal" data-bs-dismiss="modal">
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
