@extends('layouts/layoutMaster')

@section('title', 'Leads Descartados')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/toastr/toastr.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/scss/pages/dashboard-analytics.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/toastr/toastr.js',
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js'
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/leads-descartados.js'])
@endsection

@php
    $meses = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Marco', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];
@endphp

@section('page-style')
<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap');

.leads-descartados-wrapper {
    --ld-primary: #7C3AED;
    --ld-primary-light: #A78BFA;
    --ld-primary-rgb: 124, 58, 237;
    --ld-success: #10B981;
    --ld-success-light: #34D399;
    --ld-info: #06B6D4;
    --ld-info-light: #22D3EE;
    --ld-info-rgb: 6, 182, 212;
    --ld-warning: #F59E0B;
    --ld-warning-light: #FBBF24;
    --ld-warning-rgb: 245, 158, 11;
    --ld-danger: #EF4444;
    --ld-danger-light: #F87171;
    --ld-danger-rgb: 239, 68, 68;

    --ld-card-bg: rgba(255, 255, 255, 0.95);
    --ld-card-border: rgba(255, 255, 255, 0.2);
    --ld-glass-bg: rgba(255, 255, 255, 0.7);
    --ld-glass-blur: 12px;
    --ld-border-radius: 16px;
    --ld-shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
    --ld-shadow-md: 0 4px 20px rgba(0, 0, 0, 0.08);
    --ld-shadow-lg: 0 8px 40px rgba(0, 0, 0, 0.12);
    --ld-shadow-glow: 0 0 40px rgba(124, 58, 237, 0.15);
    --ld-text-primary: #1F2937;
    --ld-text-secondary: #6B7280;
    --ld-text-muted: #9CA3AF;
    --ld-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height: 100vh;
}

.dark-style .leads-descartados-wrapper {
    --ld-card-bg: rgba(30, 32, 47, 0.95);
    --ld-card-border: rgba(255, 255, 255, 0.08);
    --ld-glass-bg: rgba(30, 32, 47, 0.8);
    --ld-shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.2);
    --ld-shadow-md: 0 4px 20px rgba(0, 0, 0, 0.3);
    --ld-shadow-lg: 0 8px 40px rgba(0, 0, 0, 0.4);
    --ld-shadow-glow: 0 0 60px rgba(124, 58, 237, 0.25);
    --ld-text-primary: #F9FAFB;
    --ld-text-secondary: #D1D5DB;
    --ld-text-muted: #9CA3AF;
}

/* Header Section */
.ld-header {
    margin-bottom: 2rem;
    animation: fadeInDown 0.6s ease-out;
}

.ld-header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.ld-header-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, var(--ld-danger), var(--ld-danger-light));
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(var(--ld-danger-rgb), 0.3);
}

.ld-header-icon svg {
    width: 28px;
    height: 28px;
    color: white;
}

.ld-header-text h1 {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--ld-text-primary);
    margin: 0;
    letter-spacing: -0.02em;
}

.ld-header-text p {
    font-size: 0.9rem;
    color: var(--ld-text-secondary);
    margin: 0.25rem 0 0;
}

/* KPI Grid */
.ld-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}

@media (max-width: 1200px) {
    .ld-kpi-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 576px) {
    .ld-kpi-grid { grid-template-columns: 1fr; }
}

.ld-kpi-card {
    background: var(--ld-card-bg);
    backdrop-filter: blur(var(--ld-glass-blur));
    border: 1px solid var(--ld-card-border);
    border-radius: var(--ld-border-radius);
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    transition: var(--ld-transition);
    animation: fadeInUp 0.5s ease-out backwards;
}

.ld-kpi-card:nth-child(1) { animation-delay: 0ms; }
.ld-kpi-card:nth-child(2) { animation-delay: 80ms; }
.ld-kpi-card:nth-child(3) { animation-delay: 160ms; }
.ld-kpi-card:nth-child(4) { animation-delay: 240ms; }

