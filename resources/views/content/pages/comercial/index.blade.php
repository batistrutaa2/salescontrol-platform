@extends('layouts/layoutMaster')

@section('title', 'Lista de Clientes - Kanban')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/jkanban/jkanban.scss', 'resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/quill/editor.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/app-kanban.scss', 'resources/assets/vendor/scss/pages/pos-venda.scss', 'resources/assets/vendor/scss/pages/kanban-modal.scss'])
    <style>
        /* ========================================
           CRONOGRAMA - MODERN TIMELINE DESIGN
        ======================================== */

        /* Cronograma Header */
        .cronograma-header {
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
        }

        .light-style .cronograma-header {
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .dark-style .cronograma-header {
            background: #2b2c40;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
        }

        .cronograma-title {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.25rem;
        }

        .cronograma-subtitle {
            font-size: 0.9375rem;
            opacity: 0.7;
        }

        /* Filter pills container */
        .cronograma-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            padding: 0.75rem;
            border-radius: 12px;
        }

        .light-style .cronograma-filters {
            background: rgba(105, 108, 255, 0.04);
        }

        .dark-style .cronograma-filters {
            background: rgba(105, 108, 255, 0.08);
        }

        /* Filter pills modernos */
        .filtro-cronograma {
            border-radius: 25px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filtro-cronograma i {
            font-size: 1.125rem;
        }

        .filtro-cronograma.active {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(105, 108, 255, 0.35);
        }

        .filtro-cronograma .badge {
            font-size: 0.7rem;
            padding: 4px 8px;
            font-weight: 700;
            border-radius: 12px;
            min-width: 24px;
        }

        /* Timeline container */
        .daily-timeline {
            padding: 1.5rem 0;
            position: relative;
        }

        /* Vertical line connecting days */
        .daily-timeline::before {
            content: '';
            position: absolute;
            left: 32px;
            top: 0;
            bottom: 0;
            width: 3px;
            border-radius: 3px;
        }

        .light-style .daily-timeline::before {
            background: linear-gradient(180deg, rgba(105, 108, 255, 0.15) 0%, rgba(105, 108, 255, 0.05) 100%);
        }

        .dark-style .daily-timeline::before {
            background: linear-gradient(180deg, rgba(105, 108, 255, 0.25) 0%, rgba(105, 108, 255, 0.08) 100%);
        }

        /* Day section */
        .timeline-day-section {
            margin-bottom: 2.5rem;
            position: relative;
            padding-left: 80px;
        }

        /* Day marker (circle on timeline) */
        .timeline-day-marker {
            position: absolute;
            left: 12px;
            top: 0;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #fff;
            z-index: 2;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .timeline-day-marker.status-danger {
            background: linear-gradient(135deg, #ff3e1d 0%, #ff6348 100%);
        }

        .timeline-day-marker.status-info {
            background: linear-gradient(135deg, #03c3ec 0%, #0abde3 100%);
        }

        .timeline-day-marker.status-warning {
            background: linear-gradient(135deg, #ffab00 0%, #ff9f43 100%);
        }

        .timeline-day-marker.status-success {
            background: linear-gradient(135deg, #71dd37 0%, #5ec425 100%);
        }

        .timeline-day-marker.status-secondary {
            background: linear-gradient(135deg, #8592a3 0%, #a1acb8 100%);
        }

        /* Day header - PROMINENT */
        .timeline-day-header {
            padding: 1.25rem 1.5rem;
            border-radius: 16px;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            position: relative;
            overflow: hidden;
        }

        /* Gradient accent bar */
        .timeline-day-header::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
        }

        .timeline-day-header.status-danger::before {
            background: linear-gradient(180deg, #ff3e1d, #ff6348);
        }

        .timeline-day-header.status-info::before {
            background: linear-gradient(180deg, #03c3ec, #0abde3);
        }

        .timeline-day-header.status-warning::before {
            background: linear-gradient(180deg, #ffab00, #ff9f43);
        }

        .timeline-day-header.status-success::before {
            background: linear-gradient(180deg, #71dd37, #5ec425);
        }

        .timeline-day-header.status-secondary::before {
            background: linear-gradient(180deg, #8592a3, #a1acb8);
        }

        .light-style .timeline-day-header {
            background: #fff;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .dark-style .timeline-day-header {
            background: #2b2c40;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }

        /* TODAY special highlight */
        .timeline-day-header.is-today {
            border: 2px solid;
        }

        .light-style .timeline-day-header.is-today {
            border-color: #03c3ec;
            background: linear-gradient(135deg, rgba(3, 195, 236, 0.08) 0%, rgba(3, 195, 236, 0.02) 100%);
        }

        .dark-style .timeline-day-header.is-today {
            border-color: #03c3ec;
            background: linear-gradient(135deg, rgba(3, 195, 236, 0.15) 0%, rgba(3, 195, 236, 0.05) 100%);
        }

        /* OVERDUE special highlight */
        .timeline-day-header.is-overdue {
            border: 2px solid;
        }

        .light-style .timeline-day-header.is-overdue {
            border-color: #ff3e1d;
            background: linear-gradient(135deg, rgba(255, 62, 29, 0.08) 0%, rgba(255, 62, 29, 0.02) 100%);
        }

        .dark-style .timeline-day-header.is-overdue {
            border-color: #ff3e1d;
            background: linear-gradient(135deg, rgba(255, 62, 29, 0.15) 0%, rgba(255, 62, 29, 0.05) 100%);
        }

        .timeline-day-info {
            flex: 1;
        }

        .timeline-day-date {
            font-size: 1.375rem;
            font-weight: 800;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .timeline-day-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .timeline-day-label {
            font-size: 0.9375rem;
            opacity: 0.75;
            font-weight: 500;
        }

        .timeline-day-count {
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 0.9375rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .timeline-day-count i {
            font-size: 1.125rem;
        }

        /* Task cards container */
        .timeline-day-tasks {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        /* Schedule card moderna */
        .schedule-card {
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            border: none;
            position: relative;
        }

        .light-style .schedule-card {
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .dark-style .schedule-card {
            background: #2b2c40;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.35);
        }

        .schedule-card:hover {
            transform: translateX(8px);
        }

        .light-style .schedule-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .dark-style .schedule-card:hover {
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.5);
        }

        /* Status indicator left border */
        .schedule-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
        }

        .schedule-card.status-danger::before {
            background: linear-gradient(180deg, #ff3e1d, #ff6348);
        }

        .schedule-card.status-info::before {
            background: linear-gradient(180deg, #03c3ec, #0abde3);
        }

        .schedule-card.status-warning::before {
            background: linear-gradient(180deg, #ffab00, #ff9f43);
        }

        .schedule-card.status-success::before {
            background: linear-gradient(180deg, #71dd37, #5ec425);
        }

        .schedule-card.status-secondary::before {
            background: linear-gradient(180deg, #8592a3, #a1acb8);
        }

        .schedule-card-inner {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        /* Time block */
        .schedule-time-block {
            min-width: 80px;
            text-align: center;
            padding: 0.75rem;
            border-radius: 12px;
        }

        .light-style .schedule-time-block {
            background: rgba(105, 108, 255, 0.06);
        }

        .dark-style .schedule-time-block {
            background: rgba(105, 108, 255, 0.12);
        }

        .schedule-time {
            font-size: 1.5rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 4px;
        }

        .schedule-time-label {
            font-size: 0.6875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.7;
            font-weight: 600;
        }

        /* Client info */
        .schedule-client-info {
            flex: 1;
            min-width: 0;
        }

        .schedule-client-name {
            font-size: 1.0625rem;
            font-weight: 700;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .schedule-client-name i {
            font-size: 1.125rem;
            opacity: 0.7;
        }

        .schedule-broker {
            font-size: 0.8125rem;
            opacity: 0.7;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px;
        }

        .schedule-observation {
            font-size: 0.8125rem;
            padding: 8px 12px;
            border-radius: 8px;
            margin-top: 8px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .light-style .schedule-observation {
            background: rgba(0, 0, 0, 0.03);
        }

        .dark-style .schedule-observation {
            background: rgba(255, 255, 255, 0.04);
        }

        .schedule-observation i {
            margin-top: 2px;
            opacity: 0.6;
        }

        /* Status badge */
        .schedule-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Relative time */
        .schedule-relative {
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Actions */
        .schedule-actions {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 120px;
        }

        .schedule-actions .btn {
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.8125rem;
            padding: 8px 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .schedule-actions .btn i {
            font-size: 1rem;
        }

        /* Empty state moderno */
        .cronograma-empty-state {
            text-align: center;
            padding: 80px 20px;
            border-radius: 20px;
        }

        .light-style .cronograma-empty-state {
            background: linear-gradient(135deg, rgba(105, 108, 255, 0.04) 0%, rgba(105, 108, 255, 0.01) 100%);
        }

        .dark-style .cronograma-empty-state {
            background: linear-gradient(135deg, rgba(105, 108, 255, 0.08) 0%, rgba(105, 108, 255, 0.02) 100%);
        }

        .cronograma-empty-icon {
            font-size: 5rem;
            margin-bottom: 24px;
            opacity: 0.2;
        }

        .cronograma-empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .cronograma-empty-text {
            opacity: 0.6;
            font-size: 1rem;
        }

        /* Badge contador cronograma */
        #badge-cronograma-hoje {
            font-size: 0.6875rem;
            font-weight: 700;
            padding: 4px 8px;
            min-width: 22px;
            display: inline-block;
            text-align: center;
            border-radius: 12px;
        }

        #badge-cronograma-hoje .ri-spin {
            font-size: 12px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .schedule-card-inner {
                flex-wrap: wrap;
            }

            .schedule-actions {
                width: 100%;
                flex-direction: row;
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px solid rgba(0, 0, 0, 0.06);
            }

            .schedule-actions .btn {
                flex: 1;
            }
        }

        @media (max-width: 768px) {
            .timeline-day-section {
                padding-left: 0;
            }

            .daily-timeline::before {
                display: none;
            }

            .timeline-day-marker {
                display: none;
            }

            .timeline-day-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .timeline-day-count {
                width: 100%;
                justify-content: center;
            }

            .cronograma-filters {
                justify-content: center;
            }

            .filtro-cronograma {
                padding: 8px 14px;
                font-size: 0.8125rem;
            }

            .filtro-cronograma span:not(.badge) {
                display: none;
            }
        }

        /* Animation */
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .timeline-day-section {
            animation: slideInRight 0.4s ease-out;
        }

        .schedule-card {
            animation: slideInRight 0.3s ease-out;
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/jkanban/jkanban.js', 'resources/assets/vendor/libs/quill/katex.js', 'resources/assets/vendor/libs/quill/quill.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/comercialkanban.js')
    @vite('resources/assets/js/cronograma.js')
    @vite('resources/assets/js/consulta.js')
@endsection

@section('content')

    @if (session('status') == 'success')
        <div class="alert alert-solid-success d-flex align-items-center" role="alert">
            <span class="alert-icon rounded">
                <i class="ri-checkbox-circle-line ri-22px"></i>
            </span>
            {{ session('message') }}
        </div>
    @elseif(session('status') == 'error')
        <div class="alert alert-danger">
            {{ session('message') }}
        </div>
    @endif

    <!-- Modern Futuristic Tabs Navigation -->
    <ul class="nav nav-tabs nav-tabs-modern" role="tablist">
        <li class="nav-item">
            <button type="button" class="nav-link active" id="funil-tab" data-bs-toggle="tab" data-bs-target="#tab-funil" role="tab" aria-controls="tab-funil" aria-selected="true">
                <i class="ri-kanban-view"></i>
                <span>Funil de Vendas</span>
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="nav-link" id="cronograma-tab" data-bs-toggle="tab" data-bs-target="#tab-cronograma" role="tab" aria-controls="tab-cronograma" aria-selected="false">
                <i class="ri-calendar-check-line"></i>
                <span>Cronograma</span>
                <span class="badge badge-pulse" id="badge-cronograma-hoje">
                    <i class="ri-loader-4-line ri-spin"></i>
                </span>
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Tab Funil de Vendas -->
        <div class="tab-pane fade show active" id="tab-funil" role="tabpanel" aria-labelledby="funil-tab">
                <div class="app-kanban">
        <!-- Add new board (hidden) -->
        <form class="kanban-add-new-board d-none">
            <input type="text" class="form-control w-px-250 kanban-add-board-input mb-4 d-none"
                placeholder="Add Board Title" id="kanban-add-board-input" required />
            <div class="mb-4 kanban-add-board-input d-none">
                <button class="btn btn-primary btn-sm me-3">Add</button>
                <button type="button" class="btn btn-outline-secondary btn-sm kanban-add-board-cancel-btn">Cancel</button>
            </div>
        </form>

        <!-- Modern Filters Section -->
        <div class="kanban-filters">
            <div class="filter-group" style="flex: 2;">
                <div class="form-floating form-floating-outline">
                    <input type="text" id="kanban-search" class="form-control" placeholder="Pesquisar cliente..." />
                    <label for="kanban-search">
                        <i class="ri-search-line me-1"></i>Pesquisar cliente...
                    </label>
                </div>
            </div>

            <div class="filter-group">
                <div class="form-floating form-floating-outline">
                    <select class="form-select" id="type-lead" name="tipolead">
                        <option value="A">Ativo</option>
                        <option value="R">Receptivo</option>
                    </select>
                    <label for="type-lead">Tipo de Lead</label>
                </div>
            </div>

            @if ($typeUserLogeed == 'ADMINISTRATIVO' || $typeUserLogeed == 'DEVELOPER' || $typeUserLogeed == 'SUPERVISOR')
            <div class="filter-group">
                <div class="form-floating form-floating-outline">
                    <select class="form-select" id="user-filter" name="temperatura">
                        <option value="">Todos os corretores</option>
                        @foreach ($vendedores as $vendedor)
                            <option value="{{ $vendedor->id }}">{{ $vendedor->name }}</option>
                        @endforeach
                    </select>
                    <label for="user-filter">Corretor</label>
                </div>
            </div>
            @endif

            <div class="filter-group">
                <div class="form-floating form-floating-outline">
                    <select class="form-select" id="stale-filter">
                        <option value="">Todos</option>
                        <option value="7">≥ 7 dias sem atualização</option>
                        <option value="14">≥ 14 dias sem atualização</option>
                        <option value="20+">20+ dias sem atualização</option>
                    </select>
                    <label for="stale-filter">Estagnação</label>
                </div>
            </div>

            @if ($typeUserLogeed == 'VENDEDOR')
            <div class="filter-group" style="flex: 0 0 auto;">
                <button type="button" class="btn btn-primary h-100 px-4" id="btn-fila-preditiva">
                    <i class="ri-user-star-line me-2"></i>Vitrine de Clientes
                </button>
            </div>
            @endif
        </div>

        <!-- Kanban Wrapper -->
        <div class="kanban-wrapper"></div>



        <!-- Modal Cliente Kanban -->
        <div class="modal fade kanban-client-modal" id="kanbanClientModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <!-- Header -->
                    <div class="km-header">
                        <div class="km-header-left">
                            <div class="km-avatar" id="km-avatar">--</div>
                            <div class="km-header-info">
                                <h5 id="km-client-name">Nome do Cliente</h5>
                                <div class="km-header-meta">
                                    <span>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                        <span id="km-data-create">--/--/----</span>
                                    </span>
                                    <span>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        Atualizado: <span id="km-data-update">--/--/----</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="km-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>

                    <!-- Tabs -->
                    <div class="km-tabs">
                        <button type="button" class="km-tab active" data-tab="tab-edit">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            Editar
                        </button>
                        <button type="button" class="km-tab" data-tab="tab-notes">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            Anotações
                            <span class="badge bg-primary rounded-pill ms-1" id="km-notes-count">0</span>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="km-body">
                        <!-- Tab Edit -->
                        <div class="km-tab-content active" id="tab-edit">
                            <form method="POST" id="form-client" action="{{ route('comercial.saveNoteMailing') }}">
                                @csrf
                                <input type="hidden" value="" id="id_mailing" name="id_mailing">
                                <input type="hidden" value="" id="id_tabulacao" name="id_tabulacao">

                                <!-- Dados Pessoais -->
                                <div class="km-section">
                                    <div class="km-section-header">
                                        <div class="km-section-icon icon-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                        </div>
                                        <h6 class="km-section-title">Dados Pessoais</h6>
                                    </div>
                                    <div class="km-form-grid">
                                        <div class="km-field span-2">
                                            <label class="km-label">Nome Completo</label>
                                            <input type="text" id="title" class="km-input" placeholder="Nome do cliente" disabled>
                                        </div>
                                        <div class="km-field">
                                            <label class="km-label">Data de Nascimento</label>
                                            <input type="text" id="data_nascimento" class="km-input" placeholder="dd/mm/aaaa" disabled>
                                        </div>
                                        <div class="km-field">
                                            <label class="km-label">CPF / CNPJ</label>
                                            <input type="text" id="cpf" class="km-input mask-cpf" placeholder="000.000.000-00" disabled>
                                        </div>
                                        <div class="km-field span-2">
                                            <label class="km-label">E-mail</label>
                                            <input type="email" id="email" class="km-input" placeholder="email@exemplo.com" disabled>
                                        </div>
                                    </div>
                                </div>

                                <!-- Plano Atual -->
                                <div class="km-section">
                                    <div class="km-section-header">
                                        <div class="km-section-icon icon-info">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                        </div>
                                        <h6 class="km-section-title">Plano Atual</h6>
                                    </div>
                                    <div class="km-form-grid grid-3">
                                        <div class="km-field">
                                            <label class="km-label">Plano</label>
                                            <input type="text" id="plano" class="km-input" placeholder="Nome do plano" disabled>
                                        </div>
                                        <div class="km-field">
                                            <label class="km-label">Entidade</label>
                                            <input type="text" id="entidade" class="km-input" placeholder="Operadora" disabled>
                                        </div>
                                        <div class="km-field">
                                            <label class="km-label">Categoria</label>
                                            <input type="text" id="cartergoria" class="km-input" placeholder="Categoria" disabled>
                                        </div>
                                        <div class="km-field">
                                            <label class="km-label">Idades</label>
                                            <input type="text" id="idades" class="km-input" placeholder="Idades" disabled>
                                        </div>
                                        <div class="km-field">
                                            <label class="km-label">Valor Atual</label>
                                            <input type="text" id="valor_plano_atual" class="km-input km-input-monetary monetary-field" placeholder="R$ 0,00" disabled>
                                        </div>
                                        <div class="km-field">
                                            <label class="km-label">Valor Negociação</label>
                                            <input type="text" id="valor_negociacao" class="km-input km-input-monetary monetary-field" placeholder="R$ 0,00" name="valor_negociacao">
                                        </div>
                                    </div>
                                </div>

                                <!-- Contatos -->
                                <div class="km-section">
                                    <div class="km-section-header">
                                        <div class="km-section-icon icon-success">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                        </div>
                                        <h6 class="km-section-title">Telefones</h6>
                                    </div>
                                    <div class="km-form-grid grid-3">
                                        <div class="km-field">
                                            <label class="km-label">Telefone Principal</label>
                                            <input type="text" id="telefone1" class="km-input mask-telefone" placeholder="(00) 00000-0000" name="telefone1">
                                        </div>
                                        <div class="km-field">
                                            <label class="km-label">Telefone 2</label>
                                            <input type="text" id="telefone2" class="km-input mask-telefone" placeholder="(00) 00000-0000" name="telefone2">
                                        </div>
                                        <div class="km-field">
                                            <label class="km-label">Telefone 3</label>
                                            <input type="text" id="telefone3" class="km-input mask-telefone" placeholder="(00) 00000-0000" name="telefone3">
                                        </div>
                                    </div>
                                </div>

                                <!-- Temperatura do Lead -->
                                <div class="km-section">
                                    <div class="km-section-header">
                                        <div class="km-section-icon icon-warning">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"></path></svg>
                                        </div>
                                        <h6 class="km-section-title">Temperatura do Lead</h6>
                                    </div>
                                    <div class="km-temp-select">
                                        <label class="km-temp-option temp-quente">
                                            <input type="radio" name="temperatura" value="QUENTE">
                                            <span class="temp-icon">🔥</span>
                                            <span class="temp-label">Quente</span>
                                        </label>
                                        <label class="km-temp-option temp-morno">
                                            <input type="radio" name="temperatura" value="MORNO">
                                            <span class="temp-icon">☀️</span>
                                            <span class="temp-label">Morno</span>
                                        </label>
                                        <label class="km-temp-option temp-frio">
                                            <input type="radio" name="temperatura" value="FRIO" checked>
                                            <span class="temp-icon">❄️</span>
                                            <span class="temp-label">Frio</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Nova Anotação -->
                                <div class="km-section">
                                    <div class="km-section-header">
                                        <div class="km-section-icon icon-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                        </div>
                                        <h6 class="km-section-title">Nova Anotação</h6>
                                    </div>
                                    <div class="km-editor-wrapper">
                                        <div class="km-editor comment-editor"></div>
                                        <div class="km-editor-toolbar comment-toolbar">
                                            <span class="ql-formats me-0">
                                                <button class="ql-bold"></button>
                                                <button class="ql-italic"></button>
                                                <button class="ql-underline"></button>
                                                <button class="ql-list" value="ordered"></button>
                                                <button class="ql-list" value="bullet"></button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Tab Notes -->
                        <div class="km-tab-content" id="tab-notes">
                            <div class="km-notes-timeline" id="notes-container">
                                <!-- Anotações serão inseridas aqui via JS -->
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="km-footer">
                        <a href="#" class="km-profile-link" id="km-profile-link" target="_blank">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                            Ver perfil completo
                        </a>
                        <div class="km-footer-actions">
                            <button type="button" class="km-btn km-btn-outline" data-bs-dismiss="modal">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                Fechar
                            </button>
                            <button type="button" class="km-btn km-btn-primary" id="km-btn-save">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                Atualizar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div>
        <!-- Fim Tab Funil de Vendas -->

        <!-- Tab Cronograma -->
        <div class="tab-pane fade" id="tab-cronograma" role="tabpanel" aria-labelledby="cronograma-tab">
            <!-- Modern Header -->
            <div class="cronograma-header">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-4">
                    <div>
                        <h4 class="cronograma-title">
                            <i class="ri-calendar-schedule-line text-primary"></i>
                            Cronograma de Ligações
                        </h4>
                        <p class="cronograma-subtitle mb-0">
                            <span id="total-agendamentos">0</span> agendamentos programados
                        </p>
                    </div>

                    <!-- Filters -->
                    <div class="cronograma-filters">
                        <button type="button" class="btn btn-primary filtro-cronograma active" data-filter="todos">
                            <i class="ri-list-check-2"></i>
                            <span>Todos</span>
                        </button>
                        <button type="button" class="btn btn-outline-danger filtro-cronograma" data-filter="atrasados">
                            <i class="ri-alarm-warning-line"></i>
                            <span>Atrasados</span>
                            <span class="badge bg-danger" id="count-atrasados">0</span>
                        </button>
                        <button type="button" class="btn btn-outline-info filtro-cronograma" data-filter="hoje">
                            <i class="ri-focus-3-line"></i>
                            <span>Hoje</span>
                            <span class="badge bg-info" id="count-hoje">0</span>
                        </button>
                        <button type="button" class="btn btn-outline-success filtro-cronograma" data-filter="semana">
                            <i class="ri-calendar-check-line"></i>
                            <span>Esta Semana</span>
                            <span class="badge bg-success" id="count-semana">0</span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary filtro-cronograma" data-filter="futuro">
                            <i class="ri-calendar-2-line"></i>
                            <span>Futuro</span>
                            <span class="badge bg-secondary" id="count-futuro">0</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div id="cronograma-loading" class="text-center py-5">
                <div class="spinner-border text-primary spinner-border-lg" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-4 mb-0 fw-medium">Carregando seus agendamentos...</p>
            </div>

            <!-- Mensagem vazia -->
            <div id="cronograma-empty" class="cronograma-empty-state d-none">
                <div class="cronograma-empty-icon">
                    <i class="ri-calendar-check-line"></i>
                </div>
                <h5 class="cronograma-empty-title">Nenhum agendamento encontrado</h5>
                <p class="cronograma-empty-text">Não há ligações agendadas para o filtro selecionado.</p>
            </div>

            <!-- Daily Timeline Container -->
            <div id="cronograma-timeline" class="daily-timeline d-none">
                <!-- As seções de dias serão inseridas aqui dinamicamente -->
            </div>
        </div>
        <!-- Fim Tab Cronograma -->
    </div>
    <!-- Fim Tab Content -->


    <!-- Add New Address Modal -->
    <div class="modal fade" id="addNewAddress" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-simple modal-add-new-address">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-6">
                        <h4 class="address-title mb-2">Cadastrar Contrato</h4>
                        <p class="address-subtitle">Cadastre as informações de contrato do cliente.</p>
                    </div>
                    <form method="POST" action="{{ route('comercial.createSale') }}" class="row g-5 create-sale">
                        @csrf
                        <div class="col-12 col-md-6">
                            <input type="hidden" id="contato_id" name="contato_id" class="form-control" />

                            <div class="form-floating form-floating-outline">
                                <input type="text" id="nome_contrato" name="nome_contrato" class="form-control"
                                    placeholder="NovaTech Solutions" />
                                <label for="nome_contrato">Nome Contrato</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="cpf_cnpj" name="cpf_cnpj" class="form-control"
                                    placeholder="12.345.678/0001-90" />
                                <label for="cpf_cnpj">CPF / CNPJ</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="email" name="email" class="form-control"
                                    placeholder="jhon1234@salescontro.com.br" />
                                <label for="email">E-mail</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-floating form-floating-outline">
                                <input type="date" id="data_vigencia" name="data_vigencia" class="form-control"
                                    required placeholder="29/08/2024" />
                                <label for="data_vigencia">Data de Vigencia</label>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="telefone1" name="telefone1" class="form-control mask-telefone"
                                    placeholder="(11) 91254-56871" />
                                <label for="telefone1">Telefone Principal</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="telefone2" name="telefone2" class="form-control mask-telefone"
                                    placeholder="(11) 91254-56871" />
                                <label for="telefone2">Telefone Comercial</label>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="operadora" name="operadora" class="form-control"
                                    placeholder="Sulamerica" />
                                <label for="operadora">Operadora</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="nome_plano" name="nome_plano" class="form-control"
                                    placeholder="plus diamante" />
                                <label for="nome_plano">Nome do Plano</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="valor_contrato" name="valor_contrato" value="0"
                                    class="form-control monetary-field" placeholder="R$ 2500,00" />
                                <label for="valor_contrato">$ Valor do Plano</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-12">
                            <div class="form-floating form-floating-outline">
                                <input type="number" id="vidas" name="vidas" class="form-control"
                                    placeholder="Quantidade de vidas" required />
                                <label for="obs_contrato">Quantidade de vidas</label>
                            </div>
                        </div>

                        <div class="col-12 col-md-12">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="obs_contrato" name="obs_contrato" class="form-control"
                                    placeholder="Observação de contrato" />
                                <label for="obs_contrato">Observação de contrato</label>
                            </div>
                        </div>

                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-success me-3 create-sale">Salvar contrato</button>
                            <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                                aria-label="Close">cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--/ Add New Address Modal -->

    <!-- Modal para descartar lead -->
    <div class="modal fade" id="discardModal" tabindex="-1" aria-labelledby="discardModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="discardModalLabel">Descartar Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('comercial.sendRemaketing') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p>Tem certeza de que deseja descartar este lead?</p>
                        <input type="hidden" id="leadIdInput" name="contato_id" value="">
                        <div class="mb-3">
                            <label for="discardReason" class="form-label">Motivo do Descarte</label>
                            <select class="form-select" id="discardReason" name="sub_tabulacao_id" required>
                                @foreach ($subTabulacoes as $tabulation)
                                    <option value="{{ $tabulation->id }}">{{ $tabulation->descricao }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Descartar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Criar Agendamento -->
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="sheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sheduleModalLabel">CRIAR AGENDAMENTO</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('comercial.sendSchedule') }}" method="POST">
                    @csrf
                    <input type="hidden" id="leadIdInputSchedule" name="contato_id" value="">
                    <div class="modal-body">
                        <p>Escolha o horario e o dia do agendamento</p>
                        <div>
                            <label for="telefone1">Horario Agendamento</label>
                            <input type="datetime-local" id="horario_agendamento" name="horario_agendamento"
                                class="form-control" placeholder="data agendamento" required />
                        </div>

                        <div class="mt-2">
                            <label for="observacao">Observação de agendamento</label>
                            <input type="text" id="observacao" name="observacao" class="form-control" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Agendar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Marcar como Realizado (do Cronograma) -->
    <div class="modal fade" id="completarContatoModal" tabindex="-1" aria-labelledby="completarContatoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="completarContatoLabel">
                        <i class="ri-check-double-line me-2"></i>Concluir Contato Agendado
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('comercial.backqueue') }}" method="POST" id="form-completar-contato">
                    @csrf
                    <input type="hidden" id="contatoIdCompletar" name="contato_id" value="">
                    <div class="modal-body">
                        <div class="alert alert-info d-flex align-items-center mb-4">
                            <i class="ri-information-line ri-22px me-2"></i>
                            <div>
                                <strong>Cliente:</strong> <span id="nomeClienteCompletar">-</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="anotacaoResultado" class="form-label">
                                <i class="ri-file-text-line me-1"></i>Resultado do Contato
                            </label>
                            <textarea class="form-control" id="anotacaoResultado" name="anotacao" rows="4" placeholder="Descreva o que foi conversado, acordado ou observado durante o contato..." required></textarea>
                            <small class="text-muted">Esta anotação será salva no histórico do cliente</small>
                        </div>

                        <div class="mb-3">
                            <label for="tabulacaoDestino" class="form-label">
                                <i class="ri-folder-transfer-line me-1"></i>Para qual etapa do funil esse cliente vai?
                            </label>
                            <select class="form-select" id="tabulacaoDestino" name="tabulacao_id" required>
                                <option value="">Selecione a tabulação...</option>
                                @foreach ($tabulacoes as $tabulation)
                                    <option value="{{ $tabulation->id }}">{{ $tabulation->descricao }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="ri-check-line me-1"></i>Concluir e Retornar ao Funil
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Fila Preditiva (redesenhada) -->
    <div class="modal fade" id="modal-fila-preditiva" tabindex="-1" aria-labelledby="modalFilaPreditivaLabel" aria-hidden="true">
        <div class="modal-dialog modal-preditiva modal-dialog-centered">
            <div class="modal-content pv-modal-modern">

                {{-- Header --}}
                <div class="pv-modal-header pv-modal-header-primary">
                    <div class="pv-modal-header-content">
                        <div class="pv-modal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </div>
                        <div class="pv-modal-title-group">
                            <h5 class="pv-modal-title" id="modalFilaPreditivaLabel">Fila Preditiva</h5>
                            <span class="pv-modal-subtitle" id="kp-modal-subtitulo">Aguardando cliente...</span>
                        </div>
                    </div>
                    <button type="button" class="pv-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>

                {{-- Body two-panel --}}
                <div class="kp-preditiva-body">

                    {{-- Painel esquerdo: Histórico da sessão --}}
                    <div class="kp-history-panel">
                        <div class="kp-history-header">
                            <span class="kp-history-title">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                </svg>
                                Sessão Atual
                            </span>
                            <span class="kp-history-count" id="kp-history-count">0</span>
                        </div>
                        {{-- Campo de busca manual de CPF --}}
                        <div class="kp-search-area">
                            <div class="kp-search-wrap">
                                <input type="text" id="kp-manual-cpf" class="kp-search-input"
                                       placeholder="CPF ou CNPJ..." maxlength="18" autocomplete="off">
                                <button type="button" id="btn-kp-manual-search" class="kp-search-btn" title="Buscar CPF">
                                    <span class="spinner-border spinner-border-sm d-none" id="kp-search-loading"
                                          style="width:.875rem;height:.875rem" role="status"></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="kp-search-icon">
                                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="kp-history-list" id="kp-history-list">
                            <div class="kp-history-empty" id="kp-history-empty">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <p>Nenhum cliente anterior</p>
                                <small>O histórico acumula aqui conforme você avança na fila</small>
                            </div>
                        </div>
                    </div>

                    {{-- Painel direito: Cliente ativo --}}
                    <div class="kp-active-panel" id="kp-active-panel">

                        {{-- Banner: modo visualização de item do histórico --}}
                        <div class="kp-viewing-banner d-none" id="kp-viewing-banner">
                            <div class="kp-viewing-info">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                                <span id="kp-viewing-label">Visualizando busca anterior</span>
                            </div>
                            <button type="button" id="btn-kp-voltar" class="kp-viewing-back">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="15 18 9 12 15 6"/>
                                </svg>
                                Voltar ao cliente atual
                            </button>
                        </div>

                        {{-- Loading --}}
                        <div class="kp-loading-state" id="loading-fila-preditiva">
                            <div class="spinner-border" style="color:var(--km-primary);width:2rem;height:2rem" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p>Buscando próximo cliente...</p>
                        </div>

                        {{-- Sem resultados --}}
                        <div class="kp-empty-state d-none" id="no-results-fila-preditiva">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:.3">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                            <p>Fila vazia no momento</p>
                            <small>Não há clientes disponíveis na fila preditiva agora</small>
                        </div>

                        {{-- Dados do cliente --}}
                        <div id="cliente-preditiva-container" class="d-none" style="display:flex;flex-direction:column">
                            <input type="hidden" id="cliente-id" value="">

                            {{-- Hero strip --}}
                            <div class="kp-hero-strip">
                                <div class="kp-hero-info">
                                    <div class="kp-hero-nome" id="cliente-nome-header">—</div>
                                    <div class="kp-hero-sub">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                                        </svg>
                                        Titular do Plano
                                    </div>
                                </div>
                                <button type="button" id="btn-consultar-dados-cliente">
                                    <span class="spinner-border spinner-border-sm d-none" id="loading-consulta-cliente" style="width:1rem;height:1rem"></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                    </svg>
                                    Consultar Dados Completos
                                </button>
                            </div>

                            {{-- Grid chips — 4 em linha --}}
                            <div class="kp-data-chips">
                                <div class="kp-data-chip">
                                    <div class="kp-chip-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                                            <path d="M7 9h3M7 13h2"/>
                                            <circle cx="16" cy="10" r="2"/>
                                            <path d="M12 15c0-2 1.5-3 4-3"/>
                                        </svg>
                                    </div>
                                    <div class="kp-chip-content">
                                        <span class="kp-chip-label">CPF</span>
                                        <span class="kp-chip-value" id="cliente-cpf">—</span>
                                    </div>
                                </div>
                                <div class="kp-data-chip">
                                    <div class="kp-chip-icon" style="background:rgba(var(--km-success-rgb),0.12);color:var(--km-success)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                                        </svg>
                                    </div>
                                    <div class="kp-chip-content">
                                        <span class="kp-chip-label">Telefone</span>
                                        <span class="kp-chip-value" id="cliente-telefone">—</span>
                                    </div>
                                </div>
                                <div class="kp-data-chip">
                                    <div class="kp-chip-icon" style="background:rgba(var(--km-info-rgb),0.12);color:var(--km-info)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                            <polyline points="22,6 12,13 2,6"/>
                                        </svg>
                                    </div>
                                    <div class="kp-chip-content">
                                        <span class="kp-chip-label">E-mail</span>
                                        <span class="kp-chip-value" id="cliente-email">—</span>
                                    </div>
                                </div>
                                <div class="kp-data-chip">
                                    <div class="kp-chip-icon" style="background:rgba(var(--km-warning-rgb),0.12);color:var(--km-warning)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                    </div>
                                    <div class="kp-chip-content">
                                        <span class="kp-chip-label">Nascimento</span>
                                        <span class="kp-chip-value" id="cliente-nascimento">—</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Card plano --}}
                            <div class="kp-plan-card">
                                <div class="kp-plan-header">
                                    <span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                        </svg>
                                        Plano Atual
                                    </span>
                                    <div class="kp-plan-valor" id="cliente-valor">—</div>
                                </div>
                                <div class="kp-plan-grid">
                                    <div class="kp-plan-field">
                                        <label>Plano</label>
                                        <strong id="cliente-plano">—</strong>
                                    </div>
                                    <div class="kp-plan-field">
                                        <label>Categoria</label>
                                        <strong id="cliente-categoria">—</strong>
                                    </div>
                                    <div class="kp-plan-field">
                                        <label>Operadora</label>
                                        <strong id="cliente-entidade">—</strong>
                                    </div>
                                </div>
                            </div>

                            {{-- Dependentes --}}
                            <div class="kp-dep-section d-none" id="card-dependentes">
                                <div class="kp-dep-header">
                                    <span style="display:flex;align-items:center;gap:.375rem">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                        </svg>
                                        Dependentes
                                        <span id="count-dependentes" style="background:rgba(var(--km-info-rgb),.12);color:var(--km-info);font-size:.65rem;padding:.1rem .45rem;border-radius:20px">0</span>
                                    </span>
                                </div>
                                <div class="kp-dep-grid" id="lista-dependentes"></div>
                            </div>

                            {{-- select oculto mantido apenas como âncora para o consulta.js que ainda referencia tabulacao-preditiva --}}
                            <select id="tabulacao-preditiva" hidden aria-hidden="true"></select>

                            {{-- Dados completos (consulta CPF) --}}
                            <div class="kp-consulta-section d-none" id="dados-consulta-cliente">
                                <div class="kp-consulta-header">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                    </svg>
                                    Dados Completos do Cliente
                                </div>
                                <div class="kp-consulta-body" style="padding:0">

                                    {{-- Erro da consulta --}}
                                    <div id="erro-consulta-cliente" class="d-none" style="color:var(--km-danger);font-size:.875rem;padding:1rem 1.25rem;display:flex;align-items:center;gap:.375rem">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                                        </svg>
                                        <span id="mensagem-erro-cliente"></span>
                                    </div>

                                    {{-- Tabs wrapper --}}
                                    <div id="dados-pessoa-preditiva" class="kp-info-tabs-wrapper d-none">

                                        {{-- Nav das tabs --}}
                                        <nav class="kp-info-tabs-nav" id="kp-tabs-nav">
                                            <button class="kp-info-tab-btn active" data-kp-tab="tab-pessoa">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                                                </svg>
                                                Dados Pessoais
                                            </button>
                                            <button class="kp-info-tab-btn" data-kp-tab="tab-telefones">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72"/>
                                                </svg>
                                                Telefones
                                                <span class="kp-tab-count" id="kp-count-telefones">0</span>
                                            </button>
                                            <button class="kp-info-tab-btn" data-kp-tab="tab-emails">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                                                </svg>
                                                E-mails
                                                <span class="kp-tab-count" id="kp-count-emails">0</span>
                                            </button>
                                            <button class="kp-info-tab-btn" data-kp-tab="tab-enderecos">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                                                </svg>
                                                Endereços
                                                <span class="kp-tab-count" id="kp-count-enderecos">0</span>
                                            </button>
                                            <button class="kp-info-tab-btn" data-kp-tab="tab-credito">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                                </svg>
                                                Crédito
                                            </button>
                                            <button class="kp-info-tab-btn" data-kp-tab="tab-societario">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                                </svg>
                                                Societário
                                            </button>
                                        </nav>

                                        {{-- Panes --}}
                                        <div class="kp-info-tab-panes">

                                            {{-- Tab: Dados Pessoais --}}
                                            <div class="kp-info-tab-pane active" id="tab-pessoa">
                                                <div class="kp-pessoa-grid">
                                                    <div class="kp-pessoa-field">
                                                        <label>Nome</label>
                                                        <span id="nome-preditiva">—</span>
                                                    </div>
                                                    <div class="kp-pessoa-field kp-field-mono">
                                                        <label>CPF</label>
                                                        <span id="cpf-result-preditiva">—</span>
                                                    </div>
                                                    <div class="kp-pessoa-field">
                                                        <label>Data de Nascimento</label>
                                                        <span id="data-nascimento-preditiva">—</span>
                                                    </div>
                                                    <div class="kp-pessoa-field">
                                                        <label>Idade</label>
                                                        <span id="idade">—</span>
                                                    </div>
                                                    <div class="kp-pessoa-field">
                                                        <label>Sexo</label>
                                                        <span id="sexo-preditiva">—</span>
                                                    </div>
                                                    <div class="kp-pessoa-field">
                                                        <label>Nome da Mãe</label>
                                                        <span id="nome-mae-preditiva">—</span>
                                                    </div>
                                                    <div class="kp-pessoa-field">
                                                        <label>Situação CPF</label>
                                                        <span id="situacao-cpf-preditiva">—</span>
                                                    </div>
                                                    <div class="kp-pessoa-field">
                                                        <label>Renda</label>
                                                        <span id="renda-preditiva">—</span>
                                                    </div>
                                                    <div class="kp-pessoa-field">
                                                        <label>Ocupação</label>
                                                        <span id="ocupacao-preditiva">—</span>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Tab: Telefones --}}
                                            <div class="kp-info-tab-pane" id="tab-telefones">
                                                <div class="kp-tab-list" id="celulares-preditiva-wrap">
                                                    <div class="kp-tab-list-header">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>
                                                        </svg>
                                                        Celulares
                                                    </div>
                                                    <div id="celulares-preditiva"></div>
                                                </div>
                                                <div class="kp-tab-list" id="fixos-preditiva-wrap">
                                                    <div class="kp-tab-list-header">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13"/>
                                                        </svg>
                                                        Telefones Fixos
                                                    </div>
                                                    <div id="fixos-preditiva"></div>
                                                </div>
                                            </div>

                                            {{-- Tab: E-mails --}}
                                            <div class="kp-info-tab-pane" id="tab-emails">
                                                <div class="kp-tab-list">
                                                    <div class="kp-tab-list-header">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                                                        </svg>
                                                        Endereços de E-mail
                                                    </div>
                                                    <div id="emails-preditiva"></div>
                                                </div>
                                            </div>

                                            {{-- Tab: Endereços --}}
                                            <div class="kp-info-tab-pane" id="tab-enderecos">
                                                <div id="enderecos-preditiva"></div>
                                            </div>

                                            {{-- Tab: Crédito --}}
                                            <div class="kp-info-tab-pane" id="tab-credito">
                                                <div id="risco-credito-preditiva"></div>
                                            </div>

                                            {{-- Tab: Societário --}}
                                            <div class="kp-info-tab-pane" id="tab-societario">
                                                <div id="participacaoSocietaria"></div>
                                            </div>

                                        </div>{{-- /kp-info-tab-panes --}}
                                    </div>{{-- /kp-info-tabs-wrapper --}}

                                </div>
                            </div>

                        </div>{{-- /cliente-preditiva-container --}}
                    </div>{{-- /kp-active-panel --}}
                </div>{{-- /kp-preditiva-body --}}

                {{-- Footer --}}
                <div class="lim-modal-footer-bar">
                    <button type="button" class="pv-btn pv-btn-ghost" data-bs-dismiss="modal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                        Fechar
                    </button>
                    <button type="button" class="pv-btn" id="btn-descartar-cliente"
                        style="background:linear-gradient(135deg,var(--km-danger),var(--km-danger-light));color:#fff">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                        Descartar
                    </button>
                    <button type="button" class="pv-btn pv-btn-success" id="btn-converter-cliente">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Converter Lead
                    </button>
                </div>

            </div>{{-- /modal-content --}}
        </div>
    </div>
    </div>
@endsection
