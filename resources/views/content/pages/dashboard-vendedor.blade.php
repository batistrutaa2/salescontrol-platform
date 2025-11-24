@extends('layouts/layoutMaster')

@section('title', 'Dashboard Vendedor')

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
    @vite(['resources/assets/js/dashboard-vendedor.js'])
@endsection

@section('content')

    <div class="d-flex align-items-center mb-10">
        <!-- Select para Mês -->
        <div class="input-group me-3">
            <select class="form-select form-control" aria-label="Selecionar mês" id="select-month">
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
            <span class="input-group-text">
                <i class="ri-calendar-2-line"></i>
            </span>
        </div>

        <!-- Select para Ano -->
        <div class="input-group">
            <select class="form-select" aria-label="Selecionar ano" id="select-year">
                <option selected>Ano</option>
                <option value="2025">2025</option>
                <option value="2024">2024</option>
                <option value="2023">2023</option>
                <option value="2022">2022</option>
                <option value="2021">2021</option>
                <option value="2020">2020</option>
            </select>
            <span class="input-group-text">
                <i class="ri-calendar-2-line"></i>
            </span>
        </div>
    </div>

    <div class="row g-6">
        <!-- Sales Registered -->
        <div class="col-xl-6 col-lg-6 col-md-6 mt-10">
            <div class="card">
                <div class="row">
                    <div class="col-6">
                        <div class="card-body">
                            <div class="card-info">
                                <h6 class="mb-4 pb-1 text-nowrap">Vendas Cadastradas</h6>
                                <div class="d-flex align-items-center mb-3">
                                    <h4 class="mb-0 me-2 js-sales-registered">R$ 0,00</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="h-100 position-relative">
                            <img src="{{ asset('assets/img/illustrations/illustration-1.png') }}" alt="Ratings"
                                class="position-absolute card-img-position scaleX-n1-rtl bottom-0 w-auto end-0 me-3 me-xl-0 me-xxl-3 pe-2"
                                width="95">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Implanted -->
        <div class="col-xl-6 col-lg-6 col-md-6 mt-10">
            <div class="card">
                <div class="row">
                    <div class="col-6">
                        <div class="card-body">
                            <div class="card-info">
                                <h6 class="mb-4 pb-1 text-nowrap">Vendas Implantadas</h6>
                                <div class="d-flex align-items-center mb-3">
                                    <h4 class="mb-0 me-2 js-sales-implanted">R$ 0,00</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="h-100 position-relative">
                            <img src="{{ asset('assets/img/illustrations/illustration-2.png') }}" alt="Ratings"
                                class="position-absolute card-img-position scaleX-n1-rtl bottom-0 w-auto end-0 me-3 me-xl-0 me-xxl-3 pe-2"
                                width="81">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leads Status Chart -->
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header header-elements">
                    <h5 class="card-title mb-0">Status dos Leads</h5>
                </div>
                <div class="card-body">
                    <canvas id="leadsStatusChart" class="chartjs" data-height="400"></canvas>
                </div>
            </div>
        </div>

        <!-- Monthly Overview Chart -->
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header header-elements">
                    <h5 class="card-title mb-0">Visão Geral Mensal</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyOverviewChart" class="chartjs" data-height="400"></canvas>
                </div>
            </div>
        </div>

    </div>
@endsection
