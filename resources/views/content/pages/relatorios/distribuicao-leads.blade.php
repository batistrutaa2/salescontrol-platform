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
            <h1>Distribuição de Leads</h1>
            <p>Veja quem está com os vendedores, quais leads novos aguardam distribuição no reservatório e o que avançou para implantação ou carteira.</p>
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
        <section class="dl-position-panel">
            <div class="dl-section-head">
                <div><span>POSIÇÃO ATUAL</span><h2>Onde os leads estão sendo trabalhados?</h2></div>
                <p>Contagem de leads únicos pela posição atual.</p>
            </div>
            <div class="dl-kpis dl-kpis-position" id="dl-kpis-position"></div>
        </section>

        <section class="dl-sales-panel">
            <div class="dl-section-head">
                <div><span>APÓS A VENDA</span><h2>Conversão, implantação e carteira</h2></div>
                <p>O status atual da venda é a fonte destes números.</p>
            </div>
            <div class="dl-kpis dl-kpis-sales" id="dl-kpis-sales"></div>
            <div class="dl-definition">
                <i class="ri-information-line" aria-hidden="true"></i>
                <p><strong>Fila administrativa</strong> reúne somente vendas ainda em implantação: venda recebida, análises, pendências, assinaturas, contrato gerado, boleto e regularização. <strong>Implantados, declinados e estornados não entram nessa fila.</strong></p>
            </div>
            <p class="dl-method-note">O período considera a data em que o lead entrou na base. Um lead com mais de uma venda pode aparecer em mais de um resultado pós-venda.</p>
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
                <div class="dl-section-head"><div><span>DISTRIBUIÇÃO</span><h2>Leads distribuídos e no reservatório</h2></div></div>
                <div id="dl-chart-cobertura" class="dl-chart dl-chart-small"></div>
                <div class="dl-chart-caption">Leads já atribuídos versus novos leads prontos para envio</div>
            </article>
        </section>

        <section class="dl-grid dl-grid-detail">
            <article class="dl-panel">
                <div class="dl-section-head"><div><span>COMERCIAL</span><h2>Status com vendedores</h2></div></div>
                <div id="dl-list-comercial" class="dl-status-list"></div>
            </article>
            <article class="dl-panel">
                <div class="dl-section-head"><div><span>IMPLANTAÇÃO</span><h2>Fila administrativa por etapa</h2></div></div>
                <div id="dl-list-administrativo" class="dl-status-list"></div>
            </article>
            <article class="dl-panel">
                <div class="dl-section-head"><div><span>SAÍDA COMERCIAL</span><h2>Remarketing e descartes</h2></div></div>
                <div id="dl-list-descarte" class="dl-status-list"></div>
            </article>
        </section>

        <section class="dl-grid dl-grid-bottom">
            <article class="dl-panel">
                <div class="dl-section-head"><div><span>EQUIPE</span><h2>Distribuição por vendedor</h2></div><small>Top 20 no período</small></div>
                <div class="dl-table-wrap">
                    <table class="dl-table">
                        <thead><tr><th>#</th><th>Vendedor</th><th>Atribuídos</th><th>Em trabalho</th><th>Remarketing</th><th>Fila administrativa</th><th>Participação</th></tr></thead>
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
