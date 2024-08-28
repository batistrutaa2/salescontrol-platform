@extends('layouts/layoutMaster')

@section('title', 'Importar Base')

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss'])

@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/dropzone/dropzone.js'])
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])

@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/assets/js/importmailing.js'])
@endsection

@section('content')

    <div>
        <div class="card mb-6">
            <div class="card-body">
                <h6 class="mb-5">Importação de Mailing</h6>
                <form enctype="multipart/form-data" id="eCommerceCustomerAddForm">
                    <div class="form-floating form-floating-outline mb-5">
                        <input type="text" class="form-control" id="base" placeholder="Base_01_07" name="base"
                            aria-label="Base_01_07" required />
                        <label for="ecommerce-customer-add-name">Nome da Base</label>
                    </div>

                    <div class="form-floating form-floating-outline mb-5">
                        <select id="tipo_user" class="form-select" name="id_user" required>
                            <option value="">Selecione um vendedor</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ strtoupper($user->name) }}</option>
                            @endforeach
                        </select>
                        <label for="tipo_user">Selecione um Corretor</label>
                    </div>

                    <div class="form-floating form-floating-outline mb-5">
                        <select id="tipo_user" class="form-select" name="tabulacao" required>
                            <option value="">Selecione a Tabulação</option>
                            @foreach ($tabulacoes as $tabulacao)
                                <option value="{{ $tabulacao->id }}">{{ strtoupper($tabulacao->descricao) }}</option>
                            @endforeach
                        </select>
                        <label for="tipo_user">Selecione a Tabulação</label>
                    </div>
                </form>

                <div class="row">
                    <div class="col-12">
                        <div class="card mb-6">
                            <div class="card-body">
                                <form action="/upload" class="dropzone needsclick" id="dropzone-basic">
                                    <div class="dz-message needsclick">
                                        Solte os arquivos aqui ou clique para fazer upload.
                                    </div>
                                    <div class="fallback">
                                        <input name="file" type="file" />
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit submit">importa mailing</button>
            </div>
        </div>

        <div class="card mb-6 card-cpfs-duplicados">
            <div class="card-body">
                <h6 class="mb-5">LEADS DUPLICADOS</h6>
                <div class="col-xl-12 col-md-12">
                    <div class="card overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-truncate">NOME</th>
                                        <th class="text-truncate">CPF</th>
                                        <th class="text-truncate">TELEFONE</th>
                                        <th class="text-truncate">TELEFONE 1</th>
                                        <th class="text-truncate">TELEFONE 1</th>
                                    </tr>
                                </thead>
                                <tbody class="table-cpf-duplicado">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
