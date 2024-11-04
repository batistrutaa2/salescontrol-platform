@extends('layouts/layoutMaster')

@section('title', 'Lista de Agendamentos')

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
    @vite(['resources/assets/js/agendamentos.js'])

@endsection


@section('page-style')
    <style>
        .table tr.selected,
        .table tr.selected td {
            background-color: transparent !important;
        }
    </style>

@section('content')
    <div class="card">
        <div class="card-datatable table-responsive text-center">
            <table class="datatables-schedules table ">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>CORRETOR</th>
                        <th>CLIENTE</th>
                        <th>HORARIO</th>
                        <th>ACOES</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
