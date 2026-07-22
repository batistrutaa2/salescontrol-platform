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
            <span class="pp-subtitle">Torre de controle da operação — cancelamento e portabilidade por urgência. O prazo começa quando a apólice é implantada; antes disso o processo fica "aguardando implantação".</span>
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

    {{-- Abas por trilha (+ concluídos). O conteúdo é montado pelo JS. --}}
    <div class="pp-tabs" role="tablist">
        <button type="button" class="pp-tab active" data-tab="cancelamentos" role="tab">
            Cancelamento <span class="pp-tab-count" id="tabcount-cancelamentos">0</span>
        </button>
        <button type="button" class="pp-tab" data-tab="portabilidade" role="tab">
            Portabilidade <span class="pp-tab-count" id="tabcount-portabilidade">0</span>
        </button>
        <button type="button" class="pp-tab" data-tab="concluidos" role="tab">
            Concluídos <span class="pp-tab-count" id="tabcount-concluidos">0</span>
        </button>
    </div>

    <div id="pp-content">
        <div class="pp-board-loading">Carregando…</div>
    </div>
</div>
@endsection
