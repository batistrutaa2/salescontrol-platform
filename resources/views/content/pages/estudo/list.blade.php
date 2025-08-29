@extends('layouts/layoutMaster')

@section('title', 'Lista de Estudos de Planos de Saúde')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/list-estudo.js'])
@endsection

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">📊 Estudos Saúde</h4>
            <a href="{{ route('estudo.create') }}" class="btn btn-primary">
                <i class="ti ti-plus"></i> Novo Estudo
            </a>
        </div>
        <div class="card">
            <div class="card-body">
                <table class="datatables-customers table" id="tabela-estudos">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Estudo</th>
                            <th>Usuario</th>
                            <th>Criado em:</th>
                            <th class="text-nowrap">AÇÕES</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection
