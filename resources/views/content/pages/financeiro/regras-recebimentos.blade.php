@extends('layouts/layoutMaster')

@section('title', 'Financeiro - Regras de Recebimentos')

{{-- Vendor Styles --}}
@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/regras-recebimentos.scss'])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/regras-recebimentos.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y regras-recebimentos-page">

    {{-- Page Header --}}
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <span class="title-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </span>
                Regras de Recebimentos
            </h1>
            <p class="page-subtitle">Cadastre percentuais por parcela e pagador para cada Operadora e Modalidade</p>
        </div>
        <button id="btnNovaRegra" class="btn btn-nova-regra">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Nova Regra
        </button>
    </div>

    {{-- KPI Cards --}}
    <div class="kpi-grid">
        {{-- Total de Regras --}}
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
            </div>
            <span class="kpi-label">Total de Regras</span>
            <h2 class="kpi-value" id="kpiTotalRegras">0</h2>
        </div>

        {{-- Regras PME --}}
        <div class="kpi-card kpi-info">
            <div class="kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            <span class="kpi-label">Regras PME</span>
            <h2 class="kpi-value" id="kpiPME">0</h2>
        </div>

        {{-- Regras Adesão --}}
        <div class="kpi-card kpi-warning">
            <div class="kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <span class="kpi-label">Regras Adesão</span>
            <h2 class="kpi-value" id="kpiAdesao">0</h2>
        </div>

        {{-- Regras Vitalícias --}}
        <div class="kpi-card kpi-success">
            <div class="kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <polyline points="9 12 11 14 15 10"/>
                </svg>
            </div>
            <span class="kpi-label">Regras Vitalícias</span>
            <h2 class="kpi-value" id="kpiVitalicias">0</h2>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="table-card">
        <div class="table-header">
            <div class="table-title-group">
                <div class="table-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <line x1="3" y1="9" x2="21" y2="9"/>
                        <line x1="9" y1="21" x2="9" y2="9"/>
                    </svg>
                </div>
                <div>
                    <h3 class="table-title">Regras Cadastradas</h3>
                    <span class="table-subtitle">Gerencie as regras de recebimento por operadora</span>
                </div>
            </div>
            <div class="table-badge">
                <span id="tableCount">0</span> regras
            </div>
        </div>
        <div class="table-body">
            <div class="card-datatable table-responsive">
                <table id="rulesTable" class="table" width="100%">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>Operadora</th>
                            <th>Categoria</th>
                            <th>Total %</th>
                            <th>Vitalício</th>
                            <th>Descrição</th>
                            <th style="width:140px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- DataTables --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ========================= MODAL: CADASTRAR / EDITAR REGRA ========================= --}}
<div class="modal fade regras-modal" id="modalRegra" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="formRegra" class="modal-content" method="post" action="">
            @csrf
            <input type="hidden" name="id" id="regraId">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="12" y1="18" x2="12" y2="12"/>
                            <line x1="9" y1="15" x2="15" y2="15"/>
                        </svg>
                    </span>
                    <span id="tituloModalRegra">Nova Regra</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required">Operadora</label>
                        <select name="operadora_id" id="regraOperadora" class="form-select" required>
                            <option value="">Selecione</option>
                            @foreach ($operadoras ?? [] as $op)
                                <option value="{{ $op->id }}">{{ strtoupper($op->nome) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Categoria</label>
                        <select name="categoria" id="regraCategoria" class="form-select" required>
                            <option value="PME">PME</option>
                            <option value="ADESAO">ADESÃO</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Total Percentual (%)</label>
                        <input type="number" step="0.01" name="total_percentual" id="regraTotalPercentual"
                            class="form-control" placeholder="Ex.: 100.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Vitalício?</label>
                        <select name="vitalicio" id="regraVitalicio" class="form-select">
                            <option value="0">Não</option>
                            <option value="1">Sim</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="percentualVitalicioWrapper" style="display:none;">
                        <label class="form-label">Percentual Vitalício (%)</label>
                        <input type="number" step="0.01" min="0" max="100"
                            name="percentual_vitalicio" id="regraPercentualVitalicio"
                            class="form-control" placeholder="Ex.: 10.00">
                        <small class="text-muted">Percentual aplicado após as parcelas cadastradas (12 meses)</small>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Descrição</label>
                        <input type="text" class="form-control" name="descricao" id="regraDescricao"
                            placeholder="Ex.: Pago pela Amil">
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-danger" id="btnExcluirRegra" style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        <line x1="10" y1="11" x2="10" y2="17"/>
                        <line x1="14" y1="11" x2="14" y2="17"/>
                    </svg>
                    Excluir
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                            <polyline points="7 3 7 8 15 8"/>
                        </svg>
                        Salvar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ========================= MODAL: PARCELAS DA REGRA ========================= --}}
