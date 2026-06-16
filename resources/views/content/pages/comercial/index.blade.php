@extends('layouts/layoutMaster')

@section('title', 'Lista de Clientes - Kanban')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/jkanban/jkanban.scss', 'resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/quill/editor.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/app-kanban.scss', 'resources/assets/vendor/scss/pages/pos-venda.scss', 'resources/assets/vendor/scss/pages/kanban-modal.scss'])
    <style>
        /* ============================================================
           CRONOGRAMA — Agenda compacta (refit UI/UX)
        ============================================================ */

        #tab-cronograma {
            --cr-danger: #ff3e1d;
            --cr-info: #03c3ec;
            --cr-warning: #ffab00;
            --cr-success: #71dd37;
            --cr-secondary: #8592a3;
            --cr-primary: #696cff;
            --cr-radius: 12px;
            --cr-transition: all 0.2s ease;
        }

        /* ---------- Toolbar ---------- */
        .cr-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem 1rem;
            padding: 0.75rem 1rem;
            border-radius: var(--cr-radius);
            margin-bottom: 1rem;
        }

        .light-style .cr-toolbar {
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .dark-style .cr-toolbar {
            background: #2b2c40;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
        }

        .cr-toolbar-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.0625rem;
            font-weight: 700;
        }

        .cr-toolbar-title > i {
            font-size: 1.25rem;
            color: var(--cr-primary);
        }

        .cr-toolbar-total {
            font-size: 0.8125rem;
            font-weight: 500;
            opacity: 0.6;
            margin-left: 0.25rem;
        }

        /* ---------- Filtros (chips) ---------- */
        .cr-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.375rem;
        }

        .cr-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 8px;
            border: none;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--cr-transition);
        }

        .cr-chip i {
            font-size: 1rem;
        }

        .light-style .cr-chip {
            color: #566a7f;
            background: rgba(0, 0, 0, 0.04);
        }

        .dark-style .cr-chip {
            color: #b4b7bd;
            background: rgba(255, 255, 255, 0.06);
        }

        .cr-chip:hover {
            transform: translateY(-1px);
        }

        .cr-chip-count {
            font-size: 0.6875rem;
            font-weight: 700;
            min-width: 18px;
            padding: 0 5px;
            line-height: 18px;
            text-align: center;
            border-radius: 9px;
            background: rgba(0, 0, 0, 0.08);
        }

        .dark-style .cr-chip-count {
            background: rgba(255, 255, 255, 0.12);
        }

        .cr-chip.active {
            color: #fff;
        }

        .cr-chip.active .cr-chip-count {
            background: rgba(255, 255, 255, 0.25);
            color: #fff;
        }

        .cr-chip.filtro-cronograma.active {
            background: var(--cr-primary);
        }

        .cr-chip.chip-danger.active {
            background: var(--cr-danger);
        }

        .cr-chip.chip-info.active {
            background: var(--cr-info);
        }

        .cr-chip.chip-success.active {
            background: var(--cr-success);
        }

        .cr-chip.chip-secondary.active {
            background: var(--cr-secondary);
        }

        /* ---------- Hero: próxima ligação ---------- */
        .cr-proxima {
            position: relative;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-radius: var(--cr-radius);
            margin-bottom: 1.25rem;
            overflow: hidden;
            border-left: 4px solid var(--cr-primary);
            animation: crSlideIn 0.3s ease-out;
        }

        .light-style .cr-proxima {
            background: #fff;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .dark-style .cr-proxima {
            background: #2b2c40;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }

        .cr-proxima.status-danger { border-left-color: var(--cr-danger); }
        .cr-proxima.status-info { border-left-color: var(--cr-info); }
        .cr-proxima.status-warning { border-left-color: var(--cr-warning); }
        .cr-proxima.status-success { border-left-color: var(--cr-success); }
        .cr-proxima.status-secondary { border-left-color: var(--cr-secondary); }

        .cr-proxima-pulse {
            flex: 0 0 auto;
            width: 14px;
            height: 14px;
            position: relative;
        }

        .cr-proxima-pulse span {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: var(--cr-primary);
        }

        .cr-proxima.status-danger .cr-proxima-pulse span { background: var(--cr-danger); }
        .cr-proxima.status-info .cr-proxima-pulse span { background: var(--cr-info); }
        .cr-proxima.status-warning .cr-proxima-pulse span { background: var(--cr-warning); }
        .cr-proxima.status-success .cr-proxima-pulse span { background: var(--cr-success); }
        .cr-proxima.status-secondary .cr-proxima-pulse span { background: var(--cr-secondary); }

        .cr-proxima-pulse span::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: inherit;
            animation: crPulse 1.8s ease-out infinite;
        }

        @keyframes crPulse {
            0% { transform: scale(1); opacity: 0.6; }
            100% { transform: scale(3); opacity: 0; }
        }

        .cr-proxima-info {
            flex: 1;
            min-width: 0;
        }

        .cr-proxima-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            opacity: 0.6;
        }

        .cr-proxima-name {
            font-size: 1.125rem;
            font-weight: 700;
            margin: 2px 0 4px;
        }

        .cr-proxima-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .cr-proxima-time {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1rem;
            font-weight: 700;
        }

        .cr-proxima-date {
            font-size: 0.8125rem;
            opacity: 0.6;
        }

        .cr-proxima-status {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            color: #fff;
            font-size: 0.6875rem;
            font-weight: 700;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
        }

        .cr-proxima-obs {
            font-size: 0.8125rem;
            opacity: 0.7;
            margin-top: 0.375rem;
        }

        .cr-proxima-actions {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
            flex: 0 0 auto;
        }

        .cr-proxima-actions .btn {
            white-space: nowrap;
        }

        /* ---------- Timeline compacta ---------- */
        .cr-timeline {
            padding: 0.25rem 0;
        }

        .cr-section {
            margin-bottom: 0.75rem;
        }

        /* Divisor de dia (fino + sticky) */
        .cr-day {
            position: sticky;
            top: 0;
            z-index: 3;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.25rem;
            margin-bottom: 0.25rem;
            backdrop-filter: blur(6px);
        }

        .light-style .cr-day { background: rgba(245, 245, 249, 0.9); }
        .dark-style .cr-day { background: rgba(35, 37, 55, 0.9); }

        .cr-day-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex: 0 0 auto;
        }

        .cr-day.status-danger .cr-day-dot { background: var(--cr-danger); }
        .cr-day.status-info .cr-day-dot { background: var(--cr-info); }
        .cr-day.status-warning .cr-day-dot { background: var(--cr-warning); }
        .cr-day.status-success .cr-day-dot { background: var(--cr-success); }
        .cr-day.status-secondary .cr-day-dot { background: var(--cr-secondary); }

        .cr-day-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .cr-day.status-danger .cr-day-label { color: var(--cr-danger); }
        .cr-day.status-info .cr-day-label { color: var(--cr-info); }
        .cr-day.status-warning .cr-day-label { color: var(--cr-warning); }
        .cr-day.status-success .cr-day-label { color: var(--cr-success); }
        .cr-day.status-secondary .cr-day-label { color: var(--cr-secondary); }

        .cr-day-date {
            font-size: 0.75rem;
            opacity: 0.55;
            font-weight: 500;
        }

        .cr-day-count {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.6875rem;
            font-weight: 600;
            opacity: 0.55;
        }

        .cr-items {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        /* ---------- Seção recolhível (Semana / Futuro) ---------- */
        .cr-collapse {
            margin-bottom: 0.5rem;
        }

        .cr-collapse-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.6rem 0.75rem;
            border: none;
            border-radius: 10px;
            font-size: 0.8125rem;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            transition: var(--cr-transition);
        }

        .light-style .cr-collapse-toggle {
            background: rgba(0, 0, 0, 0.03);
            color: #566a7f;
        }

        .dark-style .cr-collapse-toggle {
            background: rgba(255, 255, 255, 0.05);
            color: #b4b7bd;
        }

        .cr-collapse-toggle:hover {
            filter: brightness(0.97);
        }

        .cr-collapse-caret {
            transition: transform 0.2s ease;
            font-size: 1.125rem;
        }

        .cr-collapse.is-open .cr-collapse-caret {
            transform: rotate(90deg);
        }

        .cr-collapse-icon {
            font-size: 1rem;
        }

        .cr-collapse-toggle.status-success .cr-collapse-icon { color: var(--cr-success); }
        .cr-collapse-toggle.status-secondary .cr-collapse-icon { color: var(--cr-secondary); }

        .cr-collapse-count {
            margin-left: auto;
            font-size: 0.6875rem;
            font-weight: 700;
            min-width: 20px;
            padding: 1px 6px;
            text-align: center;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.08);
        }

        .dark-style .cr-collapse-count {
            background: rgba(255, 255, 255, 0.12);
        }

        .cr-collapse-body {
            display: none;
            padding-top: 0.5rem;
        }

        .cr-collapse.is-open .cr-collapse-body {
            display: block;
        }

        /* ---------- Linha de agendamento (compacta) ---------- */
        .cr-item {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.5rem 0.75rem;
            border-radius: 10px;
            transition: var(--cr-transition);
            animation: crSlideIn 0.2s ease-out;
        }

        .light-style .cr-item {
            background: #fff;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        }

        .dark-style .cr-item {
            background: #2b2c40;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.3);
        }

        .cr-item:hover {
            transform: translateX(3px);
        }

        .light-style .cr-item:hover {
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .dark-style .cr-item:hover {
            box-shadow: 0 3px 14px rgba(0, 0, 0, 0.45);
        }

        .cr-item-dot {
            flex: 0 0 auto;
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .cr-item-time {
            flex: 0 0 auto;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9375rem;
            font-weight: 700;
            min-width: 46px;
            text-align: center;
        }

        .cr-item-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            line-height: 1.25;
        }

        .cr-item-name {
            font-size: 0.875rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cr-item-broker {
            font-size: 0.75rem;
            opacity: 0.55;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cr-item-obs {
            flex: 0 0 auto;
            font-size: 0.95rem;
            opacity: 0.45;
            cursor: help;
        }

        .cr-item-rel {
            flex: 0 0 auto;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }

        @media (min-width: 769px) {
            .cr-item-rel {
                min-width: 96px;
                text-align: right;
            }
        }

        /* Ações em ícone */
        .cr-item-actions {
            flex: 0 0 auto;
            display: flex;
            gap: 0.25rem;
        }

        .cr-btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            font-size: 1.05rem;
            transition: var(--cr-transition);
            text-decoration: none;
        }

        .light-style .cr-btn-icon {
            background: rgba(0, 0, 0, 0.04);
            color: #697a8d;
        }

        .dark-style .cr-btn-icon {
            background: rgba(255, 255, 255, 0.06);
            color: #b4b7bd;
        }

        .cr-btn-open:hover { background: var(--cr-primary); color: #fff; }
        .cr-btn-resched:hover { background: var(--cr-warning); color: #fff; }
        .cr-btn-done:hover { background: var(--cr-success); color: #fff; }

        /* ---------- Empty state (menor) ---------- */
        .cronograma-empty-state {
            text-align: center;
            padding: 2.5rem 1.25rem;
            border-radius: var(--cr-radius);
        }

        .light-style .cronograma-empty-state {
            background: rgba(105, 108, 255, 0.03);
        }

        .dark-style .cronograma-empty-state {
            background: rgba(105, 108, 255, 0.06);
        }

        .cronograma-empty-icon {
            font-size: 3rem;
            opacity: 0.25;
            margin-bottom: 0.5rem;
        }

        .cronograma-empty-title {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .cronograma-empty-text {
            opacity: 0.6;
            font-size: 0.875rem;
        }

        /* Badge contador na aba */
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

        /* ---------- Responsivo ---------- */
        @media (max-width: 768px) {
            .cr-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .cr-filters {
                justify-content: space-between;
            }

            .cr-chip span:not(.cr-chip-count) {
                display: none;
            }

            .cr-chip.filtro-cronograma[data-filter="todos"] span {
                display: inline;
            }

            .cr-proxima {
                flex-direction: column;
                align-items: flex-start;
            }

            .cr-proxima-actions {
                flex-direction: row;
                width: 100%;
            }

            .cr-proxima-actions .btn {
                flex: 1;
            }

            .cr-item-broker {
                display: none;
            }
        }

        /* ---------- Animação ---------- */
        @keyframes crSlideIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

            @if ($typeUserLogeed == 'VENDEDOR')
            <div class="filter-group" style="flex: 0 0 auto;">
                <button type="button" class="btn btn-primary h-100 px-4" id="btn-fila-preditiva">
                    <i class="ri-user-star-line me-2"></i>Vitrine de Clientes
                </button>
            </div>
            @endif
        </div>

        <!-- Top horizontal scrollbar proxy — mirrors the kanban-container scrollLeft
             so the user can navigate columns without scrolling down to find the
             native bottom scrollbar. Sync is set up in comercialkanban.js. -->
        <div class="kanban-top-scroll" aria-hidden="true">
            <div class="kanban-top-scroll-inner"></div>
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
            <!-- Toolbar compacta -->
            <div class="cr-toolbar">
                <div class="cr-toolbar-title">
                    <i class="ri-calendar-schedule-line"></i>
                    <span>Cronograma</span>
                    <span class="cr-toolbar-total"><span id="total-agendamentos">0</span> agendamentos</span>
                </div>
                <div class="cr-filters">
                    <button type="button" class="cr-chip filtro-cronograma active" data-filter="todos">
                        <i class="ri-list-check-2"></i>
                        <span>Todos</span>
                    </button>
                    <button type="button" class="cr-chip chip-danger filtro-cronograma" data-filter="atrasados">
                        <i class="ri-alarm-warning-line"></i>
                        <span>Atrasados</span>
                        <span class="cr-chip-count" id="count-atrasados">0</span>
                    </button>
                    <button type="button" class="cr-chip chip-info filtro-cronograma" data-filter="hoje">
                        <i class="ri-focus-3-line"></i>
                        <span>Hoje</span>
                        <span class="cr-chip-count" id="count-hoje">0</span>
                    </button>
                    <button type="button" class="cr-chip chip-success filtro-cronograma" data-filter="semana">
                        <i class="ri-calendar-check-line"></i>
                        <span>Semana</span>
                        <span class="cr-chip-count" id="count-semana">0</span>
                    </button>
                    <button type="button" class="cr-chip chip-secondary filtro-cronograma" data-filter="futuro">
                        <i class="ri-calendar-2-line"></i>
                        <span>Futuro</span>
                        <span class="cr-chip-count" id="count-futuro">0</span>
                    </button>
                </div>
            </div>

            <!-- Hero: Próxima ligação -->
            <div id="cronograma-proxima" class="d-none"></div>

            <!-- Loading -->
            <div id="cronograma-loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status" style="width: 2.25rem; height: 2.25rem;">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-3 mb-0 fw-medium text-muted">Carregando seus agendamentos...</p>
            </div>

            <!-- Mensagem vazia -->
            <div id="cronograma-empty" class="cronograma-empty-state d-none">
                <div class="cronograma-empty-icon">
                    <i class="ri-calendar-check-line"></i>
                </div>
                <h5 class="cronograma-empty-title">Nenhum agendamento encontrado</h5>
                <p class="cronograma-empty-text mb-0">Não há ligações agendadas para o filtro selecionado.</p>
            </div>

            <!-- Timeline compacta -->
            <div id="cronograma-timeline" class="cr-timeline d-none">
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
                        {{-- Campo de busca manual: fonte + tipo + valor --}}
                        <div class="kp-search-area">
                            <div class="oc-fonte-seg" id="kp-fonte-seg">
                                <button type="button" class="oc-fonte-btn active" data-fonte="lemit">Lemit</button>
                                <button type="button" class="oc-fonte-btn" data-fonte="assertiva">Assertiva</button>
                            </div>
                            <div class="oc-tipo-seg" id="kp-tipo-seg">
                                <button type="button" class="oc-tipo-btn active" data-tipo="documento">Documento</button>
                                <button type="button" class="oc-tipo-btn" data-tipo="telefone">Telefone</button>
                                <button type="button" class="oc-tipo-btn" data-tipo="email">E-mail</button>
                                <button type="button" class="oc-tipo-btn" data-tipo="nome">Nome</button>
                            </div>
                            <div class="kp-search-wrap">
                                <input type="text" id="kp-manual-cpf" class="kp-search-input"
                                       placeholder="CPF ou CNPJ..." maxlength="18" autocomplete="off">
                                <button type="button" id="btn-kp-manual-search" class="kp-search-btn" title="Buscar">
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

                            {{-- Selo de fonte do resultado exibido --}}
                            <div class="vit-result-meta"><span class="oc-fonte-badge d-none" id="vit-fonte-badge"></span></div>

                            {{-- Estado dinâmico: buscando / nada encontrado --}}
                            <div id="consulta-estado" class="d-none"></div>

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
                                            <button class="kp-info-tab-btn" data-kp-tab="tab-vinculos">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                                </svg>
                                                Vínculos
                                                <span class="kp-tab-count" id="kp-count-vinculos">0</span>
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

                                            {{-- Tab: Vínculos (familiares/relacionados — Lemit) --}}
                                            <div class="kp-info-tab-pane" id="tab-vinculos">
                                                <div id="vinculos-preditiva"></div>
                                            </div>

                                        </div>{{-- /kp-info-tab-panes --}}
                                    </div>{{-- /kp-info-tabs-wrapper --}}

                                    {{-- Render completo Assertiva (preenchido pelo consulta.js) --}}
                                    <div id="dados-assertiva-preditiva" class="assv-wrap d-none"></div>

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
