@extends('layouts/layoutMaster')

@section('title', 'Faturamento de Comissoes')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/toastr/toastr.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/scss/pages/comissionamento-faturamento.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/toastr/toastr.js',
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/cleavejs/cleave.js',
        'resources/assets/vendor/libs/cleavejs/cleave-phone.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js'
    ])
@endsection

@section('page-script')
    @vite('resources/assets/js/comissionamento-faturamento.js')
@endsection

@section('content')
<div class="comissionamento-faturamento-wrapper">
    {{-- Data Container --}}
    <div id="comissionamento-root"
        data-url="{{ route('comissionamento.faturamento') }}"
        data-pay-url="{{ route('comissionamento.pagar') }}"
        data-ajuste-url="{{ route('comissionamento.ajuste.store') }}">
    </div>

    {{-- Header Section --}}
    <div class="cf-header">
        <div class="header-content">
            <div class="header-text">
                <span class="greeting-label">Comissionamento</span>
                <h1 class="main-title">Faturamento de Comissoes</h1>
                <p class="subtitle">Gerencie os pagamentos de comissoes dos vendedores</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-dash btn-secondary" id="btn-lanc-avulso">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="16"/>
                        <line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                    Novo Lancamento
                </button>
            </div>
        </div>
    </div>

    {{-- KPI Cards Section --}}
    <div class="kpi-grid" id="resumo-geral">
        {{-- Vendedores --}}
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Vendedores</span>
                <h2 class="kpi-value" id="kpi-vendedores">0</h2>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Contratos Pendentes --}}
        <div class="kpi-card kpi-info">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Contratos Pendentes</span>
                <h2 class="kpi-value" id="kpi-contratos">0</h2>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Total Contratos --}}
        <div class="kpi-card kpi-success">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total Contratos (R$)</span>
                <h2 class="kpi-value" id="kpi-total-contratos">R$ 0,00</h2>
            </div>
            <div class="kpi-glow"></div>
        </div>

        {{-- Total Comissao --}}
        <div class="kpi-card kpi-warning">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total Comissao (R$)</span>
                <h2 class="kpi-value" id="kpi-total-comissao">R$ 0,00</h2>
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
                    <label class="filter-label">Data Inicio</label>
                    <input type="text" id="filtro-data-inicio" class="form-control flatpickr-date" placeholder="Selecione...">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Data Fim</label>
                    <input type="text" id="filtro-data-fim" class="form-control flatpickr-date" placeholder="Selecione...">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Vendedor</label>
                    <select id="filtro-vendedor" class="form-select select2" data-placeholder="Todos">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Grade</label>
                    <select id="filtro-grade" class="form-select select2" data-placeholder="Todas">
                        <option value="">Todas</option>
                        <option value="junior">Junior</option>
                        <option value="senior">Senior</option>
                    </select>
                </div>
                <div class="filter-group" style="display: flex; align-items: flex-end;">
                    <button id="btn-aplicar-filtro" class="btn-dash btn-primary w-100">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        Aplicar
                    </button>
                </div>
            </div>
            <div class="filter-footer">
                <div class="alert-info-custom">
                    Somente contratos <strong>sem comissao paga</strong> no periodo selecionado.
                </div>
            </div>
        </div>
    </div>

    {{-- Vendedores List (JUNIOR/SENIOR) --}}
    <div id="lista-vendedores">
        {{-- Rendered via JS --}}
    </div>
</div>

