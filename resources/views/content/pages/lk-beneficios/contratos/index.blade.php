@extends('layouts/layoutMaster')

@section('title', 'LK Benefícios - Contratos')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/dashboard-analytics.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/lk-beneficios-contratos-index.js'])
@endsection

@section('content')
@php
    use App\Modules\LkBeneficios\Enums\StatusContrato;
    use App\Modules\LkBeneficios\Enums\TipoBeneficio;
@endphp
<div class="dashboard-wrapper">

    {{-- Header --}}
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-text">
                <span class="greeting-label">LK Benefícios</span>
                <h1 class="main-title">Contratos</h1>
                <p class="subtitle">Apólices, beneficiários e movimentações</p>
            </div>
            <div class="header-filters">
                <div class="filter-group">
                    <div class="filter-item">
                        <span class="filter-label">Status</span>
                        <select id="lkb-filtro-status" class="filter-select">
                            <option value="">Todos</option>
                            @foreach($statusList as $s)
                                <option value="{{ $s }}">{{ StatusContrato::label($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-item">
                        <span class="filter-label">Tipo</span>
                        <select id="lkb-filtro-tipo" class="filter-select">
                            <option value="">Todos</option>
                            @foreach($tiposList as $t)
                                <option value="{{ $t }}">{{ TipoBeneficio::label($t) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="tables-section">
        <div class="table-card">
            <div class="table-header">
                <div class="table-title-group">
                    <div class="table-icon cadastrados">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="table-title">Lista de Contratos</h3>
                        <span class="table-subtitle">Busque por nome, CPF/CNPJ, apólice ou proposta</span>
                    </div>
                </div>
                <div style="min-width: 280px;">
                    <input type="search" id="lkb-filtro-busca" class="form-control form-control-sm"
                           placeholder="Buscar..." style="border-radius: 20px;">
                </div>
            </div>
            <div class="table-body">
                <table id="lkb-tabela-contratos" class="custom-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>CPF/CNPJ</th>
                            <th>Produto</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th class="text-end">Valor Mensal</th>
                            <th>Vigência</th>
                            <th>Corretor</th>
                            <th></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    window.lkBeneficios = {
        datatableUrl: @json(route('lk-beneficios.contratos.datatable')),
        showUrlTemplate: @json(route('lk-beneficios.contratos.show', ['id' => '__ID__'])),
    };
</script>
@endsection
