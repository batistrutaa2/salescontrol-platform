@extends('layouts/layoutMaster')

@section('title', 'Backoffice - Contratos')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/vendas.js'])
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

    <div class="card mb-6">
        <div class="card-header">
            <h5 class="mb-0">Filtros:</h5>
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
        <div class="container col-12">
            <div class="card-datatable table-responsive">
                <table id="contracts_table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome Contrato</th>
                            <th>CPF/CNPJ</th>
                            <th>Telefone</th>
                            <th>Status</th>
                            <th>Valor Contrato</th>
                            <th>Data Criação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

    </div>
@endsection
