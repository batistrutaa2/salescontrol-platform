@extends('layouts/layoutMaster')

@section('title', 'Fila de Remarketing')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/remarketing.js')
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
            {{ session('message') }}a
        </div>
    @endif

    <!-- customers List Table -->

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Fila de Remarketing</h5>
        </div>
        <div class="card-body">
            <div class="card">
                <div class="card-datatable table-responsive">
                    <table class="datatables-customers table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="form-check-input" id="selectAll"></th>
                                <th>ID</th>
                                <th>NOME</th>
                                <th>MOTIVO</th>
                                <th>TELEFONE PRINCIAL</th>
                                <th>PLANO</th>
                                <th>ENTIDADE</th>
                                <th>ULTIMA ATUALIZAÇÃO</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>



    <div class="modal fade" id="modalcomments" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-simple">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-6">
                        <h4 class="mb-2">Transferencia de contato</h4>
                        <p>Selecione o corretor que receberá esse lead</p>
                    </div>
                    <form id="transferLead" class="row" action="{{ route('comercial.transferContact') }}" method="POST">
                        @csrf
                        <input type="hidden" id="idMailing" name="idMailing">
                        <div class="col">
                            <div class="form-floating form-floating-outline">

                                <select class="select2  form-select" id="label" name="user_id" value="">
                                    <option value="">
                                        Selecione um corretor
                                    </option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">
                                            {{ strtoupper($user->name) }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="ecommerce-product-name">Selecionar corretor</label>
                            </div>

                            <div class="form-floating form-floating-outline mt-4">
                                <select class="select2  form-select" id="label" name="tabulation_id">
                                    @foreach ($tabulations as $tabulation)
                                        <option value="{{ $tabulation->id }}">
                                            {{ strtoupper($tabulation->descricao) }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="ecommerce-product-name">Selecionar o status</label>
                            </div>
                            <div>
                                <button class="btn btn-danger   btn--twitter mt-5">Transferir contato</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


@endsection
