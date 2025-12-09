@extends('layouts/layoutMaster')

@section('title', 'Backoffice - Relatorio de Performance')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'
    ])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/backoffice-performance.scss')
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/apex-charts/apexcharts.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/backoffice-performance.js'])
@endsection

@section('content')
<div class="backoffice-performance-wrapper">
    {{-- Header Section --}}
    <div class="performance-header">
        <div class="header-content">
            <div class="header-text">
                <span class="section-label">Backoffice</span>
                <h1 class="main-title">Relatorio de Performance</h1>
                <p class="subtitle">Analise de gargalos, tempos e eficiencia do processo</p>
            </div>
            <div class="header-filters">
                <div class="filter-group">
                    <div class="filter-item">
                        <label class="filter-label">Data Inicio</label>
                        <input type="text" class="filter-input flatpickr-date" id="data-inicio" placeholder="dd/mm/aaaa">
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">Data Fim</label>
                        <input type="text" class="filter-input flatpickr-date" id="data-fim" placeholder="dd/mm/aaaa">
                    </div>
                    <button class="btn-filter-apply" id="btn-aplicar-filtro">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                        </svg>
                        Aplicar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="kpi-grid">
        <div class="kpi-card kpi-total">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total de Vendas</span>
                <h2 class="kpi-value js-total-vendas">0</h2>
                <span class="kpi-detail js-total-valor">R$ 0,00</span>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-success">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Implantados</span>
                <h2 class="kpi-value js-implantadas">0</h2>
                <span class="kpi-detail kpi-rate js-taxa-conversao">0% taxa de conversao</span>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-warning">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Em Andamento</span>
                <h2 class="kpi-value js-em-andamento">0</h2>
                <span class="kpi-detail">contratos no pipeline</span>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-time">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Tempo Medio</span>
                <h2 class="kpi-value js-tempo-medio">0 dias</h2>
                <span class="kpi-detail">ate implantacao</span>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-danger">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Perdidos</span>
                <h2 class="kpi-value js-perdidas">0</h2>
                <span class="kpi-detail kpi-loss js-valor-perdido">R$ 0,00 em contratos</span>
            </div>
            <div class="kpi-glow"></div>
        </div>
    </div>

    {{-- Pipeline Section --}}
    <div class="pipeline-section">
        <div class="section-header">
            <div class="section-title-group">
                <h2 class="section-title">Pipeline do Backoffice</h2>
                <span class="section-subtitle">Fluxo de processamento dos contratos</span>
            </div>
        </div>
        <div class="pipeline-container">
            <div class="pipeline-track">
                <div class="pipeline-stage" data-stage="venda">
                    <div class="stage-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/>
                            <path d="M12 18V6"/>
                        </svg>
                    </div>
                    <span class="stage-name">Venda</span>
                    <span class="stage-count js-pipeline-venda">0</span>
                </div>
                <div class="pipeline-connector"></div>
                <div class="pipeline-stage" data-stage="analise_docs">
                    <div class="stage-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <path d="M14 2v6h6"/>
                            <path d="M16 13H8"/>
                            <path d="M16 17H8"/>
                        </svg>
                    </div>
                    <span class="stage-name">Analise Docs</span>
                    <span class="stage-count js-pipeline-analise-docs">0</span>
                </div>
                <div class="pipeline-connector"></div>
                <div class="pipeline-stage" data-stage="analise_operadora">
                    <div class="stage-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                        </svg>
                    </div>
                    <span class="stage-name">Analise Operadora</span>
                    <span class="stage-count js-pipeline-analise-operadora">0</span>
                </div>
                <div class="pipeline-connector"></div>
                <div class="pipeline-stage" data-stage="contrato_gerado">
                    <div class="stage-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </div>
                    <span class="stage-name">Aguard. Assinatura</span>
                    <span class="stage-count js-pipeline-contrato-gerado">0</span>
                </div>
                <div class="pipeline-connector"></div>
                <div class="pipeline-stage" data-stage="boleto">
                    <div class="stage-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                            <line x1="1" y1="10" x2="23" y2="10"/>
                        </svg>
                    </div>
                    <span class="stage-name">Boleto Disponivel</span>
                    <span class="stage-count js-pipeline-boleto">0</span>
                </div>
                <div class="pipeline-connector"></div>
                <div class="pipeline-stage stage-final" data-stage="implantado">
                    <div class="stage-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <span class="stage-name">Implantado</span>
                    <span class="stage-count js-pipeline-implantado">0</span>
                </div>
            </div>
            {{-- Status Secundarios --}}
            <div class="pipeline-secondary">
                <div class="secondary-stage stage-warning" data-stage="pendencia">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <span>Pendencia</span>
                    <span class="secondary-count js-pipeline-pendencia">0</span>
                </div>
                <div class="secondary-stage stage-info" data-stage="regularizado">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M9 12l2 2 4-4"/>
                    </svg>
                    <span>Regularizado</span>
                    <span class="secondary-count js-pipeline-regularizado">0</span>
                </div>
                <div class="secondary-stage stage-info" data-stage="aguard_ds">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span>Aguard. DS</span>
                    <span class="secondary-count js-pipeline-aguard-ds">0</span>
                </div>
                <div class="secondary-stage stage-danger" data-stage="estorno">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"/>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                    </svg>
                    <span>Estorno</span>
                    <span class="secondary-count js-pipeline-estorno">0</span>
                </div>
                <div class="secondary-stage stage-danger" data-stage="declinado">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                    <span>Declinado</span>
                    <span class="secondary-count js-pipeline-declinado">0</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="charts-section">
        <div class="charts-row">
            {{-- Funil de Conversao --}}
            <div class="chart-card chart-large">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Funil de Conversao</h3>
                        <span class="chart-subtitle">Progressao dos contratos no pipeline</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="funnelChart"></div>
                </div>
            </div>

            {{-- Distribuicao por Status --}}
            <div class="chart-card chart-medium">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Distribuicao por Status</h3>
                        <span class="chart-subtitle">Percentual de cada etapa</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="statusDistributionChart"></div>
                </div>
            </div>
        </div>

        <div class="charts-row">
            {{-- Evolucao Mensal --}}
            <div class="chart-card chart-full">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Evolucao Mensal</h3>
                        <span class="chart-subtitle">Vendas vs Implantacoes</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="monthlyEvolutionChart"></div>
                </div>
            </div>
        </div>

        <div class="charts-row">
            {{-- Performance por Operadora --}}
            <div class="chart-card chart-half">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Performance por Operadora</h3>
                        <span class="chart-subtitle">Volume e taxa de conversao</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="operatorChart"></div>
                </div>
            </div>

            {{-- Performance por Vendedor --}}
            <div class="chart-card chart-half">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Performance por Vendedor</h3>
                        <span class="chart-subtitle">Contratos e implantacoes</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="sellerChart"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottleneck Section --}}
    <div class="bottleneck-section">
        <div class="section-header">
            <div class="section-title-group">
                <div class="section-icon warning">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <div>
                    <h2 class="section-title">Gargalos Identificados</h2>
                    <span class="section-subtitle">Contratos parados ha mais de 3 dias</span>
                </div>
            </div>
            <div class="bottleneck-summary">
                <span class="summary-count js-total-gargalos">0</span>
                <span class="summary-label">contratos travados</span>
            </div>
        </div>
        <div class="bottleneck-table-wrapper">
            <table id="bottleneckTable" class="bottleneck-table">
                <thead>
                    <tr>
                        <th>Contrato</th>
                        <th>Cliente</th>
                        <th>Status Atual</th>
                        <th>Dias Parado</th>
                        <th>Vendedor</th>
                        <th>Operadora</th>
                        <th>Valor</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pendencias Frequentes --}}
    <div class="pendencias-section">
        <div class="section-header">
            <div class="section-title-group">
                <div class="section-icon info">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="16" x2="12" y2="12"/>
                        <line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                </div>
                <div>
                    <h2 class="section-title">Pendencias Mais Frequentes</h2>
                    <span class="section-subtitle">Motivos que mais geram atrasos</span>
                </div>
            </div>
        </div>
        <div class="pendencias-grid" id="pendenciasGrid">
            {{-- Populated via JS --}}
        </div>
    </div>
</div>
@endsection
