@extends('layouts/layoutMaster')

@section('title', 'Cadastro de empresas')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/empresa.js')
@endsection

@section('content')

    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-6">
        <div>
            <h4 class="mb-1">Empresas</h4>
            <p class="text-body-secondary mb-0">Cadastre corretoras e selecione no topo qual operação deseja administrar.</p>
        </div>
        <button type="button" class="btn btn-primary text-nowrap align-self-start align-self-sm-center"
            data-company-create-trigger aria-controls="offcanvasEcommerceCustomerAdd">
            <i class="ri-add-line me-1" aria-hidden="true"></i>
            Cadastrar empresa
        </button>
    </div>

    @if (!app(\App\Support\TenantContext::class)->isResolved())
        <div class="alert alert-warning" role="status">
            <h5 class="alert-heading mb-1">Selecione a empresa que deseja administrar</h5>
            <p class="mb-0">Nenhuma empresa está ativa. Use o seletor no topo ou cadastre a primeira corretora para liberar os módulos operacionais.</p>
        </div>
    @endif

    <!-- customers List Table -->
    <div class="card">

        <div class="card-datatable table-responsive">
            <table class="datatables-customers table" data-source="{{ route('empresa.getAllCompanies') }}">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>NOME</th>
                        <th class="text-nowrap">CNPJ</th>
                        <th>EMAIL</th>
                        <th>TELEFONE</th>
                        <th class="text-nowrap">CRIADO EM</th>
                    </tr>
                </thead>
            </table>
        </div>
        <!-- Offcanvas to add new customer -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEcommerceCustomerAdd"
            aria-labelledby="offcanvasEcommerceCustomerAddLabel">
            <div class="offcanvas-header border-bottom">
                <h5 id="offcanvasEcommerceCustomerAddLabel" class="offcanvas-title">Criar Empresa</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body mx-0 flex-grow-0">
                <form method="POST" action="{{ route('empresa.createCompanies') }}" class="ecommerce-customer-add pt-0" id="eCommerceCustomerAddForm">
                    @csrf
                    <div id="company-form-feedback" class="alert alert-danger d-none" role="alert" tabindex="-1"></div>
                    <div class="ecommerce-customer-add-basic mb-6">
                        <h6 class="mb-5">Informações básicas</h6>
                        <div class="form-floating form-floating-outline mb-5">
                            <input type="text" class="form-control" id="ecommerce-customer-add-name"
                                placeholder="Nome da corretora" name="customerName" maxlength="255" autocomplete="organization" required />
                            <label for="ecommerce-customer-add-name">Nome da empresa*</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-5">
                            <input type="text" class="form-control" id="ecommerce-customer-add-document"
                                placeholder="CPF ou CNPJ" name="customerCnpj" maxlength="18" inputmode="numeric"
                                autocomplete="off" aria-describedby="empresa-documento-ajuda" required />
                            <label for="ecommerce-customer-add-document">CNPJ ou CPF*</label>
                            <small id="empresa-documento-ajuda" class="form-text">O documento identifica a empresa e não pode ser reutilizado.</small>
                        </div>
                        <div class="form-floating form-floating-outline mb-5">
                            <input type="email" id="ecommerce-customer-add-email" class="form-control"
                                placeholder="contato@empresa.com.br" name="customerEmail" maxlength="255"
                                autocomplete="email" required />
                            <label for="ecommerce-customer-add-email">E-mail*</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-5">
                            <input type="tel" id="ecommerce-customer-add-telefone" class="form-control phone-mask"
                                placeholder="(11) 99999-9999" name="customerContact" maxlength="20"
                                inputmode="tel" autocomplete="tel" required />
                            <label for="ecommerce-customer-add-telefone">Telefone*</label>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit submit">
                            <span class="company-submit-label">Cadastrar empresa</span>
                        </button>
                        <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">Fechar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
