@extends('layouts/layoutMaster')

@section('title', 'Relatorio de Vendas | ' . auth()->user()->name)

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
    @vite(['resources/assets/js/vendas.js'])
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

        <div class="input-group">
            <select class="form-select" aria-label="Selecionar ano" id="select-year">
                <option selected>Ano</option>
                @foreach ($anosDisponiveis as $ano)
                    <option value="{{ $ano }}">{{ $ano }}</option>
                @endforeach
            </select>
            <span class="input-group-text">
                <i class="ri-calendar-2-line"></i>
            </span>
        </div>
    </div>


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
                                    <h4 class="mb-0 me-2 js-valorCadastrado"></h4>
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
                                    <h4 class="mb-0 me-2 js-implantado"></h4>
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
                                    <h4 class="mb-0 me-2 js-quantidadeContatosImportados"></h4>
                                </div>
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
                                    <h4 class="mb-0 me-2 js-conversao"></h4>
                                </div>
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
    </div>


    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Vendas Detalhadas</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabela-vendas-detalhadas" class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome do Contrato</th>
                            <th>Status</th>
                            <th>Valor</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Preenchido via DataTables --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
