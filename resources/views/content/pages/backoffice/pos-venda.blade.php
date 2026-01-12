@extends('layouts/layoutMaster')

@section('title', 'Pós-Venda - Contratos Implantados')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/scss/pages/pos-venda.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/pos-venda.js'])
@endsection

@section('content')
    <div class="pos-venda-wrapper">
        <!-- Header Section -->
        <div class="pv-header">
            <div class="header-content">
                <div class="header-text">
                    <span class="greeting-label">Carteira de Clientes</span>
                    <h1 class="main-title">Pós-Venda</h1>
                    <p class="subtitle">Gerencie seus contratos implantados e acompanhe aniversários de apólice</p>
                </div>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid">
            <!-- Total Implantados -->
            <div class="kpi-card kpi-primary">
                <div class="kpi-icon-wrapper">
                    <div class="kpi-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <div class="kpi-pulse"></div>
                </div>
                <div class="kpi-content">
                    <span class="kpi-label">Total Implantados</span>
                    <h2 class="kpi-value" id="kpi-total">0</h2>
                    <span class="kpi-subtitle">contratos ativos</span>
                </div>
                <div class="kpi-glow"></div>
            </div>

            <!-- Aniversários no Mês -->
            <div class="kpi-card kpi-warning">
                <div class="kpi-icon-wrapper">
                    <div class="kpi-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                            <path d="M12 14l2 2-2 2-2-2 2-2z"/>
                        </svg>
                    </div>
                    <div class="kpi-pulse"></div>
                </div>
                <div class="kpi-content">
                    <span class="kpi-label">Aniversários no Mês</span>
                    <h2 class="kpi-value" id="kpi-aniversarios">0</h2>
                    <span class="kpi-subtitle">renovações este mês</span>
                </div>
                <div class="kpi-glow"></div>
            </div>

            <!-- Próximos Aniversários -->
            <div class="kpi-card kpi-danger">
                <div class="kpi-icon-wrapper">
                    <div class="kpi-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div class="kpi-pulse"></div>
                </div>
                <div class="kpi-content">
                    <span class="kpi-label">Próximos 30 Dias</span>
                    <h2 class="kpi-value" id="kpi-proximos">0</h2>
                    <span class="kpi-subtitle">aniversários próximos</span>
                </div>
                <div class="kpi-glow"></div>
            </div>

            <!-- Valor da Carteira -->
            <div class="kpi-card kpi-success">
                <div class="kpi-icon-wrapper">
                    <div class="kpi-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="kpi-pulse"></div>
                </div>
                <div class="kpi-content">
                    <span class="kpi-label">Valor da Carteira</span>
                    <h2 class="kpi-value kpi-value-money" id="kpi-valor">R$ 0,00</h2>
                    <span class="kpi-subtitle">recorrência mensal</span>
                </div>
                <div class="kpi-glow"></div>
            </div>

            <!-- Aguardando Boas Vindas -->
            <div class="kpi-card kpi-info">
                <div class="kpi-icon-wrapper">
                    <div class="kpi-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </div>
                    <div class="kpi-pulse"></div>
                </div>
                <div class="kpi-content">
                    <span class="kpi-label">Aguardando Boas Vindas</span>
                    <h2 class="kpi-value" id="kpi-boas-vindas">0</h2>
                    <span class="kpi-subtitle">pendentes de envio</span>
                </div>
                <div class="kpi-glow"></div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="pv-filters-card">
            <div class="filters-row">
                <div class="filter-item">
                    <label>Operadora</label>
                    <select id="filter-operadora" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        @foreach ($operadoras as $operadora)
                            <option value="{{ $operadora }}">{{ $operadora }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-item">
                    <label>Vendedor</label>
                    <select id="filter-vendedor" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach ($vendedores as $vendedor)
                            <option value="{{ $vendedor->id }}">{{ $vendedor->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-item">
                    <label>Mês Aniversário</label>
                    <select id="filter-mes" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="1">Janeiro</option>
                        <option value="2">Fevereiro</option>
                        <option value="3">Março</option>
                        <option value="4">Abril</option>
                        <option value="5">Maio</option>
                        <option value="6">Junho</option>
                        <option value="7">Julho</option>
                        <option value="8">Agosto</option>
                        <option value="9">Setembro</option>
                        <option value="10">Outubro</option>
                        <option value="11">Novembro</option>
                        <option value="12">Dezembro</option>
                    </select>
                </div>

                <div class="filter-item">
                    <label>Boas Vindas</label>
                    <select id="filter-boas-vindas" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="pendente">Pendentes</option>
                        <option value="enviado">Enviados</option>
                    </select>
                </div>

                <div class="filter-item filter-search">
                    <label>Buscar</label>
                    <div class="search-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input type="text" id="filter-busca" class="form-control form-control-sm" placeholder="Nome, proposta...">
                    </div>
                </div>

                <div class="filter-item filter-actions">
                    <button type="button" id="btn-clear-filters" class="btn btn-clear">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="1 4 1 10 7 10"/>
                            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                        </svg>
                        Limpar
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div class="pv-loading" id="pv-loading">
            <div class="spinner-wrapper">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p>Carregando contratos...</p>
            </div>
        </div>

        <!-- Table Section -->
        <div class="pv-table-card" id="pv-table-card" style="display: none;">
            <div class="table-header">
                <div class="table-title-group">
                    <div class="table-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="table-title">Contratos Implantados</h3>
                        <span class="table-subtitle">Lista completa de contratos ativos</span>
                    </div>
                </div>
                <div class="table-badge">
                    <span id="table-count">0</span> contratos
                </div>
            </div>

            <div class="table-body">
                <table class="pv-table" id="contratos-table">
                    <thead>
                        <tr>
                            <th>Contrato</th>
                            <th>Vendedor</th>
                            <th>Operadora</th>
                            <th>Valor</th>
                            <th>Implantado</th>
                            <th>Aniversário</th>
                            <th>Boas Vindas</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="contratos-tbody">
                        <!-- Data loaded via JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div class="pv-empty-state" id="pv-empty-state" style="display: none;">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </div>
                <h4>Nenhum contrato encontrado</h4>
                <p>Não há contratos implantados com os filtros selecionados.</p>
            </div>

            <!-- Pagination -->
            <div class="pv-pagination" id="pv-pagination" style="display: none;">
                <div class="pagination-info">
                    <span>Mostrando <strong id="pagination-from">0</strong> - <strong id="pagination-to">0</strong> de <strong id="pagination-total">0</strong> contratos</span>
                </div>
                <div class="pagination-controls">
                    <div class="per-page-select">
                        <label>Por página:</label>
                        <select id="per-page" class="form-select form-select-sm">
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="pagination-buttons">
                        <button type="button" class="btn-page btn-first" id="btn-first" title="Primeira página">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="11 17 6 12 11 7"/>
                                <polyline points="18 17 13 12 18 7"/>
                            </svg>
                        </button>
                        <button type="button" class="btn-page btn-prev" id="btn-prev" title="Página anterior">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"/>
                            </svg>
                        </button>
                        <div class="page-indicator">
                            <span id="current-page">1</span>
                            <span class="separator">/</span>
                            <span id="total-pages">1</span>
                        </div>
                        <button type="button" class="btn-page btn-next" id="btn-next" title="Próxima página">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </button>
                        <button type="button" class="btn-page btn-last" id="btn-last" title="Última página">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="13 17 18 12 13 7"/>
                                <polyline points="6 17 11 12 6 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Timeline Anotações Pós-Venda -->
    <!-- Modal Anotações Pós-Venda -->
    <div class="modal fade" id="modalAnotacoes" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content pv-modal-modern">
                <div class="pv-modal-header pv-modal-header-success">
                    <div class="pv-modal-header-content">
                        <div class="pv-modal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                        </div>
                        <div class="pv-modal-title-group">
                            <h5 class="pv-modal-title">Anotações Pós-Venda</h5>
                            <span class="pv-modal-subtitle">Registre tratativas e interações com o cliente</span>
                        </div>
                    </div>
                    <button type="button" class="pv-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="pv-modal-body pv-modal-body-flush">
                    <input type="hidden" id="anotacao-venda-id">

                    <!-- Contract Info Header -->
                    <div class="pv-contract-info" id="anotacao-contrato-info">
                        <div class="pv-contract-name" id="anotacao-contrato-nome">-</div>
                        <div class="pv-contract-detail" id="anotacao-contrato-detalhe">-</div>
                    </div>

                    <!-- Nova Anotação Form -->
                    <div class="pv-form-section">
                        <div class="pv-form-section-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Nova Anotação
                        </div>
                        <textarea class="pv-form-textarea" id="anotacao-texto" rows="3" placeholder="Descreva a tratativa realizada... Ex: Marquei consulta para o cliente, Enviei segunda via do boleto, etc."></textarea>
                        <div class="pv-form-actions">
                            <button type="button" class="pv-btn pv-btn-success" id="btn-salvar-anotacao">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                Salvar Anotação
                            </button>
                        </div>
                    </div>

                    <!-- Timeline Divider -->
                    <div class="pv-timeline-divider">
                        <span>Histórico de Tratativas</span>
                    </div>

                    <!-- Timeline Content -->
                    <div class="pv-timeline-wrapper" id="anotacoes-timeline-wrapper">
                        <!-- Loading State -->
                        <div class="pv-timeline-loading" id="timeline-loading">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <span>Carregando anotações...</span>
                        </div>

                        <!-- Empty State -->
                        <div class="pv-timeline-empty" id="timeline-empty" style="display: none;">
                            <div class="pv-empty-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                            </div>
                            <p>Nenhuma anotação registrada</p>
                            <span>Adicione a primeira tratativa acima</span>
                        </div>

                        <!-- Timeline List -->
                        <div class="anotacoes-timeline" id="anotacoes-timeline" style="display: none;">
                            <!-- Items loaded via JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Histórico -->
    <div class="modal fade" id="modalHistorico" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content pv-modal-modern">
                <div class="pv-modal-header pv-modal-header-info">
                    <div class="pv-modal-header-content">
                        <div class="pv-modal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div class="pv-modal-title-group">
                            <h5 class="pv-modal-title">Histórico do Contrato</h5>
                            <span class="pv-modal-subtitle">Acompanhe todas as movimentações</span>
                        </div>
                    </div>
                    <button type="button" class="pv-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="pv-modal-body pv-modal-body-flush" id="historico-content">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Boas Vindas -->
    <div class="modal fade" id="modalBoasVindas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content pv-modal-modern">
                <div class="pv-modal-header pv-modal-header-info">
                    <div class="pv-modal-header-content">
                        <div class="pv-modal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </div>
                        <div class="pv-modal-title-group">
                            <h5 class="pv-modal-title">Registrar Boas Vindas</h5>
                            <span class="pv-modal-subtitle">Confirme o envio da mensagem ao cliente</span>
                        </div>
                    </div>
                    <button type="button" class="pv-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="pv-modal-body">
                    <input type="hidden" id="boas-vindas-venda-id">

                    <!-- Dados do Contrato -->
                    <div class="pv-contract-summary">
                        <div class="pv-summary-row">
                            <span class="pv-summary-label">Contrato:</span>
                            <span class="pv-summary-value" id="bv-contrato-nome">-</span>
                        </div>
                        <div class="pv-summary-row">
                            <span class="pv-summary-label">Operadora:</span>
                            <span class="pv-summary-value" id="bv-operadora">-</span>
                        </div>
                        <div class="pv-summary-row">
                            <span class="pv-summary-label">Plano:</span>
                            <span class="pv-summary-value" id="bv-plano">-</span>
                        </div>
                        <div class="pv-summary-row">
                            <span class="pv-summary-label">Implantado em:</span>
                            <span class="pv-summary-value" id="bv-data-implantacao">-</span>
                        </div>
                    </div>

                    <div class="pv-form-group mt-3">
                        <label for="boas-vindas-observacao" class="pv-form-label">Observação (opcional)</label>
                        <textarea class="pv-form-textarea" id="boas-vindas-observacao" rows="2" placeholder="Ex: Enviado via WhatsApp, Email enviado com carteirinha..."></textarea>
                    </div>

                    <div class="pv-modal-actions">
                        <button type="button" class="pv-btn pv-btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="pv-btn pv-btn-info" id="btn-confirmar-boas-vindas">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Confirmar Envio
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Alterar Data de Implantação -->
    <div class="modal fade" id="modalDataImplantacao" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content pv-modal-modern">
                <div class="pv-modal-header pv-modal-header-primary">
                    <div class="pv-modal-header-content">
                        <div class="pv-modal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>
                        <div class="pv-modal-title-group">
                            <h5 class="pv-modal-title">Alterar Data</h5>
                            <span class="pv-modal-subtitle">Data de implantação do contrato</span>
                        </div>
                    </div>
                    <button type="button" class="pv-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="pv-modal-body">
                    <input type="hidden" id="data-implantacao-venda-id">
                    <div class="pv-form-group">
                        <label for="nova-data-implantacao" class="pv-form-label">Nova Data de Implantação</label>
                        <input type="date" class="pv-form-input" id="nova-data-implantacao" required>
                    </div>
                    <div class="pv-modal-actions">
                        <button type="button" class="pv-btn pv-btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="pv-btn pv-btn-primary" id="btn-salvar-data-implantacao">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Salvar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
