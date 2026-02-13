@extends('layouts/layoutMaster')

@section('title', 'Backoffice - Performance Individual')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/backoffice-performance.scss')
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/apex-charts/apexcharts.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/backoffice-performance.js'])
@endsection

@section('content')
<div class="bp-wrapper">

    {{-- ========== HEADER ========== --}}
    <div class="bp-header">
        <div class="bp-header__left">
            <span class="bp-header__tag">Backoffice</span>
            <h1 class="bp-header__title">Performance Individual</h1>
            <p class="bp-header__sub">Analise de performance por colaborador</p>
        </div>
        <div class="bp-header__filters">
            <div class="bp-filter">
                <label class="bp-filter__label">Periodo</label>
                <div class="bp-filter__dates">
                    <input type="text" class="bp-filter__input flatpickr-date" id="filtro-data-inicio" placeholder="dd/mm/aaaa">
                    <span class="bp-filter__sep">ate</span>
                    <input type="text" class="bp-filter__input flatpickr-date" id="filtro-data-fim" placeholder="dd/mm/aaaa">
                </div>
            </div>
            <div class="bp-filter">
                <label class="bp-filter__label">Backoffice</label>
                <select class="bp-filter__select" id="filtro-backoffice">
                    <option value="">Todos</option>
                    @foreach($backofficeUsers as $bo)
                        <option value="{{ $bo->id }}">{{ $bo->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="bp-filter__btn" id="btn-filtrar">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filtrar
            </button>
        </div>
    </div>

    {{-- ========== KPIs ========== --}}
    <div class="bp-kpis">
        <div class="bp-kpi bp-kpi--primary">
            <div class="bp-kpi__icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <div class="bp-kpi__body">
                <span class="bp-kpi__label">Total de Contratos</span>
                <span class="bp-kpi__value js-kpi-total">0</span>
                <span class="bp-kpi__detail js-kpi-total-valor">R$ 0,00</span>
            </div>
            <div class="bp-kpi__glow"></div>
        </div>

        <div class="bp-kpi bp-kpi--success">
            <div class="bp-kpi__icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="bp-kpi__body">
                <span class="bp-kpi__label">Implantados</span>
                <span class="bp-kpi__value js-kpi-implantados">0</span>
                <span class="bp-kpi__detail js-kpi-taxa-conversao">0% de conversao</span>
            </div>
            <div class="bp-kpi__glow"></div>
        </div>

        <div class="bp-kpi bp-kpi--warning">
            <div class="bp-kpi__icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="bp-kpi__body">
                <span class="bp-kpi__label">Em Andamento</span>
                <span class="bp-kpi__value js-kpi-andamento">0</span>
                <span class="bp-kpi__detail">no pipeline</span>
            </div>
            <div class="bp-kpi__glow"></div>
        </div>

        <div class="bp-kpi bp-kpi--danger">
            <div class="bp-kpi__icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <div class="bp-kpi__body">
                <span class="bp-kpi__label">Perdidos</span>
                <span class="bp-kpi__value js-kpi-perdidos">0</span>
                <span class="bp-kpi__detail js-kpi-valor-perdido">R$ 0,00</span>
            </div>
            <div class="bp-kpi__glow"></div>
        </div>
    </div>

    {{-- ========== RANKING HERO ========== --}}
    <div class="bp-ranking">
        <div class="bp-ranking__header">
            <div class="bp-ranking__title-group">
                <div class="bp-ranking__icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </div>
                <div>
                    <h2 class="bp-ranking__title">Ranking de Performance</h2>
                    <span class="bp-ranking__subtitle">Desempenho individual dos colaboradores</span>
                </div>
            </div>
            <div class="bp-ranking__sort">
                <label class="bp-ranking__sort-label">Ordenar por</label>
                <select class="bp-ranking__sort-select" id="ranking-sort">
                    <option value="implantados">Implantados</option>
                    <option value="taxa_conversao">Taxa de Conversao</option>
                    <option value="total">Total de Contratos</option>
                    <option value="tempo_medio">Tempo Medio</option>
                    <option value="valor_implantado">Valor Implantado</option>
                </select>
            </div>
        </div>

        {{-- Podium (top 3) --}}
        <div class="bp-podium" id="podium-container">
            {{-- JS populated --}}
        </div>

        {{-- Full ranking table --}}
        <div class="bp-ranking__table-wrap">
            <table class="bp-ranking__table" id="ranking-table">
                <thead>
                    <tr>
                        <th class="col-pos">#</th>
                        <th class="col-user">Colaborador</th>
                        <th class="col-num">Contratos</th>
                        <th class="col-num">Implantados</th>
                        <th class="col-num">Andamento</th>
                        <th class="col-num">Perdidos</th>
                        <th class="col-bar">Conversao</th>
                        <th class="col-num">Tempo Medio</th>
                        <th class="col-num">Valor Implantado</th>
                    </tr>
                </thead>
                <tbody id="ranking-tbody">
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========== CHARTS ========== --}}
    <div class="bp-charts">
        <div class="bp-charts__row bp-charts__row--1">
            <div class="bp-chart-card">
                <div class="bp-chart-card__head">
                    <h3 class="bp-chart-card__title">Comparativo por Backoffice</h3>
                    <span class="bp-chart-card__sub">Total vs Implantados por colaborador</span>
                </div>
                <div class="bp-chart-card__body">
                    <div id="chart-backoffice-compare"></div>
                </div>
            </div>
        </div>

        <div class="bp-charts__row bp-charts__row--2-equal">
            <div class="bp-chart-card">
                <div class="bp-chart-card__head">
                    <h3 class="bp-chart-card__title">Performance por Operadora</h3>
                    <span class="bp-chart-card__sub">Volume e taxa de conversao</span>
                </div>
                <div class="bp-chart-card__body">
                    <div id="chart-operadora"></div>
                </div>
            </div>

            <div class="bp-chart-card">
                <div class="bp-chart-card__head">
                    <h3 class="bp-chart-card__title">Pipeline por Etapa</h3>
                    <span class="bp-chart-card__sub">Distribuicao nos estagios do funil</span>
                </div>
                <div class="bp-chart-card__body">
                    <div id="chart-pipeline"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== PENDENCIAS ========== --}}
    <div class="bp-pendencias">
        <div class="bp-section-head">
            <div class="bp-section-head__left">
                <div class="bp-section-head__icon bp-section-head__icon--info">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                </div>
                <div>
                    <h2 class="bp-section-head__title">Pendencias Frequentes</h2>
                    <span class="bp-section-head__sub">Motivos que mais geram atraso</span>
                </div>
            </div>
        </div>
        <div class="bp-pendencias__grid" id="pendencias-grid">
        </div>
    </div>

</div>
@endsection
