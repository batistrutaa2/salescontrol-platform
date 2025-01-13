@extends('layouts/layoutMaster')

@section('title', 'DataTables - Advanced Tables')

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
    @vite(['resources/assets/js/vendas.js'])
@endsection

@section('content')

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

@endsection
