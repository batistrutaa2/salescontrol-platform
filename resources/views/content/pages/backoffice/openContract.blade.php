@extends('layouts/layoutMaster')

@section('title', 'Gestão de contrato')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/openContract.js'])
@endsection

@section('content')
    <!-- First column -->
    <div class="col-12 col-lg-12">
        <!-- Product Information -->
        <div class="card mb-6">

            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Informações do Contrato</h5>
            </div>

            <div class="card-body">
                <form method="POST" action="">
                    @csrf
                    <input type="hidden" name="id" value="{{ $contract->id }}">

                    <div class="form-floating form-floating-outline mb-5">
                        <input type="text" class="form-control" id="nome_contrato" value="{{ $contract->nome_contrato }}"
                            placeholder="Nome do Contrato" name="nome_contrato">
                        <label for="nome_contrato">Nome do Contrato</label>
                    </div>

                    <div class="row gx-5 mb-5">
                        <div class="col">
                            <div class="form-floating form-floating-outline">
                                <input type="text" class="form-control" id="cpf_cnpj" value="{{ $contract->cpf_cnpj }}"
                                    placeholder="CPF / CNPJ" name="cpf_cnpj">
                                <label for="cpf_cnpj">CPF / CNPJ</label>
                            </div>
                        </div>

                        <div class="col">
                            <div class="form-floating form-floating-outline">
                                <input type="email" class="form-control" id="email" value="{{ $contract->email }}"
                                    placeholder="Email" name="email">
                                <label for="email">Email</label>
                            </div>
                        </div>

                        <div class="col">
                            <div class="form-floating form-floating-outline">
                                <input type="text" class="form-control" id="data_vigencia"
                                    value="{{ $contract->data_vigencia }}" placeholder="Data de Vigência"
                                    name="data_vigencia">
                                <label for="data_vigencia">Data de Vigência</label>
                            </div>
                        </div>
                    </div>

                    <div class="row gx-5 mb-5">
                        <div class="col">
                            <div class="form-floating form-floating-outline">
                                <input type="text" class="form-control" id="telefone1" value="{{ $contract->telefone1 }}"
                                    placeholder="Telefone 1" name="telefone1">
                                <label for="telefone1">Telefone 1</label>
                            </div>
                        </div>

                        <div class="col">
                            <div class="form-floating form-floating-outline">
                                <input type="text" class="form-control" id="telefone2" value="{{ $contract->telefone2 }}"
                                    placeholder="Telefone 2" name="telefone2">
                                <label for="telefone2">Telefone 2</label>
                            </div>
                        </div>

                        <div class="col">
                            <div class="form-floating form-floating-outline">
                                <input type="text" class="form-control" id="telefone3" value="{{ $contract->telefone3 }}"
                                    placeholder="Telefone 3" name="telefone3">
                                <label for="telefone3">Telefone 3</label>
                            </div>
                        </div>
                    </div>

                    <div class="col mt-5">
                        <div class="form-floating form-floating-outline">
                            <input type="text" class="form-control monetary-field" id="valor_contrato"
                                value="{{ number_format($contract->valor_contrato, 2, ',', '.') }}" placeholder="R$ 1080.10"
                                name="valor_contrato">
                            <label for="valor_contrato">Valor do Contrato</label>
                        </div>
                    </div>

                    <div class="col mt-4">
                        <div class="form-floating form-floating-outline">
                            <input type="text" class="form-control" id="vidas" value="{{ $contract->vidas }}"
                                placeholder="Número de Vidas" name="vidas">
                            <label for="vidas">Número de Vidas</label>
                        </div>
                    </div>

                    <div class="col mt-4">
                        <div class="form-floating form-floating-outline">
                            <input type="text" class="form-control" id="operadora" value="{{ $contract->operadora }}"
                                placeholder="Operadora" name="operadora">
                            <label for="operadora">Operadora</label>
                        </div>
                    </div>

                    <div class="col mt-4">
                        <div class="form-floating form-floating-outline">
                            <input type="text" class="form-control" id="nome_plano"
                                value="{{ $contract->nome_plano }}" placeholder="Nome do Plano" name="nome_plano">
                            <label for="nome_plano">Nome do Plano</label>
                        </div>
                    </div>

                    <div class="col mt-4">
                        <div class="form-floating form-floating-outline">
                            <textarea class="form-control" id="obs_contrato" name="obs_contrato" rows="4">{{ $contract->obs_contrato }}</textarea>
                            <label for="obs_contrato">Observações</label>
                        </div>
                    </div>

                    <div class="d-flex mt-5">
                        <button class="btn btn-success btn--twitter ms-auto">Atualizar contrato</button>
                    </div>
                </form>

            </div>
        </div>
    </div>


@endsection