{{-- Modal: Lancar Ajuste (Credito/Debito) --}}
<div class="modal fade" id="modalLancAjuste" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" id="form-lanc-ajuste" action="#" method="POST">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title js-natureza-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="16"/>
                        <line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                    Lancar ajuste para vendedor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <input type="hidden" id="ajVendId" name="vendedor_id">
                    <input type="hidden" id="ajNatureza" name="natureza">
                    <input type="hidden" id="ajImpVal" name="imposto_valor">
                    <input type="hidden" id="ajLiqVal" name="valor_liquido">

                    <div class="col-md-4">
                        <label class="form-label">Mes de referencia</label>
                        <input type="month" class="form-control" id="ajMes" name="mes"
                            value="{{ \Carbon\Carbon::now('America/Sao_Paulo')->format('Y-m') }}" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Vendedor</label>
                        <input type="text" class="form-control" id="ajVendNome" disabled>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Categoria</label>
                        <select class="form-select" id="ajCategoria" name="categoria" required>
                            <option value="MOTIVACIONAL">MOTIVACIONAL</option>
                            <option value="AJUSTE">AJUSTE</option>
                            <option value="DESCONTO">ESTORNO</option>
                            <option value="ANGARIACAO">ANGARIACAO</option>
                            <option value="PRESTACAO">PRESTACAO</option>
                            <option value="OUTRO">OUTROS</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">% Imposto</label>
                        <input type="number" step="0.01" class="form-control" id="ajImpPerc"
                            name="imposto_perc" value="0.00" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Valor (R$)</label>
                        <input type="number" step="0.01" class="form-control" id="ajValor" name="valor_bruto"
                            required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Descricao/observacao</label>
                        <input type="text" class="form-control" id="ajDesc" name="descricao"
                            placeholder="Ex.: Bonus por meta ou desconto por atraso">
                    </div>

                    <div class="col-md-4">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" id="ajParcelado" name="parcelado">
                            <label class="form-check-label" for="ajParcelado">Parcelar lancamento</label>
                        </div>
                    </div>

                    <div class="col-md-4 js-parcelas-fields" style="display: none;">
                        <label class="form-label">Numero de parcelas</label>
                        <input type="number" class="form-control" id="ajParcelas" name="parcelas"
                            min="2" max="60" value="2">
                    </div>

                    <div class="col-md-8 js-parcelas-fields" style="display: none;">
                        <div class="alert-info-custom small">
                            As parcelas serao criadas automaticamente nos meses subsequentes
                        </div>
                    </div>

                    <div class="col-md-12 js-parcelas-fields" style="display: none;">
                        <label class="form-label">Detalhamento das parcelas:</label>
                        <div class="card">
                            <div class="card-body p-3">
                                <div id="ajDetalheParcelas" class="small text-muted">
                                    Informe o valor e numero de parcelas para visualizar o detalhamento
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Imposto (R$)</label>
                        <input type="text" class="form-control" id="ajImpValView" disabled>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Liquido (R$)</label>
                        <input type="text" class="form-control" id="ajLiqView" disabled>
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <div class="small text-muted">
                            * Para <strong>Credito</strong>, o liquido sera somado ao total do vendedor. Para
                            <strong>Debito</strong>, sera subtraido.
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-dash btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn-dash btn-success" id="btn-confirm-ajuste">Salvar ajuste</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Lancamento Avulso (com selecao de vendedor) --}}
<div class="modal fade" id="modalLancAvulso" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" id="form-lanc-avulso" action="#" method="POST">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="12" y1="18" x2="12" y2="12"/>
                        <line x1="9" y1="15" x2="15" y2="15"/>
                    </svg>
                    Novo Lancamento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <input type="hidden" id="avNatureza" name="natureza">
                    <input type="hidden" id="avImpVal" name="imposto_valor">
                    <input type="hidden" id="avLiqVal" name="valor_liquido">

                    <div class="col-md-4">
                        <label class="form-label">Mes de referencia</label>
                        <input type="month" class="form-control" id="avMes" name="mes"
                            value="{{ \Carbon\Carbon::now('America/Sao_Paulo')->format('Y-m') }}" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Vendedor <span class="text-danger">*</span></label>
                        <select class="form-select select2" id="avVendedor" name="vendedor_id" required>
                            <option value="">Selecione um vendedor</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select class="form-select" id="avTipo" required>
                            <option value="">Selecione</option>
                            <option value="CREDITO">Credito</option>
                            <option value="DEBITO">Debito</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Categoria</label>
                        <select class="form-select" id="avCategoria" name="categoria" required>
                            <option value="MOTIVACIONAL">MOTIVACIONAL</option>
                            <option value="AJUSTE">AJUSTE</option>
                            <option value="DESCONTO">ESTORNO</option>
                            <option value="ANGARIACAO">ANGARIACAO</option>
                            <option value="PRESTACAO">PRESTACAO</option>
                            <option value="OUTRO">OUTROS</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">% Imposto</label>
                        <input type="number" step="0.01" class="form-control" id="avImpPerc"
                            name="imposto_perc" value="0.00" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Valor (R$) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="avValor" name="valor_bruto"
                            required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Descricao/observacao</label>
                        <input type="text" class="form-control" id="avDesc" name="descricao"
                            placeholder="Ex.: Bonus por meta ou desconto por atraso">
                    </div>

                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="avParcelado" name="parcelado">
                            <label class="form-check-label" for="avParcelado">Parcelar lancamento</label>
                        </div>
                    </div>

                    <div class="col-md-4 js-parcelas-fields-av" style="display: none;">
                        <label class="form-label">Numero de parcelas</label>
                        <input type="number" class="form-control" id="avParcelas" name="parcelas"
                            min="2" max="60" value="2">
                    </div>

                    <div class="col-md-8 js-parcelas-fields-av" style="display: none;">
                        <div class="alert-info-custom small">
                            As parcelas serao criadas automaticamente nos meses subsequentes
                        </div>
                    </div>

                    <div class="col-md-12 js-parcelas-fields-av" style="display: none;">
                        <label class="form-label">Detalhamento das parcelas:</label>
                        <div class="card">
                            <div class="card-body p-3">
                                <div id="avDetalheParcelas" class="small text-muted">
                                    Informe o valor e numero de parcelas para visualizar o detalhamento
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Imposto (R$)</label>
                        <input type="text" class="form-control" id="avImpValView" disabled>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Liquido (R$)</label>
                        <input type="text" class="form-control" id="avLiqView" disabled>
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <div class="small text-muted">
                            * Para <strong>Credito</strong>, o liquido sera somado ao total do vendedor. Para
                            <strong>Debito</strong>, sera subtraido.
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-dash btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn-dash btn-success" id="btn-confirm-avulso">Salvar lancamento</button>
            </div>
        </form>
    </div>
</div>
@endsection
