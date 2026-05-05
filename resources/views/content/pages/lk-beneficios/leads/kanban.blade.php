@extends('layouts/layoutMaster')

@section('title', 'LK Benefícios - Pipeline')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
    ])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/lk-beneficios-kanban.scss')
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/sortablejs/sortable.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/lk-beneficios-kanban.js'])
@endsection

@section('content')
<div class="lkb-kanban-page">

    <div class="lkb-kanban-header">
        <div class="lkb-header-title-group">
            <div class="lkb-title-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="9" rx="1.5"/>
                    <rect x="14" y="3" width="7" height="5" rx="1.5"/>
                    <rect x="14" y="12" width="7" height="9" rx="1.5"/>
                    <rect x="3" y="16" width="7" height="5" rx="1.5"/>
                </svg>
            </div>
            <div class="lkb-title-text">
                <span class="lkb-greeting-label">Pipeline</span>
                <h4>Kanban de Leads</h4>
                <p class="lkb-subtitle">Arraste os cards entre as colunas para atualizar o status</p>
            </div>
        </div>

        <div class="lkb-header-actions">
            <a href="{{ route('lk-beneficios.base-saude.index') }}" class="lkb-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <ellipse cx="12" cy="5" rx="9" ry="3"/>
                    <path d="M3 5v6c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                    <path d="M3 11v6c0 1.66 4 3 9 3s9-1.34 9-3v-6"/>
                </svg>
                Base Saúde
            </a>
            <a href="{{ route('lk-beneficios.leads.novo') }}" class="lkb-btn lkb-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Novo Lead
            </a>
        </div>
    </div>

    <div class="lkb-kanban-board">
        @foreach($colunas as $coluna)
            <div class="lkb-kanban-column"
                 data-status="{{ $coluna['id'] }}"
                 style="--lkb-col-color-start: {{ $coluna['cor_start'] }}; --lkb-col-color-end: {{ $coluna['cor_end'] }};">
                <div class="lkb-kanban-column-header">
                    <span class="lkb-status-indicator" aria-hidden="true"></span>
                    <span class="lkb-status-name">{{ $coluna['label'] }}</span>
                    <span class="lkb-status-count" data-count="{{ $coluna['id'] }}">0</span>
                </div>
                <div class="lkb-kanban-column-body" data-column="{{ $coluna['id'] }}">
                    {{-- cards renderizados via JS --}}
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
        statusCores: @json($statusCores),
        csrf: @json(csrf_token()),
    };
</script>
@endsection
