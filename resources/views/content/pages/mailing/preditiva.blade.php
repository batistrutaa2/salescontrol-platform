@extends('layouts/layoutMaster')

@section('title', 'Fila Preditiva')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/toastr/toastr.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/scss/pages/dashboard-analytics.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/toastr/toastr.js',
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js'
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/preditiva.js'])
@endsection

@section('page-style')
<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap');

.preditiva-wrapper {
    --pd-primary: #7C3AED;
    --pd-primary-light: #A78BFA;
    --pd-primary-rgb: 124, 58, 237;
    --pd-success: #10B981;
    --pd-success-light: #34D399;
    --pd-success-rgb: 16, 185, 129;
    --pd-info: #06B6D4;
    --pd-info-light: #22D3EE;
    --pd-info-rgb: 6, 182, 212;
    --pd-warning: #F59E0B;
    --pd-warning-light: #FBBF24;
    --pd-warning-rgb: 245, 158, 11;
    --pd-danger: #EF4444;
    --pd-danger-light: #F87171;
    --pd-danger-rgb: 239, 68, 68;

    --pd-card-bg: rgba(255, 255, 255, 0.95);
    --pd-card-border: rgba(255, 255, 255, 0.2);
    --pd-glass-bg: rgba(255, 255, 255, 0.7);
    --pd-glass-blur: 12px;
    --pd-border-radius: 16px;
    --pd-shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
    --pd-shadow-md: 0 4px 20px rgba(0, 0, 0, 0.08);
    --pd-shadow-lg: 0 8px 40px rgba(0, 0, 0, 0.12);
    --pd-shadow-glow: 0 0 40px rgba(124, 58, 237, 0.15);
    --pd-text-primary: #1F2937;
    --pd-text-secondary: #6B7280;
    --pd-text-muted: #9CA3AF;
    --pd-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height: 100vh;
}

.dark-style .preditiva-wrapper {
    --pd-card-bg: rgba(30, 32, 47, 0.95);
    --pd-card-border: rgba(255, 255, 255, 0.08);
    --pd-glass-bg: rgba(30, 32, 47, 0.8);
    --pd-shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.2);
    --pd-shadow-md: 0 4px 20px rgba(0, 0, 0, 0.3);
    --pd-shadow-lg: 0 8px 40px rgba(0, 0, 0, 0.4);
    --pd-shadow-glow: 0 0 60px rgba(124, 58, 237, 0.25);
    --pd-text-primary: #F9FAFB;
    --pd-text-secondary: #D1D5DB;
    --pd-text-muted: #9CA3AF;
}

/* Alert Messages */
.pd-alert {
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    animation: fadeInDown 0.4s ease-out;
}

.pd-alert-success {
    background: linear-gradient(135deg, rgba(var(--pd-success-rgb), 0.15), rgba(var(--pd-success-rgb), 0.05));
    border: 1px solid rgba(var(--pd-success-rgb), 0.3);
    color: var(--pd-success);
}

.pd-alert-error {
    background: linear-gradient(135deg, rgba(var(--pd-danger-rgb), 0.15), rgba(var(--pd-danger-rgb), 0.05));
    border: 1px solid rgba(var(--pd-danger-rgb), 0.3);
    color: var(--pd-danger);
}

.pd-alert svg {
    width: 22px;
    height: 22px;
    flex-shrink: 0;
}

/* Header Section */
.pd-header {
    margin-bottom: 2rem;
    animation: fadeInDown 0.6s ease-out;
}

.pd-header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.pd-header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.pd-header-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, var(--pd-primary), var(--pd-primary-light));
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(var(--pd-primary-rgb), 0.3);
}

.pd-header-icon svg {
    width: 28px;
    height: 28px;
    color: white;
}

.pd-header-text h1 {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--pd-text-primary);
    margin: 0;
    letter-spacing: -0.02em;
}

.pd-header-text p {
    font-size: 0.9rem;
    color: var(--pd-text-secondary);
    margin: 0.25rem 0 0;
}

.pd-btn-import {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, var(--pd-success), var(--pd-success-light));
    border: none;
    border-radius: 12px;
    color: white;
    font-size: 0.9rem;
    font-weight: 700;
    text-decoration: none;
    transition: var(--pd-transition);
    box-shadow: 0 4px 16px rgba(var(--pd-success-rgb), 0.3);
}

.pd-btn-import:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(var(--pd-success-rgb), 0.4);
    color: white;
}