.ld-kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--ld-shadow-lg);
}

.ld-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    border-radius: var(--ld-border-radius) var(--ld-border-radius) 0 0;
}

.ld-kpi-card.kpi-danger::before { background: linear-gradient(90deg, var(--ld-danger), var(--ld-danger-light)); }
.ld-kpi-card.kpi-info::before { background: linear-gradient(90deg, var(--ld-info), var(--ld-info-light)); }
.ld-kpi-card.kpi-warning::before { background: linear-gradient(90deg, var(--ld-warning), var(--ld-warning-light)); }
.ld-kpi-card.kpi-primary::before { background: linear-gradient(90deg, var(--ld-primary), var(--ld-primary-light)); }

.ld-kpi-card .kpi-glow {
    position: absolute;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    filter: blur(40px);
    opacity: 0;
    right: -20px;
    top: -20px;
    transition: opacity 0.4s ease;
    pointer-events: none;
}

.ld-kpi-card:hover .kpi-glow { opacity: 0.15; }

.ld-kpi-card.kpi-danger .kpi-glow { background: var(--ld-danger); }
.ld-kpi-card.kpi-info .kpi-glow { background: var(--ld-info); }
.ld-kpi-card.kpi-warning .kpi-glow { background: var(--ld-warning); }
.ld-kpi-card.kpi-primary .kpi-glow { background: var(--ld-primary); }

.ld-kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.ld-kpi-card.kpi-danger .ld-kpi-icon { background: linear-gradient(135deg, var(--ld-danger), var(--ld-danger-light)); }
.ld-kpi-card.kpi-info .ld-kpi-icon { background: linear-gradient(135deg, var(--ld-info), var(--ld-info-light)); }
.ld-kpi-card.kpi-warning .ld-kpi-icon { background: linear-gradient(135deg, var(--ld-warning), var(--ld-warning-light)); }
.ld-kpi-card.kpi-primary .ld-kpi-icon { background: linear-gradient(135deg, var(--ld-primary), var(--ld-primary-light)); }

.ld-kpi-icon svg {
    width: 24px;
    height: 24px;
    color: white;
}

.ld-kpi-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--ld-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.ld-kpi-value {
    font-family: 'JetBrains Mono', monospace;
    font-size: 2rem;
    font-weight: 700;
    color: var(--ld-text-primary);
    line-height: 1;
    margin: 0;
}

/* Filter Panel */
.ld-filter-panel {
    background: var(--ld-card-bg);
    backdrop-filter: blur(var(--ld-glass-blur));
    border: 1px solid var(--ld-card-border);
    border-radius: var(--ld-border-radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    animation: fadeInUp 0.5s ease-out 0.3s backwards;
}

.ld-filter-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}

.ld-filter-header svg {
    width: 20px;
    height: 20px;
    color: var(--ld-primary);
}

.ld-filter-header h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--ld-text-primary);
    margin: 0;
}

.ld-filter-header span {
    font-size: 0.85rem;
    color: var(--ld-text-muted);
    margin-left: auto;
}

.ld-filter-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 1rem;
    align-items: end;
}

@media (max-width: 992px) {
    .ld-filter-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 576px) {
    .ld-filter-grid { grid-template-columns: 1fr; }
}

.ld-filter-group label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--ld-text-secondary);
    margin-bottom: 0.5rem;
}

.ld-filter-group .form-select,
.ld-filter-group .form-control {
    border-radius: 10px;
    border: 1px solid var(--ld-card-border);
    background: var(--ld-glass-bg);
    font-size: 0.9rem;
    padding: 0.625rem 1rem;
    transition: var(--ld-transition);
}

.ld-filter-group .form-select:focus,
.ld-filter-group .form-control:focus {
    border-color: var(--ld-primary);
    box-shadow: 0 0 0 3px rgba(var(--ld-primary-rgb), 0.15);
}

