@extends('layouts/layoutMaster')

@section('title', 'Relatorio de Vendas Analitico')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/vendasAnalitico.js'])
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Filtrar Vendas</h5>
            </div>
            <div class="card-body">
                <form id="filter-form" method="GET" action="">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="filter-month" class="form-label">Mês</label>
                            <select id="filter-month" name="month" class="form-select">
                                <option selected>Mês</option>
                                <option value="1">Janeiro</option>
                                <option value="2">Fevereiro</option>
                                <option value="3">Março</option>
                                <option value="4">Abril</option>
                                <option value="5">Maio</option>
                                <option value="6">Junho</option>
                                <option value="7">Julho</option>
                                <option value="8">Agosto</option>
                                <option value="9">Setembro</option>
                                <option value="10">Outubro</option>
                                <option value="11">Novembro</option>
                                <option value="12">Dezembro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="filter-year" class="form-label">Ano</label>
                            <select id="filter-year" name="year" class="form-select">
                                <option selected>Ano</option>
                                <option value="2025">2025</option>
                                <option value="2024">2024</option>
                                <option value="2023">2023</option>
                                <option value="2022">2022</option>
                                <option value="2021">2021</option>
                                <option value="2020">2020</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Relatório de Vendas</h5>
            </div>
            <div class="card-body">
                <table id="sales-table" class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Corretor</th>
                            <th>Cliente</th>
                            <th>Valor</th>
                            <th>Data Cadastro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="text-center">Nenhuma venda encontrada para o filtro selecionado.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
