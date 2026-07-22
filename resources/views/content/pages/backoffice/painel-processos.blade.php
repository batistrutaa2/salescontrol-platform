@extends('layouts/layoutMaster')

@section('title', 'Painel de Processos')

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/painel-processos.scss')
@endsection

@section('page-script')
    @vite('resources/assets/js/painel-processos.js')
@endsection

@section('content')
<div id="painel-processos" data-csrf="{{ csrf_token() }}">
    <script>
        window.__painel = {
            responsaveis: @json($responsaveis),
            tipos: @json($tipos),
        };
    </script>

    <div class="pp-header">
        <div>
            <h4 class="pp-title">Painel de Processos</h4>
            <span class="pp-subtitle">A esteira da operação — cancelamento e portabilidade, do "aguardando implantação" à conclusão. O prazo começa quando a apólice é implantada.</span>
        </div>
    </div>

    {{-- KPIs de acionabilidade (clicáveis: filtram os boards por urgência) --}}
    <div class="pp-kpis">
        <button type="button" class="pp-kpi pp-kpi-danger" data-urg="atrasado">
            <span class="pp-kpi-label">Atrasados</span>
            <span class="pp-kpi-value" id="kpi-atrasados">—</span>
            <span class="pp-kpi-hint">passaram do prazo</span>
        </button>
        <button type="button" class="pp-kpi pp-kpi-warning" data-urg="vencendo">
            <span class="pp-kpi-label">Vencendo</span>
            <span class="pp-kpi-value" id="kpi-vencendo">—</span>
            <span class="pp-kpi-hint">vencem em até 7 dias</span>
        </button>
        <button type="button" class="pp-kpi pp-kpi-muted" data-urg="aguardando_implantacao">
            <span class="pp-kpi-label">Aguardando implantação</span>
            <span class="pp-kpi-value" id="kpi-aguardando">—</span>
            <span class="pp-kpi-hint">travados até a apólice implantar</span>
        </button>
        <div class="pp-kpi pp-kpi-success pp-kpi-static">
            <span class="pp-kpi-label">Concluídos no mês</span>
            <span class="pp-kpi-value" id="kpi-concluidos">—</span>
            <span class="pp-kpi-hint">baixados neste mês</span>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="pp-filters">
        <input type="text" id="pp-busca" class="pp-input pp-input-grow" placeholder="Buscar por contrato ou pessoa…">
        <select id="pp-responsavel" class="pp-input">
            <option value="">Todos os responsáveis</option>
            <option value="sem">Sem responsável</option>
        </select>
        <button type="button" id="pp-limpar" class="pp-btn pp-btn-ghost">Limpar</button>
        <span class="pp-urg-tag" id="pp-urg-tag" style="display:none;"></span>
    </div>

    {{-- Esteiras (kanban por fase). As colunas são montadas pelo JS. --}}
    <div class="pp-boards" id="pp-boards">
        <section class="pp-board" data-esteira="cancelamentos">
            <div class="pp-board-head">
                <h5 class="pp-board-title">Cancelamento na operadora anterior</h5>
                <span class="pp-board-sub">só roda depois que a apólice atual é implantada</span>
                <span class="pp-board-count" id="count-cancelamentos">0</span>
            </div>
            <div class="pp-board-track" id="board-cancelamentos">
                <div class="pp-board-loading">Carregando…</div>
            </div>
        </section>

        <section class="pp-board" data-esteira="portabilidade">
            <div class="pp-board-head">
                <h5 class="pp-board-title">Portabilidade</h5>
                <span class="pp-board-sub">normalmente após implantar a apólice, portar quem precisa</span>
                <span class="pp-board-count" id="count-portabilidade">0</span>
            </div>
            <div class="pp-board-track" id="board-portabilidade">
                <div class="pp-board-loading">Carregando…</div>
            </div>
        </section>
    </div>
</div>
@endsection
