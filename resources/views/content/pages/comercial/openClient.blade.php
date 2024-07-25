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

        <!-- Add Product -->
        <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 gap-4 gap-md-0">

            <div class="d-flex flex-column justify-content-center">
                <h4 class="mb-1">Cliente : Aqui sera o nome do cliente</h4>
            </div>
        </div>

        <div class="row">

            <!-- First column-->
            <div class="col-12 col-lg-8">
                <!-- Product Information -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h5 class="card-tile mb-0">Informações Pessoais</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-floating form-floating-outline mb-5">
                            <input type="text" class="form-control" id="ecommerce-product-name"
                                placeholder="Product title" name="nome_cliente" aria-label="Product title">
                            <label for="ecommerce-product-name">Nome Completo</label>
                        </div>


                        <div class="row gx-5 mb-5">
                            <div class="col">
                                <div class="form-floating form-floating-outline">
                                    <input type="email" class="form-control" id="ecommerce-product-sku"
                                        placeholder="admin@admin.com.br" name="email" aria-label="Email Cliente">
                                    <label for="ecommerce-product-sku">E-mail</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="ecommerce-product-barcode"
                                        placeholder="154.548.545/54" name="cpf" aria-label="Product barcode">
                                    <label for="ecommerce-product-name">CPF / CNPJ</label>
                                </div>
                            </div>

                            <div class="col">
                                <select class="select2  form-select" id="label" name="temperatura">
                                    <option data-color="bg-label-danger" value="QUENTE">
                                        QUENTE
                                    </option>
                                    <option data-color="bg-label-warning" value="MORNO">
                                        MORNO
                                    </option>
                                    <option data-color="bg-label-info" value="FRIO">
                                        FRIO
                                    </option>
                                </select>

                            </div>


                        </div>
                        <div class="row gx-5 mb-5">
                            <div class="col">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="ecommerce-product-sku"
                                        placeholder="Top nacional" name="plano" aria-label="Email Cliente">
                                    <label for="ecommerce-product-sku">Plano Atual</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="ecommerce-product-barcode"
                                        placeholder="Black infinity" name="cartegoria" aria-label="Product barcode">
                                    <label for="ecommerce-product-name">Cartegoria</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="ecommerce-product-barcode"
                                        placeholder="Sulamerica" name="entidade" aria-label="Product barcode">
                                    <label for="ecommerce-product-name">Entidade</label>
                                </div>
                            </div>
                        </div>
                        <div class="row gx-5 mb-5">
                            <div class="col">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="ecommerce-product-sku"
                                        placeholder="(99) 95844-1559" name="telefone1" aria-label="Email Cliente">
                                    <label for="ecommerce-product-sku">Telefone Principal</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="ecommerce-product-barcode"
                                        placeholder="(99) 95844-1559" name="telefone2" aria-label="Product barcode">
                                    <label for="ecommerce-product-name">Telefone Comercial</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="ecommerce-product-barcode"
                                        placeholder="(99) 95844-1559" name="telefone3" aria-label="Product barcode">
                                    <label for="ecommerce-product-name">Telefone Adicional</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="ecommerce-product-barcode"
                                        placeholder="R$ 1080.10" name="telefone3" aria-label="Product barcode">
                                    <label for="ecommerce-product-name">Valor Atual Investido</label>
                                </div>
                            </div>
                        </div>
                        <div>
                            <button class="btn btn-success   btn--twitter">Atualizar informações</button>
                        </div>
                    </div>

                </div>
                <!-- /Product Information -->
                <!-- Media -->
                <div class="card mb-6">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 card-title">Adicionar Comentario</h5>
                    </div>
                    <div class="card-body">
                        <div>
                            <div class="form-control p-0 pt-1">
                                <div class="comment-toolbar border-0 border-bottom">
                                    <div class="d-flex justify-content-start">
                                        <span class="ql-formats me-0">
                                            <button class="ql-bold"></button>
                                            <button class="ql-italic"></button>
                                            <button class="ql-underline"></button>
                                            <button class="ql-list" value="ordered"></button>
                                            <button class="ql-list" value="bullet"></button>
                                            <button class="ql-link"></button>
                                            <button class="ql-image"></button>
                                        </span>
                                    </div>
                                </div>
                                <div class="comment-editor border-0 pb-1" id="ecommerce-category-description">
                                </div>
                            </div>
                            <div>
                                <button class="btn btn-primary mt-5   btn--twitter">Salvar Comentario</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Second column -->

            <!-- Second column -->
            <div class="col-12 col-lg-4">
                <!-- Pricing Card -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Anotações</h5>
                    </div>
                    <div class="card-body">
                        <div class="card mb-6">
                            <div class="card-header">
                                <h5 class="card-title m-0">Ultimas atividades</h5>
                            </div>
                            <div class="card-body mt-3">
                                <ul class="timeline pb-0 mb-0">
                                    <li class="timeline-item timeline-item-transparent border-primary">
                                        <span class="timeline-point timeline-point-primary"></span>
                                        <div class="timeline-event">
                                            <div class="timeline-header mb-1">
                                                <h6 class="mb-0">Order was placed (Order ID: #32543)</h6>
                                                <small class="text-muted">Tuesday 11:29 AM</small>
                                            </div>
                                            <p class="mt-1 mb-3">Your order has been placed successfully</p>
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

@endsection