.pd-btn-config {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .5rem .9rem;
    border-radius: 10px;
    font-size: .8rem;
    font-weight: 600;
    background: rgba(124, 58, 237, 0.08);
    color: var(--pd-primary);
    border: 1px solid rgba(124, 58, 237, 0.2);
    cursor: pointer;
    transition: var(--pd-transition);
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.pd-btn-config:hover {
    background: var(--pd-primary);
    color: #fff;
    border-color: transparent;
    transform: translateY(-1px);
}
.dark-style .pd-btn-config {
    background: rgba(167, 139, 250, 0.1);
    border-color: rgba(167, 139, 250, 0.25);
    color: var(--pd-primary-light);
}
.dark-style .pd-btn-config:hover {
    background: var(--pd-primary);
    color: #fff;
}

.badge-tab-hard {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    background: rgba(239, 68, 68, 0.1);
    color: #DC2626;
    border: 1px solid rgba(239, 68, 68, 0.25);
    border-radius: 6px;
    padding: .25rem .6rem;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .02em;
}
.dark-style .badge-tab-hard {
    background: rgba(248, 113, 113, 0.12);
    color: #F87171;
    border-color: rgba(248, 113, 113, 0.25);
}
.btn-remove-tab-hard {
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    color: inherit;
    opacity: .6;
    display: flex;
    align-items: center;
}
.btn-remove-tab-hard:hover { opacity: 1; }

.pd-btn-import svg {
    width: 18px;
    height: 18px;
}

/* KPI Grid */
.pd-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}

@media (max-width: 1200px) {
    .pd-kpi-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 576px) {
    .pd-kpi-grid { grid-template-columns: 1fr; }
}

.pd-kpi-card {
    background: var(--pd-card-bg);
    backdrop-filter: blur(var(--pd-glass-blur));
    border: 1px solid var(--pd-card-border);
    border-radius: var(--pd-border-radius);
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    transition: var(--pd-transition);
    animation: fadeInUp 0.5s ease-out backwards;
}

.pd-kpi-card:nth-child(1) { animation-delay: 0ms; }
.pd-kpi-card:nth-child(2) { animation-delay: 80ms; }
.pd-kpi-card:nth-child(3) { animation-delay: 160ms; }
.pd-kpi-card:nth-child(4) { animation-delay: 240ms; }

.pd-kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--pd-shadow-lg);
}

.pd-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    border-radius: var(--pd-border-radius) var(--pd-border-radius) 0 0;
}

.pd-kpi-card.kpi-primary::before { background: linear-gradient(90deg, var(--pd-primary), var(--pd-primary-light)); }
.pd-kpi-card.kpi-info::before { background: linear-gradient(90deg, var(--pd-info), var(--pd-info-light)); }
.pd-kpi-card.kpi-success::before { background: linear-gradient(90deg, var(--pd-success), var(--pd-success-light)); }
.pd-kpi-card.kpi-danger::before { background: linear-gradient(90deg, var(--pd-danger), var(--pd-danger-light)); }

.pd-kpi-card .kpi-glow {
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

.pd-kpi-card:hover .kpi-glow { opacity: 0.15; }

.pd-kpi-card.kpi-primary .kpi-glow { background: var(--pd-primary); }
.pd-kpi-card.kpi-info .kpi-glow { background: var(--pd-info); }
.pd-kpi-card.kpi-success .kpi-glow { background: var(--pd-success); }
.pd-kpi-card.kpi-danger .kpi-glow { background: var(--pd-danger); }

.pd-kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.pd-kpi-card.kpi-primary .pd-kpi-icon { background: linear-gradient(135deg, var(--pd-primary), var(--pd-primary-light)); }
.pd-kpi-card.kpi-info .pd-kpi-icon { background: linear-gradient(135deg, var(--pd-info), var(--pd-info-light)); }
.pd-kpi-card.kpi-success .pd-kpi-icon { background: linear-gradient(135deg, var(--pd-success), var(--pd-success-light)); }
.pd-kpi-card.kpi-danger .pd-kpi-icon { background: linear-gradient(135deg, var(--pd-danger), var(--pd-danger-light)); }

.pd-kpi-icon svg {
    width: 24px;
    height: 24px;
    color: white;
}

.pd-kpi-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--pd-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.pd-kpi-value {
    font-family: 'JetBrains Mono', monospace;
    font-size: 2rem;
    font-weight: 700;
    color: var(--pd-text-primary);
    line-height: 1;
    margin: 0;
    transition: var(--pd-transition);
}

.pd-kpi-value.updated {
    animation: pulse 0.5s ease;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* Filter Panel */
.pd-filter-panel {
    background: var(--pd-card-bg);
    backdrop-filter: blur(var(--pd-glass-blur));
    border: 1px solid var(--pd-card-border);
    border-radius: var(--pd-border-radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    animation: fadeInUp 0.5s ease-out 0.3s backwards;
}

.pd-filter-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}

.pd-filter-header svg {
    width: 20px;
    height: 20px;
    color: var(--pd-primary);
}

.pd-filter-header h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--pd-text-primary);
    margin: 0;
}

.pd-filter-header span {
    font-size: 0.85rem;
    color: var(--pd-text-muted);
    margin-left: auto;
}

.pd-filter-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 1rem;
    align-items: end;
}

@media (max-width: 992px) {
    .pd-filter-grid { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 576px) {
    .pd-filter-grid { grid-template-columns: 1fr; }
}

.pd-filter-group label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--pd-text-secondary);
    margin-bottom: 0.5rem;
}

.pd-filter-group .form-select,
.pd-filter-group .form-control {
    border-radius: 10px;
    border: 1px solid var(--pd-card-border);
    background: var(--pd-glass-bg);
    font-size: 0.9rem;
    padding: 0.625rem 1rem;
    transition: var(--pd-transition);
}

