@extends('layouts/layoutMaster')

@section('title', 'Relatorio - Preditiva')

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
    @vite(['resources/assets/js/relatorioPreditiva.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Relatórios /</span> Atividade Preditiva
    </h4>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Filtros</h5>
        </div>
        <div class="card-body">
          <form id="form-filtro-preditiva" method="POST" action="{{ route('relatorios.preditiva.buscar') }}">
              @csrf
              <div class="row g-3">
                  <div class="col-md-4">
                      <label class="form-label" for="data_inicio">Data Início</label>
                      <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="{{ date('Y-m-d', strtotime('-30 days')) }}" />
                  </div>
                  <div class="col-md-4">
                      <label class="form-label" for="data_fim">Data Fim</label>
                      <input type="date" id="data_fim" name="data_fim" class="form-control" value="{{ date('Y-m-d') }}" />
                  </div>
                  <div class="col-md-3">
                      <label class="form-label" for="usuario_id">Usuário</label>
                      <select id="usuario_id" name="usuario_id" class="select2 form-select" data-allow-clear="true">
                          <option value="">Todos os usuários</option>
                          @foreach($usuarios as $usuario)
                              <option value="{{ $usuario->id }}">{{ $usuario->name }}</option>
                          @endforeach
                      </select>
                  </div>
                  <div class="col-md-1 d-flex align-items-end">
                      <button type="submit" class="btn btn-primary w-100">
                          <i class="ri-search-line me-1"></i> Buscar
                      </button>
                  </div>
              </div>
          </form>
        </div>
    </div>

    <!-- Cards de Resumo -->
    <div class="row mb-4" id="cards-resumo">
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Total de Contatos</h5>
                            <small class="text-muted">Período selecionado</small>
                        </div>
                        <div class="avatar bg-label-primary p-2">
                            <i class="ri-user-line ri-xl"></i>
                        </div>
                    </div>
                    <h2 class="mt-3 mb-0" id="total-contatos">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Convertidos</h5>
                            <small class="text-muted">Adicionados ao Kanban</small>
                        </div>
                        <div class="avatar bg-label-success p-2">
                            <i class="ri-check-line ri-xl"></i>
                        </div>
                    </div>
                    <h2 class="mt-3 mb-0" id="total-convertidos">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Descartados</h5>
                            <small class="text-muted">Não interessados</small>
                        </div>
                        <div class="avatar bg-label-danger p-2">
                            <i class="ri-close-line ri-xl"></i>
                        </div>
                    </div>
                    <h2 class="mt-3 mb-0" id="total-descartados">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Taxa de Conversão</h5>
                            <small class="text-muted">Convertidos / Total</small>
                        </div>
                        <div class="avatar bg-label-info p-2">
                            <i class="ri-percent-line ri-xl"></i>
                        </div>
                    </div>
                    <h2 class="mt-3 mb-0" id="taxa-conversao">0%</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Atividade por Dia</h5>
                </div>
                <div class="card-body">
                    <div id="grafico-atividade-diaria" style="height: 300px;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Distribuição por Status</h5>
                </div>
                <div class="card-body">
                    <div id="grafico-status" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de Tabulações -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Distribuição por Tabulação</h5>
                </div>
                <div class="card-body">
                    <div id="grafico-tabulacao" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Atividades -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Detalhamento de Atividades</h5>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table table-bordered table-hover dt-complex-header" id="tabela-atividades">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Usuário</th>
                        <th>Cliente</th>
                        <th>Telefone</th>
                        <th>Status</th>
                        <th>Tabulação</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Dados serão carregados via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
