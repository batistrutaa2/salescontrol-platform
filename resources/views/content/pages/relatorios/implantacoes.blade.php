@extends('layouts/layoutMaster')

@section('title', 'Relatório de Implantações')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('page-script')
    @vite(['resources/assets/js/relatorioImplantacoes.js'])
    <script>
        function limparFiltros() {
            document.getElementById('filtrosForm').reset();
            $('#filtrosForm').trigger('submit');
        }

        function atualizarDados() {
            location.reload();
        }
    </script>
@endsection

@section('content')
    <div class="container-fluid">
        <!-- Filtros -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Relatório de Implantações</h4>
                    </div>
                    <div class="card-body">
                        <div class="filter-section">
                            <form id="filtrosForm">
                                @csrf

                                <div class="filter-group">
                                    <div class="filter-group-title">
                                        <i class="ti ti-calendar"></i>Período
                                    </div>
                                    <div class="row g-3 mt-5">
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label">Ano</label>
                                            <select id="filtroAno" name="ano" class="form-select">
                                                <option value="">Todos os anos</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label">Mês</label>
                                            <select id="filtroMes" name="mes" class="form-select">
                                                <option value="">Todos os meses</option>
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
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label">Data Início</label>
                                            <input type="date" id="dataInicio" name="data_inicio" class="form-control">
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label">Data Fim</label>
                                            <input type="date" id="dataFim" name="data_fim" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="filter-group  mt-5">
                                    <div class="row g-3  mt-5">
                                        <div class="col-md-6">
                                            <label class="form-label">Vendedor</label>
                                            <select id="filtroVendedor" name="vendedor_id" class="form-select">
                                                <option value="">Todos os vendedores</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Operadora</label>
                                            <select id="filtroOperadora" name="operadora" class="form-select">
                                                <option value="">Todas as operadoras</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="filter-group">
                                    <div class="filter-group-title mt-3">
                                        <i class="ti ti-settings"></i>Ações
                                    </div>
                                    <div class="filter-actions mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-search me-1"></i>Filtrar
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="limparFiltros()">
                                            <i class="ti ti-refresh me-1"></i>Limpar
                                        </button>
                                        <button type="button" class="btn btn-info" onclick="atualizarDados()">
                                            <i class="ti ti-reload me-1"></i>Atualizar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards de Resumo -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title text-white">Total de Contratos</h5>
                        <h2 class="text-white" id="totalContratos">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title text-white">Valor Implantado</h5>
                        <h2 class="text-white" id="valorTotal">R$ 0,00</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title text-white">Total de Vidas</h5>
                        <h2 class="text-white" id="totalVidas">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title text-white">Ticket Médio</h5>
                        <h2 class="text-white" id="ticketMedio">R$ 0,00</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Implantações por Mês</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="vendasPorMesChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Implantações por Vendedor</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="vendasPorVendedorChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Implantações por Operadora</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="vendasPorOperadoraChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Top 10 Planos</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="vendasPorPlanoChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de Implantações -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Lista de Implantações</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="implantacoesTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Data Implantação</th>
                                        <th>Contrato</th>
                                        <th>CPF/CNPJ</th>
                                        <th>Vendedor</th>
                                        <th>Operadora</th>
                                        <th>Plano</th>
                                        <th>Valor</th>
                                        <th>Vidas</th>
                                    </tr>
                                </thead>
                                <tbody id="implantacoesTableBody">
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none"
        style="background: rgba(0,0,0,0.5); z-index: 9999;">
        <div class="d-flex justify-content-center align-items-center h-100">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>
    </div>
@endsection
