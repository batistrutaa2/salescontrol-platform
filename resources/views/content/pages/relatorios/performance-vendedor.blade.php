@extends('layouts/layoutMaster')

@section('title', 'Performance do Vendedor')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/performance-vendedor.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/performance-vendedor.js'])
@endsection

@section('content')
<div class="pv-wrapper">

    {{-- ============ HEADER + FILTROS ============ --}}
    <div class="pv-page-header">
        <div class="pv-header-content">
            <div class="pv-header-text">
                <span class="pv-greeting-label">Relatório de Carreira</span>
                <h1 class="pv-main-title">Performance do Vendedor</h1>
                <p class="pv-subtitle">Evolução ano a ano: quanto cresceu, em que nível está e como vem performando ao longo do tempo</p>
            </div>
            <div class="pv-header-filters">
                <div class="pv-filter-group">
                    <div class="pv-filter-item">
                        <span class="pv-filter-label">Vendedor</span>
                        <select class="pv-filter-select" id="pv-select-vendedor">
                            @foreach($vendedores as $vendedor)
                                <option value="{{ $vendedor->id }}">{{ $vendedor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pv-filter-item">
                        <span class="pv-filter-label">Ano foco</span>
                        <select class="pv-filter-select" id="pv-select-ano"></select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ FAIXA DE TEMPO DE CASA ============ --}}
    <div class="pv-tenure-bar">
        <div class="pv-tenure-main">
            <div class="pv-tenure-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div class="pv-tenure-text">
                <span class="pv-tenure-value" id="pv-tenure-anos">—</span>
                <span class="pv-tenure-label" id="pv-tenure-desde">—</span>
            </div>
        </div>
        <div class="pv-tenure-right">
            <span class="pv-periodo-chip" id="pv-periodo-chip" hidden></span>
            <div class="pv-nivel-badge" id="pv-nivel-badge">
                <span class="pv-nivel-dot"></span>
                <span id="pv-nivel-text">—</span>
            </div>
        </div>
    </div>

    {{-- ============ KPI CARDS (ano foco vs ano anterior) ============ --}}
    <div class="pv-kpi-grid">
        @php
            $kpiCards = [
                ['key' => 'valor-vendido', 'label' => 'Valor Vendido', 'variant' => 'primary', 'icon' => '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'],
                ['key' => 'total-vendas', 'label' => 'Vendas', 'variant' => 'success', 'icon' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'],
                ['key' => 'leads', 'label' => 'Leads Trabalhados', 'variant' => 'info', 'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
                ['key' => 'conversao', 'label' => 'Taxa de Conversão', 'variant' => 'warning', 'icon' => '<path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>'],
                ['key' => 'vidas', 'label' => 'Vidas Vendidas', 'variant' => 'info', 'icon' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 1 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/>'],
                ['key' => 'ticket', 'label' => 'Ticket Médio', 'variant' => 'primary', 'icon' => '<path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"/><path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z"/>'],
            ];
        @endphp

        @foreach($kpiCards as $card)
        <div class="pv-kpi-card pv-kpi-{{ $card['variant'] }}">
            <div class="pv-kpi-icon-wrapper">
                <div class="pv-kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        {!! $card['icon'] !!}
                    </svg>
                </div>
                <div class="pv-kpi-pulse"></div>
            </div>
            <div class="pv-kpi-content">
                <span class="pv-kpi-label">{{ $card['label'] }}</span>
                <h2 class="pv-kpi-value" id="pv-kpi-{{ $card['key'] }}">—</h2>
                <div class="pv-kpi-trend" id="pv-trend-{{ $card['key'] }}">
                    <span class="pv-trend-text">vs ano anterior</span>
                </div>
            </div>
            <div class="pv-kpi-glow"></div>
        </div>
        @endforeach
    </div>

    {{-- ============ GRÁFICOS ============ --}}
    <div class="pv-charts-row">
        <div class="pv-chart-card pv-chart-large">
            <div class="pv-chart-header">
                <div class="pv-chart-title-group">
                    <h3 class="pv-chart-title">Curva de Evolução</h3>
                    <span class="pv-chart-subtitle" id="pv-curva-subtitle">Valor vendido e taxa de conversão ao longo da carreira</span>
                </div>
                <div class="pv-chart-legend">
                    <span class="pv-legend-item"><span class="pv-legend-dot primary"></span> Valor vendido</span>
                    <span class="pv-legend-item"><span class="pv-legend-dot warning"></span> Conversão</span>
                </div>
            </div>
            <div class="pv-chart-body">
                <div id="pvCurvaEvolucao"></div>
            </div>
        </div>

        <div class="pv-chart-card">
            <div class="pv-chart-header">
                <div class="pv-chart-title-group">
                    <h3 class="pv-chart-title">Comparativo Mensal</h3>
                    <span class="pv-chart-subtitle" id="pv-comparativo-subtitle">Valor vendido mês a mês</span>
                </div>
                <div class="pv-chart-legend">
                    <span class="pv-legend-item"><span class="pv-legend-dot primary"></span> <span id="pv-legend-ano-foco">Ano foco</span></span>
                    <span class="pv-legend-item"><span class="pv-legend-dot muted"></span> <span id="pv-legend-ano-anterior">Ano anterior</span></span>
                </div>
            </div>
            <div class="pv-chart-body">
                <div id="pvComparativoMensal"></div>
            </div>
        </div>
    </div>

    {{-- ============ DIAGNÓSTICO ============ --}}
    <div class="pv-diagnostico">
        <div class="pv-diag-header">
            <div class="pv-diag-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/>
                </svg>
            </div>
            <div>
                <h3 class="pv-diag-title">Diagnóstico de Performance</h3>
                <span class="pv-diag-subtitle">Leitura inteligente da trajetória do vendedor</span>
            </div>
        </div>
        <div class="pv-diag-grid">
            <div class="pv-diag-card" id="pv-diag-tendencia">
                <span class="pv-diag-card-label">Tendência</span>
                <span class="pv-diag-card-value" id="pv-diag-tendencia-text">—</span>
            </div>
            <div class="pv-diag-card">
                <span class="pv-diag-card-label">Melhor ano</span>
                <span class="pv-diag-card-value" id="pv-diag-melhor-ano">—</span>
                <span class="pv-diag-card-hint" id="pv-diag-melhor-ano-valor"></span>
            </div>
            <div class="pv-diag-card pv-diag-resumo">
                <span class="pv-diag-card-label">Destaques do ano foco</span>
                <ul class="pv-diag-resumo-list" id="pv-diag-resumo"></ul>
            </div>
        </div>
    </div>

    {{-- ============ TABELA YEAR-OVER-YEAR ============ --}}
    <div class="pv-table-card">
        <div class="pv-table-header">
            <div class="pv-table-title-group">
                <div class="pv-table-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                </div>
                <div>
                    <h3 class="pv-table-title">Histórico Ano a Ano</h3>
                    <span class="pv-table-subtitle">Cada linha resume um ano de trabalho</span>
                </div>
            </div>
        </div>
        <div class="pv-table-body">
            <table class="pv-table">
                <thead>
                    <tr>
                        <th>Ano</th>
                        <th class="pv-num">Valor Vendido</th>
                        <th class="pv-num">Crescimento</th>
                        <th class="pv-num">Vendas</th>
                        <th class="pv-num">Leads</th>
                        <th class="pv-num">Conversão</th>
                        <th class="pv-num">Vidas</th>
                        <th class="pv-num">Ticket Médio</th>
                    </tr>
                </thead>
                <tbody id="pv-table-tbody">
                    <tr><td colspan="8" class="pv-table-empty">Selecione um vendedor.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
