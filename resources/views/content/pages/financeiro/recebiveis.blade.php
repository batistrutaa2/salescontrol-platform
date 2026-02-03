@extends('layouts/layoutMaster')

@section('title', 'Recebiveis')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/recebiveis.scss')
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/recebiveis.js'])
@endsection

@section('content')
<div class="recebiveis-page">
    {{-- Page Header --}}
    <header class="page-header">
        <div class="header-content">
            <div class="header-title-section">
                <div class="breadcrumb-nav">
                    <span class="breadcrumb-item">Financeiro</span>
                    <svg class="breadcrumb-separator" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                    <span class="breadcrumb-item active">Recebiveis</span>
                </div>
                <h1 class="page-title">Gestao de Recebiveis</h1>
                <p class="page-description">Acompanhe e gerencie os recebimentos de comissoes</p>
            </div>
            <div class="header-actions">
                <div class="last-update">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Atualizado agora</span>
                </div>
            </div>
        </div>
    </header>

    {{-- KPI Cards --}}
    <section class="kpi-section">
        {{-- Linha 1: KPIs Gerais --}}
        <div class="kpi-grid kpi-grid-3">
            {{-- Total Recebido --}}
            <article class="kpi-card kpi-success">
                <div class="kpi-background">
                    <svg class="kpi-bg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <span class="kpi-badge">Recebido</span>
                </div>
                <div class="kpi-body">
                    <span class="kpi-value" id="kpi-pago" data-value="{{ $totais['pago'] }}">
                        R$ {{ number_format($totais['pago'], 2, ',', '.') }}
                    </span>
                    <span class="kpi-label">Valor Total Pago</span>
                </div>
                <div class="kpi-footer">
                    <div class="kpi-indicator positive">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                            <polyline points="17 6 23 6 23 12"/>
                        </svg>
                        <span>Atualizado</span>
                    </div>
                </div>
            </article>

            {{-- Pendente --}}
            <article class="kpi-card kpi-warning">
                <div class="kpi-background">
                    <svg class="kpi-bg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <span class="kpi-badge">Aguardando</span>
                </div>
                <div class="kpi-body">
                    <span class="kpi-value" id="kpi-pendente" data-value="{{ $totais['pendente'] }}">
                        R$ {{ number_format($totais['pendente'], 2, ',', '.') }}
                    </span>
                    <span class="kpi-label">Valor Pendente</span>
                </div>
                <div class="kpi-footer">
                    <div class="kpi-indicator neutral">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        <span>A receber</span>
                    </div>
                </div>
            </article>

            {{-- Em Atraso --}}
            <article class="kpi-card kpi-danger">
                <div class="kpi-background">
                    <svg class="kpi-bg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>
                    <span class="kpi-badge">Atencao</span>
                </div>
                <div class="kpi-body">
                    <span class="kpi-value" id="kpi-atraso" data-value="{{ $totais['atraso'] }}">
                        R$ {{ number_format($totais['atraso'], 2, ',', '.') }}
                    </span>
                    <span class="kpi-label">Valor em Atraso</span>
                </div>
                <div class="kpi-footer">
                    <div class="kpi-indicator negative">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <span>Requer acao</span>
                    </div>
                </div>
            </article>
        </div>

        {{-- Linha 2: KPIs por Tipo (Parcelas vs Vitalicio) --}}
        <div class="kpi-grid kpi-grid-2" style="margin-top: 1.5rem;">
            {{-- Parcelas de Pagamento (1-3) --}}
            <article class="kpi-card kpi-parcela">
                <div class="kpi-background">
                    <svg class="kpi-bg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <rect x="2" y="4" width="20" height="16" rx="2"/>
                        <path d="M7 15h0M2 9h20"/>
                    </svg>
                </div>
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="4" width="20" height="16" rx="2"/>
                            <path d="M7 15h0M2 9h20"/>
                        </svg>
                    </div>
                    <span class="kpi-badge">Parcelas 1-3</span>
                </div>
                <div class="kpi-body">
                    <span class="kpi-value" id="kpi-parcelas-total">
                        R$ {{ number_format($totaisPorTipo['parcelas']['pago'] + $totaisPorTipo['parcelas']['pendente'], 2, ',', '.') }}
                    </span>
                    <span class="kpi-label">Receita de Parcelas</span>
                </div>
                <div class="kpi-footer kpi-footer-split">
                    <div class="kpi-split-item">
                        <span class="split-label">Recebido</span>
                        <span class="split-value split-success" id="kpi-parcelas-pago">
                            R$ {{ number_format($totaisPorTipo['parcelas']['pago'], 2, ',', '.') }}
                        </span>
                    </div>
                    <div class="kpi-split-divider"></div>
                    <div class="kpi-split-item">
                        <span class="split-label">Pendente</span>
                        <span class="split-value split-warning" id="kpi-parcelas-pendente">
                            R$ {{ number_format($totaisPorTipo['parcelas']['pendente'], 2, ',', '.') }}
                        </span>
                    </div>
                </div>
            </article>

            {{-- Vitalicio (4+) --}}
            <article class="kpi-card kpi-vitalicio">
                <div class="kpi-background">
                    <svg class="kpi-bg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="M2 17l10 5 10-5"/>
                            <path d="M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <span class="kpi-badge">Vitalicio 4+</span>
                </div>
                <div class="kpi-body">
                    <span class="kpi-value" id="kpi-vitalicio-total">
                        R$ {{ number_format($totaisPorTipo['vitalicio']['pago'] + $totaisPorTipo['vitalicio']['pendente'], 2, ',', '.') }}
                    </span>
                    <span class="kpi-label">Receita Vitalicia</span>
                </div>
                <div class="kpi-footer kpi-footer-split">
                    <div class="kpi-split-item">
                        <span class="split-label">Recebido</span>
                        <span class="split-value split-success" id="kpi-vitalicio-pago">
                            R$ {{ number_format($totaisPorTipo['vitalicio']['pago'], 2, ',', '.') }}
                        </span>
                    </div>
                    <div class="kpi-split-divider"></div>
                    <div class="kpi-split-item">
                        <span class="split-label">Pendente</span>
                        <span class="split-value split-warning" id="kpi-vitalicio-pendente">
                            R$ {{ number_format($totaisPorTipo['vitalicio']['pendente'], 2, ',', '.') }}
                        </span>
                    </div>
                </div>
            </article>
        </div>
    </section>

    {{-- Filtro por Ano de Implantação --}}
    <section class="filter-section">
        <div class="timeline-filter-card">
            <div class="timeline-header">
                <div class="timeline-title-group">
                    <div class="timeline-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="timeline-title">Ano de Implantacao</h3>
                        <p class="timeline-subtitle">Filtre contratos por ano de implantacao</p>
                    </div>
                </div>
                @if($anoSelecionado)
                    <div class="timeline-active-filter">
                        <span class="active-filter-badge">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                            </svg>
                            {{ $anoSelecionado }}
                        </span>
                        <a href="{{ route('financeiro.recebiveis.index') }}" class="clear-filter-btn" title="Limpar filtro">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </a>
                    </div>
                @endif
            </div>

            {{-- Anos como tabs/links --}}
            <div class="timeline-years">
                <a href="{{ route('financeiro.recebiveis.index') }}"
                   class="year-tab {{ !$anoSelecionado ? 'active' : '' }}">
                    Todos
                </a>
                @foreach($anosDisponiveis as $periodo)
                    <a href="{{ route('financeiro.recebiveis.index', ['ano' => $periodo['ano']]) }}"
                       class="year-tab {{ $anoSelecionado == $periodo['ano'] ? 'active' : '' }}">
                        {{ $periodo['ano'] }}
                        <span class="year-count">{{ $periodo['total_contratos'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Filters & Table Section --}}
    <section class="data-section">
        <div class="data-card">
            {{-- Card Header with Filters --}}
            <div class="data-card-header">
                <div class="header-left">
                    <h2 class="section-title">Contratos</h2>
                    <span class="contracts-count">{{ count($contratos) }} registros</span>
                </div>
                <div class="header-right">
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-status="todos">
                            <span class="tab-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7"/>
                                    <rect x="14" y="3" width="7" height="7"/>
                                    <rect x="14" y="14" width="7" height="7"/>
                                    <rect x="3" y="14" width="7" height="7"/>
                                </svg>
                            </span>
                            <span class="tab-label">Todos</span>
                        </button>
                        <button class="filter-tab" data-status="Quitado">
                            <span class="tab-icon success">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                            </span>
                            <span class="tab-label">Quitado</span>
                        </button>
                        <button class="filter-tab" data-status="Pendente">
                            <span class="tab-icon warning">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                            </span>
                            <span class="tab-label">Pendente</span>
                        </button>
                        <button class="filter-tab" data-status="Atrasado">
                            <span class="tab-icon danger">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                            </span>
                            <span class="tab-label">Atrasado</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Data Table --}}
            <div class="data-card-body">
                <div class="table-wrapper">
                    <table id="recebiveisTable" class="recebiveis-table">
                        <thead>
                            <tr>
                                <th class="col-contract">Contrato</th>
                                <th class="col-operator">Operadora</th>
                                <th class="col-seller">Vendedor</th>
                                <th class="col-policy">Valor Apolice</th>
                                <th class="col-total">Total Recebiveis</th>
                                <th class="col-paid">Pago</th>
                                <th class="col-pending">Pendente</th>
                                <th class="col-status">Status</th>
                                <th class="col-actions">Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contratos as $contrato)
                            <tr class="contract-row" data-venda-id="{{ $contrato->venda->id }}">
                                <td class="col-contract">
                                    <div class="contract-info">
                                        <span class="contract-name">{{ $contrato->venda->nome_contrato ?? 'N/A' }}</span>
                                        <span class="contract-id">#{{ $contrato->venda->id ?? '' }}</span>
                                    </div>
                                </td>
                                <td class="col-operator">
                                    <span class="operator-badge">{{ $contrato->operadora }}</span>
                                </td>
                                <td class="col-seller">
                                    <div class="seller-info">
                                        <div class="seller-avatar">
                                            {{ strtoupper(substr($contrato->vendedor->name ?? 'N', 0, 1)) }}
                                        </div>
                                        <span class="seller-name">{{ $contrato->vendedor->name ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td class="col-policy">
                                    <span class="value-policy">R$ {{ number_format($contrato->venda->valor_contrato ?? 0, 2, ',', '.') }}</span>
                                </td>
                                <td class="col-total">
                                    <span class="valor-total">R$ {{ number_format($contrato->valor_total, 2, ',', '.') }}</span>
                                </td>
                                <td class="col-paid">
                                    <span class="valor-pago">R$ {{ number_format($contrato->valor_pago, 2, ',', '.') }}</span>
                                </td>
                                <td class="col-pending">
                                    <span class="valor-pendente">R$ {{ number_format($contrato->valor_pendente, 2, ',', '.') }}</span>
                                </td>
                                <td class="col-status">
                                    @if($contrato->valor_pendente == 0)
                                        <span class="status-badge status-success">Quitado</span>
                                    @elseif($contrato->em_atraso)
                                        <span class="status-badge status-danger">Atrasado</span>
                                    @else
                                        <span class="status-badge status-warning">Pendente</span>
                                    @endif
                                </td>
                                <td class="col-actions">
                                    <button class="btn-view-parcelas view-parcelas" data-id="{{ $contrato->venda->id }}" title="Ver parcelas">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        <span>Parcelas</span>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- Modal Parcelas --}}
