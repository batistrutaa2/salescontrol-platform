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
            <span class="pp-subtitle">Tudo que está em aberto na operação — por tipo, prazo e responsável</span>
        </div>
    </div>

    {{-- KPIs (clicáveis: filtram a fila) --}}
    <div class="pp-kpis">
        <button type="button" class="pp-kpi" data-filter="grupo:cancelamentos">
            <span class="pp-kpi-label">Cancelamentos em aberto</span>
            <span class="pp-kpi-value" id="kpi-cancel">—</span>
        </button>
        <button type="button" class="pp-kpi" data-filter="tipo:PORTABILIDADE">
            <span class="pp-kpi-label">Portabilidades em aberto</span>
            <span class="pp-kpi-value" id="kpi-portab">—</span>
        </button>
        <button type="button" class="pp-kpi pp-kpi-danger" data-filter="so_atrasados:1">
            <span class="pp-kpi-label">Atrasados (fora do prazo)</span>
            <span class="pp-kpi-value" id="kpi-atrasados">—</span>
        </button>
        <button type="button" class="pp-kpi pp-kpi-muted" data-filter="">
            <span class="pp-kpi-label">Total em aberto</span>
            <span class="pp-kpi-value" id="kpi-total">—</span>
        </button>
        <div class="pp-kpi pp-kpi-success pp-kpi-static">
            <span class="pp-kpi-label">Concluídos no mês</span>
            <span class="pp-kpi-value" id="kpi-concluidos">—</span>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="pp-filters">
        <input type="text" id="pp-busca" class="pp-input pp-input-grow" placeholder="Buscar por contrato ou pessoa…">
        <select id="pp-tipo" class="pp-input"><option value="">Todos os tipos</option></select>
        <select id="pp-responsavel" class="pp-input">
            <option value="">Todos os responsáveis</option>
            <option value="sem">Sem responsável</option>
        </select>
        <label class="pp-check"><input type="checkbox" id="pp-atrasados"> Só atrasados</label>
        <button type="button" id="pp-limpar" class="pp-btn pp-btn-ghost">Limpar</button>
    </div>

    <div class="pp-table-wrap">
        <table class="pp-table">
            <thead>
                <tr>
                    <th>Contrato</th>
                    <th>Processo</th>
                    <th>Pessoa</th>
                    <th>Responsável</th>
                    <th>Aberto há</th>
                    <th>Prazo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="pp-tbody">
                <tr><td colspan="7" class="pp-loading">Carregando…</td></tr>
            </tbody>
        </table>

        <div class="pp-pagination" id="pp-pagination" style="display:none;">
            <span class="pp-pag-info" id="pp-pag-info"></span>
            <div class="pp-pag-controls">
                <button type="button" class="pp-btn pp-btn-ghost pp-btn-sm" id="pp-pag-prev" aria-label="Página anterior">‹ Anterior</button>
                <span class="pp-pag-atual" id="pp-pag-atual"></span>
                <button type="button" class="pp-btn pp-btn-ghost pp-btn-sm" id="pp-pag-next" aria-label="Próxima página">Próxima ›</button>
            </div>
        </div>
    </div>
</div>
@endsection