.pd-filter-group .form-select:focus,
.pd-filter-group .form-control:focus {
    border-color: var(--pd-primary);
    box-shadow: 0 0 0 3px rgba(var(--pd-primary-rgb), 0.15);
}

/* Action Bar */
.pd-action-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 1.25rem;
}

.pd-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--pd-transition);
    border: none;
}

.pd-btn svg {
    width: 16px;
    height: 16px;
}

.pd-btn-primary {
    background: linear-gradient(135deg, var(--pd-primary), var(--pd-primary-light));
    color: white;
    box-shadow: 0 4px 12px rgba(var(--pd-primary-rgb), 0.25);
}

.pd-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(var(--pd-primary-rgb), 0.35);
}

.pd-btn-outline {
    background: transparent;
    border: 1px solid var(--pd-card-border);
    color: var(--pd-text-secondary);
}

.pd-btn-outline:hover {
    background: var(--pd-glass-bg);
    color: var(--pd-text-primary);
    border-color: var(--pd-primary);
}

.pd-btn-warning {
    background: linear-gradient(135deg, var(--pd-warning), var(--pd-warning-light));
    color: white;
    box-shadow: 0 4px 12px rgba(var(--pd-warning-rgb), 0.25);
}

.pd-btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(var(--pd-warning-rgb), 0.35);
}

.pd-btn-danger {
    background: linear-gradient(135deg, var(--pd-danger), var(--pd-danger-light));
    color: white;
    box-shadow: 0 4px 12px rgba(var(--pd-danger-rgb), 0.25);
}

.pd-btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(var(--pd-danger-rgb), 0.35);
}

.pd-action-spacer {
    flex: 1;
}

/* Selected Badge */
.pd-selected-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, rgba(var(--pd-primary-rgb), 0.1), rgba(var(--pd-primary-rgb), 0.05));
    border: 1px solid rgba(var(--pd-primary-rgb), 0.2);
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--pd-primary);
}

.pd-selected-badge span {
    font-family: 'JetBrains Mono', monospace;
    font-weight: 700;
}

/* Table Card */
.pd-table-card {
    background: var(--pd-card-bg);
    backdrop-filter: blur(var(--pd-glass-blur));
    border: 1px solid var(--pd-card-border);
    border-radius: var(--pd-border-radius);
    overflow: hidden;
    animation: fadeInUp 0.5s ease-out 0.4s backwards;
}

.pd-table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--pd-card-border);
}

.pd-table-title-group {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.pd-table-icon {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, var(--pd-primary), var(--pd-primary-light));
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pd-table-icon svg {
    width: 20px;
    height: 20px;
    color: white;
}

.pd-table-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--pd-text-primary);
    margin: 0;
}

.pd-table-subtitle {
    font-size: 0.8rem;
    color: var(--pd-text-muted);
}

.pd-table-body {
    padding: 1rem 1.5rem 1.5rem;
}

/* DataTable Overrides */
.pd-table-body .table {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.875rem;
}

.pd-table-body .table thead th {
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--pd-text-muted);
    border-bottom: 2px solid var(--pd-card-border);
    padding: 1rem 0.75rem;
    background: transparent;
}

.pd-table-body .table tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--pd-card-border);
    color: var(--pd-text-primary);
}

.pd-table-body .table tbody tr:hover {
    background: rgba(var(--pd-primary-rgb), 0.03);
}

.dark-style .pd-table-body .table tbody tr:hover {
    background: rgba(255, 255, 255, 0.02);
}

.pd-table-body .table tbody tr.selected {
    background: rgba(var(--pd-primary-rgb), 0.08) !important;
}

.pd-table-body .form-check-input {
    width: 18px;
    height: 18px;
    border-radius: 5px;
    cursor: pointer;
}

.pd-table-body .form-check-input:checked {
    background-color: var(--pd-primary);
    border-color: var(--pd-primary);
}

.pd-valor-plano {
    font-family: 'JetBrains Mono', monospace;
    font-weight: 600;
    color: var(--pd-success);
}

.pd-tentativas-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.8rem;
    font-weight: 700;
}

.pd-tentativas-0 {
    background: rgba(var(--pd-success-rgb), 0.1);
    color: var(--pd-success);
}

.pd-tentativas-low {
    background: rgba(var(--pd-info-rgb), 0.1);
    color: var(--pd-info);
}

.pd-tentativas-medium {
    background: rgba(var(--pd-warning-rgb), 0.1);
    color: var(--pd-warning);
}

.pd-tentativas-high {
    background: rgba(var(--pd-danger-rgb), 0.1);
    color: var(--pd-danger);
}

.pd-tabulacao-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: rgba(var(--pd-info-rgb), 0.1);
    color: var(--pd-info);
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.pd-text-muted {
    color: var(--pd-text-muted);
    font-style: italic;
}

/* Dropdown Actions */
.pd-dropdown-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid var(--pd-card-border);
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--pd-transition);
}

.pd-dropdown-btn:hover {
    background: var(--pd-glass-bg);
    border-color: var(--pd-primary);
}

