@extends('layouts/layoutMaster')

@section('title', 'Configuração - Ranking de Vendas')

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
    @vite(['resources/assets/js/rankingVendasPodio.js'])
@endsection

@section('content')
    <!-- KPIs Melhorados -->
    <div class="row g-4 mb-6" id="meta-resumo">
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Total Vendido</h6>
                            <h3 class="mb-0 text-success js-total-vendido">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </h3>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-success rounded">
                                <i class="ri-money-dollar-circle-line ri-26px"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Meta Atual</h6>
                            <h3 class="mb-0 text-primary js-meta-total">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </h3>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-primary rounded">
                                <i class="ri-flag-line ri-26px"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Falta Para Atingir</h6>
                            <h3 class="mb-0 js-meta-restante js-meta-restante-color">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </h3>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-warning rounded">
                                <i class="ri-arrow-up-circle-line ri-26px"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 js-progresso-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Progresso</h6>
                            <h3 class="mb-0 js-progresso-percent">0%</h3>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-info rounded">
                                <i class="ri-pie-chart-line ri-26px"></i>
                            </div>
                        </div>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar js-progresso-bar" role="progressbar" style="width: 0%"
                             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Pódio e Ranking -->
    <div class="card mb-6">
        <div class="card-header d-flex justify-content-between align-items-center pb-0">
            <h5 class="mb-0 js-meta-nome">🏆 Ranking de Vendas - Mês Atual</h5>
            <small class="text-muted">Atualização automática a cada 5s</small>
        </div>

        <div class="card-body">
            {{-- Pódio Melhorado --}}
            <div class="row justify-content-center g-4 mb-6" id="ranking-podio">
                {{-- JS renderiza aqui --}}
            </div>

            <hr class="my-5">

            <h6 class="text-muted mb-4 d-flex align-items-center">
                <i class="ri-trophy-line me-2"></i>
                Ranking Completo
            </h6>
            <div class="table-responsive">
                <table class="table align-middle" id="ranking-tabela">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Posição</th>
                            <th>Vendedor</th>
                            <th class="text-end" style="width: 150px;">Total Vendido</th>
                            <th style="width: 200px;">Progresso</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- JS renderiza aqui --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Vendas do mês (gráfico) --}}
    <div class="card mb-6">
        <div class="card-header d-flex justify-content-between align-items-center pb-0">
            <div>
                <h5 class="mb-1">📈 Desempenho Individual</h5>
                <p class="text-muted mb-0 small">Valores vendidos no mês atual</p>
            </div>
            <small class="text-muted">Atualizado a cada 1 min</small>
        </div>
        <div class="card-body">
            <div id="chart-vendas-mes" style="min-height: 380px;"></div>
        </div>
    </div>


@endsection
