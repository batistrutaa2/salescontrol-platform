@extends('layouts/layoutMaster')

@section('title', 'Vitalicios')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
    ])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/recebiveis.scss')
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js'
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/vitalicios.js'])
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
                    <span class="breadcrumb-item active">Vitalicios</span>
                </div>
                <h1 class="page-title">Gestao de Vitalicios</h1>
                <p class="page-description">Parcelas recorrentes — acompanhe os recebimentos mensais das operadoras</p>
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

    {{-- Filtro por Data de Implantacao --}}
    <section class="filter-section">
        <div class="filter-bar">
            <div class="filter-bar-left">
                <div class="filter-bar-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <span class="filter-bar-label">Implantacao</span>
            </div>
            <div class="filter-controls">
                <div class="filter-date-group">
                    <label class="filter-date-label">De</label>
                    <input type="text" id="filtro-data-inicial" class="filter-select filter-date-input" placeholder="dd/mm/aaaa"
                           value="{{ $dataInicial ? \Carbon\Carbon::parse($dataInicial)->format('d/m/Y') : '' }}">
                </div>
                <div class="filter-date-group">
                    <label class="filter-date-label">Ate</label>
                    <input type="text" id="filtro-data-final" class="filter-select filter-date-input" placeholder="dd/mm/aaaa"
                           value="{{ $dataFinal ? \Carbon\Carbon::parse($dataFinal)->format('d/m/Y') : '' }}">
                </div>
                <button type="button" id="btnFiltrar" class="btn-filtrar" title="Filtrar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <span>Filtrar</span>
                </button>
                @if($dataInicial && $dataFinal)
                    <a href="{{ route('financeiro.vitalicios.index') }}" class="btn-limpar-filtro" title="Limpar filtro">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- KPI Cards --}}
    <section class="kpi-section">
        <div class="kpi-grid kpi-grid-4">
            {{-- Total Esperado --}}
            <div class="kpi-card kpi-primary">
                <div class="kpi-icon-wrapper">
                    <div class="kpi-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="kpi-pulse"></div>
                </div>
                <div class="kpi-content">
                    <span class="kpi-label">Total Esperado</span>
                    <h2 class="kpi-value">R$ {{ number_format($kpis->total_esperado ?? 0, 2, ',', '.') }}</h2>
                    <div class="kpi-meta">
                        <span class="kpi-meta-item">{{ $kpis->total_contratos ?? 0 }} contratos</span>
                        <span class="kpi-meta-divider">&middot;</span>
                        <span class="kpi-meta-item">{{ $kpis->total_parcelas ?? 0 }} parcelas</span>
                    </div>
                </div>
                <div class="kpi-glow"></div>
            </div>

            {{-- Total Recebido --}}
            <div class="kpi-card kpi-success">
                <div class="kpi-icon-wrapper">
                    <div class="kpi-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <div class="kpi-pulse"></div>
                </div>
                <div class="kpi-content">
                    <span class="kpi-label">Total Recebido</span>
                    <h2 class="kpi-value">R$ {{ number_format($kpis->total_recebido ?? 0, 2, ',', '.') }}</h2>
                    @php
                        $percentRecebido = ($kpis->total_esperado ?? 0) > 0
                            ? round((($kpis->total_recebido ?? 0) / $kpis->total_esperado) * 100, 1)
                            : 0;
                    @endphp
                    <div class="kpi-trend trend-up">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                            <polyline points="17 6 23 6 23 12"/>
                        </svg>
                        <span>{{ $percentRecebido }}% do esperado</span>
                    </div>
                </div>
                <div class="kpi-glow"></div>
            </div>

            {{-- Total Pendente --}}
            <div class="kpi-card kpi-warning">
                <div class="kpi-icon-wrapper">
                    <div class="kpi-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div class="kpi-pulse"></div>
                </div>
                <div class="kpi-content">
                    <span class="kpi-label">Total Pendente</span>
                    <h2 class="kpi-value">R$ {{ number_format($kpis->total_pendente ?? 0, 2, ',', '.') }}</h2>
                    @php
                        $percentPendente = ($kpis->total_esperado ?? 0) > 0
                            ? round((($kpis->total_pendente ?? 0) / $kpis->total_esperado) * 100, 1)
                            : 0;
                    @endphp
                    <div class="kpi-trend trend-neutral">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        <span>{{ $percentPendente }}% do esperado</span>
                    </div>
                </div>
                <div class="kpi-glow"></div>
            </div>

            {{-- Total Atrasado --}}
            <div class="kpi-card kpi-danger">
                <div class="kpi-icon-wrapper">
                    <div class="kpi-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>
                    <div class="kpi-pulse"></div>
                </div>
                <div class="kpi-content">
                    <span class="kpi-label">Total Atrasado</span>
                    <h2 class="kpi-value">R$ {{ number_format($kpis->total_atrasado ?? 0, 2, ',', '.') }}</h2>
                    @php
                        $percentAtrasado = ($kpis->total_pendente ?? 0) > 0
                            ? round((($kpis->total_atrasado ?? 0) / ($kpis->total_pendente ?? 1)) * 100, 1)
                            : 0;
                    @endphp
                    <div class="kpi-trend trend-down">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/>
                            <polyline points="17 18 23 18 23 12"/>
                        </svg>
                        <span>{{ $percentAtrasado }}% do pendente</span>
                    </div>
                </div>
                <div class="kpi-glow"></div>
            </div>
        </div>
    </section>

    {{-- Contratos com Vitalicios --}}
    <section class="data-section">
        <div class="data-card">
            <div class="data-card-header">
                <div class="header-left">
                    <h2 class="section-title">Contratos com Vitalicios</h2>
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

            <div class="data-card-body">
                <div class="table-wrapper">
                    <table id="vitaliciosTable" class="recebiveis-table">
                        <thead>
                            <tr>
                                <th class="col-contract">Contrato</th>
                                <th class="col-operator">Operadora</th>
                                <th class="col-seller">Vendedor</th>
                                <th class="col-policy">Valor Apolice</th>
                                <th class="col-total">Total Vitalicios</th>
                                <th class="col-paid">Pago</th>
                                <th class="col-pending">Pendente</th>
                                <th class="col-status">Status</th>
                                <th class="col-actions">Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contratos as $contrato)
                            <tr class="contract-row {{ !$contrato->vitalicio_ativo ? 'vitalicio-desativado' : '' }}" data-venda-id="{{ $contrato->venda->id }}">
                                <td class="col-contract">
                                    <div class="contract-info">
                                        <span class="contract-name">{{ $contrato->venda->nome_contrato ?? 'N/A' }}</span>
                                        <span class="contract-id">#{{ $contrato->venda->id ?? '' }} &middot; {{ $contrato->total_parcelas }} parcelas</span>
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
                                    <div class="actions-group">
                                        <button class="btn-toggle-vitalicio {{ $contrato->vitalicio_ativo ? 'active' : '' }}"
                                                data-venda-id="{{ $contrato->venda->id }}"
                                                title="{{ $contrato->vitalicio_ativo ? 'Desativar vitalicio' : 'Reativar vitalicio' }}">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M18.36 6.64a9 9 0 1 1-12.73 0"/>
                                                <line x1="12" y1="2" x2="12" y2="12"/>
                                            </svg>
                                        </button>
                                        <button class="btn-excluir-lancamento btn-delete-all-vitalicios"
                                                data-venda-id="{{ $contrato->venda->id }}"
                                                title="Excluir todos os vitalicios">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            </svg>
                                        </button>
                                        <button class="btn-view-parcelas view-parcelas" data-id="{{ $contrato->venda->id }}" title="Ver parcelas">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                            <span>Parcelas</span>
                                        </button>
                                    </div>
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
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="M2 17l10 5 10-5"/>
                            <path d="M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <div class="modal-title-group">
                        <h5 class="modal-title">Parcelas Vitalicias</h5>
                        <span class="modal-subtitle">Gerencie os recebimentos recorrentes do contrato</span>
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
                    <div class="modal-actions-left">
                        <button class="btn-dar-baixa-selecionados" id="btnDarBaixaSelecionados" title="Dar Baixa" style="display: none;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            <span>Dar Baixa (<span id="countDarBaixa">0</span>)</span>
                        </button>
                        <button class="btn-editar-selecionados" id="btnEditarSelecionados" title="Editar Selecionados" style="display: none;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            <span>Editar (<span id="countEditarSelecionados">0</span>)</span>
                        </button>
                        <button class="btn-excluir-selecionados" id="btnExcluirSelecionados" title="Excluir Selecionados" style="display: none;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                            <span>Excluir (<span id="countSelecionados">0</span>)</span>
                        </button>
                    </div>
                    <div class="modal-actions-right">
                        <button class="btn-recalcular" id="btnGerarManual" title="Gerar Parcelas" style="background: linear-gradient(135deg, var(--rcb-primary, #7C3AED), var(--rcb-primary-light, #A78BFA)); color: #fff;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="16"/>
                                <line x1="8" y1="12" x2="16" y2="12"/>
                            </svg>
                            <span>Gerar Parcelas</span>
                        </button>
                        <button class="btn-excluir-todos" id="btnExcluirTodos" title="Excluir Todos">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                <line x1="10" y1="11" x2="10" y2="17"/>
                                <line x1="14" y1="11" x2="14" y2="17"/>
                            </svg>
                            <span>Excluir Todos</span>
                        </button>
                    </div>
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
                                <th>Custo Atual</th>
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
                        <div class="pagination-pages" id="paginationPages"></div>
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
