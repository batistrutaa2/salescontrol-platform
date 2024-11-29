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

    @if (session('status') == 'success')
        <div class="alert alert-solid-success d-flex align-items-center" role="alert">
            <span class="alert-icon rounded">
                <i class="ri-checkbox-circle-line ri-22px"></i>
            </span>
            {{ session('message') }}
        </div>
    @elseif(session('status') == 'error')
        <div class="alert alert-danger">
            {{ session('message') }}
        </div>
    @endif
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
                    </tr>
                </thead>
            </table>
        </div>
        <!-- Offcanvas to add new customer -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEcommerceCustomerAdd"
            aria-labelledby="offcanvasEcommerceCustomerAddLabel">
            <div class="offcanvas-header border-bottom">
                <h5 id="offcanvasEcommerceCustomerAddLabel" class="offcanvas-title">Criar Usuario</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body mx-0 flex-grow-0">
                <form method="POST" action="{{ route('pabx.createramal') }}" class="ecommerce-customer-add pt-0"
                    id="createramal">
                    @csrf
                    <div class="ecommerce-customer-add-basic mb-6">
                        <h6 class="mb-5">Informações basicas</h6>
                        <div class="form-floating form-floating-outline mb-5">
                            <input type="text" class="form-control" id="ramal" name="ramal" />
                            <label for="ramal">RAMAL*</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-5">
                            <select id="empresas" class="form-select" name="usuario_id">
                                @foreach ($usuarios as $usuario)
                                    <option value="{{ $usuario->id }}">
                                        {{ $usuario->name }}</option>
                                @endforeach
                            </select>
                            <label for="empresas">VENDEDOR</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-5">
                            @if (is_iterable($companies) && count($companies) > 0)
                                <select id="empresas" class="form-select" {{ count($companies) == 1 ? 'disabled' : '' }}
                                    name="empresa_id">
                                    @foreach ($companies as $companie)
                                        <option value="{{ $companie->id }}" {{ count($companies) == 1 ? 'selected' : '' }}>
                                            {{ $companie->nome_fantasia }}</option>
                                    @endforeach
                                </select>
                                <label for="empresas">Empresa</label>
                            @elseif(is_object($companies))
                                <select id="empresas" class="form-select" disabled name="empresa_id">
                                    <option value="{{ $companies->id }}" selected>{{ $companies->nome_fantasia }}</option>
                                </select>
                                <label for="empresas">Empresa</label>
                            @else
                                <select id="empresas" class="form-select" disabled name="empresa_id">
                                    <option value="">Nenhuma empresa disponível</option>
                                </select>
                                <label for="empresas">Empresa</label>
                            @endif
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit submit">Cadastrar</button>
                        <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">Fechar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