.ld-btn-clear {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 10px;
    border: 1px solid var(--ld-card-border);
    background: transparent;
    color: var(--ld-text-secondary);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--ld-transition);
    width: 100%;
}

.ld-btn-clear:hover {
    background: var(--ld-glass-bg);
    color: var(--ld-text-primary);
    border-color: var(--ld-primary);
}

.ld-btn-clear svg {
    width: 16px;
    height: 16px;
}

/* Action Bar */
.ld-action-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    animation: fadeInUp 0.5s ease-out 0.35s backwards;
}

.ld-selected-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.ld-selected-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, rgba(var(--ld-primary-rgb), 0.1), rgba(var(--ld-primary-rgb), 0.05));
    border: 1px solid rgba(var(--ld-primary-rgb), 0.2);
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--ld-primary);
}

.ld-selected-badge span {
    font-family: 'JetBrains Mono', monospace;
    font-weight: 700;
}

.ld-btn-preditiva {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, var(--ld-primary), var(--ld-primary-light));
    border: none;
    border-radius: 12px;
    color: white;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    transition: var(--ld-transition);
    box-shadow: 0 4px 16px rgba(var(--ld-primary-rgb), 0.3);
}

.ld-btn-preditiva:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(var(--ld-primary-rgb), 0.4);
}

.ld-btn-preditiva:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    box-shadow: none;
}

.ld-btn-preditiva svg {
    width: 18px;
    height: 18px;
}

.ld-btn-preditiva .spinner-border {
    width: 18px;
    height: 18px;
    border-width: 2px;
}

/* Table Card */
.ld-table-card {
    background: var(--ld-card-bg);
    backdrop-filter: blur(var(--ld-glass-blur));
    border: 1px solid var(--ld-card-border);
    border-radius: var(--ld-border-radius);
    overflow: hidden;
    animation: fadeInUp 0.5s ease-out 0.4s backwards;
}

.ld-table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--ld-card-border);
}

.ld-table-title-group {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.ld-table-icon {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, var(--ld-danger), var(--ld-danger-light));
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ld-table-icon svg {
    width: 20px;
    height: 20px;
    color: white;
}

.ld-table-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--ld-text-primary);
    margin: 0;
}

.ld-table-subtitle {
    font-size: 0.8rem;
    color: var(--ld-text-muted);
}

.ld-table-body {
    padding: 1rem 1.5rem 1.5rem;
}

/* DataTable Overrides */
.ld-table-body .table {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.875rem;
}

.ld-table-body .table thead th {
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--ld-text-muted);
    border-bottom: 2px solid var(--ld-card-border);
    padding: 1rem 0.75rem;
    background: transparent;
}

.ld-table-body .table tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--ld-card-border);
    color: var(--ld-text-primary);
}

.ld-table-body .table tbody tr:hover {
    background: rgba(var(--ld-primary-rgb), 0.03);
}

.dark-style .ld-table-body .table tbody tr:hover {
    background: rgba(255, 255, 255, 0.02);
}

.ld-table-body .form-check-input {
    width: 18px;
    height: 18px;
    border-radius: 5px;
    cursor: pointer;
}

.ld-table-body .form-check-input:checked {
    background-color: var(--ld-primary);
    border-color: var(--ld-primary);
}

.ld-valor-plano {
    font-family: 'JetBrains Mono', monospace;
    font-weight: 600;
    color: var(--ld-success);
}

.ld-categoria-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: rgba(var(--ld-info-rgb), 0.1);
    color: var(--ld-info);
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.ld-base-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: rgba(var(--ld-primary-rgb), 0.1);
    color: var(--ld-primary);
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.ld-text-muted {
    color: var(--ld-text-muted);
    font-style: italic;
}

/* Dropdown Actions */
.ld-dropdown-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid var(--ld-card-border);
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--ld-transition);
}

.ld-dropdown-btn:hover {
    background: var(--ld-glass-bg);
    border-color: var(--ld-primary);
}

