@extends('layouts/layoutMaster')

@section('title', 'Lista de leads - (BASE LEGADA)')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('page-style')
    <style>
        .table tr.selected,
        .table tr.selected td {
            background-color: transparent !important;
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/visualizar-leads-legado.js'])

@endsection


@section('page-style')
    <style>
        .table tr.selected,
        .table tr.selected td {
            background-color: transparent !important;
        }
    </style>
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
            <div class="d-flex justify-content-between align-items-center row gap-4 gap-md-0">
                <div class="col-md-3 product_status"></div>
                <div class="col-md-3 product_category"></div>
                <div class="col-md-3 product_stock"></div>
            </div>
            <div class="card-datatable table-responsive">
                <table class="datatables-products table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Corretor</th>
                            <th>Cliente</th>
                            <th>CARTEGORA</th>
                            <th>TELEFONE</th>
                            <th>AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contatos as $contato)
                            <tr>
                                <td>{{ $contato->id }}</td>
                                <td>{{ $contato->nome_corretor }}</td>
                                <td>{{ $contato->nome_cliente }}</td>
                                <td>{{ $contato->category }}</td>
                                <td>{{ $contato->telefone }}</td>
                                <td>
                                    <button type="button" class="btn btn-success btn--twitter"
                                        data-idMailing="{{ $contato->id }}">
                                        Visualizar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endsection
