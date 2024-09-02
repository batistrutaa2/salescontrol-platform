@extends('layouts/layoutMaster')

@section('title', 'Cadastrar Cliente - Leads')

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bootstrap-select/bootstrap-select.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/tagify/tagify.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bootstrap-select/bootstrap-select.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/tagify/tagify.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/assets/js/criarlead.js'])
@endsection

@section('content')
    <div class="row mb-12">
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

        <div class="col-md">
            <div class="card">
                <h5 class="card-header">Cadastro de Cliente</h5>
                <div class="card-body">
                    <form class="needs-validation" action="{{ route('comercial.createLead') }}" method="POST">
                        @csrf
                        <div class="form-floating form-floating-outline mb-6">
                            <input type="text" class="form-control" id="bs-validation-name" placeholder="John Doe"
                                required name="nome_cliente" value="{{ old('nome_cliente') }}" />
                            <label for="nome_cliente">Nome Completo</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-6">
                            <input type="email" id="bs-validation-email" class="form-control"
                                placeholder="john.doe@salescontrol.com.br" name="email" value="{{ old('email') }}" />
                            <label for="email">E-mail</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-6">
                            <input type="text" id="cpf" class="form-control" placeholder="12.345.678/0001-99"
                                name="cpf" required value="{{ old('cpf') }}" />
                            <label for="cpf">CPF / CNPJ</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-6">
                            <input type="date" id="bs-validation-email" class="form-control" name="data_nascimento"
                                value="{{ old('data_nascimento') }}" />
                            <label for="data_nascimento">Data de Nascimento</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-6">
                            <input type="text" id="plano" class="form-control" placeholder="SUL AMERICA"
                                aria-label="john.doe" name="plano" value="{{ old('plano') }}" />
                            <label for="plano">Plano</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-6">
                            <input type="text" id="categoria" class="form-control" placeholder="EXECUTIVO"
                                name="categoria" value="{{ old('categoria') }}" />
                            <label for="categoria">Cartegoria</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-6">
                            <input type="text" id="categoria" class="form-control" placeholder="CAASP" name="entidade"
                                value="{{ old('entidade') }}" />
                            <label for="categoria">Entidade</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-6">
                            <input type="text" id="telefone1" class="form-control mask-telefone"
                                placeholder="(11) 91245-6789" required name="telefone1" value="{{ old('telefone1') }}" />
                            <label for="telefone1">Telefone Principal</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-6">
                            <input type="text" id="telefone2" class="form-control mask-telefone"
                                placeholder="(11) 91245-6789" name="telefone2" value="{{ old('telefone2') }}" />
                            <label for="telefone1">Telefone Comercial</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-6">
                            <input type="text" id="telefone3" class="form-control mask-telefone"
                                placeholder="(11) 91245-6789" name="telefone3" value="{{ old('telefone3') }}" />
                            <label for="telefone1">Telefone Opcional</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-6">
                            <input type="text" id="Idades" class="form-control" placeholder="11/35/98/12"
                                name="idades" value="{{ old('idades') }}" />
                            <label for="Idades">Idades</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-6">
                            <input type="text" id="valor_plano_atual" class="form-control monetary-field"
                                value="{{ old('valor_plano_atual') }}" placeholder="R$ 12,000.00"
                                name="valor_plano_atual" />
                            <label for="valor_plano_atual">Valor Atual</label>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-success">Cadastrar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
