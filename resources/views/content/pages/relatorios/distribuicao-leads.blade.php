@extends('layouts/layoutMaster')

@section('title', 'Distribuição de Leads')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/distribuicao-leads.scss'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/distribuicao-leads.js'])
@endsection

@section('content')
<main class="dl-page" data-inicio="{{ $inicioMes }}" data-hoje="{{ $hoje }}">
    <header class="dl-hero">
        <div>
            <span class="dl-eyebrow">INTELIGÊNCIA COMERCIAL</span>
            <h1>Distribuição de Leads</h1>
            <p>Uma leitura simples da entrada, cobertura e destino da sua base comercial.</p>
        </div>
        <div class="dl-live"><i></i><span id="dl-period-label">Carregando período...</span></div>
    </header>

    <section class="dl-filter">
        <div class="dl-presets" id="dl-presets">
            <button type="button" data-days="7">7 dias</button>
            <button type="button" data-days="30">30 dias</button>
            <button type="button" data-preset="mes" class="is-active">Mês atual</button>
            <button type="button" data-preset="ano">Ano atual</button>
            <button type="button" data-preset="tudo">Todo período</button>
        </div>
        <div class="dl-filter-grid">
            <label><span>Data inicial</span><input type="date" id="dl-inicio" value="{{ $inicioMes }}"></label>
            <label><span>Data final</span><input type="date" id="dl-fim" value="{{ $hoje }}"></label>
            <button type="button" id="dl-aplicar"><i class="ri-filter-3-line"></i> Atualizar análise</button>
        </div>
    </section>

    <div id="dl-error" class="dl-error" hidden></div>
    <div id="dl-loading" class="dl-loading"><span></span>Consolidando os leads...</div>

    <div id="dl-dashboard" hidden>
        <section class="dl-kpis" id="dl-kpis"></section>

        <section class="dl-flow-panel">
            <div class="dl-section-head">
                <div><span>VISÃO DO FUNIL</span><h2>Para onde os leads estão indo?</h2></div>
                <p>Os grupos podem se sobrepor conforme o lead avança entre áreas.</p>
            </div>
            <div class="dl-flow" id="dl-flow"></div>
        </section>

        <section class="dl-grid dl-grid-main">
            <article class="dl-panel">
                <div class="dl-section-head">
                    <div><span>EVOLUÇÃO</span><h2>Entrada × distribuição</h2></div>
                    <small id="dl-group-label"></small>
                </div>
                <div id="dl-chart-evolucao" class="dl-chart"></div>
            </article>
            <article class="dl-panel">
                <div class="dl-section-head"><div><span>COBERTURA</span><h2>Base recebida</h2></div></div>
                <div id="dl-chart-cobertura" class="dl-chart dl-chart-small"></div>
                <div class="dl-chart-caption">Distribuídos versus aguardando distribuição</div>
            </article>
        </section>

        <section class="dl-grid dl-grid-detail">
            <article class="dl-panel">
                <div class="dl-section-head"><div><span>COMERCIAL</span><h2>Status com vendedores</h2></div></div>
                <div id="dl-list-comercial" class="dl-status-list"></div>
            </article>
            <article class="dl-panel">
                <div class="dl-section-head"><div><span>ADMINISTRATIVO</span><h2>Status após a venda</h2></div></div>
                <div id="dl-list-administrativo" class="dl-status-list"></div>
            </article>
            <article class="dl-panel">
                <div class="dl-section-head"><div><span>PERDAS</span><h2>Principais descartes</h2></div></div>
                <div id="dl-list-descarte" class="dl-status-list"></div>
            </article>
        </section>

        <section class="dl-grid dl-grid-bottom">
            <article class="dl-panel">
                <div class="dl-section-head"><div><span>EQUIPE</span><h2>Distribuição por vendedor</h2></div><small>Top 20 no período</small></div>
                <div class="dl-table-wrap">
                    <table class="dl-table">
                        <thead><tr><th>#</th><th>Vendedor</th><th>Total</th><th>Comercial</th><th>Administrativo</th><th>Descartes</th><th>Participação</th></tr></thead>
                        <tbody id="dl-ranking"></tbody>
                    </table>
                </div>
            </article>
            <article class="dl-panel">
                <div class="dl-section-head"><div><span>DIAGNÓSTICO</span><h2>Motivos de descarte</h2></div></div>
                <div id="dl-chart-motivos" class="dl-chart"></div>
            </article>
        </section>
    </div>
</main>
@endsection