.pd-dropdown-btn svg {
    width: 18px;
    height: 18px;
    color: var(--pd-text-secondary);
}

.dropdown-menu {
    border-radius: 12px;
    border: 1px solid var(--pd-card-border);
    box-shadow: var(--pd-shadow-lg);
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
    transition: var(--pd-transition);
}

.dropdown-item svg {
    width: 16px;
    height: 16px;
    opacity: 0.7;
}

.dropdown-item:hover {
    background: rgba(var(--pd-primary-rgb), 0.1);
    color: var(--pd-primary);
}

.dropdown-item.text-danger:hover {
    background: rgba(var(--pd-danger-rgb), 0.1);
    color: var(--pd-danger);
}

.dropdown-divider {
    margin: 0.5rem 0;
    border-color: var(--pd-card-border);
}

/* Modal */
.pd-modal .modal-content {
    border-radius: var(--pd-border-radius);
    border: 1px solid rgba(124, 58, 237, 0.2);
    background: #ffffff;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.dark-style .pd-modal .modal-content {
    background: #1e202f;
    border-color: rgba(255, 255, 255, 0.1);
}

.pd-modal .modal-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    padding: 1.25rem 1.5rem;
    background: #fafafa;
    border-radius: var(--pd-border-radius) var(--pd-border-radius) 0 0;
}

.dark-style .pd-modal .modal-header {
    background: #252736;
    border-bottom-color: rgba(255, 255, 255, 0.06);
}

.pd-modal .modal-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1F2937;
    display: flex;
    align-items: center;
}

.dark-style .pd-modal .modal-title {
    color: #F9FAFB;
}

.pd-modal .modal-body {
    padding: 1.5rem;
    background: #ffffff;
}

.dark-style .pd-modal .modal-body {
    background: #1e202f;
}

.pd-modal .form-label {
    font-weight: 600;
    color: #6B7280;
    font-size: 0.875rem;
}

.dark-style .pd-modal .form-label {
    color: #D1D5DB;
}

.pd-modal .form-select,
.pd-modal .form-control {
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    padding: 0.625rem 1rem;
    background-color: #ffffff;
    color: #1F2937;
    font-size: 0.9rem;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

.dark-style .pd-modal .form-select,
.dark-style .pd-modal .form-control {
    background-color: #252736;
    border-color: rgba(255, 255, 255, 0.1);
    color: #F9FAFB;
}

.pd-modal .form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
    padding-right: 2.5rem;
}

.dark-style .pd-modal .form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%239CA3AF' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
}

.pd-modal .form-select:focus,
.pd-modal .form-control:focus {
    border-color: var(--pd-primary);
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
    outline: none;
}

.pd-modal .form-select:disabled {
    background-color: #f3f4f6;
    color: #9CA3AF;
    cursor: not-allowed;
}

.dark-style .pd-modal .form-select:disabled {
    background-color: #1a1b26;
    color: #6B7280;
}

/* DataTables Pagination */
.dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius: 8px !important;
    margin: 0 2px;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: linear-gradient(135deg, var(--pd-primary), var(--pd-primary-light)) !important;
    border-color: transparent !important;
    color: white !important;
}

.dataTables_wrapper .dataTables_filter input {
    border-radius: 10px;
    border: 1px solid var(--pd-card-border);
    padding: 0.5rem 1rem;
}

.dataTables_wrapper .dataTables_length select {
    border-radius: 10px;
    border: 1px solid var(--pd-card-border);
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

/* Rules Panel */
.pd-rules-list {
    margin-top: 1rem;
}

.pd-rules-empty {
    text-align: center;
    padding: 3rem 1.5rem;
    color: var(--pd-text-muted);
}

.pd-rules-empty svg {
    margin-bottom: 1rem;
    opacity: 0.5;
}

.pd-rules-empty p {
    font-size: 1rem;
    font-weight: 600;
    color: var(--pd-text-secondary);
    margin-bottom: 0.25rem;
}

.pd-rules-empty span {
    font-size: 0.85rem;
}

.pd-rule-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: var(--pd-glass-bg);
    border: 1px solid var(--pd-card-border);
    border-radius: 12px;
    margin-bottom: 0.75rem;
    transition: var(--pd-transition);
    cursor: grab;
}

.pd-rule-item:hover {
    border-color: rgba(var(--pd-primary-rgb), 0.3);
    background: rgba(var(--pd-primary-rgb), 0.03);
}

.pd-rule-item.dragging {
    opacity: 0.5;
    cursor: grabbing;
}

.pd-rule-drag {
    color: var(--pd-text-muted);
    cursor: grab;
}

.pd-rule-drag svg {
    width: 18px;
    height: 18px;
}