<div class="modal fade regras-modal" id="modalParcelas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="8" y1="6" x2="21" y2="6"/>
                            <line x1="8" y1="12" x2="21" y2="12"/>
                            <line x1="8" y1="18" x2="21" y2="18"/>
                            <line x1="3" y1="6" x2="3.01" y2="6"/>
                            <line x1="3" y1="12" x2="3.01" y2="12"/>
                            <line x1="3" y1="18" x2="3.01" y2="18"/>
                        </svg>
                    </span>
                    <span>Parcelas da Regra</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-muted">Defina <b>nº da parcela</b>, <b>%</b> e <b>pagador</b>.</div>
                    <button class="btn btn-primary" id="btnAddParcela">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Adicionar Parcela
                    </button>
                </div>
                <div class="parcelas-table">
                    <div class="table-responsive">
                        <table id="parcelasTable" class="table">
                            <thead>
                                <tr>
                                    <th style="width:100px;">Parcela</th>
                                    <th style="width:150px;">Percentual (%)</th>
                                    <th>Pagador</th>
                                    <th style="width:120px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- via JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

{{-- ========================= MODAL: FORM PARCELA ========================= --}}
<div class="modal fade regras-modal" id="modalParcelaForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="formParcela" class="modal-content" method="post" action="">
            @csrf
            <input type="hidden" name="commission_rule_id" id="parcelaRuleId">
            <input type="hidden" name="id" id="parcelaId">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                            <line x1="1" y1="10" x2="23" y2="10"/>
                        </svg>
                    </span>
                    <span id="tituloModalParcela">Adicionar Parcela</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label required">Nº Parcela</label>
                        <input type="number" min="1" class="form-control" name="installment_number"
                            id="parcelaNumero" required placeholder="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Percentual (%)</label>
                        <input type="number" step="0.01" class="form-control" name="percent"
                            id="parcelaPercent" required placeholder="10.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Pagador</label>
                        <input type="text" class="form-control" name="payer" id="parcelaPagador" required
                            placeholder="Ex.: Amil, Brazil Health">
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-danger" id="btnExcluirParcela" style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                    Excluir
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                            <polyline points="7 3 7 8 15 8"/>
                        </svg>
                        Salvar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Rotas para JS --}}
<div id="regrasRecebimentosRoutes"
    data-index="{{ route('financeiro.regras.index') }}"
    data-store="{{ route('financeiro.regras.store') }}"
    data-update="{{ route('financeiro.regras.update', ['id' => '__ID__']) }}"
    data-destroy="{{ route('financeiro.regras.destroy', ['id' => '__ID__']) }}"
    data-installments-index="{{ route('financeiro.regras.parcelas.index', ['ruleId' => '__RULE_ID__']) }}"
    data-installments-store="{{ route('financeiro.regras.parcelas.store') }}"
    data-installments-update="{{ route('financeiro.regras.parcelas.update', ['id' => '__ID__']) }}"
    data-installments-destroy="{{ route('financeiro.regras.parcelas.destroy', ['id' => '__ID__']) }}">
</div>
@endsection
