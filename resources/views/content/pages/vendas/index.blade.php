@extends('layouts/layoutMaster')

@section('title', 'Relatorio de Vendas | ' . auth()->user()->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/lista-vendas.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/vendas.js'])
@endsection

@section('content')
<div class="lv-wrapper">

    <!-- Page Header -->
    <div class="lv-page-header">
        <div class="lv-header-content">
            <div class="lv-header-text">
                <span class="lv-greeting-label">Vendas</span>
                <h1 class="lv-main-title">Relatório de Vendas</h1>
                <p class="lv-subtitle">Acompanhe o desempenho das suas vendas</p>
            </div>
            <div class="lv-header-filters">
                <div class="lv-filter-group">
                    <div class="lv-filter-item">
                        <span class="lv-filter-label">Período</span>
                        <input type="text" class="lv-filter-select" id="filtro-periodo" placeholder="Selecione o período">
                    </div>
                </div>
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
        </div>
        <div class="lv-table-body">
            <div class="table-responsive">
                <table id="tabela-vendas-detalhadas" class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome do Contrato</th>
                            <th>Status</th>
                            <th>Backoffice</th>
                            <th>Valor</th>
                            <th>Ações</th>
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
<div class="modal fade" id="modalVisualizarVenda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <!-- Header -->
            <div class="lv-modal-header">
                <h5 class="lv-modal-title">
                    <span class="lv-modal-title-icon">
                        <i class="ri-file-text-line"></i>
                    </span>
                    <span>Proposta #<span id="venda-modal-id">-</span></span>
                </h5>
                <button type="button" class="lv-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="lv-modal-body">
                <!-- Loading State -->
                <div id="venda-loading" class="lv-loading">
                    <div class="lv-spinner"></div>
                    <p class="lv-loading-text">Carregando detalhes...</p>
                </div>

                <!-- Content -->
                <div id="venda-content" class="d-none">

                    <!-- Contract Header -->
                    <div class="lv-contract-header">
                        <div>
                            <h4 class="lv-contract-name" id="venda-nome-contrato">-</h4>
                            <div class="lv-contract-meta">
                                <span><i class="ri-id-card-line"></i> <span id="venda-cpf-cnpj">-</span></span>
                                <span><i class="ri-phone-line"></i> <span id="venda-telefone">-</span></span>
                            </div>
                        </div>
                        <span class="lv-status-badge primary" id="venda-status-badge">-</span>
                    </div>

                    <!-- Backoffice Responsável -->
                    <div class="lv-backoffice-card" id="backoffice-card">
                        <div class="lv-avatar" id="backoffice-avatar">-</div>
                        <div>
                            <div class="lv-bo-label">Responsável Backoffice</div>
                            <div class="lv-bo-name" id="venda-backoffice-nome">-</div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs lv-nav-tabs" id="vendaDetailsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-contrato" data-bs-toggle="tab" data-bs-target="#panel-contrato" type="button" role="tab">
                                <i class="ri-file-text-line me-2"></i>Contrato
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-historico" data-bs-toggle="tab" data-bs-target="#panel-historico" type="button" role="tab">
                                <i class="ri-history-line me-2"></i>Histórico
                            </button>
                        </li>
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content" id="vendaDetailsTabsContent">

                        <!-- Tab: Contrato -->
                        <div class="tab-pane fade show active" id="panel-contrato" role="tabpanel">

                            <div class="lv-info-section">
                                <div class="lv-section-title">Informações do Plano</div>
                                <div class="lv-info-grid">
                                    <div class="lv-info-item">
                                        <span class="lv-label">Operadora</span>
                                        <span class="lv-value" id="venda-operadora">-</span>
                                    </div>
                                    <div class="lv-info-item">
                                        <span class="lv-label">Plano</span>
                                        <span class="lv-value" id="venda-plano">-</span>
                                    </div>
                                    <div class="lv-info-item">
                                        <span class="lv-label">Coparticipação</span>
                                        <span class="lv-value" id="venda-coparticipacao">-</span>
                                    </div>
                                    <div class="lv-info-item">
                                        <span class="lv-label">Vidas</span>
                                        <span class="lv-value" id="venda-vidas">-</span>
                                    </div>
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
                                        <span class="lv-label">Comissão</span>
                                        <span class="lv-value" id="venda-comissao-valor">-</span>
                                    </div>
                                    <div class="lv-info-item">
                                        <span class="lv-label">% Comissão</span>
                                        <span class="lv-value" id="venda-comissao-percentual">-</span>
                                    </div>
                                    <div class="lv-info-item">
                                        <span class="lv-label">Tipo</span>
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

                        <!-- Tab: Histórico -->
                        <div class="tab-pane fade" id="panel-historico" role="tabpanel">

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