.pd-rule-status {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.pd-rule-status.active {
    background: var(--pd-success);
    box-shadow: 0 0 8px rgba(var(--pd-success-rgb), 0.5);
}

.pd-rule-status.inactive {
    background: var(--pd-text-muted);
}

.pd-rule-info {
    flex: 1;
    min-width: 0;
}

.pd-rule-name {
    font-weight: 700;
    color: var(--pd-text-primary);
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
}

.pd-rule-condition {
    font-size: 0.8rem;
    color: var(--pd-text-muted);
    font-family: 'JetBrains Mono', monospace;
}

.pd-rule-peso {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.75rem;
    background: linear-gradient(135deg, rgba(var(--pd-primary-rgb), 0.1), rgba(var(--pd-primary-rgb), 0.05));
    border: 1px solid rgba(var(--pd-primary-rgb), 0.2);
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--pd-primary);
}

.pd-rule-peso span {
    font-family: 'JetBrains Mono', monospace;
}

.pd-rule-actions {
    display: flex;
    gap: 0.5rem;
}

.pd-rule-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid var(--pd-card-border);
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--pd-transition);
}

.pd-rule-btn svg {
    width: 16px;
    height: 16px;
    color: var(--pd-text-secondary);
}

.pd-rule-btn:hover {
    background: var(--pd-glass-bg);
}

.pd-rule-btn.btn-toggle:hover {
    border-color: var(--pd-warning);
}

.pd-rule-btn.btn-toggle:hover svg {
    color: var(--pd-warning);
}

.pd-rule-btn.btn-edit:hover {
    border-color: var(--pd-primary);
}

.pd-rule-btn.btn-edit:hover svg {
    color: var(--pd-primary);
}

.pd-rule-btn.btn-delete:hover {
    border-color: var(--pd-danger);
    background: rgba(var(--pd-danger-rgb), 0.1);
}

.pd-rule-btn.btn-delete:hover svg {
    color: var(--pd-danger);
}

/* Form Elements in Modal */
.pd-form-group {
    margin-bottom: 1.25rem;
}

.pd-form-group label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #4B5563;
    margin-bottom: 0.5rem;
    letter-spacing: 0.01em;
}

.dark-style .pd-form-group label {
    color: #D1D5DB;
}

.pd-form-group .form-control,
.pd-form-group .form-select {
    width: 100%;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    background-color: #ffffff;
    color: #1F2937;
    font-size: 0.9rem;
    padding: 0.75rem 1rem;
    transition: all 0.2s ease;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

.dark-style .pd-form-group .form-control,
.dark-style .pd-form-group .form-select {
    background-color: #252736;
    border-color: rgba(255, 255, 255, 0.1);
    color: #F9FAFB;
}

.pd-form-group .form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
    padding-right: 2.5rem;
}

.dark-style .pd-form-group .form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%239CA3AF' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
}

.pd-form-group .form-control:focus,
.pd-form-group .form-select:focus {
    border-color: var(--pd-primary);
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
    outline: none;
}

.pd-form-group .form-select:disabled {
    background-color: #f9fafb;
    color: #9CA3AF;
    cursor: not-allowed;
    opacity: 0.7;
}

.dark-style .pd-form-group .form-select:disabled {
    background-color: #1a1b26;
    color: #6B7280;
}

.pd-form-group .form-control::placeholder {
    color: #9CA3AF;
}

.dark-style .pd-form-group .form-control::placeholder {
    color: #6B7280;
}

.pd-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.pd-peso-slider {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem 0;
}

.pd-peso-slider input[type="range"] {
    flex: 1;
    height: 8px;
    border-radius: 4px;
    background: linear-gradient(to right, var(--pd-primary) 0%, #e5e7eb 0%);
    -webkit-appearance: none;
    appearance: none;
    cursor: pointer;
}

.dark-style .pd-peso-slider input[type="range"] {
    background: linear-gradient(to right, var(--pd-primary) 0%, #374151 0%);
}

.pd-peso-slider input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: var(--pd-primary);
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(124, 58, 237, 0.4);
    border: 3px solid #ffffff;
    transition: transform 0.15s ease;
}

.dark-style .pd-peso-slider input[type="range"]::-webkit-slider-thumb {
    border-color: #1e202f;
}

.pd-peso-slider input[type="range"]::-webkit-slider-thumb:hover {
    transform: scale(1.1);
}

.pd-peso-slider input[type="range"]::-moz-range-thumb {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: var(--pd-primary);
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(124, 58, 237, 0.4);
    border: 3px solid #ffffff;
}

.pd-peso-value {
    font-family: 'JetBrains Mono', monospace;
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--pd-primary);
    min-width: 48px;
    text-align: center;
    background: linear-gradient(135deg, rgba(124, 58, 237, 0.1), rgba(124, 58, 237, 0.05));
    padding: 0.375rem 0.75rem;
    border-radius: 8px;
    border: 1px solid rgba(124, 58, 237, 0.2);
}

.pd-modal-footer {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.75rem;
    padding-top: 1.25rem;
    border-top: 1px solid #e5e7eb;
}

.dark-style .pd-modal-footer {
    border-top-color: rgba(255, 255, 255, 0.06);
}

.pd-modal-footer .pd-btn {
    flex: 1;
    justify-content: center;
    padding: 0.75rem 1.25rem;
}

/* Botoes do Modal - garantir cores solidas */
.pd-modal .pd-btn-outline {
    background: #f3f4f6 !important;
    border: 1px solid #d1d5db !important;
    color: #4B5563 !important;
}