.ld-dropdown-btn svg {
    width: 18px;
    height: 18px;
    color: var(--ld-text-secondary);
}

.dropdown-menu {
    border-radius: 12px;
    border: 1px solid var(--ld-card-border);
    box-shadow: var(--ld-shadow-lg);
    padding: 0.5rem;
    min-width: 200px;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: var(--ld-transition);
}

.dropdown-item svg {
    width: 16px;
    height: 16px;
    opacity: 0.7;
}

.dropdown-item:hover {
    background: rgba(var(--ld-primary-rgb), 0.1);
    color: var(--ld-primary);
}

.dropdown-item.text-danger:hover {
    background: rgba(var(--ld-danger-rgb), 0.1);
    color: var(--ld-danger);
}

.dropdown-divider {
    margin: 0.5rem 0;
    border-color: var(--ld-card-border);
}

/* Modal */
.ld-modal .modal-content {
    border-radius: var(--ld-border-radius);
    border: 1px solid var(--ld-card-border);
    background: var(--ld-card-bg);
    backdrop-filter: blur(var(--ld-glass-blur));
}

.ld-modal .modal-header {
    border-bottom: 1px solid var(--ld-card-border);
    padding: 1.25rem 1.5rem;
}

.ld-modal .modal-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--ld-text-primary);
}

.ld-modal .modal-body {
    padding: 1.5rem;
}

.ld-comment-item {
    padding: 1rem;
    background: var(--ld-glass-bg);
    border-radius: 12px;
    margin-bottom: 0.75rem;
}

.ld-comment-item:last-child {
    margin-bottom: 0;
}

.ld-comment-author {
    font-weight: 700;
    color: var(--ld-text-primary);
}

.ld-comment-date {
    font-size: 0.8rem;
    color: var(--ld-text-muted);
    margin-left: 0.5rem;
}

.ld-comment-text {
    margin-top: 0.5rem;
    color: var(--ld-text-secondary);
    line-height: 1.6;
}

/* DataTables Pagination */
.dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius: 8px !important;
    margin: 0 2px;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: linear-gradient(135deg, var(--ld-primary), var(--ld-primary-light)) !important;
    border-color: transparent !important;
    color: white !important;
}

.dataTables_wrapper .dataTables_filter input {
    border-radius: 10px;
    border: 1px solid var(--ld-card-border);
    padding: 0.5rem 1rem;
}

