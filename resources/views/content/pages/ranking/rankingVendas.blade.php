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
    <div class="row mb-4" id="meta-resumo">
        <div class="col-md-4">
            <div class="card border shadow-sm text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Vendido</h6>
                    <h5 class="fw-bold mb-0 text-success js-total-vendido">R$ 0,00</h5>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border shadow-sm text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Meta Atual</h6>
                    <h5 class="fw-bold mb-0 text-primary js-meta-total">R$ 0,00</h5>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border shadow-sm text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Falta Para Atingir</h6>
                    <h5 class="fw-bold mb-0 text-danger js-meta-restante">R$ 0,00</h5>
                </div>
            </div>
        </div>
    </div>


    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 js-meta-nome">🏆 Ranking de Vendas - Mês Atual</h5>
        </div>

        <div class="card-body">
            {{-- Pódio --}}
            <div class="row justify-content-center text-center mb-5" id="ranking-podio">
                {{-- JS renderiza aqui --}}
            </div>

            <h6 class="text-muted mb-3">Outros vendedores</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle" id="ranking-tabela">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Vendedor</th>
                            <th>Total Vendido</th>
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
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📈 Vendas do Mês</h5>
            <small class="text-muted">Valores confirmados no mês corrente</small>
        </div>
        <div class="card-body">
            <div id="chart-vendas-mes" style="min-height: 320px;"></div>
        </div>
    </div>


@endsection
