@extends('layouts/layoutMaster')

@section('title', 'Pagamentos de Comissao')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/toastr/toastr.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/scss/pages/comissionamento-pagamentos.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/toastr/toastr.js',
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js'
    ])
@endsection

@section('page-script')
    @vite('resources/assets/js/comissionamento-pagamentos-index.js')
@endsection

@section('content')
<div class="comissionamento-pagamentos-wrapper">
    {{-- Data Container --}}
    <div id="pgmts-root"
        data-url="{{ route('comissionamento.pagamentos.data') }}"
        data-pdf-base="{{ route('comissionamento.pagamento.pdf', ['pagamento' => 'PAYMENT_ID']) }}"
        data-estornar-url="{{ $podeGerenciarPagamentos ? route('comissionamento.pagamentos.estornar', ['id' => 'PAYMENT_ID']) : '' }}"
        data-contas-base="{{ route('contas.byUser') }}?user_id=USER_ID"
        data-pagar-base="{{ $podeGerenciarPagamentos ? route('comissao.pagar', ['id' => 'PAYMENT_ID']) : '' }}"
        data-can-manage-payments="{{ $podeGerenciarPagamentos ? '1' : '0' }}">
    </div>

    {{-- Header Section --}}
    <div class="cp-header">
        <div class="header-content">
            <div class="header-text">
                <span class="greeting-label">Comissionamento</span>
                <h1 class="main-title">Pagamentos de Comissao</h1>
                <p class="subtitle">Acompanhe e gerencie os pagamentos de comissoes dos vendedores</p>
            </div>
        </div>
    </div>

    {{-- KPI Cards Section --}}
    <div class="kpi-grid">
        {{-- Total Bruto --}}
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total Bruto</span>
                <h2 class="kpi-value" id="total-bruto">R$ 0,00</h2>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Total Imposto --}}
        <div class="kpi-card kpi-warning">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="5" x2="5" y2="19"/>
                        <circle cx="6.5" cy="6.5" r="2.5"/>
                        <circle cx="17.5" cy="17.5" r="2.5"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total Imposto</span>
                <h2 class="kpi-value" id="total-imposto">R$ 0,00</h2>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Total Liquido --}}
        <div class="kpi-card kpi-success">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total Liquido</span>
                <h2 class="kpi-value" id="total-liquido">R$ 0,00</h2>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Total a Receber --}}
        <div class="kpi-card kpi-info">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/>
                        <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/>
                        <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total a Receber</span>
                <h2 class="kpi-value" id="total-receber">R$ 0,00</h2>
            </div>
            <div class="kpi-glow"></div>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="filter-card">
        <div class="filter-header">
            <div class="filter-title-group">
                <div class="filter-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                </div>
                <h3 class="filter-title">Filtros</h3>
            </div>
        </div>
        <div class="filter-body">
            <div class="filter-grid">
                <div class="filter-group">
                    <label class="filter-label">Mes</label>
                    <input type="month" id="filtro-mes" class="form-control"
                        value="{{ \Carbon\Carbon::now('America/Sao_Paulo')->format('Y-m') }}">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Vendedor</label>
                    <select id="filtro-vendedor" class="form-select select2" data-placeholder="Todos">
                        <option value="">Todos</option>
                        @foreach ($vendedores as $v)
                            <option value="{{ $v->id }}">{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Criado por</label>
                    <select id="filtro-criado-por" class="form-select select2" data-placeholder="Todos">
                        <option value="">Todos</option>
                        @foreach ($criadores as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select id="filtro-status" class="form-select">
                        <option value="">Todos</option>
                        <option value="pendente">Pendente</option>
                        <option value="pago">Pago</option>
                    </select>
                </div>
                <div class="filter-group" style="display: flex; align-items: flex-end;">
                    <button id="btn-aplicar" class="btn-dash btn-primary w-100">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Container de Pagamentos --}}
    <div id="pagamentos-container"></div>
</div>

{{-- Modal: Registrar Pagamento --}}
<div class="modal fade modal-modern" id="modalPagar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formPagar" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                    Registrar pagamento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="pg_pagamento_id">
                <input type="hidden" id="pg_user_id">

                <div class="mb-3">
                    <label for="pg_conta" class="form-label">Conta de pagamento</label>
                    <select id="pg_conta" class="form-select" required>
                        <option value="" selected disabled>Carregando contas...</option>
                    </select>
                    <div class="form-text">Se nao selecionar, sera usada a conta padrao do vendedor (se existir).</div>
                </div>

                <div class="mb-3">
                    <label for="pg_pago_em" class="form-label">Data do pagamento</label>
                    <input type="text" id="pg_pago_em" class="form-control flatpickr-date" placeholder="Selecione..." required>
                </div>

                <div class="alert alert-warning d-none" id="pg_alert_no_accounts">
                    Este vendedor nao possui contas cadastradas. Cadastre uma conta padrao antes de pagar.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-dash btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn-dash btn-success">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Confirmar pagamento
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
