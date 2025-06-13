@extends('layouts/layoutMaster')

@section('title', 'Comericial - Visualizar Cliente')

@section('vendor-style')
    <style>
        /* Estilo da barra de rolagem */
        .card-body::-webkit-scrollbar {
            width: 8px;
            /* Largura da barra de rolagem */
        }

        .card-body::-webkit-scrollbar-thumb {
            background-color: #6c757d;
            /* Cor do "thumb" da barra de rolagem */
            border-radius: 10px;
            /* Bordas arredondadas para o "thumb" */
            transition: background-color 0.3s ease;
            /* Transição suave para a mudança de cor */
        }

        .card-body::-webkit-scrollbar-thumb:hover {
            background-color: #495057;
            /* Cor quando o "thumb" é focado pelo mouse */
        }

        .card-body::-webkit-scrollbar-track {
            background-color: #f1f1f1;
            /* Cor de fundo da trilha da barra de rolagem */
            border-radius: 10px;
            /* Bordas arredondadas na trilha */
            margin: 2px;
            /* Espaço ao redor da trilha */
        }

        /* Estilo do canto da barra de rolagem */
        .card-body::-webkit-scrollbar-corner {
            background-color: transparent;
            /* Transparente para o canto onde as barras se encontram */
        }
    </style>

    @vite(['resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/quill/editor.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/tagify/tagify.scss'])
    @vite(['resources/assets/vendor/js/template-customizer.js', 'resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/toastr/toastr.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/quill/katex.js', 'resources/assets/vendor/libs/quill/quill.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/dropzone/dropzone.js', 'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/tagify/tagify.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/openClient.js'])
    @vite(['resources/assets/js/consulta.js'])
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


                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#consultaModal">
                                    <i class="ri-search-line ri-16px me-1"></i>
                                    Consultar Lemit
                        </button>

                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#modalcomments" id="js-importContatos">
                            Visualizar Anotações (legado)
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#discardModal"
                            id="js-descartar-cliente">
                            Descartar contato
                        </button>

                        <button type="button" class="btn btn-success" data-bs-toggle="modal"
                            data-bs-target="#scheduleModal">
                            <i class="ri-time-line ri-20px">Agendar cliente</i>
                        </button>

                    </div>

                    <div class="card-body">
                             <h5 class="card-title mb-0 mb-5">Informações Pessoais</h5>
                        <form method="POST" action="{{ route('comercial.updateClient') }}">
                            @csrf
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
                                        <option value="5">
                                           NEGOCIO FECHADO
                                        </option>
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
                                            value="{{ $client->categoria }}" placeholder="Black infinity"
                                            name="cartegoria" aria-label="Product barcode">
                                        <label for="ecommerce-product-name">Categoria</label>
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
                                <div class="col-4 ">
                                    <div class="input-group">
                                        <!-- Campo de Telefone Principal -->
                                        <input type="text" class="form-control mask-telefone" id="telefone1"
                                            value="{{ $client->telefone1 }}" placeholder="" name="telefone1"
                                            aria-label="Telefone Principal">

                                        <!-- Botão do WhatsApp com ícone -->
                                        <a href="https://wa.me/{{ preg_replace('/\D/', '', '55' . $client->telefone1) }}"
                                            target="_blank" class="btn btn-outline-primary" id="button-addon1">
                                            <i class="ri-whatsapp-line"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="col-4 ">
                                    <div class="input-group">
                                        <!-- Campo de Telefone Principal -->
                                        <input type="text" class="form-control mask-telefone" id="telefone2"
                                            value="{{ $client->telefone2 }}" placeholder="" name="telefone2"
                                            aria-label="Telefone Principal">

                                        <!-- Botão do WhatsApp com ícone -->
                                        <a href="https://wa.me/{{ preg_replace('/\D/', '', '55' . $client->telefone2) }}"
                                            target="_blank" class="btn btn-outline-primary" id="button-addon1">
                                            <i class="ri-whatsapp-line"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="col-4 ">
                                    <div class="input-group">
                                        <!-- Campo de Telefone Principal -->
                                        <input type="text" class="form-control mask-telefone" id="telefone3"
                                            value="{{ $client->telefone3 }}" placeholder="" name="telefone3"
                                            aria-label="Telefone Principal">

                                        <!-- Botão do WhatsApp com ícone -->
                                        <a href="https://wa.me/{{ preg_replace('/\D/', '', '55' . $client->telefone3) }}"
                                            target="_blank" class="btn btn-outline-primary" id="button-addon1">
                                            <i class="ri-whatsapp-line"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col mt-5">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control monetary-field"
                                        id="ecommerce-product-barcode" {{ $editingPermission == false ? 'disabled' : '' }}
                                        id="valor_plano_atual"
                                        value="{{ number_format($client->valor_plano_atual, 2, ',', '.') }}"
                                        placeholder="R$ 1080.10" name="valor_plano_atual" aria-label="Product barcode">
                                    <label for="ecommerce-product-name">Valor Atual Investido</label>
                                </div>
                            </div>
                            <div class="col mt-4">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control monetary-field" id="valor_negociacao"
                                        value="{{ number_format($client->valor_negociacao, 2, ',', '.') }}"
                                        placeholder="R$ 0,00" name="valor_negociacao" aria-label="Valor Negociacao">
                                    <label for="valor_negociacao">Valor Negociação</label>
                                </div>
                            </div>
                            @if ($client->tipo_layout != "padrao")
                            <div class="col mt-5">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control monetary-field"
                                        id="ecommerce-product-barcode" {{ $editingPermission == false ? 'disabled' : '' }}
                                        id="valor_plano_atual"
                                        value="{{ number_format($totalFamilyPlan, 2, ',', '.') }}"
                                        placeholder="R$ 1080.10" name="total_familia" aria-label="Product barcode">
                                    <label for="ecommerce-product-name">Total Familia</label>
                                </div>
                            </div>
                            @endif
                            @if ($client->is_ads == "Y")
                            <div class="col mt-4">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="tipo_campanha" value="{{$client->tipo_criativo}}"
                                    aria-label="tipo Campanha" disabled>
                                    <label for="valor_negociacao">Tipo de anuncio</label>
                                </div>
                            </div>
                            <div class="col mt-4">
                                <div class="form-floating form-floating-outline">
                                  <input type="text" class="form-control" id="tipo_campanha" value="{{$client->possui_cnpj == "Y" ? "SIM" : "NÃO"}}"
                                    aria-label="tipo Campanha" disabled>
                                    <label for="valor_negociacao">Possui CNPJ?</label>
                                </div>
                            </div>
                            <div class="col mt-4">
                                <div class="form-floating form-floating-outline">
                                  <input type="text" class="form-control" id="tipo_campanha" value="{{$client->plano_ativo == "Y" ? "SIM" : "NÃO"}}"
                                    aria-label="tipo Campanha" disabled>
                                    <label for="valor_negociacao">Plano Ativo</label>
                                </div>
                            </div>
                            <div class="col mt-4">
                                <div class="form-floating form-floating-outline">
                                  <input type="text" class="form-control" id="tipo_campanha" value="{{$client->vidas}}"
                                    aria-label="tipo Campanha" disabled>
                                    <label for="valor_negociacao">Quantide de vidas</label>
                                </div>
                            </div>
                            @endif
                            <div class="d-flex mt-5">
                                <button class="btn btn-success btn--twitter ms-auto">Atualizar informações</button>
                            </div>
                        </form>
                    </div>
                </div>
                @if ($client->tipo_layout != "padrao")
                      <div class="accordion mt-5" id="collapsibleSection">
                          @foreach($dependentes as $index => $dependente)
                          <form method="POST" action="{{ route('comercial.updateClientDependecies') }}">
                            @csrf
                            <input type="hidden" name="id_dependente" value="{{ $dependente->id }}">
                            <input type="hidden" name="index_array" value="{{ $index }}">
                              <div class="accordion-item {{ $loop->first ? 'active' : '' }}">
                                  <h2 class="accordion-header" id="headingDependente{{ $index }}">
                                      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDependente{{ $index }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="collapseDependente{{ $index }}">
                                          Dependente {{ $index + 1 }} - {{$dependente['parentesco'] ?? ''}}
                                      </button>
                                  </h2>
                                  <div id="collapseDependente{{ $index }}" class="accordion-collapse collapse" aria-labelledby="headingDependente{{ $index }}" data-bs-parent="#collapsibleSection">
                                      <div class="accordion-body">
                                          <div class="row g-4">
                                              <div class="col-md-10">
                                                  <div class="row">
                                                      <label class="col-sm-3 col-form-label text-sm-end" for="fullname{{ $index }}">Nome Completo</label>
                                                      <div class="col-sm-9">
                                                          <input type="text" id="fullname{{ $index }}" class="form-control" name="dependentes[{{ $index }}][nome]" value="{{ $dependente['nome'] ?? '' }}" placeholder="John Doe" />
                                                      </div>
                                                  </div>
                                              </div>
                                              <div class="col-md-10">
                                                  <div class="row">
                                                      <label class="col-sm-3 col-form-label text-sm-end" for="fullname{{ $index }}">CPF</label>
                                                      <div class="col-sm-9">
                                                          <input type="text" id="fullname{{ $index }}" class="form-control" name="dependentes[{{ $index }}][cpf]" value="{{ $dependente['cpf'] ?? '' }}" placeholder="122.456.789-10" />
                                                      </div>
                                                  </div>
                                              </div>
                                              <div class="col-md-10">
                                                  <div class="row">
                                                      <label class="col-sm-3 col-form-label text-sm-end" for="phone{{ $index }}">Telefone 1</label>
                                                      <div class="col-sm-9">
                                                          <input type="text" id="phone{{ $index }}" class="form-control phone-mask" name="dependentes[{{ $index }}][telefone1]" value="{{ $dependente['telefone_1'] ?? '' }}" placeholder="(11) 94556-7166" />
                                                      </div>
                                                  </div>
                                              </div>
                                              <div class="col-md-10">
                                                  <div class="row">
                                                      <label class="col-sm-3 col-form-label text-sm-end" for="phone{{ $index }}">Telefone 2</label>
                                                      <div class="col-sm-9">
                                                          <input type="text" id="phone{{ $index }}" class="form-control phone-mask" name="dependentes[{{ $index }}][telefone2]" value="{{ $dependente['telefone_2'] ?? '' }}" placeholder="(11) 94556-7166" />
                                                      </div>
                                                  </div>
                                              </div>
                                              <div class="col-md-10">
                                                  <div class="row">
                                                      <label class="col-sm-3 col-form-label text-sm-end" for="phone{{ $index }}">Telefone 3</label>
                                                      <div class="col-sm-9">
                                                          <input type="text" id="phone{{ $index }}" class="form-control phone-mask" name="dependentes[{{ $index }}][telefone3]" value="{{ $dependente['telefone_3'] ?? '' }}" placeholder="(11) 94556-7166" />
                                                      </div>
                                                  </div>
                                              </div>
                                              <div class="col-md-10">
                                                  <div class="row">
                                                      <label class="col-sm-3 col-form-label text-sm-end" for="idade{{ $index }}">Idade</label>
                                                      <div class="col-sm-9">
                                                          <input type="text" id="idade{{ $index }}" class="form-control" name="dependentes[{{ $index }}][idade]" value="{{ $dependente['idade'] ?? '' }}" placeholder="John Doe" />
                                                      </div>
                                                  </div>
                                              </div>
                                              <div class="col-md-10">
                                                  <div class="row">
                                                      <label class="col-sm-3 col-form-label text-sm-end" for="idade{{ $index }}">Valor Dependente</label>
                                                      <div class="col-sm-9">
                                                          <input type="text" id="idade{{ $index }}" class="form-control" name="dependentes[{{ $index }}][valor_plano]" value="R$ {{ number_format($dependente['valor_plano'] ?? 0, 2, ',', '.') ?? '' }}" placeholder="John Doe" />
                                                      </div>
                                                  </div>
                                              </div>
                                            <div>
                                              <button class="btn btn-success mt-5 btn--twitter">Atualizar</button>
                                            </div>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                          </form>
                          @endforeach
                      </div>
                @endif
                <!-- /Product Information -->
                <!-- Media -->
                <div class="card mb-6 mt-5">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 card-title">Adicionar Comentario</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('comercial.saveComment') }}" method="POST" id="saveComment">
                            @csrf
                            <input type="hidden" name="id_mailing" value="{{ $client->id }}">
                            <input type="hidden" value="{{ $tabulationCurrent }}" name="id_tabulacao">
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

        <div class="col-12 col-lg-4">
            <!-- Pricing Card -->
            <div class="card mb-6">
                <div class="card-header">
                    <h5 class="card-title mb-0">Anotações</h5>
                </div>
                <div class="card-body">
                    <div class="card mb-6">
                        <div class="card-header">
                            <h5 class="card-title m-2">Últimas atividades</h5>
                        </div>
                        <div class="card-body mt-5" style="max-height: 300px; overflow-y: auto;">
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
                                <input type="number" id="vidas" name="vidas" class="form-control"
                                    placeholder="Quantidade de vidas" required />
                                <label for="obs_contrato">Quantidade de vidas</label>
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


    <div class="modal fade" id="discardModal" tabindex="-1" aria-labelledby="discardModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="discardModalLabel">Descartar Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('comercial.sendRemaketing') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p>Tem certeza de que deseja descartar este lead?</p>
                        <input type="hidden" id="leadIdInput" name="contato_id" value="{{ $client->id }}">
                        <div class="mb-3">
                            <label for="discardReason" class="form-label">Motivo do Descarte</label>
                            <select class="form-select" id="discardReason" name="sub_tabulacao_id" required>
                                @foreach ($subTabulacoes as $tabulation)
                                    <option value="{{ $tabulation->id }}">{{ $tabulation->descricao }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Descartar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!--/ Add New Address Modal -->



    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="sheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sheduleModalLabel">CRIAR AGENDAMENTO</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('comercial.sendSchedule') }}" method="POST">
                    @csrf
                    <input type="hidden" id="leadIdInputSchedule" name="contato_id" value="{{ $client->id }}">
                    <div class="modal-body">
                        <p>Escolha o horario e o dia do agendamento</p>
                        <div>
                            <label for="telefone1">Horario Agendamento</label>
                            <input type="datetime-local" id="horario_agendamento" name="horario_agendamento"
                                class="form-control" placeholder="data agendamento" required />
                        </div>

                        <div class="mt-2">
                            <label for="observacao">Observação de agendamento</label>
                            <input type="text" id="observacao" name="observacao" class="form-control" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Agendar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<div class="modal fade" id="consultaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Consulta de Dados - API Lemit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Formulário de Consulta -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title">Consulta por CPF</h6>
                                <div class="form-floating form-floating-outline mb-3">
                                    <input type="text" id="cpfConsulta" class="form-control" placeholder="000.000.000-00" maxlength="14">
                                    <label for="cpfConsulta">CPF</label>
                                </div>
                                <button type="button" class="btn btn-primary" onclick="consultarPessoa()">
                                    <span class="spinner-border spinner-border-sm d-none" id="loadingPessoa"></span>
                                    <i class="ri-user-search-line ri-16px me-1"></i>
                                    Consultar CPF
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title">Consulta por CNPJ</h6>
                                <div class="form-floating form-floating-outline mb-3">
                                    <input type="text" id="cnpjConsulta" class="form-control" placeholder="00.000.000/0000-00" maxlength="18">
                                    <label for="cnpjConsulta">CNPJ</label>
                                </div>
                                <button type="button" class="btn btn-success" onclick="consultarEmpresa()">
                                    <span class="spinner-border spinner-border-sm d-none" id="loadingEmpresa"></span>
                                    <i class="ri-building-line ri-16px me-1"></i>
                                    Consultar CNPJ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resultado da Consulta -->
                <div id="resultadoConsulta" class="d-none">
                    <!-- Dados da Pessoa -->
                    <div id="dadosPessoa" class="d-none">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="text-primary mb-0">
                                    <i class="ri-user-line ri-16px me-1"></i>
                                    Dados Pessoais Encontrados
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Nome:</strong> <span id="nome" class="text-muted"></span></p>
                                        <p><strong>CPF:</strong> <span id="cpfResult" class="text-muted"></span></p>
                                        <p><strong>Data Nascimento:</strong> <span id="dataNascimento" class="text-muted"></span></p>
                                        <p><strong>Sexo:</strong> <span id="sexo" class="text-muted"></span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Nome da Mãe:</strong> <span id="nomeMae" class="text-muted"></span></p>
                                        <p><strong>Situação CPF:</strong> <span id="situacaoCpf" class="text-muted"></span></p>
                                        <p><strong>Renda:</strong> <span id="renda" class="text-muted"></span></p>
                                        <p><strong>Ocupação:</strong> <span id="ocupacao" class="text-muted"></span></p>
                                    </div>
                                </div>

                                <!-- Contatos - Telefones -->
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <h6><i class="ri-smartphone-line ri-16px me-1"></i>Celulares</h6>
                                        <div id="celulares" class="border rounded p-2" style="min-height: 60px; max-height: 300px; overflow-y: auto;"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="ri-phone-line ri-16px me-1"></i>Telefones Fixos</h6>
                                        <div id="fixos" class="border rounded p-2" style="min-height: 60px; max-height: 300px; overflow-y: auto;"></div>
                                    </div>
                                </div>

                                <!-- Contatos - E-mails -->
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h6><i class="ri-mail-line ri-16px me-1"></i>E-mails</h6>
                                        <div id="emails" class="border rounded p-2" style="min-height: 60px; max-height: 300px; overflow-y: auto;"></div>
                                    </div>
                                </div>

                                <!-- Endereços -->
                                <div class="mt-4">
                                    <h6><i class="ri-map-pin-line ri-16px me-1"></i>Endereços</h6>
                                    <div id="enderecos" class="border rounded p-2" style="max-height: 400px; overflow-y: auto;"></div>
                                </div>

                                <!-- Veículos -->
                                <div class="mt-4">
                                    <h6><i class="ri-car-line ri-16px me-1"></i>Veículos</h6>
                                    <div id="carros" class="border rounded p-2"></div>
                                </div>

                                <!-- Vínculos Familiares -->
                                <div class="mt-4">
                                    <h6><i class="ri-links-line ri-16px me-1"></i>Vínculos Familiares</h6>
                                    <div id="vinculos" class="border rounded p-2" style="max-height: 300px; overflow-y: auto;"></div>
                                </div>

                                <!-- Risco de Crédito -->
                                <div class="mt-4">
                                    <h6><i class="ri-shield-check-line ri-16px me-1"></i>Análise de Crédito</h6>
                                    <div id="riscoCredito" class="border rounded p-2"></div>
                                </div>

                                <!-- Participação Societária -->
                                <div class="mt-4">
                                    <h6><i class="ri-building-2-line ri-16px me-1"></i>Participação Societária</h6>
                                    <div id="participacaoSocietaria" class="border rounded p-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dados da Empresa -->
                    <div id="dadosEmpresa" class="d-none">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="text-success mb-0">
                                    <i class="ri-building-line ri-16px me-1"></i>
                                    Dados da Empresa Encontrados
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="infoEmpresa"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensagem de Erro -->
                <div id="erroConsulta" class="alert alert-danger d-none">
                    <i class="ri-error-warning-line ri-16px me-1"></i>
                    <span id="mensagemErro"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>


@endsection
