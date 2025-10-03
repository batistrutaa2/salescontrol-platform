@extends('layouts/layoutMaster')

@section('title', 'Faturamento de Comissões')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/comissionamento-faturamento.js')
@endsection

@section('content')

    <div id="comissionamento-root" data-url="{{ route('comissionamento.faturamento') }}"
        data-pay-url="{{ route('comissionamento.pagar') }}" data-empresa-id="{{ auth()->user()->empresa_id }}"
        data-ajuste-url="{{ route('comissionamento.ajuste.store') }}">
    </div>


    <div class="card mb-6">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="filtro-mes" class="form-label">Mês de referência</label>
                    <input type="month" id="filtro-mes" class="form-control">
                </div>

                <div class="col-md-3">
                    <label for="filtro-vendedor" class="form-label">Vendedor (opcional)</label>
                    <select id="filtro-vendedor" class="form-select select2" data-placeholder="Todos">
                        <option value="">Todos</option>
                    </select>
                </div>

                {{-- NOVO: filtro por grade --}}
                <div class="col-md-3">
                    <label for="filtro-grade" class="form-label">Grade</label>
                    <select id="filtro-grade" class="form-select select2" data-placeholder="Todas">
                        <option value="">Todas</option>
                        <option value="junior">Junior</option>
                        <option value="senior">Senior</option>
                        <option value="admin">Admin</option>
                        <option value="comercial">Comercial</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <button id="btn-aplicar-filtro" class="btn btn-primary w-100">
                        <i class="ri-filter-3-line me-1"></i> Aplicar
                    </button>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col">
                    <div class="text-muted small">
                        Somente contratos <strong>sem comissão paga</strong> no período selecionado.
                    </div>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-lanc-avulso">
                        <i class="ri-add-circle-line me-1"></i> Novo Lançamento
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="resumo-geral" class="row g-4 mb-6">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted">Vendedores</div>
                            <h4 class="mb-0" id="kpi-vendedores">0</h4>
                        </div>
                        <i class="ri-group-fill ri-28px"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted">Contratos pendentes</div>
                            <h4 class="mb-0" id="kpi-contratos">0</h4>
                        </div>
                        <i class="ri-file-list-3-fill ri-28px"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted">Total contratos (R$)</div>
                            <h4 class="mb-0" id="kpi-total-contratos">0,00</h4>
                        </div>
                        <i class="ri-money-dollar-circle-fill ri-28px"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted">Total comissão (R$)</div>
                            <h4 class="mb-0" id="kpi-total-comissao">0,00</h4>
                        </div>
                        <i class="ri-bar-chart-2-fill ri-28px"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="lista-vendedores" class="row g-4">
        <!-- Render via JS: JUNIOR/SENIOR -->
    </div>

    <!-- ADMIN -->
    <div class="card mt-6" id="grade-admin-card" style="display:none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="ri-shield-user-fill me-2"></i> Grade ADMIN</h5>
            <div class="text-muted small" id="grade-admin-resumo"></div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle" id="tabela-grade-admin">
                    <thead>
                        <tr>
                            <th>Admin</th>
                            <th class="text-end">% Base</th>
                            <th class="text-end">Comissão bruta</th>
                            <th class="text-end">Imposto (%)</th>
                            <th class="text-end">Comissão líquida</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Total líquido</th>
                            <th class="text-end" id="admin-total-liquido">R$ 0,00</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- COMERCIAL -->
    <div class="card mt-4" id="grade-comercial-card" style="display:none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="ri-briefcase-4-fill me-2"></i> Grade COMERCIAL</h5>
            <div class="text-muted small" id="grade-comercial-resumo"></div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3" id="grade-comercial-kpis"></div>

            <div class="table-responsive">
                <table class="table align-middle" id="tabela-grade-comercial">
                    <thead>
                        <tr>
                            <th>Supervisor</th>
                            <th class="text-end">Quota (com imposto)</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <th class="text-end">Total distribuído</th>
                            <th class="text-end" id="comercial-total-distribuido">R$ 0,00</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>


    {{-- Modal: Lançar Ajuste (Crédito/Débito) --}}
    <div class="modal fade" id="modalLancAjuste" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form class="modal-content" id="form-lanc-ajuste" action="#" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title js-natureza-title">
                        <i class="ri-hand-coin-line me-2"></i>Lançar ajuste para vendedor
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
                            <label class="form-label">Mês de referência</label>
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
                                <option value="ANGARIACAO">ANGARIAÇÃO</option>
                                <option value="PRESTACAO">PRESTAÇÃO</option>
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
                            <label class="form-label">Descrição/observação</label>
                            <input type="text" class="form-control" id="ajDesc" name="descricao"
                                placeholder="Ex.: Bônus por meta ou desconto por atraso">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Imposto (R$)</label>
                            <input type="text" class="form-control" id="ajImpValView" disabled>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Líquido (R$)</label>
                            <input type="text" class="form-control" id="ajLiqView" disabled>
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <div class="small text-muted">
                                * Para <strong>Crédito</strong>, o líquido será somado ao total do vendedor. Para
                                <strong>Débito</strong>, será subtraído.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-confirm-ajuste">Salvar ajuste</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Lançamento Avulso (com seleção de vendedor) --}}
    <div class="modal fade" id="modalLancAvulso" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form class="modal-content" id="form-lanc-avulso" action="#" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ri-file-add-line me-2"></i>Novo Lançamento
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <input type="hidden" id="avNatureza" name="natureza">
                        <input type="hidden" id="avImpVal" name="imposto_valor">
                        <input type="hidden" id="avLiqVal" name="valor_liquido">

                        <div class="col-md-4">
                            <label class="form-label">Mês de referência</label>
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
                                <option value="CREDITO">Crédito</option>
                                <option value="DEBITO">Débito</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Categoria</label>
                            <select class="form-select" id="avCategoria" name="categoria" required>
                                <option value="MOTIVACIONAL">MOTIVACIONAL</option>
                                <option value="AJUSTE">AJUSTE</option>
                                <option value="DESCONTO">ESTORNO</option>
                                <option value="ANGARIACAO">ANGARIAÇÃO</option>
                                <option value="PRESTACAO">PRESTAÇÃO</option>
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
                            <label class="form-label">Descrição/observação</label>
                            <input type="text" class="form-control" id="avDesc" name="descricao"
                                placeholder="Ex.: Bônus por meta ou desconto por atraso">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Imposto (R$)</label>
                            <input type="text" class="form-control" id="avImpValView" disabled>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Líquido (R$)</label>
                            <input type="text" class="form-control" id="avLiqView" disabled>
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <div class="small text-muted">
                                * Para <strong>Crédito</strong>, o líquido será somado ao total do vendedor. Para
                                <strong>Débito</strong>, será subtraído.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-confirm-avulso">Salvar lançamento</button>
                </div>
            </form>
        </div>
    </div>


@endsection
