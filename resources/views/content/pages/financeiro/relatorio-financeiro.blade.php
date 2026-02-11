@extends('layouts/layoutMaster')

@section('title', 'Visão CFO — Receita & Recebíveis')

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
<div class="container-fluid flex-grow-1 container-p-y financeiro-wrapper">

    {{-- ═══════════════════════════════════════════ --}}
    {{-- Header --}}
    {{-- ═══════════════════════════════════════════ --}}
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
                <h1 class="main-title">Visão CFO — Receita & Recebíveis</h1>
                <p class="subtitle">Painel estratégico de faturamento, inadimplência e projeções</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-export" id="btnExportar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Exportar
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- Filtros --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div class="filter-card">
        <form id="filtroRelatorio">
            @csrf
            <input type="hidden" id="tipo_receita" name="tipo_receita" value="todas">
            <div class="filter-row">
                {{-- Período --}}
                <div class="filter-group">
                    <label class="filter-label">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Período
                    </label>
                    <input type="text" id="periodo_range" name="periodo_range" class="filter-input" placeholder="Selecione o período">
                </div>

                {{-- Operadora --}}
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

                {{-- Vendedor --}}
                <div class="filter-group">
                    <label class="filter-label">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        Vendedor
                    </label>
                    <select id="vendedor_id" name="vendedor_id" class="filter-select">
                        <option value="">Todos os Vendedores</option>
                        @foreach($vendedores as $v)
                            <option value="{{ $v->id }}">{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tipo Receita (Pill Toggle) --}}
                <div class="filter-group">
                    <label class="filter-label">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        Tipo Receita
                    </label>
                    <div class="filter-pills">
                        <button type="button" class="pill-option active" data-value="todas">Todas</button>
                        <button type="button" class="pill-option" data-value="fixa">Fixa</button>
                        <button type="button" class="pill-option" data-value="vitalicio">Vitalício</button>
                    </div>
                </div>

                {{-- Botões --}}
                <div class="filter-group filter-group-btns">
                    <label class="filter-label">&nbsp;</label>
                    <div class="filter-btn-row">
                        <button type="button" id="btnFiltrar" class="btn-filter">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                            Filtrar
                        </button>
                        <button type="button" id="btnLimpar" class="btn-limpar">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="1 4 1 10 7 10"></polyline>
                                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                            </svg>
                            Limpar
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- KPI Row 1 — Visão de Receita --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div class="kpi-grid">
        {{-- Receita Total --}}
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
                <span class="kpi-label">Receita Total</span>
                <h2 class="kpi-value" id="kpiReceitaTotal">R$ 0,00</h2>
                <div class="kpi-trend trend-neutral" id="trendVariacaoAnual">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        <polyline points="17 6 23 6 23 12"></polyline>
                    </svg>
                    <span>0% vs ano anterior</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Receita Recebida --}}
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
                <span class="kpi-label">Receita Recebida</span>
                <h2 class="kpi-value" id="kpiReceitaRecebida">R$ 0,00</h2>
                <div class="kpi-trend trend-up" id="trendTaxaRecebimento">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        <polyline points="17 6 23 6 23 12"></polyline>
                    </svg>
                    <span>Taxa: 0%</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- MRR --}}
        <div class="kpi-card kpi-info">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        <polyline points="17 6 23 6 23 12"></polyline>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">MRR (Receita Recorrente)</span>
                <h2 class="kpi-value" id="kpiMrr">R$ 0,00</h2>
                <div class="kpi-trend trend-neutral" id="trendMrrVariacao">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        <polyline points="17 6 23 6 23 12"></polyline>
                    </svg>
                    <span>0%</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Inadimplência --}}
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
                <span class="kpi-label">Inadimplência</span>
                <h2 class="kpi-value" id="kpiInadimplencia">R$ 0,00</h2>
                <div class="kpi-trend trend-neutral" id="trendInadimplenciaQtd">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>0 parcelas</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- KPI Row 2 — Saúde do Negócio --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div class="kpi-grid">
        {{-- Receita Fixa --}}
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Receita Fixa</span>
                <h2 class="kpi-value" id="kpiReceitaFixa">R$ 0,00</h2>
                <span class="kpi-sublabel" id="sublabelFixa">Parcelas 1-3</span>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Receita Vitalício --}}
        <div class="kpi-card kpi-success">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Receita Vitalício</span>
                <h2 class="kpi-value" id="kpiReceitaVitalicio">R$ 0,00</h2>
                <div class="kpi-trend trend-neutral" id="trendVitalicioPerc">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                    </svg>
                    <span>0% do total</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Contratos Ativos --}}
        <div class="kpi-card kpi-info">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Contratos Ativos</span>
                <h2 class="kpi-value kpi-value-int" id="kpiContratosAtivos">0</h2>
                <div class="kpi-trend trend-neutral" id="trendValorCarteira">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    <span>Carteira: R$ 0</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Receita Cancelada --}}
        <div class="kpi-card kpi-warning">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Receita Cancelada</span>
                <h2 class="kpi-value" id="kpiReceitaCancelada">R$ 0,00</h2>
                <div class="kpi-trend trend-neutral" id="trendTaxaCancelamento">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                    </svg>
                    <span>0% cancelamento</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- Charts Section --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div class="charts-section">

        {{-- Row 1: Evolução Mensal + Gauge --}}
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
                            <span class="chart-subtitle">Receita Fixa vs Vitalício (pagos)</span>
                        </div>
                    </div>
                    <div class="chart-legend">
                        <span class="legend-item">
                            <span class="legend-dot primary"></span>
                            Receita Fixa
                        </span>
                        <span class="legend-item">
                            <span class="legend-dot success"></span>
                            Receita Vitalício
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
                            <h3 class="chart-title">Taxa de Recebimento</h3>
                            <span class="chart-subtitle">Recebido / Previsto</span>
                        </div>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="chartTaxaRecebimento"></div>
                    <div class="gauge-stats">
                        <div class="gauge-stat-item">
                            <span class="gauge-stat-label">Previsto</span>
                            <span class="gauge-stat-value primary" id="gaugePrevisto">R$ 0</span>
                        </div>
                        <div class="gauge-stat-item">
                            <span class="gauge-stat-label">Recebido</span>
                            <span class="gauge-stat-value success" id="gaugeRecebido">R$ 0</span>
                        </div>
                        <div class="gauge-stat-item">
                            <span class="gauge-stat-label">Em Aberto</span>
                            <span class="gauge-stat-value warning" id="gaugeAberto">R$ 0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 2: Operadoras (Full Width) --}}
        <div class="chart-row">
            <div class="chart-card chart-full">
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
                            <h3 class="chart-title">Receita por Operadora</h3>
                            <span class="chart-subtitle">Top 10 — Recebido, Pendente e Cancelado</span>
                        </div>
                    </div>
                    <div class="chart-legend">
                        <span class="legend-item"><span class="legend-dot success"></span> Recebido</span>
                        <span class="legend-item"><span class="legend-dot warning"></span> Pendente</span>
                        <span class="legend-item"><span class="legend-dot danger"></span> Cancelado</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="chartPorOperadora"></div>
                </div>
            </div>
        </div>

        {{-- Row 3: YoY (Full Width) --}}
        <div class="chart-row">
            <div class="chart-card chart-full">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <div class="chart-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="20" x2="18" y2="10"></line>
                                <line x1="12" y1="20" x2="12" y2="4"></line>
                                <line x1="6" y1="20" x2="6" y2="14"></line>
                            </svg>
                        </div>
                        <div>
                            <h3 class="chart-title">Comparativo Anual (YoY)</h3>
                            <span class="chart-subtitle">Últimos 3 anos — valores pagos</span>
                        </div>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="chartComparativoAnual"></div>
                </div>
            </div>
        </div>

        {{-- Row 4: Previsão (Full Width) --}}
        <div class="chart-row">
            <div class="chart-card chart-full">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <div class="chart-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 16v-4"></path>
                                <path d="M12 8h.01"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="chart-title">Previsão de Receita</h3>
                            <span class="chart-subtitle">Realizado (12 meses) + Previsão (6 meses)</span>
                        </div>
                    </div>
                    <div class="chart-legend">
                        <span class="legend-item"><span class="legend-dot success"></span> Realizado</span>
                        <span class="legend-item"><span class="legend-dot dashed"></span> Previsão</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="chartPrevisaoReceita"></div>
                </div>
            </div>
        </div>

    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- Tabela 1: Detalhamento por Operadora --}}
    {{-- ═══════════════════════════════════════════ --}}
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
                    <span class="table-subtitle">Análise detalhada dos valores por operadora</span>
                </div>
            </div>
        </div>
        <div class="table-body">
            <table class="custom-table" id="tabelaOperadoras">
                <thead>
                    <tr>
                        <th>Operadora</th>
                        <th class="text-end">Previsto</th>
                        <th class="text-end">Recebido</th>
                        <th class="text-end">Pendente</th>
                        <th class="text-end">Cancelado</th>
                        <th class="text-center">Taxa %</th>
                        <th class="text-end">Ticket Médio</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- Tabela 2: Análise de Coorte --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div class="table-card">
        <div class="table-header">
            <div class="table-title-group">
                <div class="table-icon cohort">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="3" y1="15" x2="21" y2="15"></line>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                        <line x1="15" y1="3" x2="15" y2="21"></line>
                    </svg>
                </div>
                <div>
                    <h3 class="table-title">Análise de Coorte</h3>
                    <span class="table-subtitle">Por ano de implantação — ROI acumulado</span>
                </div>
            </div>
        </div>
        <div class="table-body">
            <table class="custom-table" id="tabelaCohort">
                <thead>
                    <tr>
                        <th>Ano</th>
                        <th class="text-center">Contratos</th>
                        <th class="text-end">Valor Contratos</th>
                        <th class="text-end">Receita Fixa</th>
                        <th class="text-end">Receita Vitalício</th>
                        <th class="text-end">Receita Total</th>
                        <th class="text-center">ROI %</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>
@endsection
