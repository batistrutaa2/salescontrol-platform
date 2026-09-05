@extends('layouts/layoutMaster')

@section('title', 'Meu desempenho comercial')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/dashboard-vendedor.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/dashboard-vendedor.js'])
@endsection

@section('content')
<main class="dv-season" id="dv-dashboard" aria-busy="true">
    <header class="dv-season-header">
        <div>
            <h1>Seu desempenho em números</h1>
            <p>Acompanhe vendas válidas, posição no ranking e os contratos que construíram seu resultado.</p>
        </div>
        <div class="dv-period-filter" role="group" aria-label="Período do relatório">
            <button type="button" class="is-active" data-period="year" aria-pressed="true">Ano atual</button>
            <button type="button" data-period="month" aria-pressed="false">Mês atual</button>
            <button type="button" data-period="quarter" aria-pressed="false">Trimestre</button>
        </div>
    </header>

    <div class="dv-live-region visually-hidden" id="dv-live-region" aria-live="polite"></div>

    <section class="dv-scoreboard" aria-label="Resumo do período">
        <article class="dv-score-panel dv-score-total">
            <div class="dv-score-heading">
                <span>Valor válido no período</span>
                <button class="dv-info-button" type="button" aria-label="Como o valor válido é calculado" data-bs-toggle="tooltip" title="Contrato mais angariação. Apenas vendas com declínio são excluídas.">
                    <i class="ri-information-line" aria-hidden="true"></i>
                </button>
            </div>
            <strong class="dv-money dv-loading-line" id="dv-valid-value">R$ 0,00</strong>
            <p><b id="dv-sales-count">0</b> vendas válidas · ticket médio <b id="dv-average-ticket">R$ 0,00</b></p>
            <div class="dv-value-composition" aria-label="Composição do valor válido">
                <div><span>Contratos</span><b id="dv-contract-value">R$ 0,00</b></div>
                <div><span>Angariação</span><b id="dv-fundraising-value">R$ 0,00</b></div>
            </div>
        </article>

        <article class="dv-score-panel dv-score-ranking">
            <div class="dv-ranking-heading">
                <span>Ranking da corretora</span>
                <span id="dv-ranking-period">—</span>
            </div>
            <div class="dv-position-wrap">
                <strong id="dv-ranking-position">—</strong>
                <span id="dv-ranking-suffix">º</span>
            </div>
            <p id="dv-ranking-context">Calculando sua posição…</p>
            <div class="dv-ranking-movement" id="dv-ranking-movement" role="status" aria-live="polite" hidden>
                <i id="dv-ranking-movement-icon" aria-hidden="true"></i>
                <div>
                    <strong id="dv-ranking-movement-title"></strong>
                    <span id="dv-ranking-movement-copy"></span>
                </div>
            </div>
            <div class="dv-ranking-gap" id="dv-ranking-gap" hidden>
                <i class="ri-arrow-up-line" aria-hidden="true"></i>
                <span>Faltam <b id="dv-ranking-distance">R$ 0,00</b> para a posição anterior</span>
            </div>
        </article>

        <article class="dv-score-panel dv-score-record">
            <div class="dv-score-heading">
                <span>Maior venda do período</span>
                <i class="ri-trophy-line" aria-hidden="true"></i>
            </div>
            <strong class="dv-money" id="dv-largest-total">R$ 0,00</strong>
            <h2 id="dv-largest-client">Nenhuma venda no período</h2>
            <p id="dv-largest-product">O primeiro grande contrato aparecerá aqui.</p>
            <dl class="dv-record-split" id="dv-largest-split" hidden>
                <div><dt>Contrato</dt><dd id="dv-largest-contract">R$ 0,00</dd></div>
                <div><dt>Angariação</dt><dd id="dv-largest-fundraising">R$ 0,00</dd></div>
            </dl>
        </article>
    </section>

    <div class="dv-rank-celebration" id="dv-rank-celebration" aria-hidden="true" hidden>
        @for ($particle = 0; $particle < 18; $particle++)
            <i style="--particle: {{ $particle }}"></i>
        @endfor
    </div>

    <section class="dv-season-track" aria-labelledby="dv-track-title">
        <div class="dv-section-heading">
            <div>
                <h2 id="dv-track-title">Evolução do período</h2>
                <p>Distribuição das vendas válidas nos meses selecionados.</p>
            </div>
        </div>
        <div class="dv-months" id="dv-months" role="list" aria-label="Meses do ano"></div>
        <div class="dv-chart-wrap" id="dv-chart-wrap">
            <div id="dvMonthlyChart" aria-label="Gráfico de vendas válidas por mês"></div>
        </div>
    </section>

    <section class="dv-insights" aria-label="Destaques do ano">
        <article class="dv-insight-product">
            <div class="dv-insight-heading">
                <i class="ri-award-line" aria-hidden="true"></i>
                <span>Produto mais vendido</span>
            </div>
            <h2 id="dv-top-product">Ainda sem produto líder</h2>
            <p id="dv-top-product-operator">As vendas válidas do ano definirão o destaque.</p>
            <div class="dv-product-stats">
                <div><strong id="dv-top-product-count">0</strong><span>contratos</span></div>
                <div><strong id="dv-top-product-total">R$ 0,00</strong><span>valor vendido</span></div>
            </div>
        </article>

        <article class="dv-insight-health">
            <div class="dv-section-heading compact">
                <div>
                    <h2>Qualidade da temporada</h2>
                    <p>Indicadores que ajudam a interpretar o valor total.</p>
                </div>
            </div>
            <dl class="dv-health-list">
                <div><dt>Vendas implantadas</dt><dd><b id="dv-implanted-count">0</b><span id="dv-implanted-value">R$ 0,00</span></dd></div>
                <div><dt>Taxa de implantação</dt><dd><b id="dv-implantation-rate">0%</b><span>sobre vendas válidas</span></dd></div>
                <div><dt>Melhor mês</dt><dd><b id="dv-best-month">—</b><span id="dv-best-month-value">Sem vendas</span></dd></div>
            </dl>
            <a href="{{ route('sale.meusEstornos') }}" class="dv-reversal-link" id="dv-reversal-link">
                <span><i class="ri-arrow-go-back-line" aria-hidden="true"></i> Estornos aguardando tratativa</span>
                <b id="dv-pending-reversals">0</b>
            </a>
        </article>

        <article class="dv-insight-ranking">
            <div class="dv-section-heading compact">
                <div>
                    <h2>Líderes</h2>
                    <p>Top 3 da corretora e a sua posição.</p>
                </div>
            </div>
            <ol class="dv-leaders" id="dv-leaders">
                <li class="dv-empty-inline">O ranking será exibido aqui.</li>
            </ol>
        </article>
    </section>

    <section class="dv-sales-ledger" aria-labelledby="dv-ledger-title">
        <div class="dv-section-heading">
            <div>
                <h2 id="dv-ledger-title">Vendas que formam o resultado</h2>
                <p id="dv-ledger-subtitle">Últimas vendas válidas do ano inteiro.</p>
            </div>
            <div class="dv-ledger-actions">
                <span class="dv-result-count" id="dv-detail-count">0 vendas</span>
                <a href="{{ route('sale.listSale') }}">Ver todas <i class="ri-arrow-right-line" aria-hidden="true"></i></a>
            </div>
        </div>
        <div class="dv-table-wrap">
            <table class="dv-sales-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente e produto</th>
                        <th>Contrato</th>
                        <th>Angariação</th>
                        <th>Total válido</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="dv-sales-body">
                    <tr><td colspan="6" class="dv-table-state">Carregando suas vendas…</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="dv-error" id="dv-error" role="alert" hidden>
        <i class="ri-wifi-off-line" aria-hidden="true"></i>
        <div><b>Não foi possível carregar seus números.</b><span>Verifique a conexão e tente novamente.</span></div>
        <button type="button" id="dv-retry">Tentar novamente</button>
    </section>
</main>
@endsection
