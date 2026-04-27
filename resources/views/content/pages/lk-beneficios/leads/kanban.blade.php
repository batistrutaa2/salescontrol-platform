@extends('layouts/layoutMaster')

@section('title', 'LK Benefícios - Pipeline')

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/dashboard-analytics.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sortablejs/sortable.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/lk-beneficios-kanban.js'])
@endsection

@section('content')
<div class="dashboard-wrapper">

    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-text">
                <span class="greeting-label">Pipeline</span>
                <h1 class="main-title">Kanban de Leads</h1>
                <p class="subtitle">Arraste os cards entre colunas para atualizar o status</p>
            </div>
            <div class="header-filters">
                <div class="filter-group">
                    <a href="{{ route('lk-beneficios.leads.novo') }}" class="btn btn-sm btn-primary">
                        <i class="ri-add-line me-1"></i> Novo Lead
                    </a>
                    <a href="{{ route('lk-beneficios.base-saude.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="ri-database-2-line me-1"></i> Base Saúde
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="lkb-kanban-board">
        @foreach($colunas as $coluna)
            <div class="lkb-kanban-column" data-status="{{ $coluna['id'] }}">
                <div class="lkb-kanban-column-header"
                     style="background: linear-gradient(135deg, {{ $coluna['cor_start'] }} 0%, {{ $coluna['cor_end'] }} 100%);">
                    <span class="lkb-kanban-column-title">{{ $coluna['label'] }}</span>
                    <span class="lkb-kanban-column-count" data-count="{{ $coluna['id'] }}">0</span>
                </div>
                <div class="lkb-kanban-column-body" data-column="{{ $coluna['id'] }}">
                    {{-- cards preenchidos via JS --}}
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
    window.lkbKanban = {
        dadosUrl: @json(route('lk-beneficios.leads.dados')),
        moverUrl: @json(route('lk-beneficios.leads.mover')),
        converterUrlTemplate: @json(route('lk-beneficios.leads.converter.form', ['id' => '__ID__'])),
        showUrlTemplate: @json(route('lk-beneficios.leads.show', ['id' => '__ID__'])),
        csrf: @json(csrf_token()),
    };
</script>

<style>
    .lkb-kanban-board {
        display: flex;
        gap: 1rem;
        overflow-x: auto;
        padding-bottom: 1rem;
        min-height: calc(100vh - 250px);
    }
    .lkb-kanban-column {
        flex: 0 0 300px;
        background: var(--dash-glass-bg);
        backdrop-filter: blur(var(--dash-glass-blur));
        border: 1px solid var(--dash-card-border);
        border-radius: var(--dash-border-radius);
        box-shadow: var(--dash-shadow-sm);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .lkb-kanban-column-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.875rem 1rem;
        color: #fff;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .lkb-kanban-column-title {
        font-weight: 700;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .lkb-kanban-column-count {
        background: rgba(255,255,255,0.25);
        padding: 0.125rem 0.625rem;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.8125rem;
        font-family: 'JetBrains Mono', monospace;
    }
    .lkb-kanban-column-body {
        padding: 0.75rem;
        overflow-y: auto;
        flex: 1;
        min-height: 200px;
    }
    .lkb-card {
        background: var(--dash-card-bg);
        border: 1px solid var(--dash-card-border);
        border-radius: var(--dash-border-radius-sm);
        padding: 0.875rem;
        margin-bottom: 0.625rem;
        cursor: grab;
        transition: var(--dash-transition);
        box-shadow: var(--dash-shadow-sm);
    }
    .lkb-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--dash-shadow-md);
    }
    .lkb-card.sortable-drag { cursor: grabbing; opacity: 0.9; }
    .lkb-card.sortable-ghost { opacity: 0.4; }
    .lkb-card-nome {
        font-weight: 700;
        font-size: 0.875rem;
        color: var(--dash-text-primary);
        margin-bottom: 0.25rem;
    }
    .lkb-card-produto {
        font-size: 0.75rem;
        color: var(--dash-primary);
        font-weight: 600;
    }
    .lkb-card-meta {
        display: flex;
        justify-content: space-between;
        margin-top: 0.5rem;
        font-size: 0.6875rem;
        color: var(--dash-text-muted);
    }
    .lkb-card-badge-origem {
        display: inline-block;
        padding: 0.125rem 0.5rem;
        border-radius: 20px;
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .lkb-card-badge-origem.base-saude {
        background: rgba(var(--dash-info-rgb), 0.15);
        color: var(--dash-info);
    }
    .lkb-card-badge-origem.manual {
        background: rgba(var(--dash-primary-rgb), 0.15);
        color: var(--dash-primary);
    }
    .lkb-card-actions {
        display: flex;
        gap: 0.375rem;
        margin-top: 0.625rem;
    }
    .lkb-card-actions a, .lkb-card-actions button {
        font-size: 0.6875rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        text-decoration: none;
    }
</style>
@endsection
