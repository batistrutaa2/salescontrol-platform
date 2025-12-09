@extends('layouts/layoutMaster')

@section('title', 'Relatorio de Implantacoes')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/scss/pages/relatorio-implantacoes.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/apex-charts/apexcharts.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/relatorioImplantacoes.js'])
@endsection

@section('content')
<div class="relatorio-implantacoes-wrapper">
    {{-- Header Section --}}
    <div class="ri-header">
        <div class="header-content">
            <div class="header-text">
                <span class="greeting-label">Relatorios</span>
                <h1 class="main-title">Implantacoes</h1>
                <p class="subtitle">Acompanhe os contratos implantados e suas metricas de desempenho</p>
            </div>
            <div class="header-actions">
                <div class="refresh-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <span>Atualizado agora</span>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards Section --}}
    <div class="kpi-grid">
        {{-- Total Contratos --}}
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total de Contratos</span>
                <h2 class="kpi-value" id="totalContratos">0</h2>
                <div class="kpi-trend trend-neutral">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <span>Implantados</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Valor Implantado --}}
        <div class="kpi-card kpi-success">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Valor Implantado</span>
                <h2 class="kpi-value" id="valorTotal">R$ 0,00</h2>
                <div class="kpi-trend trend-up">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    <span>Confirmado</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Total Vidas --}}
        <div class="kpi-card kpi-info">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total de Vidas</span>
                <h2 class="kpi-value" id="totalVidas">0</h2>
                <div class="kpi-trend trend-neutral">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <span>Beneficiarios</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Ticket Medio --}}
        <div class="kpi-card kpi-warning">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Ticket Medio</span>
                <h2 class="kpi-value" id="ticketMedio">R$ 0,00</h2>
                <div class="kpi-trend trend-up">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    <span>Media por contrato</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="filter-card">
        <div class="filter-header">
            <div class="filter-title-group">
                <div class="filter-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                </div>
                <h3 class="filter-title">Filtros</h3>
            </div>
            <button type="button" class="filter-toggle" id="toggleFilters" aria-label="Toggle filters">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="18 15 12 9 6 15"/>
                </svg>
            </button>
        </div>
        <div class="filter-body" id="filterBody">
            <form id="filtrosForm">
                @csrf
                <div class="filter-grid">
                    <div class="filter-group">
                        <label class="filter-label">Ano</label>
                        <select id="filtroAno" name="ano" class="form-select">
                            <option value="">Todos os anos</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Mes</label>
                        <select id="filtroMes" name="mes" class="form-select">
                            <option value="">Todos os meses</option>
                            <option value="1">Janeiro</option>
                            <option value="2">Fevereiro</option>
                            <option value="3">Marco</option>
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
                    <div class="filter-group">
                        <label class="filter-label">Data Inicio</label>
                        <input type="text" id="dataInicio" name="data_inicio" class="form-control flatpickr-date" placeholder="Selecione...">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Data Fim</label>
                        <input type="text" id="dataFim" name="data_fim" class="form-control flatpickr-date" placeholder="Selecione...">
                    </div>
                </div>
                <div class="filter-grid" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="filter-group">
                        <label class="filter-label">Vendedor</label>
                        <select id="filtroVendedor" name="vendedor_id" class="form-select">
                            <option value="">Todos os vendedores</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Operadora</label>
                        <select id="filtroOperadora" name="operadora" class="form-select">
                            <option value="">Todas as operadoras</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-dash btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        Filtrar
                    </button>
                    <button type="button" class="btn-dash btn-secondary" id="limparFiltros">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="1 4 1 10 7 10"/>
                            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                        </svg>
                        Limpar
                    </button>
                    <button type="button" class="btn-dash btn-info" id="atualizarBtn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 4 23 10 17 10"/>
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                        </svg>
                        Atualizar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="charts-section">
        <div class="chart-row">
            <div class="chart-card chart-half">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Implantacoes por Mes</h3>
                        <span class="chart-subtitle">Valor e quantidade mensal</span>
                    </div>
                    <div class="chart-legend">
                        <span class="legend-item">
                            <span class="legend-dot success"></span>
                            Valor
                        </span>
                        <span class="legend-item">
                            <span class="legend-dot info"></span>
                            Quantidade
                        </span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="implantacoesPorMesChart"></div>
                </div>
            </div>

            <div class="chart-card chart-half">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Implantacoes por Vendedor</h3>
                        <span class="chart-subtitle">Ranking de performance</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="implantacoesPorVendedorChart"></div>
                </div>
            </div>
        </div>

        <div class="chart-row">
            <div class="chart-card chart-half">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Implantacoes por Operadora</h3>
                        <span class="chart-subtitle">Distribuicao por parceiro</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="implantacoesPorOperadoraChart"></div>
                </div>
            </div>

            <div class="chart-card chart-half">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Top 10 Planos</h3>
                        <span class="chart-subtitle">Planos mais implantados</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="implantacoesPorPlanoChart"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table Section --}}
    <div class="tables-section">
        <div class="table-card">
            <div class="table-header">
                <div class="table-title-group">
                    <div class="table-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="table-title">Lista de Implantacoes</h3>
                        <span class="table-subtitle">Detalhamento dos contratos implantados</span>
                    </div>
                </div>
                <div class="table-badge" id="tableBadge">
                    <span id="tableCount">0</span> contratos
                </div>
            </div>
            <div class="table-body">
                <table class="custom-table" id="implantacoesTable">
                    <thead>
                        <tr>
                            <th>Data Implantacao</th>
                            <th>Contrato</th>
                            <th>CPF/CNPJ</th>
                            <th>Vendedor</th>
                            <th>Operadora</th>
                            <th>Plano</th>
                            <th>Valor</th>
                            <th>Vidas</th>
                        </tr>
                    </thead>
                    <tbody id="implantacoesTableBody">
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"/>
                                            <line x1="12" y1="8" x2="12" y2="12"/>
                                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                                        </svg>
                                    </div>
                                    <h5 class="empty-title">Carregando dados...</h5>
                                    <p class="empty-description">Aguarde enquanto os dados sao carregados.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Loading Overlay --}}
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <span class="loading-text">Carregando...</span>
    </div>
</div>
@endsection
