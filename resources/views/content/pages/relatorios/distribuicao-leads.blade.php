@extends('layouts/layoutMaster')

@section('title', 'Relatório de Distribuição de Leads')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/distribuicao-leads.js'])
@endsection

@section('page-style')
<style>
    .metric-card {
        border-radius: 12px;
        border: none;
        padding: 1.25rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        height: 100%;
    }

    [data-theme="light"] .metric-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    [data-theme="dark"] .metric-card {
        background: #2d3748;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }

    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.12);
    }

    .metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
    }

    .metric-card.primary::before { background: linear-gradient(90deg, #5a67d8, #667eea); }
    .metric-card.success::before { background: linear-gradient(90deg, #28a745, #20c997); }
    .metric-card.warning::before { background: linear-gradient(90deg, #ffc107, #fd7e14); }
    .metric-card.danger::before { background: linear-gradient(90deg, #dc3545, #e83e8c); }
    .metric-card.info::before { background: linear-gradient(90deg, #17a2b8, #20c997); }
    .metric-card.secondary::before { background: linear-gradient(90deg, #6c757d, #868e96); }

    .metric-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 0.75rem;
    }

    .metric-card.primary .metric-icon {
        background: linear-gradient(135deg, rgba(90, 103, 216, 0.1), rgba(102, 126, 234, 0.15));
        color: #5a67d8;
    }

    .metric-card.success .metric-icon {
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.15));
        color: #28a745;
    }

    .metric-card.warning .metric-icon {
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(253, 126, 20, 0.15));
        color: #ffc107;
    }

    .metric-card.danger .metric-icon {
        background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(232, 62, 140, 0.15));
        color: #dc3545;
    }

    .metric-card.info .metric-icon {
        background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(32, 201, 151, 0.15));
        color: #17a2b8;
    }

    .metric-card.secondary .metric-icon {
        background: linear-gradient(135deg, rgba(108, 117, 125, 0.1), rgba(134, 142, 150, 0.15));
        color: #6c757d;
    }

    .metric-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        line-height: 1.2;
    }

    .metric-label {
        font-size: 0.875rem;
        opacity: 0.8;
        font-weight: 500;
    }

    .modern-card {
        border-radius: 16px;
        border: none;
        transition: all 0.3s ease;
    }

    [data-theme="light"] .modern-card {
        background: white;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }

    [data-theme="dark"] .modern-card {
        background: #2d3748;
        box-shadow: 0 2px 12px rgba(0,0,0,0.3);
    }

    .chart-container {
        min-height: 380px;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .chart-container > div {
        width: 100%;
        height: 100%;
    }

    .chart-container .text-muted {
        font-size: 14px;
    }

    /* Animação suave nos cards */
    .metric-card {
        animation: fadeInUp 0.5s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Delay para animação em cascata */
    .col-lg-2:nth-child(1) .metric-card { animation-delay: 0.05s; }
    .col-lg-2:nth-child(2) .metric-card { animation-delay: 0.1s; }
    .col-lg-2:nth-child(3) .metric-card { animation-delay: 0.15s; }
    .col-lg-2:nth-child(4) .metric-card { animation-delay: 0.2s; }
    .col-lg-2:nth-child(5) .metric-card { animation-delay: 0.25s; }
    .col-lg-2:nth-child(6) .metric-card { animation-delay: 0.3s; }
</style>
@endsection

@section('content')
    <!-- Filtros -->
    <div class="card modern-card mb-4">
        <div class="card-header">
            <h4 class="card-title mb-0">
                <i class="ri-pie-chart-line me-2"></i>Relatório de Distribuição de Leads
            </h4>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Período</label>
                    <input type="text" id="filtro_periodo" class="form-control flatpickr-range" placeholder="Selecione o período">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" id="btn_filtrar" class="btn btn-primary me-2 w-100">
                        <i class="ri-filter-line me-1"></i>Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de Métricas -->
    <div class="row g-3 mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card metric-card primary">
                <div class="metric-icon">
                    <i class="ri-database-2-line"></i>
                </div>
                <div class="metric-value" id="total-leads">0</div>
                <div class="metric-label">Total de Leads</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card metric-card success">
                <div class="metric-icon">
                    <i class="ri-user-follow-line"></i>
                </div>
                <div class="metric-value" id="leads-distribuidos">0</div>
                <div class="metric-label">Distribuídos (Total)</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card metric-card info">
                <div class="metric-icon">
                    <i class="ri-briefcase-line"></i>
                </div>
                <div class="metric-value" id="leads-comercial">0</div>
                <div class="metric-label">Com Vendedores</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card metric-card warning">
                <div class="metric-icon">
                    <i class="ri-file-list-line"></i>
                </div>
                <div class="metric-value" id="leads-administrativo">0</div>
                <div class="metric-label">Administrativo</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card metric-card info">
                <div class="metric-icon">
                    <i class="ri-phone-line"></i>
                </div>
                <div class="metric-value" id="leads-preditiva">0</div>
                <div class="metric-label">Na Preditiva</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card metric-card danger">
                <div class=" metric-icon">
                    <i class="ri-close-circle-line"></i>
                </div>
                <div class="metric-value" id="leads-descartados">0</div>
                <div class="metric-label">Desativados</div>
            </div>
        </div>
    </div>

    <!-- Primeira Linha - Visão Geral -->
    <div class="row g-3 mb-4">
        <!-- Gráfico Donut - Distribuição Geral -->
        <div class="col-lg-5 col-md-12">
            <div class="card modern-card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-pie-chart-2-line me-2 text-primary"></i>Distribuição Geral de Leads
                    </h5>
                </div>
                <div class="card-body">
                    <div id="chart-distribuicao-geral" class="chart-container"></div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Barras Horizontal - Motivos de Descarte -->
        <div class="col-lg-7 col-md-12">
            <div class="card modern-card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-bar-chart-horizontal-line me-2 text-purple"></i>Motivos de Descarte
                    </h5>
                </div>
                <div class="card-body">
                    <div id="chart-motivos-descarte" class="chart-container"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Segunda Linha - Detalhamento por Status -->
    <div class="row g-3 mb-4">
        <!-- Gráfico de Barras - Leads Comerciais -->
        <div class="col-lg-4 col-md-6">
            <div class="card modern-card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-bar-chart-grouped-line me-2 text-info"></i>Leads Comerciais
                    </h5>
                    <small class="text-muted">Por status de tabulação</small>
                </div>
                <div class="card-body">
                    <div id="chart-comercial" class="chart-container"></div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Barras - Leads Administrativos -->
        <div class="col-lg-4 col-md-6">
            <div class="card modern-card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-bar-chart-grouped-line me-2 text-warning"></i>Leads Administrativos
                    </h5>
                    <small class="text-muted">Por status de tabulação</small>
                </div>
                <div class="card-body">
                    <div id="chart-administrativo" class="chart-container"></div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Barras - Leads Descarte -->
        <div class="col-lg-4 col-md-12">
            <div class="card modern-card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-bar-chart-grouped-line me-2 text-danger"></i>Leads Descartados
                    </h5>
                    <small class="text-muted">Por categoria de descarte</small>
                </div>
                <div class="card-body">
                    <div id="chart-descarte" class="chart-container"></div>
                </div>
            </div>
        </div>
    </div>

@endsection
