@extends('layouts/layoutMaster')

@section('title', 'Comissionamento individual')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/comissionamento-vendedor.js')
@endsection

@section('content')

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="inputMes" class="form-label fw-semibold">Mês de referência</label>
                    <input type="month" id="inputMes" class="form-control"
                        value="{{ \Carbon\Carbon::now('America/Sao_Paulo')->format('Y-m') }}">
                </div>
                <div class="col-12 col-md-3">
                    <button id="btnBuscar" class="btn btn-primary w-100">
                        <i class="ri-search-line me-1"></i> Buscar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de resumo -->
    <div class="row g-3" id="cardsResumo" data-url="{{ url('/comissionamento/getCommissioningBySeller') }}">
        <!-- ajuste se tiver rota nomeada -->
        <div class="col-12 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Comissão Bruta</div>
                    <div class="fs-4 fw-bold" id="cardBruto">R$ 0,00</div>
                    <div class="text-muted small mt-1">Soma de (Valor Contrato × % Comissão)</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Imposto RF (10%)</div>
                    <div class="fs-4 fw-bold" id="cardImposto">R$ 0,00</div>
                    <div class="text-muted small mt-1">Total de imposto descontado</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Comissão Líquida</div>
                    <div class="fs-4 fw-bold" id="cardLiquido">R$ 0,00</div>
                    <div class="text-muted small mt-1">Bruto - Imposto</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Perfil de Comissionamento</div>
                    <div class="mt-1">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Grade</span>
                            <span class="fw-semibold" id="cfgGrade">—</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">% Comissão</span>
                            <span class="fw-semibold" id="cfgPercentual">—</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Salário</span>
                            <span class="fw-semibold" id="cfgSalario">—</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Imposto</span>
                            <span class="fw-semibold" id="cfgImposto">—</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Segunda linha de cards: Total a Receber -->
    <div class="row g-3 mt-0">
        <div class="col-12 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Total a Receber</div>
                    <div class="fs-4 fw-bold" id="cardTotalReceber">R$ 0,00</div>
                    <div class="text-muted small mt-1">Salário + Comissão Líquida</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela -->
    <div class="card mt-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex gap-2">
                <a id="btnPdf" class="btn btn-outline-primary btn-sm" target="_blank">
                    <i class="ri-file-pdf-line me-1"></i> Exportar PDF
                </a>
            </div>

            <div class="table-responsive mt-3">
                <table id="tabela-comissao" class="table table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Data Implantação</th>
                            <th>Contrato</th>
                            <th>Valor Contrato</th>
                            <th>% Comissão</th>
                            <th>Bruto</th>
                            <th>Imposto</th>
                            <th>Líquido</th>
                            <th>Angariação</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr class="fw-semibold">
                            <td colspan="4" class="text-end">Totais:</td>
                            <td id="ftBruto">R$ 0,00</td>
                            <td id="ftImposto">R$ 0,00</td>
                            <td id="ftLiquido">R$ 0,00</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr class="fw-semibold">
                            <td colspan="6" class="text-end">Total a Receber (Salário + Líquido):</td>
                            <td id="ftTotalReceber">R$ 0,00</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

@endsection
