@extends('layouts/layoutMaster')

@section('title', 'LK Benefícios - Dashboard')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/dashboard-analytics.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/lk-beneficios-dashboard.js'])
@endsection

@section('content')
@php
    use App\Modules\LkBeneficios\Enums\TipoBeneficio;
@endphp
<div class="dashboard-wrapper">

    {{-- Header --}}
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-text">
                <span class="greeting-label">LK Benefícios</span>
                <h1 class="main-title">Gestão de Contratos</h1>
                <p class="subtitle">Visão consolidada de apólices, beneficiários e renovações</p>
            </div>
            <div class="header-filters">
                <div class="filter-group">
                    <a href="{{ route('lk-beneficios.contratos.index') }}" class="btn btn-primary btn-sm">
                        <i class="ri-file-list-3-line me-1"></i> Ver contratos
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="kpi-grid">
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Contratos Ativos</span>
                <h2 class="kpi-value">{{ number_format($kpis['contratos_ativos'], 0, ',', '.') }}</h2>
                <span class="kpi-trend trend-neutral">
                    de {{ number_format($kpis['contratos_total'], 0, ',', '.') }} no total
                </span>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-success">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">MRR (Receita Mensal)</span>
                <h2 class="kpi-value">R$ {{ number_format($kpis['mrr'], 2, ',', '.') }}</h2>
                <span class="kpi-trend trend-up">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    ARR R$ {{ number_format($kpis['arr'], 2, ',', '.') }}
                </span>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-info">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Vidas Cobertas</span>
                <h2 class="kpi-value">{{ number_format($kpis['vidas_total'], 0, ',', '.') }}</h2>
                <span class="kpi-trend trend-neutral">em contratos ativos</span>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-warning">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Ticket Médio</span>
                <h2 class="kpi-value">R$ {{ number_format($kpis['ticket_medio'], 2, ',', '.') }}</h2>
                <span class="kpi-trend trend-neutral">por contrato ativo</span>
            </div>
            <div class="kpi-glow"></div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="charts-section">
        <div class="chart-row">
            <div class="chart-card chart-large">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Distribuição por Tipo</h3>
                        <span class="chart-subtitle">Contratos agrupados por produto</span>
                    </div>
                    <div class="chart-legend">
                        <span class="legend-item"><span class="legend-dot primary"></span>Vida</span>
                        <span class="legend-item"><span class="legend-dot info"></span>Odonto</span>
                        <span class="legend-item"><span class="legend-dot success"></span>Previdência</span>
                        <span class="legend-item"><span class="legend-dot warning"></span>Patrimonial</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="lkb-chart-por-tipo"
                         data-series='@json(array_values($porTipo))'
                         data-labels='@json(collect(array_keys($porTipo))->map(fn($t) => TipoBeneficio::label($t))->values())'></div>
                </div>
            </div>

            <div class="chart-card chart-medium">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Reajustes Próximos</h3>
                        <span class="chart-subtitle">Nos próximos 60 dias</span>
                    </div>
                    <div class="chart-legend">
                        <span class="legend-item"><span class="legend-dot warning"></span>{{ $reajustes->count() }} contratos</span>
                    </div>
                </div>
                <div class="chart-body" style="max-height: 320px; overflow-y: auto;">
                    @if($reajustes->isEmpty())
                        <p class="text-center p-4 mb-0" style="color: var(--dash-text-muted);">Nenhum reajuste na janela.</p>
                    @else
                        <table class="custom-table">
                            <tbody>
                                @foreach($reajustes as $c)
                                    <tr>
                                        <td>
                                            <a href="{{ route('lk-beneficios.contratos.show', $c->id) }}" class="contract-name" style="text-decoration: none;">
                                                {{ $c->nome_cliente }}
                                            </a>
                                            <br>
                                            <small style="color: var(--dash-text-muted);">{{ $c->produto?->nome ?? '—' }}</small>
                                        </td>
                                        <td class="contract-value angariacao text-end">
                                            {{ \Carbon\Carbon::parse($c->data_aniversario_reajuste)->format('d/m') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