.dark-style .pd-modal .pd-btn-outline {
    background: #374151 !important;
    border-color: #4B5563 !important;
    color: #E5E7EB !important;
}

.pd-modal .pd-btn-outline:hover {
    background: #e5e7eb !important;
    border-color: var(--pd-primary) !important;
    color: var(--pd-primary) !important;
}

.dark-style .pd-modal .pd-btn-outline:hover {
    background: #4B5563 !important;
}

.pd-modal .pd-btn-primary {
    background: linear-gradient(135deg, #7C3AED, #A78BFA) !important;
    border: none !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.35);
}

.pd-modal .pd-btn-primary:hover {
    background: linear-gradient(135deg, #6D28D9, #8B5CF6) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(124, 58, 237, 0.45);
}

.pd-valor-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.pd-valor-suggestion {
    padding: 0.375rem 0.875rem;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    font-size: 0.8rem;
    color: #4B5563;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 500;
}

.dark-style .pd-valor-suggestion {
    background: #252736;
    border-color: rgba(255, 255, 255, 0.1);
    color: #D1D5DB;
}

.pd-valor-suggestion:hover {
    border-color: var(--pd-primary);
    color: var(--pd-primary);
    background: rgba(124, 58, 237, 0.08);
    transform: translateY(-1px);
}

.dark-style .pd-valor-suggestion:hover {
    background: rgba(124, 58, 237, 0.15);
}

/* Small helper text */
.pd-modal small.text-muted {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: #9CA3AF;
}

.dark-style .pd-modal small.text-muted {
    color: #6B7280;
}

/* Modal close button */
.pd-modal .btn-close {
    opacity: 0.5;
    transition: opacity 0.2s ease;
}

.pd-modal .btn-close:hover {
    opacity: 1;
}

.dark-style .pd-modal .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}

/* Modal backdrop override */
.modal-backdrop.show {
    opacity: 0.6;
}

/* Ensure modal is above everything */
.pd-modal {
    z-index: 1055;
}
</style>
@endsection

