@extends('layouts/layoutMaster')

@section('title', 'Aproveitamento de Leads - Análise IA')

@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
])
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/relatorio-aproveitamento.scss'])
@endsection

@section('vendor-script')
@vite([
    'resources/assets/vendor/libs/apex-charts/apexcharts.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
])
@endsection

@section('page-script')
@vite(['resources/assets/js/relatorio-aproveitamento.js'])
@endsection

@section('content')
<div class="aproveitamento-wrapper">
    <!-- Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    <polyline points="7.5 4.21 12 6.81 16.5 4.21"/>
                    <polyline points="7.5 19.79 7.5 14.6 3 12"/>
                    <polyline points="21 12 16.5 14.6 16.5 19.79"/>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                    <line x1="12" y1="22.08" x2="12" y2="12"/>
                </svg>
            </div>
            <div class="header-text">
                <h1 class="page-title">Relatório de Aproveitamento</h1>
                <p class="page-subtitle">Análise completa de leads e conversões com inteligência artificial</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-card">
        <div class="filters-grid">
            <div class="filter-item">
                <label class="filter-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    Ano
                </label>
                <select class="filter-select" id="filtro-ano">
                    @foreach($anos as $ano)
                        <option value="{{ $ano }}" {{ $ano == $anoAtual ? 'selected' : '' }}>{{ $ano }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-item">
                <label class="filter-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    Mês Início
                </label>
                <select class="filter-select" id="filtro-mes-inicio">
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
                <label class="filter-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    Mês Fim
                </label>
                <select class="filter-select" id="filtro-mes-fim">
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
                    <option value="12" selected>Dezembro</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="button" class="btn-filter-apply" id="btn-carregar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"/>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                    </svg>
                    Carregar Dados
                </button>
                @if($temApiKey)
                <button type="button" class="btn-ia-analyze" id="btn-analisar" disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/>
                        <circle cx="7.5" cy="14.5" r="1.5"/>
                        <circle cx="16.5" cy="14.5" r="1.5"/>
                    </svg>
                    Gerar Análise IA
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- KPIs -->
    <div class="kpi-grid kpi-grid-6" id="kpis-container" style="display: none;">
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total de Leads</span>
                <h2 class="kpi-value" id="kpi-total-leads">0</h2>
            </div>
        </div>

        <div class="kpi-card kpi-info">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="12" y1="18" x2="12" y2="12"/>
                        <line x1="9" y1="15" x2="15" y2="15"/>
                    </svg>
                </div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Vendas Cadastradas</span>
                <h2 class="kpi-value" id="kpi-vendas-cadastradas">0</h2>
            </div>
        </div>

        <div class="kpi-card kpi-success">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Vendas Implantadas</span>
                <h2 class="kpi-value" id="kpi-vendas-implantadas">0</h2>
                <span class="kpi-badge kpi-badge-success">Gera Receita</span>
            </div>
        </div>

        <div class="kpi-card kpi-warning">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"/>
                    </svg>
                </div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Taxa de Conversão</span>
                <h2 class="kpi-value" id="kpi-taxa-conversao">0%</h2>
            </div>
        </div>

        <div class="kpi-card kpi-secondary">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                </div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Valor Cadastrado</span>
                <h2 class="kpi-value kpi-monetary" id="kpi-valor-cadastradas">R$ 0,00</h2>
            </div>
        </div>

        <div class="kpi-card kpi-success-alt">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Valor Implantado</span>
                <h2 class="kpi-value kpi-monetary" id="kpi-valor-implantadas">R$ 0,00</h2>
                <span class="kpi-badge kpi-badge-success">Receita Real</span>
            </div>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="charts-grid" id="charts-container" style="display: none;">
        <!-- Leads por Mês - Full Width -->
        <div class="chart-card chart-full">
            <div class="chart-header">
                <div class="chart-title-group">
                    <h3 class="chart-title">Leads Importados por Mês</h3>
                    <span class="chart-subtitle">Evolução mensal de importações</span>
                </div>
                <div class="chart-legend">
                    <span class="legend-item">
                        <span class="legend-dot primary"></span>
                        Total
                    </span>
                    <span class="legend-item">
                        <span class="legend-dot info"></span>
                        ADS
                    </span>
                    <span class="legend-item">
                        <span class="legend-dot warning"></span>
                        Base
                    </span>
                </div>
            </div>
            <div class="chart-body">
                <div id="chart-leads-mes"></div>
            </div>
        </div>

        <!-- Vendas por Mês - Full Width -->
        <div class="chart-card chart-full">
            <div class="chart-header">
                <div class="chart-title-group">
                    <h3 class="chart-title">Vendas por Mês</h3>
                    <span class="chart-subtitle">Cadastradas vs Implantadas (receita)</span>
                </div>
                <div class="chart-legend">
                    <span class="legend-item">
                        <span class="legend-dot info"></span>
                        Cadastradas
                    </span>
                    <span class="legend-item">
                        <span class="legend-dot success"></span>
                        Implantadas
                    </span>
                    <span class="legend-item">
                        <span class="legend-dot primary"></span>
                        Valor Impl.
                    </span>
                </div>
            </div>
            <div class="chart-body">
                <div id="chart-vendas-mes"></div>
            </div>
        </div>

        <!-- Funil de Conversão -->
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title-group">
                    <h3 class="chart-title">Funil de Conversão</h3>
                    <span class="chart-subtitle">Jornada do lead até a venda</span>
                </div>
            </div>
            <div class="chart-body">
                <div id="chart-funil"></div>
            </div>
        </div>

        <!-- Distribuição por Temperatura -->
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title-group">
                    <h3 class="chart-title">Temperatura dos Leads</h3>
                    <span class="chart-subtitle">Classificação por qualidade</span>
                </div>
            </div>
            <div class="chart-body">
                <div id="chart-temperatura"></div>
            </div>
        </div>

        <!-- Fontes de Importação -->
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title-group">
                    <h3 class="chart-title">Fontes de Importação</h3>
                    <span class="chart-subtitle">Origem dos leads</span>
                </div>
            </div>
            <div class="chart-body">
                <div id="chart-fontes"></div>
            </div>
        </div>

        <!-- Distribuição por Status - Full Width -->
        <div class="chart-card chart-full">
            <div class="chart-header">
                <div class="chart-title-group">
                    <h3 class="chart-title">Distribuição por Status</h3>
                    <span class="chart-subtitle">Leads agrupados por etapa do funil</span>
                </div>
            </div>
            <div class="chart-body">
                <div id="chart-status"></div>
            </div>
        </div>
    </div>

    <!-- AI Analysis Section -->
    @if($temApiKey)
    <div class="ia-analysis-card" id="ia-analysis-container" style="display: none;">
        <div class="ia-analysis-header">
            <div class="ia-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/>
                    <circle cx="7.5" cy="14.5" r="1.5"/>
                    <circle cx="16.5" cy="14.5" r="1.5"/>
                </svg>
            </div>
            <div class="ia-header-text">
                <h3 class="ia-header-title">Análise Inteligente</h3>
                <p class="ia-header-subtitle">Insights gerados por IA baseados nos dados do período</p>
            </div>
        </div>
        <div class="ia-analysis-body">
            <!-- Loading State -->
            <div class="ia-loading" id="ia-loading" style="display: none;">
                <div class="ia-loading-animation">
                    <div class="ia-loading-brain">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/>
                        </svg>
                    </div>
                    <div class="ia-loading-dots">
                        <span></span><span></span><span></span>
                    </div>
                </div>
                <p class="ia-loading-text">Analisando dados do período...</p>
                <span class="ia-loading-subtext">A IA está processando as métricas e gerando insights</span>
            </div>

            <!-- Result State -->
            <div class="ia-result" id="ia-result" style="display: none;">
                <div class="ia-result-content" id="ia-result-text"></div>
            </div>

            <!-- Error State -->
            <div class="ia-error" id="ia-error" style="display: none;">
                <div class="ia-error-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <p class="ia-error-title">Erro na análise</p>
                <span class="ia-error-text" id="ia-error-text"></span>
            </div>

            <!-- Empty State -->
            <div class="ia-empty" id="ia-empty">
                <div class="ia-empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/>
                        <circle cx="7.5" cy="14.5" r="1.5"/>
                        <circle cx="16.5" cy="14.5" r="1.5"/>
                    </svg>
                </div>
                <p class="ia-empty-text">Clique em "Gerar Análise IA" para obter insights</p>
                <span class="ia-empty-subtext">A IA analisará os dados do período selecionado</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Empty State Initial -->
    <div class="empty-state" id="empty-state">
        <div class="empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                <polyline points="7.5 4.21 12 6.81 16.5 4.21"/>
                <polyline points="7.5 19.79 7.5 14.6 3 12"/>
                <polyline points="21 12 16.5 14.6 16.5 19.79"/>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                <line x1="12" y1="22.08" x2="12" y2="12"/>
            </svg>
        </div>
        <h3 class="empty-title">Selecione o período para análise</h3>
        <p class="empty-text">Escolha o ano e os meses que deseja analisar e clique em "Carregar Dados"</p>
    </div>
</div>
@endsection
