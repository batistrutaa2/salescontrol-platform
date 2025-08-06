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
    <div class="row mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="card-title mb-1 text-muted">Total Vendido no Período</h6>
                    <h4 class="fw-bold text-primary" id="card-total-vendido">R$ 0,00</h4>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="card-title mb-1 text-muted">Total Comissionado</h6>
                    <h4 class="fw-bold text-success" id="card-total-comissionado">R$ 0,00</h4>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3 mt-3 mt-md-0">
            <label for="filtroPeriodo" class="form-label">Período</label>
            <input type="month" class="form-control" id="filtroPeriodo" value="{{ date('Y-m') }}">
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Comissionamento Disponível</h5>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="comissao-faturamento-table" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Vendedor</th>
                            <th>Total Implantado (R$)</th>
                            <th>% Comissão</th>
                            <th>Valor Comissão (R$)</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Faturamento -->
    <div class="modal fade" id="modalFaturar" tabindex="-1" aria-labelledby="modalFaturarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="formFaturarComissao" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar Faturamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body">
                        <dl class="row">
                            <dt class="col-sm-5">Vendedor:</dt>
                            <dd class="col-sm-7" id="faturar-vendedor">—</dd>

                            <dt class="col-sm-5">Período:</dt>
                            <dd class="col-sm-7" id="faturar-periodo">—</dd>

                            <dt class="col-sm-5">Valor Vendido:</dt>
                            <dd class="col-sm-7" id="faturar-vendido">—</dd>

                            <dt class="col-sm-5">% Comissão:</dt>
                            <dd class="col-sm-7" id="faturar-percentual">—</dd>

                            <dt class="col-sm-5">Valor Comissionado:</dt>
                            <dd class="col-sm-7" id="faturar-comissao">—</dd>
                        </dl>

                        <input type="hidden" id="faturar-user-id">
                        <input type="hidden" id="faturar-valor">
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Faturamento</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection
