@extends('layouts/layoutMaster')

@section('title', 'Dashboard Inicial')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/swiper/swiper.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/cards-statistics.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/swiper/swiper.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/cards-statistics.js'])
@endsection

@section('content')

    <div class="row g-6">
        <!-- Ratings -->
        <div class="col-xl-3 col-lg-6 col-sm-6 mt-10">
            <div class="card">
                <div class="row">
                    <div class="col-6">
                        <div class="card-body">
                            <div class="card-info">
                                <h6 class="mb-4 pb-1 text-nowrap">Contratos Cadastrados</h6>
                                <div class="d-flex align-items-center mb-3">
                                    <h4 class="mb-0 me-2">R$ 25.000,00</h4>
                                </div>
                                <div class="badge bg-label-primary rounded-pill">Year of 2021</div>
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
        <!--/ Ratings -->

        <!-- Sessions -->
        <div class="col-xl-3 col-lg-6 col-sm-6 mt-10">
            <div class="card">
                <div class="row">
                    <div class="col-6">
                        <div class="card-body">
                            <div class="card-info">
                                <h6 class="mb-4 pb-1 text-nowrap">Contratos Implantados</h6>
                                <div class="d-flex align-items-center mb-3">
                                    <h4 class="mb-0 me-2">R$ 25.000,00</h4>
                                </div>
                                <div class="badge bg-label-secondary rounded-pill">Last Week</div>
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
        <!--/ Sessions -->

        <!-- Customers -->
        <div class="col-xl-3 col-lg-6 col-sm-6 mt-10">
            <div class="card">
                <div class="row">
                    <div class="col-6">
                        <div class="card-body">
                            <div class="card-info">
                                <h6 class="mb-4 pb-1 text-nowrap">Contatos importados</h6>
                                <div class="d-flex align-items-center mb-3">
                                    <h4 class="mb-0 me-2">2,856</h4>
                                </div>
                                <div class="badge bg-label-info rounded-pill">Daily Customers</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="h-100 position-relative">
                            <img src="{{ asset('assets/img/illustrations/illustration-3.png') }}" alt="Ratings"
                                class="position-absolute card-img-position scaleX-n1-rtl bottom-0 w-auto end-0 me-3 me-xl-0 me-xxl-3 pe-2"
                                width="84">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Customers -->

        <!-- Total Orders -->
        <div class="col-xl-3 col-lg-6 col-sm-6 mt-10">
            <div class="card">
                <div class="row">
                    <div class="col-6">
                        <div class="card-body">
                            <div class="card-info">
                                <h6 class="mb-4 pb-1 text-nowrap">Conversão Mensal</h6>
                                <div class="d-flex align-items-center mb-3">
                                    <h4 class="mb-0 me-2">16%</h4>
                                </div>
                                <div class="badge bg-label-warning rounded-pill">Last Month</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="h-100 position-relative">
                            <img src="{{ asset('assets/img/illustrations/illustration-4.png') }}" alt="Ratings"
                                class="position-absolute card-img-position scaleX-n1-rtl bottom-0 w-auto end-0 me-3 me-xl-0 me-xxl-3 pe-2"
                                width="78">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Total Orders -->
    </div>
@endsection
