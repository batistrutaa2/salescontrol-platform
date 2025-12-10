@extends('layouts/layoutMaster')

@section('title', 'Dashboard Financeiro - Recebíveis')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'
    ])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/relatorio-financeiro.scss'])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/apex-charts/apexcharts.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/relatorio-financeiro.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y financeiro-wrapper">

    <!-- Header -->
    <div class="financeiro-header">
        <div class="header-content">
            <div class="header-text">
                <span class="page-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    Financeiro
                </span>
                <h1 class="main-title">Dashboard de Recebíveis</h1>
                <p class="subtitle">Análise completa dos recebíveis e performance financeira</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-export" id="btnExportar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Exportar Relatório
                </button>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filter-card">
        <form id="filtroRelatorio">
            @csrf
            <div class="filter-row">
                <div class="filter-group">
                    <label class="filter-label">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Data Inicial
                    </label>
                    <input type="text" id="data_inicial" name="data_inicial" class="filter-input flatpickr-date" placeholder="dd/mm/yyyy">
                </div>
                <div class="filter-group">
                    <label class="filter-label">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Data Final
                    </label>
                    <input type="text" id="data_final" name="data_final" class="filter-input flatpickr-date" placeholder="dd/mm/yyyy">
                </div>
                <div class="filter-group">
                    <label class="filter-label">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        Operadora
                    </label>
                    <select id="operadora_id" name="operadora_id" class="filter-select">
                        <option value="">Todas as Operadoras</option>
                        @foreach($operadoras as $op)
                            <option value="{{ $op->id }}">{{ $op->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group filter-group-sm">
                    <label class="filter-label">&nbsp;</label>
                    <button type="button" id="btnFiltrar" class="btn-filter">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <!-- Total Previsto -->
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total Previsto</span>
                <h2 class="kpi-value" id="totalPrevisto">R$ 0,00</h2>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <!-- Total Recebido -->
        <div class="kpi-card kpi-success">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total Recebido</span>
                <h2 class="kpi-value" id="totalRecebido">R$ 0,00</h2>
                <div class="kpi-trend trend-up" id="taxaRecebimento">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        <polyline points="17 6 23 6 23 12"></polyline>
                    </svg>
                    <span>0%</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <!-- Em Aberto -->
        <div class="kpi-card kpi-warning">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Em Aberto</span>
                <h2 class="kpi-value" id="totalAberto">R$ 0,00</h2>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <!-- Em Atraso -->
        <div class="kpi-card kpi-danger">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Em Atraso</span>
                <h2 class="kpi-value" id="totalAtraso">R$ 0,00</h2>
            </div>
            <div class="kpi-glow"></div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <!-- Row 1: Evolução Mensal + Status -->
        <div class="chart-row">
            <div class="chart-card chart-large">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <div class="chart-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>
                        </div>
                        <div>
                            <h3 class="chart-title">Evolução Mensal</h3>
                            <span class="chart-subtitle">Acompanhamento de recebíveis ao longo do tempo</span>
                        </div>
                    </div>
                    <div class="chart-legend">
                        <span class="legend-item">
                            <span class="legend-dot primary"></span>
                            Previsto
                        </span>
                        <span class="legend-item">
                            <span class="legend-dot success"></span>
                            Recebido
                        </span>
                        <span class="legend-item">
                            <span class="legend-dot warning"></span>
                            Em Aberto
                        </span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="chartEvolucaoMensal"></div>
                </div>
            </div>

            <div class="chart-card chart-medium">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <div class="chart-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                                <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="chart-title">Distribuição por Status</h3>
                            <span class="chart-subtitle">Visão geral dos recebíveis</span>
                        </div>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="chartStatusDistribuicao"></div>
                </div>
            </div>
        </div>

        <!-- Row 2: Por Operadora + Top Vendedores -->
        <div class="chart-row">
            <div class="chart-card chart-large">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <div class="chart-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </div>
                        <div>
                            <h3 class="chart-title">Recebíveis por Operadora</h3>
                            <span class="chart-subtitle">Comparativo entre operadoras</span>
                        </div>
                    </div>
                    <div class="chart-legend">
                        <span class="legend-item">
                            <span class="legend-dot primary"></span>
                            Previsto
                        </span>
                        <span class="legend-item">
                            <span class="legend-dot success"></span>
                            Recebido
                        </span>
                        <span class="legend-item">
                            <span class="legend-dot warning"></span>
                            Em Aberto
                        </span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="chartPorOperadora"></div>
                </div>
            </div>

            <div class="chart-card chart-medium">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <div class="chart-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path>
                                <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path>
                                <path d="M4 22h16"></path>
                                <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path>
                                <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path>
                                <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="chart-title">Top 10 Vendedores</h3>
                            <span class="chart-subtitle">Ranking por valor recebido</span>
                        </div>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="chartTopVendedores"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela Detalhada -->
    <div class="table-card">
        <div class="table-header">
            <div class="table-title-group">
                <div class="table-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3h18v18H3z"></path>
                        <path d="M21 9H3"></path>
                        <path d="M21 15H3"></path>
                        <path d="M9 3v18"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="table-title">Detalhamento por Operadora</h3>
                    <span class="table-subtitle">Análise detalhada dos valores</span>
                </div>
            </div>
        </div>
        <div class="table-body">
            <table class="custom-table" id="relatorioTable">
                <thead>
                    <tr>
                        <th>Operadora</th>
                        <th class="text-end">Previsto</th>
                        <th class="text-end">Recebido</th>
                        <th class="text-end">Em Aberto</th>
                        <th class="text-end">Cancelado</th>
                        <th class="text-center">Taxa (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Dados via AJAX -->
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
