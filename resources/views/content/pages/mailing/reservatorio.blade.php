@extends('layouts/layoutMaster')

@section('title', 'Reservatório de Leads')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/scss/pages/reservatorio-leads.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/reservatorio-leads.js'])
@endsection

@section('content')
    <!--
      DESIGN CONTRACT
      THESIS: uma mesa de comando operacional, densa e confiável, para decidir antes de distribuir.
      OWN-WORLD: extensão do SalesControl/Materio; neutros do produto, índigo para ação e âmbar apenas para migração.
      STORY: leads entram protegidos, critérios reduzem a seleção, pessoas definem as cotas e o histórico prova a saída.
      FIRST VIEWPORT: decisão, regra irreversível, saúde do estoque e acesso imediato à estratégia.
      FORM: mesa de comando tabular, direção operacional 7; seed key 19a80354.
      FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance.
    -->
    <main class="lead-vault"
        data-list-url="{{ route('mailing.reservatorio.dados') }}"
        data-strategies-url="{{ route('mailing.reservatorio.estrategias.index') }}"
        data-history-url="{{ route('mailing.reservatorio.historico') }}"
        data-preview-url="{{ route('mailing.reservatorio.preview') }}"
        data-random-preview-url="{{ route('mailing.reservatorio.aleatoria.preview') }}"
        data-random-execute-url="{{ route('mailing.reservatorio.aleatoria.executar') }}"
        data-strategy-base-url="{{ url('/mailing/reservatorio/estrategias') }}"
        data-migration-preview-url="{{ route('mailing.reservatorio.migracao.preview') }}"
        data-migration-url="{{ route('mailing.reservatorio.migracao.executar') }}"
        data-vendors='@json($vendedores)'
        data-migration-complete="{{ $migracaoConcluida ? '1' : '0' }}"
        aria-labelledby="lead-vault-title">

        <header class="lv-header">
            <div class="lv-header-copy">
                <div class="lv-title-mark" aria-hidden="true"><i class="ri-database-2-line"></i></div>
                <div><h1 id="lead-vault-title">Reservatório de Leads</h1>
                <p>Centralize os leads novos, salve critérios de seleção e distribua volumes exatos para o time comercial.</p>
                </div>
            </div>
            <div class="lv-header-actions">
                @unless($migracaoConcluida)
                    <button type="button" class="lv-button lv-button-warning" id="open-migration">
                        <i class="ri-inbox-unarchive-line" aria-hidden="true"></i>
                        Carga inicial
                    </button>
                @endunless
                <button type="button" class="lv-button lv-button-secondary" id="random-distribution">
                    <i class="ri-shuffle-line" aria-hidden="true"></i>
                    Distribuição rápida
                </button>
                <button type="button" class="lv-button lv-button-primary" id="new-strategy">
                    <i class="ri-filter-3-line" aria-hidden="true"></i>
                    Nova estratégia
                </button>
            </div>
        </header>

        <section class="lv-workspace" aria-label="Área de trabalho do reservatório">
        <div class="lv-rule" aria-label="Regra de saída do reservatório">
            <i class="ri-shield-check-line" aria-hidden="true"></i>
            <div>
                <strong>Regra de saída</strong>
                <span>Depois de distribuído, o lead não retorna ao reservatório. Vendidos, descartados, remarketing e leads já vinculados são bloqueados.</span>
            </div>
        </div>

        <section class="lv-metrics" aria-label="Indicadores do reservatório">
            <article><span>Disponíveis agora</span><strong id="metric-available">—</strong><small>aptos para estratégia</small></article>
            <article><span>Entradas em {{ $indicadoresJanelaDias }} dias</span><strong id="metric-entered">—</strong><small>mailing, marketing e carga</small></article>
            <article><span>Distribuídos no mês</span><strong id="metric-distributed">—</strong><small>saídas concluídas</small></article>
            <article><span>Bloqueados</span><strong id="metric-blocked">—</strong><small>retidos pelas regras</small></article>
        </section>

        <nav class="lv-tabs" role="tablist" aria-label="Áreas do reservatório">
            <button type="button" id="tab-leads" class="is-active" role="tab" data-tab="leads" aria-controls="panel-leads" aria-selected="true" tabindex="0">Leads disponíveis</button>
            <button type="button" id="tab-strategies" role="tab" data-tab="strategies" aria-controls="panel-strategies" aria-selected="false" tabindex="-1">Estratégias</button>
            <button type="button" id="tab-history" role="tab" data-tab="history" aria-controls="panel-history" aria-selected="false" tabindex="-1">Histórico</button>
        </nav>

        <section class="lv-panel is-active" id="panel-leads" role="tabpanel" aria-labelledby="tab-leads" data-panel="leads">
            <div class="lv-toolbar">
                <label class="lv-search">
                    <i class="ri-search-line" aria-hidden="true"></i>
                    <span class="visually-hidden">Buscar lead</span>
                    <input type="search" id="lead-search" placeholder="Buscar por nome, CPF, telefone ou base">
                </label>
                <select class="form-select lv-control" id="lead-origin" aria-label="Filtrar por origem">
                    <option value="">Todas as origens</option>
                    <option value="IMPORTACAO">Mailing</option>
                    <option value="MARKETING">Marketing</option>
                    <option value="MIGRACAO_INICIAL">Carga inicial</option>
                </select>
                <select class="form-select lv-control" id="lead-base" aria-label="Filtrar por base">
                    <option value="">Todas as bases</option>
                </select>
                <button type="button" class="lv-icon-button" id="refresh-leads" aria-label="Atualizar lista">
                    <i class="ri-refresh-line" aria-hidden="true"></i>
                </button>
            </div>

            <div class="lv-table-shell">
                <table class="table lv-table">
                    <thead><tr><th>Lead</th><th>Origem e base</th><th>Perfil</th><th>Potencial</th><th>Entrada</th></tr></thead>
                    <tbody id="leads-body"></tbody>
                </table>
                <div class="lv-state d-none" id="leads-state"></div>
            </div>
            <footer class="lv-pagination">
                <span id="leads-range">Carregando…</span>
                <div>
                    <button type="button" class="lv-button lv-button-quiet lv-button-small" id="leads-prev"><i class="ri-arrow-left-s-line" aria-hidden="true"></i>Anterior</button>
                    <button type="button" class="lv-button lv-button-quiet lv-button-small" id="leads-next">Próxima<i class="ri-arrow-right-s-line" aria-hidden="true"></i></button>
                </div>
            </footer>
        </section>

        <section class="lv-panel" id="panel-strategies" role="tabpanel" aria-labelledby="tab-strategies" data-panel="strategies" hidden>
            <div class="lv-section-heading">
                <div><h2>Estratégias salvas</h2><p>Os critérios permanecem salvos; os vendedores e volumes são escolhidos em cada execução.</p></div>
                <button type="button" class="lv-button lv-button-primary" data-create-strategy><i class="ri-add-line"></i> Criar estratégia</button>
            </div>
            <div class="lv-strategy-list" id="strategy-list"></div>
            <div class="lv-state d-none" id="strategies-state"></div>
        </section>

        <section class="lv-panel" id="panel-history" role="tabpanel" aria-labelledby="tab-history" data-panel="history" hidden>
            <div class="lv-section-heading"><div><h2>Histórico de decisões</h2><p>Cada distribuição e carga inicial fica registrada com autor, volumes e momento da execução.</p></div></div>
            <div class="lv-table-shell">
                <table class="table lv-table">
                    <thead><tr><th>Execução</th><th>Estratégia / origem</th><th>Volume</th><th>Responsável</th><th>Quando</th></tr></thead>
                    <tbody id="history-body"></tbody>
                </table>
                <div class="lv-state d-none" id="history-state"></div>
            </div>
            <footer class="lv-pagination">
                <span id="history-range">Carregando…</span>
                <div>
                    <button type="button" class="lv-button lv-button-quiet lv-button-small" id="history-prev"><i class="ri-arrow-left-s-line" aria-hidden="true"></i>Anterior</button>
                    <button type="button" class="lv-button lv-button-quiet lv-button-small" id="history-next">Próxima<i class="ri-arrow-right-s-line" aria-hidden="true"></i></button>
                </div>
            </footer>
        </section>
        </section>

        <div class="modal fade lv-modal" id="strategy-modal" tabindex="-1" aria-labelledby="strategy-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                <form id="strategy-form">
                    <div class="modal-header"><div class="lv-modal-heading"><span><i class="ri-filter-3-line"></i></span><div><h2 class="modal-title" id="strategy-modal-title">Nova estratégia</h2><p>Combine condições. Todas precisam ser verdadeiras para o lead participar.</p></div></div><button type="button" class="lv-icon-button" data-bs-dismiss="modal" aria-label="Fechar"><i class="ri-close-line"></i></button></div>
                    <div class="modal-body">
                        <label class="form-label" for="strategy-name">Nome da estratégia</label>
                        <input class="form-control lv-control" id="strategy-name" maxlength="255" placeholder="Ex.: PME com 3 ou mais vidas" required>
                        <div class="lv-builder-heading"><div><h3>Condições</h3><p>Valores separados por vírgula significam “qualquer um deles”.</p></div><button type="button" class="lv-button lv-button-quiet lv-button-small" id="add-condition"><i class="ri-add-line"></i> Condição</button></div>
                        <div class="lv-condition-list" id="condition-list"></div>
                        <div class="lv-preview" id="strategy-preview"><span>Prévia da seleção</span><strong>Adicione as condições e confira quantos leads serão alcançados.</strong></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="lv-button lv-button-quiet" data-bs-dismiss="modal">Cancelar</button><button type="button" class="lv-button lv-button-secondary" id="preview-draft">Calcular prévia</button><button type="submit" class="lv-button lv-button-primary" id="save-strategy">Salvar estratégia</button></div>
                </form>
            </div></div>
        </div>

        <div class="modal fade lv-modal" id="execution-modal" tabindex="-1" aria-labelledby="execution-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                <form id="execution-form">
                    <div class="modal-header"><div class="lv-modal-heading"><span><i class="ri-shuffle-line"></i></span><div><h2 class="modal-title" id="execution-modal-title">Distribuir leads</h2><p id="execution-copy"></p></div></div><button type="button" class="lv-icon-button" data-bs-dismiss="modal" aria-label="Fechar"><i class="ri-close-line"></i></button></div>
                    <div class="modal-body">
                        <div class="lv-execution-summary"><span id="execution-available-label">Leads disponíveis agora</span><strong id="execution-available">—</strong></div>
                        <div class="lv-allocation-heading"><strong>Defina a quantidade por vendedor</strong><span>Somente vendedores ativos recebem leads.</span></div>
                        <div id="allocation-list" class="lv-allocation-list"></div>
                        <div class="lv-total"><span>Total solicitado</span><strong id="allocation-total">0</strong></div>
                        <div class="lv-inline-row"><div class="lv-inline-status" id="execution-status" role="status">Aguarde a prévia para definir as cotas.</div><button type="button" class="lv-button lv-button-quiet lv-button-small" id="refresh-execution-preview"><i class="ri-refresh-line"></i> Atualizar prévia</button></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="lv-button lv-button-quiet" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="lv-button lv-button-primary" id="execute-strategy" disabled>Confirmar distribuição aleatória</button></div>
                </form>
            </div></div>
        </div>

        <div class="modal fade lv-modal" id="migration-modal" tabindex="-1" aria-labelledby="migration-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
                <form id="migration-form">
                    <div class="modal-header"><div class="lv-modal-heading is-warning"><span><i class="ri-inbox-unarchive-line"></i></span><div><h2 class="modal-title" id="migration-modal-title">Carga inicial excepcional</h2><p>Mova os leads novos concentrados em um vendedor para o reservatório, uma única vez.</p></div></div><button type="button" class="lv-icon-button" data-bs-dismiss="modal" aria-label="Fechar"><i class="ri-close-line"></i></button></div>
                    <div class="modal-body">
                        <div class="lv-warning"><i class="ri-error-warning-line"></i><div><strong>Esta ação remove o vínculo atual.</strong><span>O histórico do lead é preservado. Vendidos, descartados, remarketing, fechados e preditiva não serão movidos.</span></div></div>
                        <label class="form-label" for="migration-vendor">Vendedor que concentra os leads novos</label>
                        <select class="form-select lv-control" id="migration-vendor" required><option value="">Selecione o vendedor</option>@foreach($vendedores as $vendedor)<option value="{{ $vendedor->id }}">{{ $vendedor->name }}{{ $vendedor->ativo === 'Y' ? '' : ' (inativo)' }}</option>@endforeach</select>
                        <div class="lv-migration-preview d-none" id="migration-preview"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="lv-button lv-button-quiet" data-bs-dismiss="modal">Cancelar</button><button type="button" class="lv-button lv-button-secondary" id="preview-migration">Conferir impacto</button><button type="submit" class="lv-button lv-button-warning-solid" id="execute-migration" disabled>Mover leads aptos</button></div>
                </form>
            </div></div>
        </div>

        <div class="modal fade lv-modal" id="confirm-modal" tabindex="-1" aria-labelledby="confirm-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                <div class="modal-header"><div class="lv-modal-heading is-warning"><span><i class="ri-alert-line"></i></span><div><h2 class="modal-title" id="confirm-modal-title">Confirmar ação</h2><p id="confirm-modal-copy"></p></div></div><button type="button" class="lv-icon-button" data-bs-dismiss="modal" aria-label="Fechar"><i class="ri-close-line"></i></button></div>
                <div class="modal-footer"><button type="button" class="lv-button lv-button-quiet" data-bs-dismiss="modal">Cancelar</button><button type="button" class="lv-button lv-button-warning-solid" id="confirm-modal-action">Confirmar</button></div>
            </div></div>
        </div>

        <div class="visually-hidden" id="reservoir-live" aria-live="polite"></div>
    </main>
@endsection
