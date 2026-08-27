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
<main class="dl-page" data-inicio="{{ $inicioMes }}" data-hoje="{{ $hoje }}" data-vendedor-detalhes-url="{{ url('/relatorios/distribuicao-leads/vendedores') }}">
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
                <div class="dl-section-head"><div><span>EQUIPE</span><h2>Distribuição por vendedor</h2></div><small>Selecione uma linha para ver a fila comercial</small></div>
                <div class="dl-table-wrap">
                    <table class="dl-table">
                        <thead><tr><th>#</th><th>Vendedor</th><th>Atribuídos</th><th>Em trabalho</th><th>Remarketing</th><th>Fila administrativa</th><th>Participação</th><th><span class="visually-hidden">Abrir detalhes</span></th></tr></thead>
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

    <div class="modal fade dl-seller-modal" id="dl-seller-modal" tabindex="-1" aria-labelledby="dl-seller-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="dl-modal-heading">
                        <span class="dl-modal-icon"><i class="ri-user-star-line" aria-hidden="true"></i></span>
                        <div>
                            <h2 class="modal-title" id="dl-seller-modal-title">Detalhes do vendedor</h2>
                            <p id="dl-seller-modal-period">Carregando período…</p>
                        </div>
                    </div>
                    <button type="button" class="dl-modal-close" data-bs-dismiss="modal" aria-label="Fechar detalhes do vendedor"><i class="ri-close-line" aria-hidden="true"></i></button>
                </div>
                <div class="modal-body">
                    <div class="dl-modal-loading" id="dl-seller-modal-loading" role="status" aria-live="polite"><span></span>Carregando fila comercial…</div>
                    <div class="dl-modal-error" id="dl-seller-modal-error" role="alert" hidden>
                        <p>Não foi possível carregar os detalhes deste vendedor.</p>
                        <button type="button" id="dl-seller-modal-retry">Tentar novamente</button>
                    </div>
                    <div id="dl-seller-modal-content" hidden>
                        <section class="dl-modal-sale" aria-labelledby="dl-seller-sales-title">
                            <span><i class="ri-hand-coin-line" aria-hidden="true"></i></span>
                            <div><p id="dl-seller-sales-title">Viraram venda</p><small>Leads que entraram na base no período e possuem venda válida</small></div>
                            <strong id="dl-seller-sales-total">0</strong>
                        </section>
                        <section class="dl-modal-queue" aria-labelledby="dl-seller-queue-title">
                            <div class="dl-modal-section-head">
                                <div><h3 id="dl-seller-queue-title">Fila comercial por status</h3><p>Somente clientes atualmente em status comerciais.</p></div>
                                <strong id="dl-seller-queue-total">0</strong>
                            </div>
                            <div class="dl-modal-statuses" id="dl-seller-statuses"></div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
