@extends('layouts/layoutMaster')

@section('title', 'Comericial - Visualizar Cliente')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/quill/editor.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/tagify/tagify.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/quill/katex.js', 'resources/assets/vendor/libs/quill/quill.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/dropzone/dropzone.js', 'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/tagify/tagify.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
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
            <div class="col-12 col-lg-8">
                <!-- Product Information -->
                <div class="card mb-6">

                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Informações Pessoais</h5>

                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#modalcomments" id="js-importContatos">
                            Visualizar Anotações (legado)
                        </button>

                    </div>
                    <form method="POST" action="{{ route('comercial.updateClient') }}">
                        @csrf
                        <div class="card-body">
                            <input type="hidden" name="id" value="{{ $client->id }}">
                            <div class="form-floating form-floating-outline mb-5">
                                <label for="ecommerce-product-name">Status Atual</label>
                                <select class="select2  form-select" id="label" name="tabulacao_id">
                                    @foreach ($tabulations as $tabulation)
                                        <option value="{{ $tabulation->id }}"
                                            {{ $tabulation->id == $tabulationCurrent ? 'selected' : '' }}>
                                            {{ $tabulation->descricao }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>


                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" class="form-control" id="ecommerce-product-name"
                                    {{ $editingPermission == false ? 'disabled' : '' }} value="{{ $client->nome_cliente }}"
                                    placeholder="Nome Cliente" name="nome_cliente" aria-label="Product title">
                                <label for="ecommerce-product-name">Nome Completo</label>
                            </div>


                            <div class="row gx-5 mb-5">
                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="email" class="form-control" id="ecommerce-product-sku"
                                            {{ $editingPermission == false ? 'disabled' : '' }}
                                            value="{{ $client->email }}" placeholder="admin@admin.com.br" name="email"
                                            aria-label="Email Cliente">
                                        <label for="ecommerce-product-sku">E-mail</label>
                                    </div>
                                </div>

                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="cpf"
                                            {{ $editingPermission == false ? 'disabled' : '' }}
                                            value="{{ $client->cpf }}" placeholder="154.548.545/54" name="cpf"
                                            aria-label="Product barcode">
                                        <label for="ecommerce-product-name">CPF / CNPJ</label>
                                    </div>
                                </div>

                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="ecommerce-product-barcode"
                                            {{ $editingPermission == false ? 'disabled' : '' }}
                                            value="{{ $client->data_nascimento }}" placeholder="11/10/1997"
                                            name="data_nascimento" aria-label="Product barcode">
                                        <label for="ecommerce-product-name">Data de nascimento</label>
                                    </div>
                                </div>

                                <div class="col">
                                    <select class="select2  form-select" id="label" name="temperatura">
                                        <option data-color="bg-label-danger" value="QUENTE"
                                            {{ $client->temperatura == 'QUENTE' ? 'selected' : '' }}>
                                            QUENTE
                                        </option>
                                        <option data-color="bg-label-warning" value="MORNO"
                                            {{ $client->temperatura == 'MORNO' ? 'selected' : '' }}>
                                            MORNO
                                        </option>
                                        <option data-color="bg-label-info" value="FRIO"
                                            {{ $client->temperatura == 'FRIO' ? 'selected' : '' }}>
                                            FRIO
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="row gx-5 mb-5">
                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="ecommerce-product-sku"
                                            {{ $editingPermission == false ? 'disabled' : '' }}
                                            value="{{ $client->plano }}" placeholder="Top nacional" name="plano"
                                            aria-label="Email Cliente">
                                        <label for="ecommerce-product-sku">Plano Atual</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="ecommerce-product-barcode"
                                            {{ $editingPermission == false ? 'disabled' : '' }}
                                            value="{{ $client->categoria }}" placeholder="Black infinity" name="cartegoria"
                                            aria-label="Product barcode">
                                        <label for="ecommerce-product-name">Cartegoria</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="ecommerce-product-barcode"
                                            {{ $editingPermission == false ? 'disabled' : '' }}
                                            value="{{ $client->entidade }}" placeholder="Sulamerica" name="entidade"
                                            aria-label="Product barcode">
                                        <label for="ecommerce-product-name">Entidade</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="ecommerce-product-barcode"
                                            {{ $editingPermission == false ? 'disabled' : '' }}
                                            value="{{ $client->idades }}" placeholder="Sulamerica" name="idades"
                                            aria-label="Product barcode">
                                        <label for="ecommerce-product-name">idades</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row gx-5 mb-5">
                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control mask-telefone" id="telefone1"
                                            value="{{ $client->telefone1 }}" placeholder="(99) 95844-1559"
                                            name="telefone1" aria-label="Email Cliente">
                                        <label for="ecommerce-product-sku">Telefone Principal</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control mask-telefone" id="telefone2"
                                            value="{{ $client->telefone2 }}" placeholder="(99) 95844-1559"
                                            name="telefone2" aria-label="Product barcode">
                                        <label for="ecommerce-product-name">Telefone Comercial</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control mask-telefone" id="telefone3"
                                            value="{{ $client->telefone3 }}" placeholder="(99) 95844-1559"
                                            name="telefone3" aria-label="Product barcode">
                                        <label for="ecommerce-product-name">Telefone Adicional</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control monetary-field"
                                            id="ecommerce-product-barcode"
                                            {{ $editingPermission == false ? 'disabled' : '' }} id="valor_plano_atual"
                                            value="{{ number_format($client->valor_plano_atual, 2, ',', '.') }}"
                                            placeholder="R$ 1080.10" name="valor_plano_atual"
                                            aria-label="Product barcode">
                                        <label for="ecommerce-product-name">Valor Atual Investido</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control monetary-field" id="valor_negociacao"
                                            value="{{ number_format($client->valor_negociacao, 2, ',', '.') }}"
                                            placeholder="R$ 0,00" name="valor_negociacao" aria-label="Valor Negociacao">
                                        <label for="valor_negociacao">Valor Negociação</label>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <button class="btn btn-success   btn--twitter">Atualizar informações</button>
                            </div>
                        </div>
                    </form>

                </div>
                <!-- /Product Information -->
                <!-- Media -->
                <div class="card mb-6">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 card-title">Adicionar Comentario</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('comercial.saveComment') }}" method="POST" id="saveComment">
                            @csrf
                            <input type="hidden" name="id_mailing" value="{{ $client->id }}">
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
                                            </span>
                                        </div>
                                    </div>
                                    <div class="comment-editor border-0 pb-1" id="ecommerce-category-description">
                                    </div>
                                </div>
                                <div>
                                    <button class="btn btn-primary mt-5   btn--twitter">Salvar Comentario</button>
                                </div>
                        </form>
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
                            @foreach ($comments as $comment)
                                @if ($comment->tipo_usuario === 'DEVELOPER' || $comment->tipo_usuario === 'ADMIN')
                                    <ul class="timeline pb-0 mb-0">
                                        <li class="timeline-item timeline-item-transparent border-primary">
                                            <span class="timeline-point timeline-point-primary"></span>
                                            <div class="timeline-event">
                                                <div class="timeline-header mb-1">
                                                    <h6 class="mb-0">Feito por: ({{ $comment->name }})
                                                        <span
                                                            class="badge bg-label-success">{{ $comment->tipo_usuario }}</span>
                                                    </h6>
                                                    <small class="text-muted">{!! $comment->created_at !!}</small>
                                                </div>
                                                <p class="mt-1 mb-3"> {!! $comment->anotacao !!}</p>
                                            </div>
                                        </li>
                                    </ul>
                                @else
                                    <ul class="timeline pb-0 mb-0">
                                        <li class="timeline-item timeline-item-transparent border-primary">
                                            <span class="timeline-point timeline-point-primary"></span>
                                            <div class="timeline-event">
                                                <div class="timeline-header mb-1">
                                                    <h6 class="mb-0">Feito por: ({{ $comment->name }})
                                                        <span
                                                            class="badge bg-label-primary">{{ $comment->tipo_usuario }}</span>
                                                        <p class=""></p>
                                                    </h6>
                                                    <small class="text-muted">{!! $comment->created_at !!}</small>
                                                </div>
                                                <p class="mt-1 mb-3"> {!! $comment->anotacao !!}</p>
                                            </div>
                                        </li>
                                    </ul>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-onboarding modal fade animate__animated" id="modalcomments" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content text-center">
                <div class="modal-header border-0">
                    <a class="text-muted close-label" href="javascript:void(0);" data-bs-dismiss="modal">Anotações
                        Sistema (LEGADO)</a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body onboarding-horizontal p-0">
                    <div class="card col-10 p-5 m-5">
                        <div class="card-body mt-3">
                            <ul class="timeline pb-0 mb-0" id="timeline-list">

                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Add New Address Modal -->
    <div class="modal fade" id="addNewAddress" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-simple modal-add-new-address">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-6">
                        <h4 class="address-title mb-2">Cadastrar Contrato</h4>
                        <p class="address-subtitle">Cadastre as informações de contrato do cliente.</p>
                    </div>
                    <form method="POST" action="{{ route('comercial.createSale') }}" class="row g-5 create-sale">
                        @csrf
                        <div class="col-12 col-md-6">
                            <input type="hidden" id="contato_id" name="contato_id" class="form-control"
                                value="{{ $client->id }}" />

                            <div class="form-floating form-floating-outline">
                                <input type="text" id="nome_contrato" name="nome_contrato" class="form-control"
                                    placeholder="NovaTech Solutions" />
                                <label for="nome_contrato">Nome Contrato</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="cpf_cnpj" name="cpf_cnpj" class="form-control"
                                    placeholder="12.345.678/0001-90" />
                                <label for="cpf_cnpj">CPF / CNPJ</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="email" name="email" class="form-control"
                                    placeholder="jhon1234@salescontro.com.br" />
                                <label for="email">E-mail</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-floating form-floating-outline">
                                <input type="date" id="data_vigencia" name="data_vigencia" class="form-control"
                                    placeholder="29/08/2024" />
                                <label for="data_vigencia">Data de Vigencia</label>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="telefone1" name="telefone1" class="form-control mask-telefone"
                                    placeholder="(11) 91254-56871" />
                                <label for="telefone1">Telefone Principal</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="telefone2" name="telefone2" class="form-control mask-telefone"
                                    placeholder="(11) 91254-56871" />
                                <label for="telefone2">Telefone Comercial</label>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="operadora" name="operadora" class="form-control"
                                    placeholder="Sulamerica" />
                                <label for="operadora">Operadora</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="nome_plano" name="nome_plano" class="form-control"
                                    placeholder="plus diamante" />
                                <label for="nome_plano">Nome do Plano</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="valor_contrato" name="valor_contrato" value="0"
                                    class="form-control monetary-field" placeholder="R$ 2500,00" />
                                <label for="valor_contrato">$ Valor do Plano</label>
                            </div>
                        </div>

                        <div class="col-12 col-md-12">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="obs_contrato" name="obs_contrato" class="form-control"
                                    placeholder="Observação de contrato" />
                                <label for="obs_contrato">Observação de contrato</label>
                            </div>
                        </div>
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-success me-3 create-sale">Salvar contrato</button>
                            <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                                aria-label="Close">cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--/ Add New Address Modal -->
@endsection
