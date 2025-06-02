@extends('layouts/layoutMaster')

@section('title', 'Relatorio - Atividades')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/swiper/swiper.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/cards-statistics.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/swiper/swiper.js', 'resources/assets/vendor/libs/chartjs/chartjs.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/atividades.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="ti ti-filter me-2"></i>Filtros de Análise
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Data Início</label>
                    <input type="date" class="form-control" id="data-inicio" value="{{ date('Y-m-01') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Fim</label>
                    <input type="date" class="form-control" id="data-fim" value="{{ date('Y-m-t') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Vendedor</label>
                    <select class="form-select select2" id="vendedor-select">
                     <option value="">Todos os vendedores</option>
                      @foreach ($users as $user)
                        <option value="{{$user->id}}">{{$user->name}}</option>
                      @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo de Lead</label>
                    <select class="form-select" id="tipo-lead">
                        <option value="todos">Todos os leads</option>
                        <option value="mes">Leads do mês</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button class="btn btn-primary me-2" id="aplicar-filtros">
                        <i class="ti ti-search me-1"></i>Aplicar Filtros
                    </button>
                    <button class="btn btn-label-secondary" id="limpar-filtros">
                        <i class="ti ti-refresh me-1"></i>Limpar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="card-icon mb-3">
                        <div class="avatar">
                            <div class="avatar-initial bg-primary rounded">
                                <i class="ti ti-clipboard-list ti-md"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-info">
                        <h4 class="card-title mb-2" id="total-atividades">0</h4>
                        <p class="text-muted mb-1">Total de Atividades</p>
                        <small class="text-success">
                            <i class="ti ti-chevron-up me-1"></i>
                            <span id="variacao-atividades">0%</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="card-icon mb-3">
                        <div class="avatar">
                            <div class="avatar-initial bg-success rounded">
                                <i class="ti ti-users ti-md"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-info">
                        <h4 class="card-title mb-2" id="total-leads">0</h4>
                        <p class="text-muted mb-1">Total de Leads</p>
                        <small class="text-success">
                            <i class="ti ti-chevron-up me-1"></i>
                            <span id="variacao-leads">0%</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="card-icon mb-3">
                        <div class="avatar">
                            <div class="avatar-initial bg-warning rounded">
                                <i class="ti ti-chart-line ti-md"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-info">
                        <h4 class="card-title mb-2" id="media-atividades">0</h4>
                        <p class="text-muted mb-1">Média por Lead</p>
                        <small class="text-info">
                            <i class="ti ti-minus me-1"></i>
                            <span id="variacao-media">0%</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="card-icon mb-3">
                        <div class="avatar">
                            <div class="avatar-initial bg-info rounded">
                                <i class="ti ti-user-check ti-md"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-info">
                        <h4 class="card-title mb-2" id="vendedores-ativos">0</h4>
                        <p class="text-muted mb-1">Vendedores Ativos</p>
                        <small class="text-success">
                            <i class="ti ti-chevron-up me-1"></i>
                            <span id="variacao-vendedores">0%</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row">
        <!-- Gráfico de Atividades por Período -->
        <div class="col-xl-8 col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Atividades por Período</h5>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="periodo-grafico" id="diario" value="diario" checked>
                        <label class="btn btn-outline-primary btn-sm" for="diario">Diário</label>
                        <input type="radio" class="btn-check" name="periodo-grafico" id="semanal" value="semanal">
                        <label class="btn btn-outline-primary btn-sm" for="semanal">Semanal</label>
                        <input type="radio" class="btn-check" name="periodo-grafico" id="mensal" value="mensal">
                        <label class="btn btn-outline-primary btn-sm" for="mensal">Mensal</label>
                    </div>
                </div>
                <div class="card-body">
                    <div id="atividadesPeriodoChart"></div>
                </div>
            </div>
        </div>

        <!-- Ranking de Vendedores -->
        <div class="col-xl-4 col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ranking de Vendedores</h5>
                </div>
                <div class="card-body">
                    <div id="rankingVendedoresChart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Distribuição por Tabulação -->
        <div class="col-xl-6 col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Distribuição por Tabulação</h5>
                </div>
                <div class="card-body">
                    <div id="tabulacaoChart"></div>
                </div>
            </div>
        </div>

        <!-- Atividades por Hora -->
        <div class="col-xl-6 col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Atividades por Hora do Dia</h5>
                </div>
                <div class="card-body">
                    <div id="atividadesHoraChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela Detalhada -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Detalhamento por Lead</h5>
            <div>
                <button class="btn btn-primary btn-sm" id="atualizar-tabela">
                    <i class="ti ti-refresh me-1"></i>Atualizar
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="tabela-detalhes">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Tabulação</th>
                            <th>Total Atividades</th>
                            <th>Última Atividade</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dados serão carregados via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<!-- Modal Detalhes do Lead -->
<div class="modal fade" id="modalDetalhesLead" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-user me-2"></i>
                    Detalhes do Lead - <span id="modal-nome-cliente"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Informações Básicas -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Informações Básicas</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">ID:</small>
                                        <p class="mb-2" id="modal-lead-id">-</p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Data Criação:</small>
                                        <p class="mb-2" id="modal-data-criacao">-</p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Telefone:</small>
                                        <p class="mb-2" id="modal-telefone">-</p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Email:</small>
                                        <p class="mb-2" id="modal-email">-</p>
                                    </div>
                                    <div class="col-12">
                                        <small class="text-muted">Tabulação Atual:</small>
                                        <p class="mb-0" id="modal-tabulacao">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Estatísticas</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h4 class="text-primary mb-1" id="modal-total-comentarios">0</h4>
                                        <small class="text-muted">Comentários</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-success mb-1" id="modal-total-atividades">0</h4>
                                        <small class="text-muted">Atividades</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-warning mb-1" id="modal-ultimo-contato">-</h4>
                                        <small class="text-muted">Último Contato</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs" id="detalhesTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="comentarios-tab" data-bs-toggle="tab" data-bs-target="#comentarios" type="button" role="tab">
                            <i class="ti ti-message-circle me-1"></i>
                            Comentários (<span id="count-comentarios">0</span>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="atividades-tab" data-bs-toggle="tab" data-bs-target="#atividades" type="button" role="tab">
                            <i class="ti ti-activity me-1"></i>
                            Histórico de Atividades (<span id="count-atividades">0</span>)
                        </button>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="detalhesTabContent">
                    <!-- Tab Comentários -->
                    <div class="tab-pane fade show active" id="comentarios" role="tabpanel">
                        <div id="lista-comentarios" style="max-height: 400px; overflow-y: auto;">
                            <!-- Comentários serão carregados aqui -->
                        </div>
                    </div>

                    <!-- Tab Atividades -->
                    <div class="tab-pane fade" id="atividades" role="tabpanel">
                        <div id="lista-atividades" style="max-height: 400px; overflow-y: auto;">
                            <!-- Atividades serão carregadas aqui -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

@endsection