.dataTables_wrapper .dataTables_length select {
    border-radius: 10px;
    border: 1px solid var(--ld-card-border);
    padding: 0.5rem;
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
@endsection

@section('content')
<div class="leads-descartados-wrapper">

    <!-- Header -->
    <div class="ld-header">
        <div class="ld-header-content">
            <div class="ld-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"/>
                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                    <line x1="10" y1="11" x2="10" y2="17"/>
                    <line x1="14" y1="11" x2="14" y2="17"/>
                </svg>
            </div>
            <div class="ld-header-text">
                <h1>Leads Descartados</h1>
                <p>Gerencie e recupere leads para novas oportunidades na preditiva</p>
            </div>
        </div>
    </div>

    <!-- KPI Grid -->
    <div class="ld-kpi-grid">
        <!-- Total Descartados -->
        <div class="ld-kpi-card kpi-danger">
            <div class="ld-kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
            </div>
            <p class="ld-kpi-label">Total Descartados</p>
            <h2 class="ld-kpi-value" id="total-descartados">0</h2>
            <div class="kpi-glow"></div>
        </div>

        <!-- Descartados no Mes -->
        <div class="ld-kpi-card kpi-info">
            <div class="ld-kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <p class="ld-kpi-label">Descartados no Mes</p>
            <h2 class="ld-kpi-value" id="total-mes-atual">0</h2>
            <div class="kpi-glow"></div>
        </div>

        <!-- Resultados Filtrados -->
        <div class="ld-kpi-card kpi-warning">
            <div class="ld-kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                </svg>
            </div>
            <p class="ld-kpi-label">Resultados Filtrados</p>
            <h2 class="ld-kpi-value" id="total-filtrado">0</h2>
            <div class="kpi-glow"></div>
        </div>

        <!-- Bases de Origem -->
        <div class="ld-kpi-card kpi-primary">
            <div class="ld-kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <ellipse cx="12" cy="5" rx="9" ry="3"/>
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                </svg>
            </div>
            <p class="ld-kpi-label">Bases de Origem</p>
            <h2 class="ld-kpi-value" id="total-bases">0</h2>
            <div class="kpi-glow"></div>
        </div>
    </div>

    <!-- Filter Panel -->
    <div class="ld-filter-panel">
        <div class="ld-filter-header">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="4" y1="21" x2="4" y2="14"/>
                <line x1="4" y1="10" x2="4" y2="3"/>
                <line x1="12" y1="21" x2="12" y2="12"/>
                <line x1="12" y1="8" x2="12" y2="3"/>
                <line x1="20" y1="21" x2="20" y2="16"/>
                <line x1="20" y1="12" x2="20" y2="3"/>
                <line x1="1" y1="14" x2="7" y2="14"/>
                <line x1="9" y1="8" x2="15" y2="8"/>
                <line x1="17" y1="16" x2="23" y2="16"/>
            </svg>
            <h3>Filtros Avancados</h3>
            <span>Refine sua busca por periodo e origem</span>
        </div>
        <div class="ld-filter-grid">
            <div class="ld-filter-group">
                <label>Mes</label>
                <select id="filtro_mes" class="form-select">
                    <option value="">Todos os meses</option>
                    @foreach ($meses as $num => $nome)
                        <option value="{{ $num }}">{{ $nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ld-filter-group">
                <label>Ano</label>
                <select id="filtro_ano" class="form-select">
                    <option value="">Todos</option>
                    @for ($y = now()->year; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="ld-filter-group">
                <label>Periodo Personalizado</label>
                <input type="text" id="filtro_periodo" class="form-control flatpickr-range" placeholder="Selecione o periodo">
            </div>
            <div class="ld-filter-group">
                <label>Base de Origem</label>
                <select id="filtro_base" class="form-select">
                    <option value="">Todas as bases</option>
                </select>
            </div>
            <div class="ld-filter-group">
                <label>&nbsp;</label>
                <button id="limpar_filtros" class="ld-btn-clear">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="1 4 1 10 7 10"/>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                    </svg>
                    Limpar Filtros
                </button>
            </div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="ld-action-bar">
        <div class="ld-selected-info">
            <div class="ld-selected-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 11 12 14 22 4"/>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                <span id="selected-count">0</span> selecionado(s)
            </div>
        </div>
        <button id="enviarSelecionadosBtn" class="ld-btn-preditiva" disabled>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"/>
                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
            Enviar para Preditiva
        </button>
    </div>

    <!-- Table Card -->
    <div class="ld-table-card">
        <div class="ld-table-header">
            <div class="ld-table-title-group">
                <div class="ld-table-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div>
                    <h3 class="ld-table-title">Lista de Leads</h3>
                    <span class="ld-table-subtitle">Selecione os leads para enviar a fila preditiva</span>
                </div>
            </div>
        </div>
        <div class="ld-table-body">
            <div class="table-responsive">
                <table class="table w-100" id="leadsDescartadosTable">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Telefone</th>
                            <th>Valor Plano</th>
                            <th>Categoria</th>
                            <th>Base Origem</th>
                            <th>Data Criacao</th>
                            <th>Ultima Atualizacao</th>
                            <th style="width: 60px;">Acoes</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Comentarios -->
<div class="modal fade ld-modal" id="modalComentarios" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2" style="vertical-align: -4px;">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Historico de Comentarios
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="comentariosContainer">
                <div class="ld-comment-item">
                    <span class="ld-comment-author">Carregando...</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
