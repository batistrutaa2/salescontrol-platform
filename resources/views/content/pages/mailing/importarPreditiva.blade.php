@extends('layouts/layoutMaster')

@section('title', 'Importar para Preditiva')

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/dropzone/dropzone.js'])
    @vite(['resources/assets/vendor/libs/toastr/toastr.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/assets/js/importarPreditiva.js'])
@endsection

@section('content')

    <div>
        <div class="card mb-6">
            <div class="card-body">
                <h6 class="mb-5">Importação de Leads para Preditiva</h6>
                <p class="text-muted mb-5">Os leads serão importados diretamente para a fila da preditiva. CPFs duplicados serão automaticamente bloqueados.</p>

                <form enctype="multipart/form-data" id="formImportarPreditiva">
                    <div class="form-floating form-floating-outline mb-5">
                        <input type="text" class="form-control" id="base" placeholder="Base_Preditiva_01" name="base"
                            aria-label="Base_Preditiva_01" required />
                        <label for="base">Nome da Base</label>
                    </div>
                </form>

                <div class="row">
                    <div class="col-12">
                        <div class="card mb-6">
                            <div class="card-body">
                                <form action="/upload" class="dropzone needsclick" id="dropzone-preditiva">
                                    <div class="dz-message needsclick">
                                        Solte os arquivos aqui ou clique para fazer upload.
                                        <br>
                                        <small class="text-muted">Formato aceito: XLSX com dependentes (categoria, nome, cpf, idade, parentesco, valor, entidade)</small>
                                    </div>
                                    <div class="fallback">
                                        <input name="file" type="file" />
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit submit-preditiva">
                    <i class="mdi mdi-upload me-1"></i>Importar para Preditiva
                </button>
                <a href="{{ route('mailing.preditiva') }}" class="btn btn-outline-secondary">
                    <i class="mdi mdi-arrow-left me-1"></i>Voltar para Preditiva
                </a>
            </div>
        </div>

        <div class="card mb-6 card-cpfs-duplicados" style="display: none;">
            <div class="card-body">
                <div class="alert alert-warning" role="alert">
                    <h5 class="alert-heading mb-2">
                        <i class="mdi mdi-alert-outline me-2"></i>CPFs Duplicados Encontrados
                    </h5>
                    <p class="mb-0">Os seguintes CPFs já existem no sistema e foram bloqueados:</p>
                </div>
                <div class="col-xl-12 col-md-12">
                    <div class="card overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-truncate">CPF</th>
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

    <!-- Modal de Confirmação de Importação Parcial -->
    <div class="modal fade" id="modalImportarParcial" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="mdi mdi-alert-circle-outline text-warning me-2"></i>
                        CPFs Duplicados Encontrados
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        <strong id="total-duplicados">0</strong> CPF(s) duplicado(s) foram encontrados no arquivo.
                    </p>
                    <p class="mb-3">
                        <strong id="total-nao-duplicados">0</strong> lead(s) podem ser importados.
                    </p>
                    <div class="alert alert-info" role="alert">
                        <i class="mdi mdi-information-outline me-2"></i>
                        Deseja importar apenas os leads não duplicados?
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="btn-confirmar-importacao-parcial">
                        <i class="mdi mdi-check me-1"></i>Importar Não Duplicados
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
