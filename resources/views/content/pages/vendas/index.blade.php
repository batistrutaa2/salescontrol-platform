@extends('layouts/layoutMaster')

@section('title', 'eCommerce Product List - Apps')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/vendas.js'])
@endsection

@section('content')
    <!-- Product List Widget -->
    <div class="card mb-6">
        <div class="card-widget-separator-wrapper">
            <div class="card-body card-widget-separator">
                <div class="row gy-4 gy-sm-1">
                    <div class="col-sm-6 col-lg-3">
                        <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
                            <div>
                                <p class="mb-1">Contratos Cadastrados</p>
                                <h4 class="mb-1">R$ 5,345.43</h4>
                                <p class="mb-0"><span class="me-2">5 Contratos</span></p>
                            </div>
                            <div class="avatar me-sm-6">
                                <span class="avatar-initial rounded bg-label-secondary text-heading">
                                    <i class="ri-exchange-line ri-24px"></i>
                                </span>
                            </div>
                        </div>
                        <hr class="d-none d-sm-block d-lg-none me-6">
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-4 pb-sm-0">
                            <div>
                                <p class="mb-1">Contratos implantados</p>
                                <h4 class="mb-1">R$ 674,347.12</h4>
                                <p class="mb-0"><span class="me-2">21 Contratos</span></p>
                            </div>
                            <div class="avatar me-lg-6">
                                <span class="avatar-initial rounded bg-label-secondary text-heading">
                                    <i class="ri-bar-chart-fill ri-24px"></i>
                                </span>
                            </div>
                        </div>
                        <hr class="d-none d-sm-block d-lg-none">
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0 card-widget-3">
                            <div>
                                <p class="mb-1">Estornos</p>
                                <h4 class="mb-1">R$ 14,235.12</h4>
                                <p class="mb-0">6k orders</p>
                            </div>
                            <div class="avatar me-sm-6">
                                <span class="avatar-initial rounded bg-label-secondary text-heading">
                                    <i class="ri-corner-up-left-line ri-24px"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-1">Conversão mensal</p>
                                <h4 class="mb-1">% 8,0</h4>
                                <p class="mb-0"><span class="me-2">5 Contratos</span></p>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-secondary text-heading">
                                    <i class="ri-money-dollar-circle-line ri-24px"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product List Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Filter</h5>
            <div class="d-flex justify-content-between align-items-center row pt-4 gap-4 gap-md-0">
                <div class="col-md-4 product_status"></div>
                <div class="col-md-4 product_category"></div>
                <div class="col-md-4 product_stock"></div>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-products table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome Contrato</th>
                        <th>Email</th>
                        <th>Telefone (Principal)</th>
                        <th>Valor Contrato</th>
                        <th>Data Vigencia</th>
                        <th>Status</th>
                        <th>Feito em:</th>
                        <th>Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
