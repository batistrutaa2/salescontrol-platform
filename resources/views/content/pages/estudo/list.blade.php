@extends('layouts/layoutMaster')

@section('title', 'Lista de Estudos de Planos de Saúde')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/lista-estudo.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/list-estudo.js'])
@endsection

@section('content')
<div class="es-wrapper">

    <!-- Page Header -->
    <div class="es-page-header">
        <div class="es-header-content">
            <div class="es-header-text">
                <span class="es-greeting-label">Estudos</span>
                <h1 class="es-main-title">Estudos de Saúde</h1>
                <p class="es-subtitle">Gerencie cotações e estudos de planos</p>
            </div>
            <a href="{{ route('estudo.create') }}" class="es-btn-new">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Novo Estudo
            </a>
        </div>
    </div>

    <!-- Data Table -->
    <div class="es-table-card">
        <div class="es-table-header">
            <div class="es-table-title-group">
                <div class="es-table-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="es-table-title">Lista de Estudos</h3>
                    <span class="es-table-subtitle">Cotações e propostas</span>
                </div>
            </div>
        </div>
        <div class="es-table-body">
            <table class="datatables-customers table" id="tabela-estudos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Estudo</th>
                        <th>Usuário</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

</div>
@endsection
