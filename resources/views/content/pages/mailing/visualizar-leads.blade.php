@extends('layouts/layoutMaster')

@section('title', 'Lista de leads - Mailing')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/visualizar-leads.js'])
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
    <!-- Product List Widget -->

    <!-- Product List Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Filtrar por: </h5>
            <div class="d-flex justify-content-between align-items-center row pt-4 gap-4 gap-md-0">
                <div class="col-md-4 product_status"></div>
                <div class="col-md-4 product_category"></div>
                <div class="col-md-4 product_stock"></div>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-products table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Corretor</th>
                        <th>Cliente</th>
                        <th>CPF</th>
                        <th>Telefone</th>
                        <th>Status</th>
                        <th>Criado em:</th>
                        <th>status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
            </table>
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
                                    <option value="">
                                        Selecione o Status
                                    </option>
                                    @foreach ($tabulations as $tabulation)
                                        <option value="{{ $tabulation->id }}">
                                            {{ strtoupper($tabulation->descricao) }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="ecommerce-product-name">Selecionar corretor</label>
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
