@extends('layouts/layoutMaster')

@section('title', 'BackOffice - Contratos')

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/assets/js/backoffice.js'])
@endsection

@section('content')
    @if (session('status') == 'success')
        <div class="alert alert-solid-success d-flex align-items-center" role="alert">
            <span class="alert-icon rounded">
                <i class="ri-checkbox-circle-line ri-22px"></i>
            </span>
            {{ session('message') }}
        </div>
    @elseif(session('status') == 'error')
        <div class="alert alert-danger">
            {{ session('message') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Filtro:</h5>
            <div class="row pt-4">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Data Inicial</label>
                    <input type="date" id="start_date" class="form-control">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">Data Final</label>
                    <input type="date" id="end_date" class="form-control">
                </div>
                <div class="col-md-4 d-flex align-items-end ">
                    <button id="filter_button" class="btn btn-primary w-65 r">Filtrar</button>

                    <button id="clear_filter" class="btn btn-success w-65">Limpar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Ajax Sourced Server-side -->
    <div class="card mt-5">
        <h5 class="card-header">Contratos</h5>
        <div class="card-datatable text-nowrap">
            <table class="datatables-ajax table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Corretor</th>
                        <th>Nome Contrato</th>
                        <th>Status</th>
                        <th>Valor Contrato</th>
                        <th>Data Criação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <!--/ Ajax Sourced Server-side -->


    <div class="modal fade" id="modalcomments" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-simple">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-6">
                        <h4 class="mb-2">Alterar Status</h4>
                        <p>Selecione o status atual do contrato</p>
                    </div>
                    <form id="transferLead" class="row" action="{{ route('backoffice.alterStatusContract') }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="idSale" name="idSale" value="">
                        <div class="col">
                            <div class="form-floating form-floating-outline">
                                <select class="select2  form-select" id="label" name="tabulacao_id">
                                    <option value="">Selecione o Status</option>
                                    @foreach ($tabulacoes as $tabulation)
                                        <option value="{{ $tabulation->id }}">{{ strtoupper($tabulation->descricao) }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="ecommerce-product-name">Selecione o status</label>
                            </div>
                            <div id="proof-group" class="mt-3" style="display: none;">
                                <label for="comprovante" class="form-label">Comprovante de Pagamento</label>
                                <input type="file" id="comprovante" name="comprovante" class="form-control"
                                    accept="image/*,application/pdf">
                            </div>
                            <div>
                                <button class="btn btn-danger   btn--twitter mt-5">Alterar Status</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
