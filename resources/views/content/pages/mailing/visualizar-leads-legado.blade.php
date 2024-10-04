@extends('layouts/layoutMaster')

@section('title', 'Lista de leads - (BASE LEGADA)')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('page-style')
    <style>
        .table tr.selected,
        .table tr.selected td {
            background-color: transparent !important;
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/visualizar-leads-legado.js'])

@endsection


@section('page-style')
    <style>
        .table tr.selected,
        .table tr.selected td {
            background-color: transparent !important;
        }
    </style>
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

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center row gap-4 gap-md-0">
                <div class="col-md-3 product_status"></div>
                <div class="col-md-3 product_category"></div>
                <div class="col-md-3 product_stock"></div>
            </div>
            <div class="card-datatable table-responsive">
                <table class="datatables-products table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Corretor</th>
                            <th>Cliente</th>
                            <th>CARTEGORA</th>
                            <th>TELEFONE</th>
                            <th>STATUS</th>
                            <th>AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contatos as $contato)
                            <tr>
                                <td>{{ $contato->id }}</td>
                                <td>{{ $contato->nome_corretor }}</td>
                                <td>{{ $contato->nome_cliente }}</td>
                                <td>{{ $contato->category }}</td>
                                <td>{{ $contato->telefone }}</td>
                                <td>{{ $contato->status }}</td>
                                <td>
                                    <button type="button" class="btn btn-success btn--twitter js-click-button-modal"
                                        data-idMailing="{{ $contato->id }}">
                                        Visualizar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Extra Large modal example -->
        <div class="modal fade bs-example-modal-xl" tabindex="-1" id="getDate" role="dialog"
            aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title mt-0" id="myExtraLargeModalLabel">Visualizar Mailing</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-pills" role="tablist">
                            <li class="nav-item waves-effect waves-light">
                                <a class="nav-link active" data-bs-toggle="tab" href="#navpills-home" role="tab">
                                    <span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
                                    <span class="d-none d-sm-block">Dados Pessoais</span>
                                </a>
                            </li>
                            <li class="nav-item waves-effect waves-light">
                                <a class="nav-link" data-bs-toggle="tab" href="#navpills-profile" role="tab">
                                    <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                                    <span class="d-none d-sm-block">Comentários</span>
                                </a>
                            </li>
                        </ul>
                        <!-- Tab panes -->
                        <div class="tab-content p-3 text-muted">
                            <div class="tab-pane fade show active" id="navpills-home" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <label class="form-label" for="name">Nome</label>
                                            <input type="text" autocomplete="off" class="form-control" id="name"
                                                name="nameClient" required value="">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <label class="form-label" for="email">Email</label>
                                            <input type="email" autocomplete="off" class="form-control" id="email"
                                                name="email" required value="">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <label class="form-label" for="cpf">CPF</label>
                                            <input type="text" autocomplete="off" class="form-control" id="cpf"
                                                name="cpf" required value="">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <label class="form-label" for="phone1">Telefone 1</label>
                                            <input type="tel" autocomplete="off" class="form-control" id="phone1"
                                                name="phone1" required value="">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <label class="form-label" for="phone2">Telefone 2</label>
                                            <input type="tel" autocomplete="off" class="form-control" id="phone2"
                                                name="phone2" required value="">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <label class="form-label" for="phone3">Telefone 3</label>
                                            <input type="tel" autocomplete="off" class="form-control" id="phone3"
                                                name="phone3" required value="">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <label class="form-label" for="ages">Idade</label>
                                            <input type="number" autocomplete="off" class="form-control" id="ages"
                                                name="ages" required value="">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <label class="form-label" for="plan">Nome do plano</label>
                                            <input type="text" autocomplete="off" class="form-control" id="plan"
                                                name="plan" required value="">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <label class="form-label" for="price">Valor do Plano</label>
                                            <input type="text" autocomplete="off" class="form-control" id="price"
                                                name="price" required value="">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <label class="form-label" for="situation">Situação</label>
                                            <input type="text" autocomplete="off" class="form-control" id="situation"
                                                name="situation" required value="">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <label class="form-label" for="entity">Entidade</label>
                                            <input type="text" autocomplete="off" class="form-control" id="entity"
                                                name="entity" required value="">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <label class="form-label" for="category">Categoria</label>
                                            <input type="text" autocomplete="off" class="form-control" id="category"
                                                name="category" required value="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="navpills-profile" role="tabpanel">
                                <div class="row">
                                    <div class="col-xl-12">
                                        <div class="card">
                                            <div class="card-body mt-3 js-list-commentsOne">
                                                <ul class="timeline pb-0 mb-0">
                                                    <li class="timeline-item timeline-item-transparent border-primary">
                                                        <span class="timeline-point timeline-point-primary"></span>
                                                        <div class="timeline-event">
                                                            <div class="timeline-header mb-1">
                                                                <h6 class="mb-0">Feito por:
                                                                    <span class="badge bg-label-success"></span>
                                                                </h6>
                                                                <small class="text-muted"></small>
                                                            </div>
                                                            <p class="mt-1 mb-3"></p>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>



    @endsection