<div class="modal fade parcelas-modal" id="parcelasModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </div>
                    <div class="modal-title-group">
                        <h5 class="modal-title">Detalhes das Parcelas</h5>
                        <span class="modal-subtitle">Gerencie os recebimentos do contrato</span>
                    </div>
                </div>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-actions">
                    <button class="btn-excluir-selecionados" id="btnExcluirSelecionados" style="display: none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                        <span>Excluir Selecionados (<span id="countSelecionados">0</span>)</span>
                    </button>
                    <button class="btn-recalcular" id="btnRecalcular">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"/>
                            <polyline points="1 20 1 14 7 14"/>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                        </svg>
                        <span>Recalcular Valores</span>
                    </button>
                    <button class="btn-excluir-todos" id="btnExcluirTodos">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            <line x1="10" y1="11" x2="10" y2="17"/>
                            <line x1="14" y1="11" x2="14" y2="17"/>
                        </svg>
                        <span>Excluir Todos</span>
                    </button>
                </div>
                <div class="parcelas-table-wrapper">
                    <table class="parcelas-table" id="parcelasTable">
                        <thead>
                            <tr>
                                <th class="col-checkbox">
                                    <label class="custom-checkbox">
                                        <input type="checkbox" id="selectAllParcelas">
                                        <span class="checkmark"></span>
                                    </label>
                                </th>
                                <th>Parcela</th>
                                <th>Valor</th>
                                <th>Vencimento</th>
                                <th>Recebimento</th>
                                <th>Status</th>
                                <th>Acao</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Populated via JS --}}
                        </tbody>
                    </table>
                </div>
                {{-- Pagination Controls --}}
                <div class="parcelas-pagination" id="parcelasPagination">
                    <div class="pagination-info">
                        <span id="paginationInfo">Mostrando 0 de 0 parcelas</span>
                    </div>
                    <div class="pagination-controls">
                        <button type="button" class="pagination-btn" id="btnPrevPage" disabled>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>
                        <div class="pagination-pages" id="paginationPages">
                            {{-- Page numbers populated via JS --}}
                        </div>
                        <button type="button" class="pagination-btn" id="btnNextPage" disabled>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