@section('content')
<div class="preditiva-wrapper">

    @if (session('status') == 'success')
        <div class="pd-alert pd-alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            {{ session('message') }}
        </div>
    @elseif(session('status') == 'error')
        <div class="pd-alert pd-alert-error">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            {{ session('message') }}
        </div>
    @endif

    <!-- Header -->
    <div class="pd-header">
        <div class="pd-header-content">
            <div class="pd-header-left">
                <div class="pd-header-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                </div>
                <div class="pd-header-text">
                    <h1>Fila Preditiva</h1>
                    <p>Gerencie a fila de leads para discagem automatica</p>
                </div>
            </div>
            <div style="display:flex;gap:.6rem;align-items:center;">
                <button type="button" class="pd-btn-config" id="btnAbrirConfiguracoes" title="Configuracoes da Preditiva">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2"/></svg>
                    Configuracoes
                </button>
                <a href="{{ route('preditiva.importar') }}" class="pd-btn-import">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    Importar Leads
                </a>
            </div>
        </div>
    </div>

    {{-- Modal: Configuracoes da Preditiva --}}
    <div class="modal fade" id="modalConfiguracoesPreditiva" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px;overflow:hidden;">
                <div class="modal-header" style="background:linear-gradient(135deg,#7C3AED,#A78BFA);padding:1.25rem 1.5rem;">
                    <div style="display:flex;align-items:center;gap:.75rem;">
                        <div style="width:36px;height:36px;background:rgba(255,255,255,.2);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
                        </div>
                        <h5 class="modal-title mb-0" style="color:#fff;font-weight:700;font-size:.95rem;">Configuracoes da Preditiva</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:1.5rem;">

                    {{-- Secao: Parametros gerais --}}
                    <p class="text-muted mb-3" style="font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;">Parametros Gerais</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.82rem;font-weight:600;">Cooldown entre tentativas (horas)
                                <span class="text-muted fw-normal">— 0 = sem espera</span>
                            </label>
                            <input type="number" id="cfgCooldownHoras" class="form-control form-control-sm"
                                min="0" max="168" value="{{ $config->cooldown_horas ?? 0 }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.82rem;font-weight:600;">Exclusao por vendedor (dias)
                                <span class="text-muted fw-normal">— vazio = permanente</span>
                            </label>
                            <input type="number" id="cfgExclusaoDias" class="form-control form-control-sm"
                                min="1" max="3650" placeholder="Permanente"
                                value="{{ $config->exclusao_usuario_dias ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.82rem;font-weight:600;">Limite de descartes SOFT</label>
                            <input type="number" id="cfgLimiteSoft" class="form-control form-control-sm"
                                min="1" max="50" value="{{ $config->limite_descartes_soft ?? 5 }}">
                            <div class="form-text">Lead sem retorno — maximo de tentativas antes de sair da fila.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.82rem;font-weight:600;">Limite de descartes HARD</label>
                            <input type="number" id="cfgLimiteHard" class="form-control form-control-sm"
                                min="1" max="10" value="{{ $config->limite_descartes_hard ?? 1 }}">
                            <div class="form-text">Descarte definitivo — recomendado: 1 (remove na primeira negativa).</div>
                        </div>
                    </div>

                    <hr style="border-color:rgba(0,0,0,.06);margin:1.25rem 0;">

                    {{-- Secao: Tabulacoes HARD --}}
                    <p class="text-muted mb-1" style="font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;">Tabulacoes de Descarte Definitivo (HARD)</p>
                    <p style="font-size:.8rem;color:#6B7280;margin-bottom:1rem;">Quando o cliente <strong>atende</strong> e da uma negativa definitiva. Um unico descarte com essas tabulacoes remove o lead imediatamente.</p>

                    <div class="d-flex gap-2 mb-3">
                        <div style="flex:1;">
                            <select id="inputNovaTabHard" class="form-select form-select-sm select2-tab-hard" style="width:100%;">
                                <option value=""></option>
                            </select>
                        </div>
                        <button class="btn btn-primary btn-sm px-3 flex-shrink-0" type="button" id="btnAdicionarTabHard">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Adicionar
                        </button>
                    </div>

                    <div id="listaTabsHard" style="display:flex;flex-wrap:wrap;gap:.4rem;min-height:40px;">
                        @foreach($tabsHard as $tab)
                        <span class="badge-tab-hard" data-tab="{{ $tab }}">
                            {{ $tab }}
                            <button type="button" class="btn-remove-tab-hard" data-tab="{{ $tab }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </span>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(0,0,0,.06);">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btnSalvarConfiguracoes">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Salvar Configuracoes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Grid -->
    <div class="pd-kpi-grid">
        <!-- Leads na Fila -->
        <div class="pd-kpi-card kpi-primary">
            <div class="pd-kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <p class="pd-kpi-label">Leads na Fila</p>
            <h2 class="pd-kpi-value" id="total-leads">--</h2>
            <div class="kpi-glow"></div>
        </div>

        <!-- Tentativas Hoje -->
        <div class="pd-kpi-card kpi-info">
            <div class="pd-kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/>
                    <polyline points="15 2 15 8 21 8"/>
                    <line x1="21" y1="2" x2="15" y2="8"/>
                </svg>
            </div>
            <p class="pd-kpi-label">Tentativas Hoje</p>
            <h2 class="pd-kpi-value" id="tentativas-hoje">--</h2>
            <div class="kpi-glow"></div>
        </div>

        <!-- Conversoes -->
        <div class="pd-kpi-card kpi-success">
            <div class="pd-kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <p class="pd-kpi-label">Conversoes Hoje</p>
            <h2 class="pd-kpi-value" id="conversoes-hoje">--</h2>
            <div class="kpi-glow"></div>
        </div>

        <!-- Recusas -->
        <div class="pd-kpi-card kpi-danger">
            <div class="pd-kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
            </div>
            <p class="pd-kpi-label">Recusas Hoje</p>
            <h2 class="pd-kpi-value" id="recusas-hoje">--</h2>
            <div class="kpi-glow"></div>
        </div>
    </div>

    <!-- Filter Panel -->
    <div class="pd-filter-panel">
        <div class="pd-filter-header">
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
            <h3>Filtros</h3>
            <span>Busque por nome, tabulacao ou tentativas</span>
        </div>
        <div class="pd-filter-grid">
            <div class="pd-filter-group">
                <label>Nome do Cliente</label>
                <input type="text" class="form-control" id="filtroNomeCliente" placeholder="Buscar por nome...">
            </div>
            <div class="pd-filter-group">
                <label>Ultima Tabulacao</label>
                <select class="form-select" id="filtroUltimaTabulacao">
                    <option value="">Todas</option>
                </select>
            </div>
            <div class="pd-filter-group">
                <label>Tentativas</label>
                <select class="form-select" id="filtroTentativas">
                    <option value="">Todas</option>
                    <option value="0">0 tentativas</option>
                    <option value="1">1 tentativa</option>
                    <option value="2">2 tentativas</option>
                    <option value="3">3 tentativas</option>
                    <option value="4">4 tentativas</option>
                    <option value="5+">5 ou mais</option>
                </select>
            </div>
        </div>

        <div class="pd-action-bar">
            <button id="btnAplicarFiltros" class="pd-btn pd-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                </svg>
                Aplicar Filtros
            </button>
            <button id="btnLimparFiltros" class="pd-btn pd-btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="1 4 1 10 7 10"/>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                </svg>
                Limpar
            </button>

            <div class="pd-action-spacer"></div>

            <div class="pd-selected-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 11 12 14 22 4"/>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                <span id="selected-count">0</span> selecionado(s)
            </div>

            <button id="btnLimparLogsLeads" class="pd-btn pd-btn-warning">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="1 4 1 10 7 10"/>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                </svg>
                Reiniciar Tentativas
            </button>
            <button id="btnLimparFila" class="pd-btn pd-btn-danger">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"/>
                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                </svg>
                Limpar Fila
            </button>
        </div>
    </div>

    <!-- Table Card -->
    <div class="pd-table-card">
        <div class="pd-table-header">
            <div class="pd-table-title-group">
                <div class="pd-table-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <line x1="3" y1="9" x2="21" y2="9"/>
                        <line x1="9" y1="21" x2="9" y2="9"/>
                    </svg>
                </div>
                <div>
                    <h3 class="pd-table-title">Leads na Fila</h3>
                    <span class="pd-table-subtitle">Selecione para acoes em lote</span>
                </div>
            </div>
        </div>
        <div class="pd-table-body">
            <div class="table-responsive">
                <table id="tabela-fila-preditiva" class="table w-100">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" class="form-check-input" id="select-all-leads">
                            </th>
                            <th>Cliente</th>
                            <th style="width: 140px;">Telefone</th>
                            <th style="width: 140px;">Valor Plano</th>
                            <th style="width: 100px;">Tentativas</th>
                            <th style="width: 180px;">Ultima Tabulacao</th>
                            <th style="width: 60px;">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Rules Panel -->
    <div class="pd-rules-panel mt-4">
        <div class="pd-filter-panel">
            <div class="pd-filter-header">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                    <polyline points="2 17 12 22 22 17"/>
                    <polyline points="2 12 12 17 22 12"/>
                </svg>
                <h3>Regras de Priorizacao</h3>
                <span>Configure quais leads devem ser priorizados na fila</span>
                <button id="btnNovaRegra" class="pd-btn pd-btn-primary ms-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Nova Regra
                </button>
            </div>

            <div id="regras-lista" class="pd-rules-list">
                <div class="pd-rules-empty" id="regras-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="12" y1="18" x2="12" y2="12"/>
                        <line x1="9" y1="15" x2="15" y2="15"/>
                    </svg>
                    <p>Nenhuma regra configurada</p>
                    <span>Crie regras para priorizar leads especificos na fila</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Transferir Lead -->
