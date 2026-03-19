@extends('layouts/layoutMaster')

@section('title', 'Relatorio - Atividade Preditiva')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/scss/pages/relatorio-preditiva.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/apex-charts/apexcharts.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/relatorioPreditiva.js'])
@endsection

@section('content')
<div class="preditiva-wrapper">
    {{-- Header Section --}}
    <div class="pred-header">
        <div class="header-content">
            <div class="header-text">
                <span class="greeting-label">Relatorios</span>
                <h1 class="main-title">Atividade Preditiva</h1>
                <p class="subtitle">Acompanhe o desempenho e conversao do discador preditivo</p>
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
        {{-- Total Contatos --}}
        <div class="kpi-card kpi-primary">
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
                <span class="kpi-label">Total de Contatos</span>
                <h2 class="kpi-value" id="total-contatos">0</h2>
                <div class="kpi-trend trend-neutral">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <span>Periodo selecionado</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Convertidos --}}
        <div class="kpi-card kpi-success">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Convertidos</span>
                <h2 class="kpi-value" id="total-convertidos">0</h2>
                <div class="kpi-trend trend-up">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    <span>Adicionados ao Kanban</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Cotacoes --}}
        <div class="kpi-card kpi-success">
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
                <span class="kpi-label">Cotacoes</span>
                <h2 class="kpi-value" id="total-cotacoes">0</h2>
                <div class="kpi-trend trend-up">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    <span>Interesse real</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Ligar Depois --}}
        <div class="kpi-card kpi-warning">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Ligar Depois</span>
                <h2 class="kpi-value" id="total-ligar-depois">0</h2>
                <div class="kpi-trend trend-neutral">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <span>Retorno agendado</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Descartados --}}
        <div class="kpi-card kpi-danger">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Descartados</span>
                <h2 class="kpi-value" id="total-descartados">0</h2>
                <div class="kpi-trend trend-down">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/>
                        <polyline points="17 18 23 18 23 12"/>
                    </svg>
                    <span>Nao interessados</span>
                </div>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Taxa de Conversao --}}
        <div class="kpi-card kpi-info">
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
                <span class="kpi-label">Taxa de Conversao</span>
                <h2 class="kpi-value" id="taxa-conversao">0%</h2>
                <div class="kpi-trend trend-up">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    <span>Convertidos / Total</span>
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
            <form id="form-filtro-preditiva" method="POST" action="{{ route('relatorios.preditiva.buscar') }}">
                @csrf
                <div class="filter-grid">
                    <div class="filter-group">
                        <label class="filter-label">Data Inicio</label>
                        <input type="text" id="data_inicio" name="data_inicio" class="form-control flatpickr-date" placeholder="Selecione...">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Data Fim</label>
                        <input type="text" id="data_fim" name="data_fim" class="form-control flatpickr-date" placeholder="Selecione...">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Usuario</label>
                        <select id="usuario_id" name="usuario_id" class="form-select select2" data-allow-clear="true">
                            <option value="">Todos os usuarios</option>
                            @foreach($usuarios as $usuario)
                                <option value="{{ $usuario->id }}">{{ $usuario->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn-dash btn-primary" style="width: 100%;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                            Buscar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="charts-section">
        {{-- Daily Activity Chart --}}
        <div class="chart-row">
            <div class="chart-card chart-full">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Atividade por Dia</h3>
                        <span class="chart-subtitle">Evolucao diaria de contatos e conversoes</span>
                    </div>
                    <div class="chart-legend">
                        <span class="legend-item">
                            <span class="legend-dot primary"></span>
                            Contatos
                        </span>
                        <span class="legend-item">
                            <span class="legend-dot success"></span>
                            Cotacoes
                        </span>
                        <span class="legend-item">
                            <span class="legend-dot warning"></span>
                            Ligar Depois
                        </span>
                        <span class="legend-item">
                            <span class="legend-dot danger"></span>
                            Descartados
                        </span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="grafico-atividade-diaria"></div>
                </div>
            </div>
        </div>

        {{-- Desempenho por Vendedor --}}
        <div class="chart-row">
            <div class="chart-card chart-full vendedor-performance-card">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Desempenho por Vendedor</h3>
                        <span class="chart-subtitle">Conversoes por tipo, descartes e taxa de conversao</span>
                    </div>
                    <div class="chart-legend">
                        <span class="legend-item">
                            <span class="legend-dot success"></span>
                            Cotacoes
                        </span>
                        <span class="legend-item">
                            <span class="legend-dot info"></span>
                            Ligar Depois
                        </span>
                        <span class="legend-item">
                            <span class="legend-dot danger" style="opacity: 0.45;"></span>
                            Descartados
                        </span>
                        <span class="legend-item">
                            <span class="legend-dot warning" style="width: 16px; height: 3px; border-radius: 2px;"></span>
                            Taxa de Conversao
                        </span>
                    </div>
                </div>
                {{-- Top performers strip --}}
                <div class="vendedor-highlights" id="vendedor-highlights">
                    <div class="highlight-item highlight-best" id="highlight-best">
                        <div class="highlight-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                        </div>
                        <div class="highlight-text">
                            <span class="highlight-label">Melhor conversao</span>
                            <span class="highlight-value" id="best-seller-name">--</span>
                        </div>
                        <span class="highlight-badge success" id="best-seller-taxa">--%</span>
                    </div>
                    <div class="highlight-divider"></div>
                    <div class="highlight-item highlight-top-volume" id="highlight-volume">
                        <div class="highlight-icon volume">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="20" x2="18" y2="10"/>
                                <line x1="12" y1="20" x2="12" y2="4"/>
                                <line x1="6" y1="20" x2="6" y2="14"/>
                            </svg>
                        </div>
                        <div class="highlight-text">
                            <span class="highlight-label">Mais cotacoes</span>
                            <span class="highlight-value" id="volume-seller-name">--</span>
                        </div>
                        <span class="highlight-badge primary" id="volume-seller-total">-- cotacoes</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="grafico-vendedores"></div>
                </div>
            </div>
        </div>

        {{-- Distribuicao por Tabulacao --}}
        <div class="chart-row">
            <div class="chart-card chart-full">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Distribuicao por Tabulacao</h3>
                        <span class="chart-subtitle">Motivos de contato</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div id="grafico-tabulacao"></div>
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
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="table-title">Detalhamento de Atividades</h3>
                        <span class="table-subtitle">Historico completo de contatos</span>
                    </div>
                </div>
                <div class="table-filter">
                    <select id="filtro-vendedor-tabela" class="form-select" style="min-width: 200px;">
                        <option value="">Todos os vendedores</option>
                    </select>
                </div>
            </div>
            <div class="table-body">
                <table class="custom-table" id="tabela-atividades" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Usuario</th>
                            <th>Cliente</th>
                            <th>Telefone</th>
                            <th>Status</th>
                            <th>Tabulacao</th>
                        </tr>
                    </thead>
                    <tbody>
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
