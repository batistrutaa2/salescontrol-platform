@extends('layouts/layoutMaster')

@section('title', 'Cadastro de Ramais')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/cadastroRamais.js')
@endsection

@section('content')

    <!-- customers List Table -->
    <div class="card">

        <div class="card-datatable table-responsive">
            <table class="datatables-customers table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>NOME</th>
                        <th class="text-nowrap">RAMAL</th>
                        <th class="text-nowrap">CRIADO EM</th>
                        <th class="text-nowrap">AÇÕES</th>
                    </tr>
                </thead>
            </table>
        </div>
        {{-- <!-- Offcanvas to add new customer -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEcommerceCustomerAdd"
            aria-labelledby="offcanvasEcommerceCustomerAddLabel">
            <div class="offcanvas-header border-bottom">
                <h5 id="offcanvasEcommerceCustomerAddLabel" class="offcanvas-title">Criar Empresa</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body mx-0 flex-grow-0">
                <form method="POST" class="ecommerce-customer-add pt-0" id="eCommerceCustomerAddForm">
                    @csrf
                    <div class="ecommerce-customer-add-basic mb-6">
                        <h6 class="mb-5">Informações basicas</h6>
                        <div class="form-floating form-floating-outline mb-5">
                            <input type="text" class="form-control" id="ecommerce-customer-add-name"
                                placeholder="John Doe" name="customerName" aria-label="John Doe" />
                            <label for="ecommerce-customer-add-name">Nome*</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-5">
                            <input type="number" class="form-control" id="ecommerce-customer-add-name"
                                placeholder="49.877.625/0001-25" name="customerCnpj" aria-label="49.877.625/0001-25" />
                            <label for="ecommerce-customer-add-name">CNPJ / CPF*</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-5">
                            <input type="email" id="ecommerce-customer-add-email" class="form-control"
                                placeholder="john.doe@example.com" aria-label="john.doe@example.com" name="customerEmail" />
                            <label for="ecommerce-customer-add-email">Email*</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-5">
                            <input type="text" id="ecommerce-customer-add-telefone" class="form-control"
                                placeholder="(11) 94556-7166" aria-label="(11)945567166" name="customerContact" />
                            <label for="ecommerce-customer-add-telefone">Telefone*</label>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit submit">Cadastrar</button>
                        <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">Fechar</button>
                    </div>
                </form>
            </div>
        </div> --}}
    </div>

@endsection
