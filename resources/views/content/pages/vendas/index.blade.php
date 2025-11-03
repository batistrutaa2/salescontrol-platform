@extends('layouts/layoutMaster')

@section('title', 'Relatorio de Vendas | ' . auth()->user()->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/swiper/swiper.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/cards-statistics.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/swiper/swiper.js', 'resources/assets/vendor/libs/chartjs/chartjs.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Preenchido via DataTables --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Visualizar Venda -->
    <div class="modal fade" id="modalVisualizarVenda" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h5 class="modal-title text-white">
                        <i class="ri-file-list-3-line me-2"></i>
                        Detalhes da Venda #<span id="venda-modal-id">-</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" style="background-color: #f8f9fa;">
                    <!-- Loading -->
                    <div id="venda-loading" class="text-center py-5">
                        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-3 fw-medium">Carregando detalhes da venda...</p>
                    </div>

                    <!-- Conteúdo -->
                    <div id="venda-content" class="d-none">
                        <!-- Card Informações do Contrato -->
                        <div class="card shadow-sm mb-3">
                            <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <h6 class="mb-0 text-white">
                                    <i class="ri-file-text-line me-2"></i>
                                    Informações do Contrato
                                </h6>
                            </div>
                            <div class="card-body">
                                <!-- Nome e Status em destaque -->
                                <div class="row mb-4">
                                    <div class="col-md-8">
                                        <h5 class="mb-1" id="venda-nome-contrato">-</h5>
                                        <small class="text-muted">
                                            <i class="ri-id-card-line me-1"></i>
                                            <span id="venda-cpf-cnpj">-</span>
                                        </small>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <span class="badge fs-6" id="venda-status-badge">-</span>
                                    </div>
                                </div>

                                <div class="row g-4">
                                    <!-- Plano e Operadora -->
                                    <div class="col-md-6">
                                        <div class="border-start border-primary border-3 ps-3">
                                            <small class="text-muted d-block">Plano</small>
                                            <p class="mb-1 fw-semibold" id="venda-plano">-</p>
                                            <small class="text-muted d-block mt-2">Operadora</small>
                                            <p class="mb-0 fw-semibold" id="venda-operadora">-</p>
                                        </div>
                                    </div>

                                    <!-- Valor e Vidas -->
                                    <div class="col-md-6">
                                        <div class="border-start border-success border-3 ps-3">
                                            <small class="text-muted d-block">Valor do Contrato</small>
                                            <h4 class="mb-1 text-success" id="venda-valor">-</h4>
                                            <small class="text-muted d-block mt-2">Quantidade de Vidas</small>
                                            <p class="mb-0 fw-semibold" id="venda-vidas">-</p>
                                        </div>
                                    </div>

                                    <!-- Datas -->
                                    <div class="col-md-4">
                                        <i class="ri-calendar-line text-primary me-1"></i>
                                        <small class="text-muted">Data Vigência</small>
                                        <p class="mb-0 fw-medium" id="venda-data-vigencia">-</p>
                                    </div>
                                    <div class="col-md-4">
                                        <i class="ri-calendar-check-line text-success me-1"></i>
                                        <small class="text-muted">Data Implantação</small>
                                        <p class="mb-0 fw-medium" id="venda-data-implantacao">-</p>
                                    </div>
                                    <div class="col-md-4">
                                        <i class="ri-price-tag-3-line text-info me-1"></i>
                                        <small class="text-muted">Tipo</small>
                                        <p class="mb-0"><span class="badge bg-info" id="venda-angariacao">-</span></p>
                                    </div>

                                    <!-- Vendedor e Proposta -->
                                    <div class="col-md-6">
                                        <i class="ri-user-star-line text-warning me-1"></i>
                                        <small class="text-muted">Vendedor</small>
                                        <p class="mb-0 fw-medium" id="venda-vendedor">-</p>
                                    </div>
                                    <div class="col-md-6">
                                        <i class="ri-file-list-2-line text-secondary me-1"></i>
                                        <small class="text-muted">Número da Proposta</small>
                                        <p class="mb-0 fw-medium" id="venda-numero-proposta">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Comissão -->
                        <div class="card shadow-sm">
                            <div class="card-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                <h6 class="mb-0 text-white">
                                    <i class="ri-money-dollar-circle-line me-2"></i>
                                    Informações de Comissão
                                </h6>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar flex-shrink-0 me-3">
                                                <span class="avatar-initial rounded" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                                    <i class="ri-currency-line ri-lg text-white"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block mb-1">Valor da Comissão</small>
                                                <h5 class="mb-0 fw-bold" id="venda-comissao-valor" style="color: #f5576c;">-</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar flex-shrink-0 me-3">
                                                <span class="avatar-initial rounded" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                                    <i class="ri-percent-line ri-lg text-white"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block mb-1">Percentual</small>
                                                <h5 class="mb-0 fw-bold" id="venda-comissao-percentual">-</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="ri-close-line me-1"></i>Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection
