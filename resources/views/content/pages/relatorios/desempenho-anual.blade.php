@extends('layouts/layoutMaster')

@section('title', 'Relatório de Desempenho Anual')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('page-script')
    @vite(['resources/assets/js/desempenho-anual.js'])
@endsection

@section('content')
    <div class="container-fluid">
        <!-- Filtros -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            <i class="ri-bar-chart-line me-2"></i>Relatório de Desempenho Anual
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Ano</label>
                                <select id="filtroAno" class="form-select">
                                    @foreach($anos as $ano)
                                        <option value="{{ $ano }}" {{ $ano == date('Y') ? 'selected' : '' }}>{{ $ano }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Vendedor</label>
                                <select id="filtroVendedor" class="form-select">
                                    <option value="">Todos os vendedores</option>
                                    @foreach($vendedores as $vendedor)
                                        <option value="{{ $vendedor->id }}">{{ $vendedor->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" id="btnFiltrar" class="btn btn-primary me-2">
                                    <i class="ri-filter-line me-1"></i>Filtrar
                                </button>
                                <button type="button" id="btnLimpar" class="btn btn-outline-secondary">
                                    <i class="ri-refresh-line me-1"></i>Limpar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div id="loading" class="text-center py-5" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-3">Carregando dados...</p>
        </div>

        <!-- Content -->
        <div id="conteudoRelatorio" style="display: none;">

            <!-- Cards de Estatísticas Gerais -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="mb-1 text-muted">Leads Trabalhados</p>
                                    <h3 class="mb-0" id="totalLeadsUnicos">0</h3>
                                </div>
                                <div class="avatar">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="ri-user-line ri-24px"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="mb-1 text-muted">Total de Vendas</p>
                                    <h3 class="mb-0" id="totalVendas">0</h3>
                                </div>
                                <div class="avatar">
                                    <span class="avatar-initial rounded bg-label-success">
                                        <i class="ri-shopping-cart-line ri-24px"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="mb-1 text-muted">Valor Total</p>
                                    <h3 class="mb-0" id="valorTotal">R$ 0,00</h3>
                                </div>
                                <div class="avatar">
                                    <span class="avatar-initial rounded bg-label-info">
                                        <i class="ri-money-dollar-circle-line ri-24px"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="mb-1 text-muted">Ticket Médio</p>
                                    <h3 class="mb-0" id="ticketMedio">R$ 0,00</h3>
                                </div>
                                <div class="avatar">
                                    <span class="avatar-initial rounded bg-label-warning">
                                        <i class="ri-line-chart-line ri-24px"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cards de Dados por Trimestre -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Análise por Trimestre</h5>
                        </div>
                        <div class="card-body">
                            <div class="row" id="dadosTrimestre">
                                <!-- Os cards dos trimestres serão inseridos aqui via JS -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="row mb-4">
                <!-- Evolução Mensal -->
                <div class="col-md-8 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Evolução Mensal</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chartEvolucaoMensal" height="80"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Taxa de Conversão -->
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Taxa de Conversão</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chartTaxaConversao" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Planos e Operadoras -->
            <div class="row mb-4">
                <!-- Top 10 Planos Mais Vendidos -->
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Top 10 Planos Mais Vendidos</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm" id="tabelaTopPlanos">
                                    <thead>
                                        <tr>
                                            <th>Plano</th>
                                            <th>Operadora</th>
                                            <th class="text-center">Vendas</th>
                                            <th class="text-end">Valor Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Dados inseridos via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Distribuição por Operadora -->
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Distribuição por Operadora</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chartOperadoras" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ranking de Vendedores (somente quando não filtrado) -->
            <div class="row mb-4" id="rankingSection" style="display: none;">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Ranking de Vendedores</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="tabelaRankingVendedores">
                                    <thead>
                                        <tr>
                                            <th>Posição</th>
                                            <th>Vendedor</th>
                                            <th class="text-center">Leads</th>
                                            <th class="text-center">Vendas</th>
                                            <th class="text-end">Valor Total</th>
                                            <th class="text-end">Ticket Médio</th>
                                            <th class="text-center">Conversão</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Dados inseridos via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalhes do Vendedor (quando filtrado) -->
            <div class="row mb-4" id="detalhesVendedorSection" style="display: none;">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Planos Favoritos - <span id="nomeVendedor"></span></h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm" id="tabelaPlanosFavoritos">
                                    <thead>
                                        <tr>
                                            <th>Plano</th>
                                            <th>Operadora</th>
                                            <th class="text-center">Total de Vendas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Dados inseridos via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
