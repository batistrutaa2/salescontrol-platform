@extends('layouts/layoutMaster')

@section('title', 'Financeiro - Relatório')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('page-style')
    <style>
        .card-metric { text-align: center; }
        .card-metric h4 { margin: 0; font-size: 1.5rem; }
        .card-metric span { font-size: 0.9rem; color: #6c757d; }
    </style>
@endsection

@section('page-script')
    @vite(['resources/assets/js/relatorio-financeiro.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    <h4 class="mb-4">📊 Relatório Financeiro</h4>

    <!-- 🔍 Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filtroRelatorio" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label for="data_inicial" class="form-label">Data Inicial</label>
                    <input type="text" id="data_inicial" class="form-control datepicker" placeholder="dd/mm/yyyy">
                </div>
                <div class="col-md-3">
                    <label for="data_final" class="form-label">Data Final</label>
                    <input type="text" id="data_final" class="form-control datepicker" placeholder="dd/mm/yyyy">
                </div>
                <div class="col-md-3">
                    <label for="operadora_id" class="form-label">Operadora</label>
                    <select id="operadora_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach($operadoras as $op)
                            <option value="{{ $op->id }}">{{ $op->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" id="btnFiltrar" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 📌 Cards de resumo -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card card-metric">
                <div class="card-body">
                    <h4 id="valorPrevisto">R$ 0,00</h4>
                    <span>Previsto</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-metric">
                <div class="card-body">
                    <h4 id="valorRecebido">R$ 0,00</h4>
                    <span>Recebido</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-metric">
                <div class="card-body">
                    <h4 id="valorAberto">R$ 0,00</h4>
                    <span>Em Aberto</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 📑 Detalhes por Operadora -->
    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-striped" id="relatorioTable">
                <thead>
                    <tr>
                        <th>Operadora</th>
                        <th>Previsto</th>
                        <th>Recebido</th>
                        <th>Em Aberto</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Dados via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
