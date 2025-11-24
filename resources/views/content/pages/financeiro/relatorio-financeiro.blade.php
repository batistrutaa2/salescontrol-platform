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

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/apex-charts/apexcharts.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
    ])
@endsection

@section('page-style')
    <style>
        .metric-card {
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .metric-value {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.2;
            margin: 8px 0 4px;
        }
        .metric-label {
            font-size: 0.875rem;
            opacity: 0.8;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .metric-trend {
            font-size: 0.75rem;
            margin-top: 4px;
        }
        .chart-container {
            border-radius: 12px;
            overflow: hidden;
        }
        .filter-card {
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .loading-state {
            animation: pulse 1.5s ease-in-out infinite;
        }
    </style>
@endsection

@section('page-script')
    @vite(['resources/assets/js/relatorio-financeiro.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="ri-dashboard-3-line me-2"></i>Dashboard Financeiro
            </h4>
            <p class="text-muted mb-0">Análise completa dos recebíveis da empresa</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" id="btnExportar">
                <i class="ri-download-2-line me-1"></i>Exportar Relatório
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card filter-card mb-4">
        <div class="card-body">
            <form id="filtroRelatorio" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label for="data_inicial" class="form-label small fw-semibold">
                        <i class="ri-calendar-line me-1"></i>Data Inicial
                    </label>
                    <input type="text" id="data_inicial" name="data_inicial" class="form-control flatpickr-date" placeholder="dd/mm/yyyy">
                </div>
                <div class="col-md-3">
                    <label for="data_final" class="form-label small fw-semibold">
                        <i class="ri-calendar-line me-1"></i>Data Final
                    </label>
                    <input type="text" id="data_final" name="data_final" class="form-control flatpickr-date" placeholder="dd/mm/yyyy">
                </div>
                <div class="col-md-4">
                    <label for="operadora_id" class="form-label small fw-semibold">
                        <i class="ri-building-line me-1"></i>Operadora
                    </label>
                    <select id="operadora_id" name="operadora_id" class="form-select">
                        <option value="">Todas as Operadoras</option>
                        @foreach($operadoras as $op)
                            <option value="{{ $op->id }}">{{ $op->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" id="btnFiltrar" class="btn btn-primary w-100">
                        <i class="ri-filter-3-line me-1"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card metric-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon bg-label-primary text-primary me-3">
                            <i class="ri-money-dollar-circle-line"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="metric-label text-muted">Total Previsto</div>
                            <div class="metric-value text-primary" id="totalPrevisto">R$ 0,00</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card metric-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon bg-label-success text-success me-3">
                            <i class="ri-check-double-line"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="metric-label text-muted">Recebido</div>
                            <div class="metric-value text-success" id="totalRecebido">R$ 0,00</div>
                            <div class="metric-trend text-success" id="taxaRecebimento">
                                <i class="ri-arrow-up-line"></i>0%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card metric-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon bg-label-warning text-warning me-3">
                            <i class="ri-time-line"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="metric-label text-muted">Em Aberto</div>
                            <div class="metric-value text-warning" id="totalAberto">R$ 0,00</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card metric-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon bg-label-danger text-danger me-3">
                            <i class="ri-alarm-warning-line"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="metric-label text-muted">Em Atraso</div>
                            <div class="metric-value text-danger" id="totalAtraso">R$ 0,00</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row g-3 mb-4">
        <!-- Evolução Mensal -->
        <div class="col-xl-8">
            <div class="card chart-container shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ri-line-chart-line me-2 text-primary"></i>Evolução Mensal
                    </h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-text-secondary rounded-pill btn-icon" type="button" data-bs-toggle="dropdown">
                            <i class="ri-more-2-line"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="javascript:void(0);">Ver Detalhes</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Exportar Dados</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div id="chartEvolucaoMensal"></div>
                </div>
            </div>
        </div>

        <!-- Status Distribution -->
        <div class="col-xl-4">
            <div class="card chart-container shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ri-pie-chart-line me-2 text-primary"></i>Distribuição por Status
                    </h5>
                </div>
                <div class="card-body">
                    <div id="chartStatusDistribuicao"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row g-3 mb-4">
        <!-- Por Operadora -->
        <div class="col-xl-7">
            <div class="card chart-container shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ri-building-line me-2 text-primary"></i>Recebíveis por Operadora
                    </h5>
                </div>
                <div class="card-body">
                    <div id="chartPorOperadora"></div>
                </div>
            </div>
        </div>

        <!-- Top Vendedores -->
        <div class="col-xl-5">
            <div class="card chart-container shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ri-trophy-line me-2 text-primary"></i>Top 10 Vendedores
                    </h5>
                </div>
                <div class="card-body">
                    <div id="chartTopVendedores"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela Detalhada -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="ri-table-line me-2 text-primary"></i>Detalhamento por Operadora
            </h5>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table table-hover" id="relatorioTable">
                <thead class="table-light">
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
