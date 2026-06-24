@extends('layouts/layoutMaster')

@section('title', 'Dashboard')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/toastr/toastr.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
    ])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/dashboard-analytics.scss')
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/toastr/toastr.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/apex-charts/apexcharts.js'
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/dashboard-analytics.js'])
@endsection

@section('content')
<div class="dashboard-wrapper">
    {{-- Header Section --}}
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-text">
                <span class="greeting-label">Dashboard</span>
                <h1 class="main-title">Visao Geral de Vendas</h1>
                <p class="subtitle">Acompanhe o desempenho da sua equipe em tempo real</p>
            </div>
            <div class="header-filters">
                <div class="filter-group">
                    <div class="filter-item">
                        <label class="filter-label">Periodo</label>
                        <select class="filter-select" id="select-month">
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
                    <div class="filter-item">
                        <label class="filter-label">Ano</label>
                        <select class="filter-select" id="select-year">
                            <option value="2026">2026</option>
                            <option value="2025">2025</option>
                            <option value="2024">2024</option>
                            <option value="2023">2023</option>
                            <option value="2022">2022</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards Section --}}
    <div class="kpi-grid">
        <div class="kpi-card kpi-primary" data-aos="fade-up" data-aos-delay="0">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Contratos Cadastrados</span>
                <h2 class="kpi-value js-valorCadastrado">R$ 0,00</h2>
                <div class="kpi-trend trend-up">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    <span class="js-trend-cadastrado">--</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-warning" data-aos="fade-up" data-aos-delay="100">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Angariacao</span>
                <h2 class="kpi-value js-angariacao">R$ 0,00</h2>
                <div class="kpi-trend trend-up">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    <span class="js-trend-angariacao">--</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-success" data-aos="fade-up" data-aos-delay="200">
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
                <span class="kpi-label">Contratos Implantados</span>
                <h2 class="kpi-value js-implantado">R$ 0,00</h2>
                <div class="kpi-trend trend-up">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    <span class="js-trend-implantado">--</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-info" data-aos="fade-up" data-aos-delay="300">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Contatos Importados</span>
                <h2 class="kpi-value js-quantidadeContatosImportados">0</h2>
                <div class="kpi-trend trend-neutral">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <span>Novos leads</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="charts-section">
        <div class="chart-row">
            {{-- Main Sales Chart --}}
            <div class="chart-card chart-large" data-aos="fade-up" data-aos-delay="100">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Performance de Vendas</h3>
                        <span class="chart-subtitle">Valor total por vendedor</span>
                    </div>
                    <div class="chart-legend">
                        <span class="legend-item">
                            <span class="legend-dot primary"></span>
                            Cadastradas
                        </span>
                        <span class="legend-item">
                            <span class="legend-dot warning"></span>
                            Angariacao
                        </span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="salesPerformanceChart"></div>
                </div>
            </div>

            {{-- Conversion Funnel --}}
            <div class="chart-card chart-medium" data-aos="fade-up" data-aos-delay="200">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Funil de Conversao</h3>
                        <span class="chart-subtitle">Progresso do pipeline</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="conversionFunnelChart"></div>
                </div>
            </div>
        </div>

        <div class="chart-row">
            {{-- Leads Import Chart --}}
            <div class="chart-card chart-half" data-aos="fade-up" data-aos-delay="100">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Leads Importados</h3>
                        <span class="chart-subtitle">Novos contatos por vendedor</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="leadsImportChart"></div>
                </div>
            </div>

            {{-- Transfer Chart --}}
            <div class="chart-card chart-half" data-aos="fade-up" data-aos-delay="200">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Transferencia de Leads</h3>
                        <span class="chart-subtitle">Movimentacao entre vendedores</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="leadsTransferChart"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Tables Section --}}
    <div class="tables-section">
        <div class="tables-row">
            <div class="table-card" data-aos="fade-up" data-aos-delay="100">
                <div class="table-header">
                    <div class="table-title-group">
                        <div class="table-icon cadastrados">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10 9 9 9 8 9"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="table-title">Contratos Cadastrados</h3>
                            <span class="table-subtitle">Lista detalhada do periodo</span>
                        </div>
                    </div>
                    <div class="table-badge cadastrados">
                        <span class="js-count-cadastrados">0</span> contratos
                    </div>
                </div>
                <div class="table-body">
                    <table id="tableContratosCadastrados" class="custom-table">
                        <thead>
                            <tr>
                                <th>Nome do Contrato</th>
                                <th class="text-end">Valor</th>
                                <th class="text-end">Angariacao</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="table-card" data-aos="fade-up" data-aos-delay="200">
                <div class="table-header">
                    <div class="table-title-group">
                        <div class="table-icon implantados">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="table-title">Contratos Implantados</h3>
                            <span class="table-subtitle">Contratos ativos no periodo</span>
                        </div>
                    </div>
                    <div class="table-badge implantados">
                        <span class="js-count-implantados">0</span> contratos
                    </div>
                </div>
                <div class="table-body">
                    <table id="tableContratosImplantados" class="custom-table">
                        <thead>
                            <tr>
                                <th>Nome do Contrato</th>
                                <th class="text-end">Valor</th>
                                <th class="text-end">Angariacao</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
