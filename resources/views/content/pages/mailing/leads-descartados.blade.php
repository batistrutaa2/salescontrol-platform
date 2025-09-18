@extends('layouts/layoutMaster')

@section('title', 'Leads Descartados')


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
    @vite(['resources/assets/js/leads-descartados.js'])
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Leads Descartados</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped w-100" id="leadsDescartadosTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Telefone</th>
                            <th>Valor do Plano</th>
                            <th>Data Criação</th>
                            <th>Ações</th>

                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>


    <!-- Modal de Comentários -->
    <div class="modal fade" id="modalComentarios" tabindex="-1" aria-labelledby="modalComentariosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalComentariosLabel">Histórico de Comentários</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group" id="comentariosList">
                        <li class="list-group-item">Carregando...</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

@endsection