<div class="modal fade pd-modal" id="modalTransferirLead" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2" style="vertical-align: -4px;">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <polyline points="17 11 19 13 23 9"/>
                    </svg>
                    Transferir Lead
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formTransferenciaLead" action="{{ route('comercial.transferContact') }}" method="POST">
                    @csrf
                    <input type="hidden" name="idMailing" id="idMailing">
                    <div class="mb-3">
                        <label for="vendedorDestino" class="form-label">Selecionar Vendedor</label>
                        <select class="form-select" name="user_id" id="vendedorDestino" required>
                            <option value="">Selecione um vendedor</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Selecionar Tabulacao</label>
                        <select class="form-select" name="tabulation_id" required>
                            <option value="">Selecione uma tabulacao</option>
                            @foreach ($tabulacoes as $tabulacao)
                                <option value="{{ $tabulacao->id }}">{{ $tabulacao->descricao }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="pd-btn pd-btn-primary w-100">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 11 12 14 22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                        Confirmar Transferencia
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Regra de Priorizacao -->
<div class="modal fade pd-modal" id="modalRegra" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRegraTitle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2" style="vertical-align: -4px;">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                        <polyline points="2 17 12 22 22 17"/>
                        <polyline points="2 12 12 17 22 12"/>
                    </svg>
                    Nova Regra de Priorizacao
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formRegra">
                    <input type="hidden" id="regraId" value="">

                    <div class="pd-form-group">
                        <label for="regraNome">Nome da Regra</label>
                        <input type="text" class="form-control" id="regraNome" placeholder="Ex: Priorizar Asprofili" required>
                    </div>

                    <div class="pd-form-group">
                        <label for="regraDescricao">Descricao (opcional)</label>
                        <input type="text" class="form-control" id="regraDescricao" placeholder="Breve descricao da regra">
                    </div>

                    <div class="pd-form-row">
                        <div class="pd-form-group">
                            <label for="regraCampo">Campo</label>
                            <select class="form-select" id="regraCampo" required>
                                <option value="">Selecione o campo</option>
                            </select>
                        </div>
                        <div class="pd-form-group">
                            <label for="regraOperador">Operador</label>
                            <select class="form-select" id="regraOperador" required disabled>
                                <option value="">Selecione o operador</option>
                            </select>
                        </div>
                    </div>

                    <div class="pd-form-group">
                        <label for="regraValor">Valor</label>
                        <input type="text" class="form-control" id="regraValor" placeholder="Valor para comparacao" required>
                        <div id="valorSuggestions" class="pd-valor-suggestions"></div>
                    </div>

                    <div class="pd-form-group">
                        <label>Peso (Prioridade)</label>
                        <div class="pd-peso-slider">
                            <input type="range" id="regraPeso" min="1" max="100" value="50">
                            <span class="pd-peso-value" id="pesoValue">50</span>
                        </div>
                        <small class="text-muted">Quanto maior o peso, maior a prioridade do lead na fila</small>
                    </div>

                    <div class="pd-modal-footer">
                        <button type="button" class="pd-btn pd-btn-outline" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="pd-btn pd-btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 11 12 14 22 4"/>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                            </svg>
                            <span id="btnSalvarRegraText">Salvar Regra</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
