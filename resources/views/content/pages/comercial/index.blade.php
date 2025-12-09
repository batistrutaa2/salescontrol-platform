@extends('layouts/layoutMaster')

@section('title', 'Lista de Clientes - Kanban')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/jkanban/jkanban.scss', 'resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/quill/editor.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/app-kanban.scss')
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



        <!-- Edit Task/Task & Activities -->
        <div class="offcanvas offcanvas-end kanban-update-item-sidebar">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title">Visualizar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body pt-2">
                <ul class="nav nav-tabs mb-2 border-bottom">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-update">
                            <i class="ri-edit-box-line me-1_5"></i>
                            <span class="align-middle">Editar</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-activity">
                            <i class="ri-pie-chart-line me-1_5"></i>
                            <span class="align-middle">Anotações</span>
                        </button>
                    </li>
                </ul>
                <div class="tab-content px-0 pb-0 pt-4">
                    <!-- Update item/tasks -->
                    <div class="tab-pane fade show active" id="tab-update" role="tabpanel">
                        <form method="POST" id="form-client" action="{{ route('comercial.saveNoteMailing') }}">
                            @csrf
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="hidden" value="" id="id_mailing" name="id_mailing">
                                <input type="hidden" value="" id="id_tabulacao" name="id_tabulacao">
                                <input type="text" id="title" class="form-control" placeholder="Enter Title"
                                    disabled />
                                <label for="title">Nome Completo</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="data_nascimento" class="form-control" placeholder="11/10/1997"
                                    disabled />
                                <label for="title">Data de Nascimento</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="cpf" class="form-control mask-cpf"
                                    placeholder="476.338.528.36" disabled />
                                <label for="title">CPF / CNPJ</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="email" id="email" class="form-control"
                                    placeholder="corretor@corretor.com.br" disabled />
                                <label for="title">E-mail</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="plano" class="form-control" placeholder="TOP NACIONAL"
                                    disabled />
                                <label for="title">Plano Atual</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="entidade" class="form-control" placeholder="SULAMERICA"
                                    disabled />
                                <label for="title">Entidade</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="cartergoria" class="form-control" placeholder="MEDIA"
                                    disabled />
                                <label for="title">Cartegoria</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="idades" class="form-control" placeholder="MEDIA"
                                    disabled />
                                <label for="title">Idades</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="telefone1" class="form-control mask-telefone"
                                    placeholder="(11) 99020-5484" name="telefone1" />
                                <label for="title">Telefone Principal</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="telefone2" class="form-control mask-telefone"
                                    placeholder="(11) 99020-5484" name="telefone2" />
                                <label for="title">Telefone Adicional</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="telefone3" class="form-control mask-telefone"
                                    placeholder="(11) 99020-5484" name="telefone3" />
                                <label for="title">Telefone Adicional</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="valor_plano_atual" class="form-control monetary-field"
                                    placeholder="R$ 197,84" disabled />
                                <label for="title">Valor do plano atual</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="valor_negociacao" class="form-control monetary-field"
                                    placeholder="R$ 197,84" name="valor_negociacao" />
                                <label for="title">valor da negociação</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <select class="select2  form-select" id="label" name="temperatura">
                                    <option data-color="bg-label-danger" value="QUENTE">
                                        QUENTE
                                    </option>
                                    <option data-color="bg-label-warning" value="MORNO">
                                        MORNO
                                    </option>
                                    <option data-color="bg-label-info" value="FRIO">
                                        FRIO
                                    </option>
                                </select>
                                <label for="label"> label</label>
                            </div>

                            <div class="mb-8">
                                <label class="form-label">ANOTAÇÕES</label>
                                <div class="comment-editor"></div>
                                <div class="d-flex justify-content-end">
                                    <div class="comment-toolbar">
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
                            <div class="mb-5">
                                <div class="d-flex flex-wrap">
                                    <button type="submit" class="btn btn-primary me-4 " data-bs-dismiss="offcanvas">
                                        Atualizar
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">
                                        Fechar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- Activities -->
                    <div class="tab-pane fade text-heading" id="tab-activity" role="tabpanel">
                        <div id="notes-container">
                            <!-- As anotações serão inseridas aqui -->
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

    <!-- Modal para Fila Preditiva -->
    <div class="modal fade" id="modal-fila-preditiva" tabindex="-1" aria-labelledby="modalFilaPreditivaLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalFilaPreditivaLabel">Cliente na Fila Preditiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="loading-fila-preditiva" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2">Buscando próximo cliente...</p>
                    </div>

                    <div id="no-results-fila-preditiva" class="text-center d-none">
                        <div class="alert alert-info">
                            <i class="ri-information-line me-2"></i>
                            <span>Não há clientes disponíveis na fila preditiva no momento.</span>
                        </div>
                    </div>

                    <div id="cliente-preditiva-container" class="d-none">
                        <!-- Card com dados básicos do cliente -->
                        <div class="card border-primary">
                            <div class="card-header bg-label-primary d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="ri-user-3-line me-2"></i>
                                    <span id="cliente-nome-header">Cliente</span>
                                </h5>
                                <!-- Botão de consulta no header do card -->
                                <button type="button" class="btn btn-sm btn-info" id="btn-consultar-dados-cliente">
                                    <span class="spinner-border spinner-border-sm d-none"
                                        id="loading-consulta-cliente"></span>
                                    <i class="ri-search-line me-1"></i>
                                    Consultar Dados Completos
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Titular do Plano</small>
                                        </div>

                                        <div class="mb-3">
                                            <i class="ri-mail-line me-2 text-primary ri-lg"></i>
                                            <span id="cliente-email" class="fs-6">-</span>
                                        </div>
                                        <div class="mb-3">
                                            <i class="ri-phone-line me-2 text-primary ri-lg"></i>
                                            <span id="cliente-telefone" class="fs-6">-</span>
                                        </div>
                                        <div class="mb-3">
                                            <i class="ri-id-card-line me-2 text-primary ri-lg"></i>
                                            <span id="cliente-cpf" class="fs-6">-</span>
                                        </div>
                                        <div class="mb-3">
                                            <i class="ri-calendar-line me-2 text-primary ri-lg"></i>
                                            <span id="cliente-nascimento" class="fs-6">-</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3 border-0 shadow-sm">
                                            <div class="card-body p-3">
                                                <h6 class="card-title text-primary mb-3">
                                                    <i class="ri-shield-check-line me-1"></i>
                                                    Plano Atual
                                                </h6>
                                                <div class="mb-2">
                                                    <small class="text-muted">Plano:</small>
                                                    <strong class="d-block" id="cliente-plano">-</strong>
                                                </div>
                                                <div class="mb-2">
                                                    <small class="text-muted">Categoria:</small>
                                                    <strong class="d-block" id="cliente-categoria">-</strong>
                                                </div>
                                                <div class="mb-2">
                                                    <small class="text-muted">Operadora:</small>
                                                    <strong class="d-block" id="cliente-entidade">-</strong>
                                                </div>
                                                <div class="mb-0">
                                                    <small class="text-muted">Valor:</small>
                                                    <h5 class="text-success mb-0" id="cliente-valor">-</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" id="cliente-id" value="">
                            </div>
                        </div>

                        <!-- Card de Dependentes -->
                        <div class="card mt-3 d-none" id="card-dependentes">
                            <div class="card-header bg-label-info">
                                <h6 class="mb-0">
                                    <i class="ri-group-line me-2"></i>
                                    Dependentes
                                    <span class="badge bg-info ms-2" id="count-dependentes">0</span>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="lista-dependentes" class="row g-3">
                                    <!-- Dependentes serão inseridos aqui -->
                                </div>
                            </div>
                        </div>

                        <!-- Card de Tabulação -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="ri-clipboard-line me-2"></i>
                                    Tabulação do Atendimento
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="form-floating form-floating-outline">
                                    <select class="form-select" id="tabulacao-preditiva">
                                        <option value="">Selecione uma tabulação</option>
                                        <option value="NAO ATENDE">Não atende</option>
                                        <option value="NUMERO INEXISTENTE">Número inexistente</option>
                                        <option value="NAO INTERESSADO">Não interessado</option>
                                        <option value="JA POSSUI PLANO">Já possui plano</option>
                                    </select>
                                    <label for="tabulacao-preditiva">Resultado do Contato</label>
                                </div>
                            </div>
                        </div>

                        <!-- Seção para exibir dados da consulta -->
                        <div id="dados-consulta-cliente" class="d-none mt-3">
                            <!-- Dados da Pessoa -->
                            <div id="dados-pessoa-preditiva" class="d-none">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="text-primary mb-0">
                                            <i class="ri-user-search-line ri-16px me-1"></i>
                                            Dados Completos do Cliente
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Nome:</strong> <span id="nome-preditiva"
                                                        class="text-muted"></span></p>
                                                <p><strong>CPF:</strong> <span id="cpf-result-preditiva"
                                                        class="text-muted"></span></p>
                                                <p><strong>Data Nascimento:</strong> <span id="data-nascimento-preditiva"
                                                        class="text-muted"></span></p>

                                                <p><strong>Idade Atual:</strong> <span id="idade"
                                                        class="text-muted"></span></p>

                                                <p><strong>Sexo:</strong> <span id="sexo-preditiva"
                                                        class="text-muted"></span></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Nome da Mãe:</strong> <span id="nome-mae-preditiva"
                                                        class="text-muted"></span></p>
                                                <p><strong>Situação CPF:</strong> <span id="situacao-cpf-preditiva"
                                                        class="text-muted"></span></p>
                                                <p><strong>Renda:</strong> <span id="renda-preditiva"
                                                        class="text-muted"></span></p>
                                                <p><strong>Ocupação:</strong> <span id="ocupacao-preditiva"
                                                        class="text-muted"></span></p>
                                            </div>
                                        </div>

                                        <!-- Contatos - Telefones -->
                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <h6><i class="ri-smartphone-line ri-16px me-1"></i>Celulares</h6>
                                                <div id="celulares-preditiva" class="border rounded p-2"
                                                    style="min-height: 60px; max-height: 200px; overflow-y: auto;"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6><i class="ri-phone-line ri-16px me-1"></i>Telefones Fixos</h6>
                                                <div id="fixos-preditiva" class="border rounded p-2"
                                                    style="min-height: 60px; max-height: 200px; overflow-y: auto;"></div>
                                            </div>
                                        </div>

                                        <!-- Contatos - E-mails -->
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <h6><i class="ri-mail-line ri-16px me-1"></i>E-mails</h6>
                                                <div id="emails-preditiva" class="border rounded p-2"
                                                    style="min-height: 60px; max-height: 200px; overflow-y: auto;"></div>
                                            </div>
                                        </div>

                                        <!-- Endereços -->
                                        <div class="mt-4">
                                            <h6><i class="ri-map-pin-line ri-16px me-1"></i>Endereços</h6>
                                            <div id="enderecos-preditiva" class="border rounded p-2"
                                                style="max-height: 300px; overflow-y: auto;"></div>
                                        </div>

                                        <!-- Risco de Crédito -->
                                        <div class="mt-4">
                                            <h6><i class="ri-shield-check-line ri-16px me-1"></i>Análise de Crédito</h6>
                                            <div id="risco-credito-preditiva" class="border rounded p-2"></div>
                                        </div>

                                        <div class="mt-4">
                                            <h6><i class="ri-building-2-line ri-16px me-1"></i>Participação Societária</h6>
                                            <div id="participacaoSocietaria" class="border rounded p-2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mensagem de Erro da Consulta -->
                        <div id="erro-consulta-cliente" class="alert alert-danger d-none mt-3">
                            <i class="ri-error-warning-line ri-16px me-1"></i>
                            <span id="mensagem-erro-cliente"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ri-close-line me-1"></i>Fechar
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-danger" id="btn-descartar-cliente" disabled>
                            <i class="ri-close-circle-line me-1"></i>Descartar
                        </button>
                        <button type="button" class="btn btn-success" id="btn-converter-cliente">
                            <i class="ri-check-double-line me-1"></i>Converter Lead
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
