@extends('layouts/layoutMaster')

@section('title', 'Relatorio de Vendas | ' . auth()->user()->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/swiper/swiper.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/cards-statistics.scss')
    <style>
        /* ═══════════════════════════════════════════════════════════════
           EXECUTIVE DASHBOARD - Modern Sales Report
           Aesthetic: Refined • Professional • Sophisticated
        ═══════════════════════════════════════════════════════════════ */

        /* CSS Variables for Theme Support */
        :root {
            --vd-primary: #696cff;
            --vd-primary-rgb: 105, 108, 255;
            --vd-secondary: #8592a3;
            --vd-success: #71dd37;
            --vd-warning: #ffab00;
            --vd-danger: #ff3e1d;
            --vd-info: #03c3ec;
            --vd-accent: #8c57ff;
        }

        /* Light Theme */
        .light-style {
            --vd-surface: #ffffff;
            --vd-surface-alt: #f5f5f9;
            --vd-text: #566a7f;
            --vd-text-heading: #566a7f;
            --vd-text-muted: #a1acb8;
            --vd-border: #d9dee3;
            --vd-shadow: rgba(67, 89, 113, 0.12);
            --vd-shadow-lg: rgba(67, 89, 113, 0.16);
            --vd-card-bg: #ffffff;
        }

        /* Dark Theme */
        .dark-style {
            --vd-surface: #2b2c40;
            --vd-surface-alt: #232333;
            --vd-text: #a3a4cc;
            --vd-text-heading: #cfd3ec;
            --vd-text-muted: #7071a4;
            --vd-border: #444564;
            --vd-shadow: rgba(0, 0, 0, 0.25);
            --vd-shadow-lg: rgba(0, 0, 0, 0.35);
            --vd-card-bg: #2b2c40;
        }

        /* Page Container */
        .vendas-dashboard {
            position: relative;
        }

        /* Header Section with Gradient Background */
        .vendas-header {
            position: relative;
            margin: -1.5rem -1.5rem 2rem -1.5rem;
            padding: 2rem 2rem 2.5rem;
            background: linear-gradient(135deg, var(--vd-primary) 0%, var(--vd-accent) 100%);
            border-radius: 0 0 24px 24px;
            overflow: hidden;
        }

        .vendas-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 60%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }

        .vendas-header-content {
            position: relative;
            z-index: 2;
        }

        .vendas-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 0.25rem;
        }

        .vendas-subtitle {
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.875rem;
        }

        /* Filter Container */
        .filter-container {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 10px;
        }

        .filter-group .filter-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
        }

        .filter-group select {
            border: none;
            background: transparent;
            color: #fff;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            min-width: 100px;
            padding: 0.25rem 0;
        }

        .filter-group select:focus {
            outline: none;
            box-shadow: none;
        }

        .filter-group select option {
            background: var(--vd-surface);
            color: var(--vd-text);
        }

        /* Metric Cards */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 1200px) {
            .metrics-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 576px) {
            .metrics-grid { grid-template-columns: 1fr; }
        }

        .metric-card {
            position: relative;
            padding: 1.5rem;
            background: var(--vd-card-bg);
            border-radius: 12px;
            border: 1px solid var(--vd-border);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px var(--vd-shadow-lg);
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--metric-color), var(--metric-color-end));
        }

        .metric-card.cadastrado { --metric-color: var(--vd-primary); --metric-color-end: var(--vd-info); }
        .metric-card.implantado { --metric-color: var(--vd-success); --metric-color-end: #4caf50; }
        .metric-card.importados { --metric-color: var(--vd-danger); --metric-color-end: var(--vd-warning); }
        .metric-card.conversao { --metric-color: var(--vd-accent); --metric-color-end: var(--vd-primary); }

        .metric-icon-wrapper {
            width: 48px;
            height: 48px;
            margin-bottom: 1rem;
        }

        .metric-icon {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--metric-color), var(--metric-color-end));
            border-radius: 10px;
            font-size: 1.25rem;
            color: #fff;
        }

        .metric-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--vd-text-muted);
            margin-bottom: 0.35rem;
        }

        .metric-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--vd-text-heading);
            line-height: 1.2;
        }

        .metric-decoration {
            position: absolute;
            right: -15px;
            bottom: -15px;
            font-size: 80px;
            opacity: 0.06;
            color: var(--metric-color);
        }

        /* Data Table Card */
        .table-card {
            background: var(--vd-card-bg);
            border-radius: 12px;
            border: 1px solid var(--vd-border);
            overflow: hidden;
        }

        .table-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--vd-border);
            background: var(--vd-surface-alt);
        }

        .table-card-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            color: var(--vd-text-heading);
            margin: 0;
        }

        .table-card-title i {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--vd-primary), var(--vd-accent));
            border-radius: 10px;
            color: #fff;
        }

        .table-card-body {
            padding: 1.25rem;
        }

        /* DataTable Styling */
        #tabela-vendas-detalhadas thead th {
            background: var(--vd-surface-alt);
            color: var(--vd-text-muted);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.875rem 1rem;
            border-bottom: 1px solid var(--vd-border);
        }

        #tabela-vendas-detalhadas tbody tr {
            transition: background 0.2s ease;
        }

        .light-style #tabela-vendas-detalhadas tbody tr:hover {
            background: #f5f5f9;
        }

        .dark-style #tabela-vendas-detalhadas tbody tr:hover {
            background: #232333;
        }

        #tabela-vendas-detalhadas tbody td {
            padding: 1rem;
            border-bottom: 1px solid var(--vd-border);
            color: var(--vd-text);
            vertical-align: middle;
        }

        /* Action Button */
        .btn-view-sale {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--vd-primary), var(--vd-accent));
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-view-sale:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(var(--vd-primary-rgb), 0.4);
        }

        /* ═══════════════════════════════════════════════════════════════
           MODAL STYLING - Clean & Minimal
        ═══════════════════════════════════════════════════════════════ */

        #modalVisualizarVenda .modal-content {
            border: none;
            border-radius: 16px;
            overflow: hidden;
            background: var(--vd-card-bg);
        }

        .modal-header-clean {
            padding: 1.25rem 1.5rem;
            background: var(--vd-card-bg);
            border-bottom: 1px solid var(--vd-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header-clean .modal-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--vd-text-heading);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-header-clean .title-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--vd-surface-alt);
            border-radius: 8px;
            color: var(--vd-primary);
        }

        .modal-header-clean .btn-close-clean {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--vd-surface-alt);
            border: none;
            border-radius: 8px;
            color: var(--vd-text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-header-clean .btn-close-clean:hover {
            background: var(--vd-border);
            color: var(--vd-text-heading);
        }

        .modal-body-clean {
            padding: 1.5rem;
            background: var(--vd-card-bg);
        }

        /* Contract Header - Clean */
        .contract-header-clean {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding-bottom: 1.25rem;
            margin-bottom: 1.25rem;
            border-bottom: 1px solid var(--vd-border);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .contract-main-info h4 {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--vd-text-heading);
            margin-bottom: 0.5rem;
        }

        .contract-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.85rem;
            color: var(--vd-text-muted);
        }

        .contract-meta span {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .contract-meta i {
            font-size: 1rem;
        }

        .status-badge-clean {
            padding: 0.35rem 0.85rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-badge-clean.success {
            background: rgba(113, 221, 55, 0.15);
            color: var(--vd-success);
        }

        .status-badge-clean.warning {
            background: rgba(255, 171, 0, 0.15);
            color: var(--vd-warning);
        }

        .status-badge-clean.danger {
            background: rgba(255, 62, 29, 0.15);
            color: var(--vd-danger);
        }

        .status-badge-clean.primary {
            background: rgba(105, 108, 255, 0.15);
            color: var(--vd-primary);
        }

        /* Info Section Clean */
        .info-section-clean {
            margin-bottom: 1.5rem;
        }

        .info-section-clean .section-title {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--vd-text-muted);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--vd-border);
        }

        .info-grid-clean {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.25rem;
        }

        .info-item-clean {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .info-item-clean .label {
            font-size: 0.7rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: var(--vd-text-muted);
        }

        .info-item-clean .value {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--vd-text-heading);
        }

        .info-item-clean .value.highlight {
            font-size: 1.1rem;
            color: var(--vd-success);
        }

        .info-item-clean .value.muted {
            color: var(--vd-text-muted);
            font-style: italic;
            font-weight: 400;
        }

        /* Backoffice Card */
        .backoffice-card {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: var(--vd-surface-alt);
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }

        .backoffice-card .avatar {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--vd-primary);
            border-radius: 8px;
            color: #fff;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .backoffice-card .info .label {
            font-size: 0.7rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: var(--vd-text-muted);
        }

        .backoffice-card .info .name {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--vd-text-heading);
        }

        .backoffice-card.empty {
            background: transparent;
            border: 1px dashed var(--vd-border);
        }

        .backoffice-card.empty .avatar {
            background: var(--vd-border);
            color: var(--vd-text-muted);
        }

        /* Modal Footer Clean */
        .modal-footer-clean {
            padding: 1rem 1.5rem;
            background: var(--vd-card-bg);
            border-top: 1px solid var(--vd-border);
            display: flex;
            justify-content: flex-end;
        }

        .btn-close-modal-clean {
            padding: 0.5rem 1rem;
            background: var(--vd-surface-alt);
            border: 1px solid var(--vd-border);
            border-radius: 8px;
            color: var(--vd-text);
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-close-modal-clean:hover {
            background: var(--vd-border);
        }

        /* ═══════════════════════════════════════════════════════════════
           TIMELINE STYLING
        ═══════════════════════════════════════════════════════════════ */

        .timeline-venda {
            position: relative;
            padding-left: 30px;
        }

        .timeline-venda::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(180deg, var(--vd-primary), var(--vd-success));
            border-radius: 2px;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 1.25rem;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -26px;
            top: 4px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 3px solid var(--vd-primary);
            background: var(--vd-card-bg);
            z-index: 1;
        }

        .timeline-item.status-implantado::before {
            border-color: var(--vd-success);
            background: var(--vd-success);
        }

        .timeline-item.status-pendencia::before,
        .timeline-item.status-estorno::before,
        .timeline-item.status-declinado::before {
            border-color: var(--vd-danger);
            background: var(--vd-danger);
        }

        .timeline-item.status-venda::before {
            border-color: var(--vd-primary);
            background: var(--vd-primary);
        }

        .timeline-item.status-analise::before {
            border-color: var(--vd-warning);
            background: var(--vd-warning);
        }

        .timeline-item.status-boleto::before {
            border-color: var(--vd-info);
            background: var(--vd-info);
        }

        .timeline-item.status-regularizado::before {
            border-color: var(--vd-success);
            background: var(--vd-success);
        }

        .timeline-card {
            background: var(--vd-surface-alt);
            border-radius: 10px;
            padding: 1rem;
            border-left: 3px solid var(--vd-primary);
            transition: all 0.2s ease;
        }

        .timeline-card:hover {
            transform: translateX(4px);
        }

        .timeline-item.status-implantado .timeline-card { border-left-color: var(--vd-success); }
        .timeline-item.status-pendencia .timeline-card,
        .timeline-item.status-estorno .timeline-card,
        .timeline-item.status-declinado .timeline-card { border-left-color: var(--vd-danger); }
        .timeline-item.status-venda .timeline-card { border-left-color: var(--vd-primary); }
        .timeline-item.status-analise .timeline-card { border-left-color: var(--vd-warning); }
        .timeline-item.status-boleto .timeline-card { border-left-color: var(--vd-info); }
        .timeline-item.status-regularizado .timeline-card { border-left-color: var(--vd-success); }

        .timeline-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .timeline-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .timeline-status-badge {
            padding: 0.25rem 0.65rem;
            border-radius: 50px;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .timeline-date {
            font-size: 0.75rem;
            color: var(--vd-text-muted);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .timeline-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.8rem;
            color: var(--vd-text-muted);
        }

        .timeline-transition {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .timeline-transition .status-from,
        .timeline-transition .status-to {
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            font-size: 0.65rem;
            font-weight: 600;
        }

        .timeline-transition .status-from {
            background: var(--vd-surface);
            color: var(--vd-text-muted);
        }

        .light-style .timeline-transition .status-from {
            background: #e9ecef;
        }

        .timeline-transition .status-to {
            background: rgba(113, 221, 55, 0.15);
            color: var(--vd-success);
        }

        .timeline-transition .arrow {
            color: var(--vd-text-muted);
        }

        .timeline-observacao {
            background: var(--vd-surface);
            border-radius: 8px;
            padding: 0.75rem;
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: var(--vd-text);
            border: 1px solid var(--vd-border);
        }

        .timeline-tempo {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.2rem 0.5rem;
            background: rgba(var(--vd-primary-rgb), 0.1);
            border-radius: 6px;
            font-size: 0.65rem;
            color: var(--vd-primary);
            font-weight: 600;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 2rem;
        }

        .empty-state-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--vd-surface-alt);
            border-radius: 50%;
            font-size: 1.5rem;
            color: var(--vd-text-muted);
        }

        .empty-state-text {
            color: var(--vd-text-muted);
            font-size: 0.9rem;
        }

        /* Modal Footer */
        .modal-footer-premium {
            padding: 1rem 1.5rem;
            background: var(--vd-card-bg);
            border-top: 1px solid var(--vd-border);
        }

        .btn-close-modal {
            padding: 0.6rem 1.25rem;
            background: var(--vd-surface-alt);
            border: 1px solid var(--vd-border);
            border-radius: 8px;
            color: var(--vd-text);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-close-modal:hover {
            background: var(--vd-border);
        }

        /* Loading State */
        .loading-state {
            text-align: center;
            padding: 3rem 2rem;
        }

        .loading-spinner {
            width: 48px;
            height: 48px;
            border: 3px solid var(--vd-border);
            border-top-color: var(--vd-primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            color: var(--vd-text-muted);
            font-size: 0.9rem;
        }

        /* DataTables Overrides */
        .dataTables_wrapper .dataTables_filter input {
            background: var(--vd-card-bg);
            border: 1px solid var(--vd-border);
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            color: var(--vd-text);
        }

        .dataTables_wrapper .dataTables_length select {
            background: var(--vd-card-bg);
            border: 1px solid var(--vd-border);
            border-radius: 6px;
            padding: 0.35rem 0.5rem;
            color: var(--vd-text);
        }

        .dataTables_wrapper .dataTables_info {
            color: var(--vd-text-muted);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--vd-primary) !important;
            border-color: var(--vd-primary) !important;
            color: #fff !important;
        }

        /* Custom Close Button for Modal Header */
        .btn-close-custom {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 1.25rem;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .btn-close-custom:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: rotate(90deg);
        }

        /* Contract Hero Section */
        .contract-hero-section {
            background: var(--vd-surface-alt);
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid var(--vd-border);
        }

        .contract-hero-section .contract-hero {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        /* Custom Tabs Styling */
        .nav-tabs-custom {
            border-bottom: 2px solid var(--vd-border);
            gap: 0.5rem;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            border-radius: 10px 10px 0 0;
            padding: 0.85rem 1.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--vd-text-muted);
            background: transparent;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            position: relative;
        }

        .nav-tabs-custom .nav-link:hover {
            color: var(--vd-primary);
            background: var(--vd-surface-alt);
        }

        .nav-tabs-custom .nav-link.active {
            color: var(--vd-primary);
            background: var(--vd-surface-alt);
        }

        .nav-tabs-custom .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--vd-primary), var(--vd-accent));
            border-radius: 3px 3px 0 0;
        }

        .nav-tabs-custom .nav-link i {
            font-size: 1.1rem;
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/swiper/swiper.js', 'resources/assets/vendor/libs/chartjs/chartjs.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/vendas.js'])
@endsection

@section('content')
<div class="vendas-dashboard">

    <!-- Header Section -->
    <div class="vendas-header">
        <div class="vendas-header-content">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-3 mb-lg-0">
                    <h1 class="vendas-title">
                        <i class="ri-line-chart-line me-2"></i>
                        Relatório de Vendas
                    </h1>
                    <p class="vendas-subtitle mb-0">
                        Acompanhe o desempenho das suas vendas
                    </p>
                </div>
                <div class="col-lg-6">
                    <div class="filter-container justify-content-lg-end">
                        <div class="filter-group">
                            <div class="filter-icon">
                                <i class="ri-calendar-line"></i>
                            </div>
                            <select id="select-month" class="form-select">
                                <option value="">Mês</option>
                                <option value="1">Janeiro</option>
                                <option value="2">Fevereiro</option>
                                <option value="3">Março</option>
                                <option value="4">Abril</option>
                                <option value="5">Maio</option>
                                <option value="6">Junho</option>
                                <option value="7">Julho</option>
                                <option value="8">Agosto</option>
                                <option value="9">Setembro</option>
                                <option value="10">Outubro</option>
                                <option value="11">Novembro</option>
                                <option value="12">Dezembro</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <div class="filter-icon">
                                <i class="ri-calendar-2-line"></i>
                            </div>
                            <select id="select-year" class="form-select">
                                <option value="">Ano</option>
                                @foreach ($anosDisponiveis as $ano)
                                    <option value="{{ $ano }}">{{ $ano }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="metrics-grid">
        <!-- Contratos Cadastrados -->
        <div class="metric-card cadastrado">
            <div class="metric-icon-wrapper">
                <div class="metric-icon">
                    <i class="ri-file-list-3-line"></i>
                </div>
            </div>
            <div class="metric-label">Contratos Cadastrados</div>
            <div class="metric-value js-valorCadastrado">R$ 0,00</div>
            <div class="metric-decoration">
                <i class="ri-file-list-3-line"></i>
            </div>
        </div>

        <!-- Contratos Implantados -->
        <div class="metric-card implantado">
            <div class="metric-icon-wrapper">
                <div class="metric-icon">
                    <i class="ri-checkbox-circle-line"></i>
                </div>
            </div>
            <div class="metric-label">Contratos Implantados</div>
            <div class="metric-value js-implantado">R$ 0,00</div>
            <div class="metric-decoration">
                <i class="ri-checkbox-circle-line"></i>
            </div>
        </div>

        <!-- Contatos Importados -->
        <div class="metric-card importados">
            <div class="metric-icon-wrapper">
                <div class="metric-icon">
                    <i class="ri-contacts-book-line"></i>
                </div>
            </div>
            <div class="metric-label">Contatos Importados</div>
            <div class="metric-value js-quantidadeContatosImportados">0</div>
            <div class="metric-decoration">
                <i class="ri-contacts-book-line"></i>
            </div>
        </div>

        <!-- Conversão Mensal -->
        <div class="metric-card conversao">
            <div class="metric-icon-wrapper">
                <div class="metric-icon">
                    <i class="ri-percent-line"></i>
                </div>
            </div>
            <div class="metric-label">Conversão Mensal</div>
            <div class="metric-value js-conversao">0%</div>
            <div class="metric-decoration">
                <i class="ri-percent-line"></i>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="table-card">
        <div class="table-card-header">
            <h5 class="table-card-title">
                <i class="ri-table-line"></i>
                Vendas Detalhadas
            </h5>
        </div>
        <div class="table-card-body">
            <div class="table-responsive">
                <table id="tabela-vendas-detalhadas" class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome do Contrato</th>
                            <th>Status</th>
                            <th>Backoffice</th>
                            <th>Valor</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Preenchido via DataTables --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Visualizar Venda - Clean Design -->
<div class="modal fade" id="modalVisualizarVenda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <!-- Header Clean -->
            <div class="modal-header-clean">
                <h5 class="modal-title">
                    <span class="title-icon">
                        <i class="ri-file-text-line"></i>
                    </span>
                    <span>Proposta #<span id="venda-modal-id">-</span></span>
                </h5>
                <button type="button" class="btn-close-clean" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            <!-- Body Clean -->
            <div class="modal-body-clean">
                <!-- Loading State -->
                <div id="venda-loading" class="loading-state">
                    <div class="loading-spinner"></div>
                    <p class="loading-text">Carregando detalhes...</p>
                </div>

                <!-- Content -->
                <div id="venda-content" class="d-none">

                    <!-- Contract Header -->
                    <div class="contract-header-clean">
                        <div class="contract-main-info">
                            <h4 id="venda-nome-contrato">-</h4>
                            <div class="contract-meta">
                                <span><i class="ri-id-card-line"></i> <span id="venda-cpf-cnpj">-</span></span>
                                <span><i class="ri-phone-line"></i> <span id="venda-telefone">-</span></span>
                            </div>
                        </div>
                        <span class="status-badge-clean primary" id="venda-status-badge">-</span>
                    </div>

                    <!-- Backoffice Responsável -->
                    <div class="backoffice-card" id="backoffice-card">
                        <div class="avatar" id="backoffice-avatar">-</div>
                        <div class="info">
                            <div class="label">Responsável Backoffice</div>
                            <div class="name" id="venda-backoffice-nome">-</div>
                        </div>
                    </div>

                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs nav-tabs-custom mb-4" id="vendaDetailsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-contrato" data-bs-toggle="tab" data-bs-target="#panel-contrato" type="button" role="tab">
                                <i class="ri-file-text-line me-2"></i>Contrato
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-historico" data-bs-toggle="tab" data-bs-target="#panel-historico" type="button" role="tab">
                                <i class="ri-history-line me-2"></i>Histórico
                            </button>
                        </li>
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content" id="vendaDetailsTabsContent">

                        <!-- Tab: Contrato -->
                        <div class="tab-pane fade show active" id="panel-contrato" role="tabpanel">

                            <!-- Plano e Operadora -->
                            <div class="info-section-clean">
                                <div class="section-title">Informações do Plano</div>
                                <div class="info-grid-clean">
                                    <div class="info-item-clean">
                                        <span class="label">Operadora</span>
                                        <span class="value" id="venda-operadora">-</span>
                                    </div>
                                    <div class="info-item-clean">
                                        <span class="label">Plano</span>
                                        <span class="value" id="venda-plano">-</span>
                                    </div>
                                    <div class="info-item-clean">
                                        <span class="label">Coparticipação</span>
                                        <span class="value" id="venda-coparticipacao">-</span>
                                    </div>
                                    <div class="info-item-clean">
                                        <span class="label">Vidas</span>
                                        <span class="value" id="venda-vidas">-</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Valores -->
                            <div class="info-section-clean">
                                <div class="section-title">Valores</div>
                                <div class="info-grid-clean">
                                    <div class="info-item-clean">
                                        <span class="label">Valor do Contrato</span>
                                        <span class="value highlight" id="venda-valor">-</span>
                                    </div>
                                    <div class="info-item-clean">
                                        <span class="label">Comissão</span>
                                        <span class="value" id="venda-comissao-valor">-</span>
                                    </div>
                                    <div class="info-item-clean">
                                        <span class="label">% Comissão</span>
                                        <span class="value" id="venda-comissao-percentual">-</span>
                                    </div>
                                    <div class="info-item-clean">
                                        <span class="label">Tipo</span>
                                        <span class="value" id="venda-angariacao">-</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Datas e Detalhes -->
                            <div class="info-section-clean">
                                <div class="section-title">Datas e Detalhes</div>
                                <div class="info-grid-clean">
                                    <div class="info-item-clean">
                                        <span class="label">Data Vigência</span>
                                        <span class="value" id="venda-data-vigencia">-</span>
                                    </div>
                                    <div class="info-item-clean">
                                        <span class="label">Data Implantação</span>
                                        <span class="value" id="venda-data-implantacao">-</span>
                                    </div>
                                    <div class="info-item-clean">
                                        <span class="label">Nº Proposta</span>
                                        <span class="value" id="venda-numero-proposta">-</span>
                                    </div>
                                    <div class="info-item-clean">
                                        <span class="label">Vendedor</span>
                                        <span class="value" id="venda-vendedor">-</span>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Tab: Histórico -->
                        <div class="tab-pane fade" id="panel-historico" role="tabpanel">

                            <div id="historico-loading" class="text-center py-4">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                                <span class="ms-2" style="color: var(--vd-text-muted);">Carregando histórico...</span>
                            </div>

                            <div id="historico-content" class="d-none">
                                <div id="historico-timeline" class="timeline-venda"></div>
                                <div id="historico-vazio" class="empty-state d-none">
                                    <div class="empty-state-icon">
                                        <i class="ri-inbox-line"></i>
                                    </div>
                                    <p class="empty-state-text">Nenhum histórico encontrado.</p>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>
            </div>

            <!-- Footer Clean -->
            <div class="modal-footer-clean">
                <button type="button" class="btn-close-modal-clean" data-bs-dismiss="modal">
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
