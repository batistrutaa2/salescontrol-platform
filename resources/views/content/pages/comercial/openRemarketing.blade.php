@extends('layouts/layoutMaster')

@section('title', 'Comericial - Visualizar Cliente')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/quill/editor.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/tagify/tagify.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/quill/katex.js', 'resources/assets/vendor/libs/quill/quill.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/dropzone/dropzone.js', 'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/tagify/tagify.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/openClient.js'])
@endsection

@section('content')
    <div class="app-ecommerce">

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
        <!-- Add Product -->
        <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 gap-4 gap-md-0">

            <div class="d-flex flex-column justify-content-center">
                <h4 class="mb-1">Cliente : {{ $client->nome_cliente }}</h4>
            </div>
        </div>

        <div class="row">
            <!-- First column-->
            <div class="col-12 col-lg-12">
                <!-- Product Information -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h5 class="card-tile mb-0">Informações Pessoais</h5>
                    </div>
                    <form method="POST" action="{{ route('comercial.updateClient') }}">
                        @csrf
                        <div class="card-body">
                            <input type="hidden" name="id" value="{{ $client->id }}">
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" class="form-control" id="ecommerce-product-name"
                                    placeholder="Nome Cliente" name="nome_cliente" aria-label="Product title"
                                    value="{{ $client->nome_cliente }}">
                                <label for="ecommerce-product-name">Nome Completo</label>
                            </div>


                            <div class="row gx-5 mb-5">
                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="email" class="form-control" id="ecommerce-product-sku"
                                            value="{{ $client->email }}" placeholder="admin@admin.com.br" name="email"
                                            aria-label="Email Cliente">
                                        <label for="ecommerce-product-sku">E-mail</label>
                                    </div>
                                </div>

                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="ecommerce-product-barcode"
                                            value="{{ $client->cpf }}" placeholder="154.548.545/54" name="cpf"
                                            aria-label="Product barcode">
                                        <label for="ecommerce-product-name">CPF / CNPJ</label>
                                    </div>
                                </div>

                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="ecommerce-product-barcode"
                                            value="{{ $client->data_nascimento }}" placeholder="11/10/1997"
                                            name="data_nascimento" aria-label="Product barcode">
                                        <label for="ecommerce-product-name">Data de nascimento</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row gx-5 mb-5">
                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="ecommerce-product-sku"
                                            value="{{ $client->plano }}" placeholder="Top nacional" name="plano"
                                            aria-label="Email Cliente">
                                        <label for="ecommerce-product-sku">Plano Atual</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="ecommerce-product-barcode"
                                            value="{{ $client->categoria }}" placeholder="Black infinity" name="cartegoria"
                                            aria-label="Product barcode">
                                        <label for="ecommerce-product-name">Cartegoria</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="ecommerce-product-barcode"
                                            value="{{ $client->entidade }}" placeholder="Sulamerica" name="entidade"
                                            aria-label="Product barcode">
                                        <label for="ecommerce-product-name">Entidade</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row gx-5 mb-5">
                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="ecommerce-product-sku"
                                            value="{{ $client->telefone1 }}" placeholder="(99) 95844-1559" name="telefone1"
                                            aria-label="Email Cliente">
                                        <label for="ecommerce-product-sku">Telefone Principal</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="ecommerce-product-barcode"
                                            value="{{ $client->telefone2 }}" placeholder="(99) 95844-1559"
                                            name="telefone2" aria-label="Product barcode">
                                        <label for="ecommerce-product-name">Telefone Comercial</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="ecommerce-product-barcode"
                                            value="{{ $client->telefone3 }}" placeholder="(99) 95844-1559"
                                            name="telefone3" aria-label="Product barcode">
                                        <label for="ecommerce-product-name">Telefone Adicional</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-floating form-floating-outline">

                                        <input type="text" class="form-control" id="ecommerce-product-barcode"
                                            value="{{ $client->valor_plano_atual }}" placeholder="R$ 1080.10"
                                            name="valor_plano_atual" aria-label="Product barcode">
                                        <label for="ecommerce-product-name">Valor Atual Investido</label>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#addPermissionModal"> Transferir Contato </button>
                                <button class="btn btn-success   btn--twitter">Atualizar informações</button>
                                <button class="btn btn-danger   btn--twitter">Descartar cliente</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
    </div>


    <!-- Add Permission Modal -->
    <div class="modal fade" id="addPermissionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-simple">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-6">
                        <h4 class="mb-2">Transferencia de contato</h4>
                        <p>Selecione o corretor que receberá esse lead</p>
                    </div>
                    <form id="transferLead" class="row" action="{{ route('comercial.transferContact') }}"
                        method="POST">
                        @csrf
                        <div class="col">
                            <div class="form-floating form-floating-outline">

                                <select class="select2  form-select" id="label" name="user_id" value="">
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">
                                            {{ strtoupper($user->name) }}
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
    <!--/ Add Permission Modal -->


@endsection
