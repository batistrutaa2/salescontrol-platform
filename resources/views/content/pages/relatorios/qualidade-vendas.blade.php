@extends('layouts/layoutMaster')

@section('title', 'Qualidade das Vendas')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/qualidade-vendas.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/qualidade-vendas.js'])
@endsection

@section('content')
<div class="qv-page" data-ano="{{ $anoAtual }}" data-hoje="{{ $hoje }}">
    <header class="qv-hero">
        <div>
            <span class="qv-eyebrow">INTELIGÊNCIA COMERCIAL · {{ $anoAtual }}</span>
            <h1>Qualidade das Vendas</h1>
            <p>Volume real, implantação e perdas para decisões de premiação mais justas.</p>
        </div>
        <div class="qv-hero-badge">
            <span class="qv-pulse"></span>
            Status atual das propostas
        </div>
    </header>

    <section class="qv-filter-card">
        <div class="qv-presets" id="qv-presets">
            <button type="button" data-preset="mes">Mês atual</button>
            <button type="button" data-preset="q1">1º tri</button>
            <button type="button" data-preset="q2">2º tri</button>
            <button type="button" data-preset="q3">3º tri</button>
            <button type="button" data-preset="q4">4º tri</button>
            <button type="button" class="is-active" data-preset="ano">Ano acumulado</button>
        </div>
        <div class="qv-filter-grid">
            <label>
                <span>Data inicial</span>
                <input type="date" id="qv-inicio" min="{{ $anoAtual }}-01-01" max="{{ $anoAtual }}-12-31" value="{{ $anoAtual }}-01-01">
            </label>
            <label>
                <span>Data final</span>
                <input type="date" id="qv-fim" min="{{ $anoAtual }}-01-01" max="{{ $anoAtual }}-12-31" value="{{ $hoje }}">
            </label>
            <label>
                <span>Vendedor</span>
                <select id="qv-vendedor"><option value="">Toda a empresa</option></select>
            </label>
            <button type="button" id="qv-aplicar" class="qv-primary-btn">
                <i class="ri-filter-3-line"></i> Atualizar análise
            </button>
        </div>
    </section>

    <div class="qv-loading" id="qv-loading"><span></span><p>Consolidando as propostas...</p></div>
    <div class="qv-error" id="qv-error" hidden></div>

    <main id="qv-dashboard" hidden>
        <section class="qv-period-bar">
            <div><span>PERÍODO ANALISADO</span><strong id="qv-periodo">—</strong></div>
            <div id="qv-comparacao-label">Comparação com período anterior</div>
        </section>

        <section class="qv-kpi-grid" id="qv-kpis"></section>

        <section class="qv-insight-grid">
            <article class="qv-panel qv-panel-wide">
                <div class="qv-panel-head">
                    <div><span>EVOLUÇÃO</span><h2>Qualidade mês a mês</h2></div>
                    <div class="qv-legend"><i class="impl"></i>Implantada <i class="proc"></i>Em processo <i class="est"></i>Estorno <i class="dec"></i>Declínio</div>
                </div>
                <div id="qv-chart-status"></div>
            </article>
            <article class="qv-panel">
                <div class="qv-panel-head"><div><span>COMPOSIÇÃO</span><h2>Destino das propostas</h2></div></div>
                <div id="qv-chart-composicao"></div>
                <div class="qv-chart-note">Estornos e declínios ficam fora da taxa de implantação.</div>
            </article>
        </section>

        <section class="qv-panel qv-rank-panel">
            <div class="qv-panel-head qv-rank-head">
                <div><span>DESEMPENHO INDIVIDUAL</span><h2>Rankings da equipe</h2></div>
                <div class="qv-tabs" id="qv-ranking-tabs">
                    <button class="is-active" data-ranking="valor_valido">Venda válida</button>
                    <button data-ranking="percentual_implantacao">% implantação</button>
                    <button data-ranking="valor_implantado">Valor implantado</button>
                    <button data-ranking="perdas">Perdas</button>
                </div>
            </div>
            <div class="qv-podium" id="qv-podium"></div>
            <div class="qv-rank-list" id="qv-rank-list"></div>
        </section>

        <section class="qv-panel">
            <div class="qv-panel-head">
                <div><span>VISÃO CONSOLIDADA</span><h2>Auditoria por vendedor</h2></div>
                <div class="qv-audit-actions">
                    <span class="qv-help">Clique em uma linha para conferir as propostas</span>
                    <button type="button" id="qv-exportar" class="qv-export-btn">
                        <i class="ri-file-excel-2-line"></i> Exportar Excel
                    </button>
                </div>
            </div>
            <div class="qv-table-wrap">
                <table class="qv-table">
                    <thead><tr><th>#</th><th>Vendedor</th><th>Vendido</th><th>Válido ranking</th><th>Implantadas</th><th>Em processo</th><th>Estornos</th><th>Declínios</th><th>% implantação</th><th>% perda</th></tr></thead>
                    <tbody id="qv-vendedores"></tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<div class="qv-modal" id="qv-modal" hidden>
    <div class="qv-modal-backdrop" data-close-modal></div>
    <div class="qv-modal-card" role="dialog" aria-modal="true" aria-labelledby="qv-modal-title">
        <header>
            <div><span>AUDITORIA DAS PROPOSTAS</span><h2 id="qv-modal-title">Detalhamento</h2><p id="qv-modal-subtitle"></p></div>
            <button type="button" data-close-modal aria-label="Fechar"><i class="ri-close-line"></i></button>
        </header>
        <div class="qv-modal-filters" id="qv-modal-categorias">
            <button class="is-active" data-categoria="">Todas</button>
            <button data-categoria="implantada">Implantadas</button>
            <button data-categoria="em_processo">Em processo</button>
            <button data-categoria="estorno">Estornos</button>
            <button data-categoria="declinio">Declínios</button>
        </div>
        <div class="qv-table-wrap qv-detail-wrap">
            <table class="qv-table qv-detail-table">
                <thead><tr><th>Proposta / cliente</th><th>Vendedor</th><th>Venda</th><th>Implantação</th><th>Status</th><th>Contrato</th><th>Angariação</th><th>Total</th></tr></thead>
                <tbody id="qv-propostas"></tbody>
            </table>
        </div>
        <footer id="qv-pagination"></footer>
    </div>
</div>
@endsection
