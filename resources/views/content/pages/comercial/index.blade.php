@extends('layouts/layoutMaster')

@section('title', 'Lista de Clientes - Kanban')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/jkanban/jkanban.scss', 'resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/quill/editor.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/app-kanban.scss')
    <style>
        .kanban-wrapper {
            display: inline-flex;
            flex: 1;
            min-width: 0;
        }

        .kanban-board {
            flex: 1;
            min-width: 300px;
            /* Largura mínima para cada coluna */
            max-width: 300px;
            /* Largura máxima para cada coluna */
            overflow-y: auto;
            /* Adiciona scroll vertical a cada coluna */
            height: calc(100vh - 200px);
            /* Ajuste a altura conforme necessário */
            padding-right: 10px;
            /* Espaço adicional para evitar que o conteúdo seja ocultado pelo scrollbar */
            position: relative;
        }

        /* Estilo para a barra de rolagem vertical */
        .kanban-board::-webkit-scrollbar {
            width: 6px;
            /* Largura da barra de rolagem */
        }

        .kanban-board::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.2);
            /* Cor da barra de rolagem */
            border-radius: 10px;
            /* Cantos arredondados da barra de rolagem */
        }

        .kanban-board::-webkit-scrollbar-track {
            background: transparent;
            /* Cor de fundo da área de rolagem */
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/jkanban/jkanban.js', 'resources/assets/vendor/libs/quill/katex.js', 'resources/assets/vendor/libs/quill/quill.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/comercialkanban.js')
    @vite('resources/assets/js/consulta.js')
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

    <div class="app-kanban mt-5">
        <!-- Add new board -->
        <div class="row">
            <div class="col-12">
                <form class="kanban-add-new-board">
                    <input type="text" class="form-control w-px-250 kanban-add-board-input mb-4 d-none"
                        placeholder="Add Board Title" id="kanban-add-board-input" required />
                    <div class="mb-4 kanban-add-board-input d-none">
                        <button class="btn btn-primary btn-sm me-3">Add</button>
                        <button type="button"
                            class="btn btn-outline-secondary btn-sm kanban-add-board-cancel-btn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="container mb-5">
            <div class="d-flex justify-content-between">
                <div class="form-floating form-floating-outline flex-fill me-2">
                    <input type="text" id="kanban-search" class="form-control" placeholder="Pesquisar cliente.." />
                    <label for="kanban-search-1">Pesquisar cliente..</label>
                </div>
                <div class="form-floating form-floating-outline flex-fill ms-2">
                    <select class="form-select" id="type-lead" name="tipolead">
                        <option value="A">Ativo</option>
                        <option value="R">Receptivo</option>
                    </select>
                    <label for="label">Tipo de lead</label>
                </div>

                @if ($typeUserLogeed == 'ADMINISTRATIVO' || $typeUserLogeed == 'DEVELOPER' || $typeUserLogeed == 'SUPERVISOR')
                    <div class="form-floating form-floating-outline flex-fill ms-2">
                        <select class="form-select" id="user-filter" name="temperatura">
                            <option value="">
                                Selecione o corretor
                            </option>
                            @foreach ($vendedores as $vendedor)
                                <option value="{{ $vendedor->id }}">
                                    {{ $vendedor->name }}
                                </option>
                            @endforeach
                        </select>
                        <label for="label">Vendedores</label>
                    </div>
                @endif

                <!-- Botão para abrir a modal de fila preditiva -->
                @if ($typeUserLogeed == 'VENDEDOR')
                    <div class="ms-2 d-flex align-items-center">
                        <button type="button" class="btn btn-primary" id="btn-fila-preditiva">
                            <i class="ri-customer-service-line me-1"></i>Vitrini de clientes
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Kanban Wrapper -->
        <div class="container-fluid">
            <div class="kanban-wrapper mt-5"></div>
        </div>



        <!-- Edit Task/Task & Activities -->
        <div class="offcanvas offcanvas-end kanban-update-item-sidebar">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title">Visualizar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body pt-2">
                <ul class="nav nav-tabs mb-2 border-bottom">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-update">
                            <i class="ri-edit-box-line me-1_5"></i>
                            <span class="align-middle">Editar</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-activity">
                            <i class="ri-pie-chart-line me-1_5"></i>
                            <span class="align-middle">Anotações</span>
                        </button>
                    </li>
                </ul>
                <div class="tab-content px-0 pb-0 pt-4">
                    <!-- Update item/tasks -->
                    <div class="tab-pane fade show active" id="tab-update" role="tabpanel">
                        <form method="POST" id="form-client" action="{{ route('comercial.saveNoteMailing') }}">
                            @csrf
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="hidden" value="" id="id_mailing" name="id_mailing">
                                <input type="hidden" value="" id="id_tabulacao" name="id_tabulacao">
                                <input type="text" id="title" class="form-control" placeholder="Enter Title"
                                    disabled />
                                <label for="title">Nome Completo</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="data_nascimento" class="form-control" placeholder="11/10/1997"
                                    disabled />
                                <label for="title">Data de Nascimento</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="cpf" class="form-control mask-cpf"
                                    placeholder="476.338.528.36" disabled />
                                <label for="title">CPF / CNPJ</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="email" id="email" class="form-control"
                                    placeholder="corretor@corretor.com.br" disabled />
                                <label for="title">E-mail</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="plano" class="form-control" placeholder="TOP NACIONAL"
                                    disabled />
                                <label for="title">Plano Atual</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="entidade" class="form-control" placeholder="SULAMERICA"
                                    disabled />
                                <label for="title">Entidade</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="cartergoria" class="form-control" placeholder="MEDIA"
                                    disabled />
                                <label for="title">Cartegoria</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="idades" class="form-control" placeholder="MEDIA"
                                    disabled />
                                <label for="title">Idades</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="telefone1" class="form-control mask-telefone"
                                    placeholder="(11) 99020-5484" name="telefone1" />
                                <label for="title">Telefone Principal</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="telefone2" class="form-control mask-telefone"
                                    placeholder="(11) 99020-5484" name="telefone2" />
                                <label for="title">Telefone Adicional</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="telefone3" class="form-control mask-telefone"
                                    placeholder="(11) 99020-5484" name="telefone3" />
                                <label for="title">Telefone Adicional</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="valor_plano_atual" class="form-control monetary-field"
                                    placeholder="R$ 197,84" disabled />
                                <label for="title">Valor do plano atual</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="valor_negociacao" class="form-control monetary-field"
                                    placeholder="R$ 197,84" name="valor_negociacao" />
                                <label for="title">valor da negociação</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
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
                                <label for="label"> label</label>
                            </div>

                            <div class="mb-8">
                                <label class="form-label">ANOTAÇÕES</label>
                                <div class="comment-editor"></div>
                                <div class="d-flex justify-content-end">
                                    <div class="comment-toolbar">
                                        <span class="ql-formats me-0">
                                            <button class="ql-bold"></button>
                                            <button class="ql-italic"></button>
                                            <button class="ql-underline"></button>
                                            <button class="ql-list" value="ordered"></button>
                                            <button class="ql-list" value="bullet"></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-5">
                                <div class="d-flex flex-wrap">
                                    <button type="submit" class="btn btn-primary me-4 " data-bs-dismiss="offcanvas">
                                        Atualizar
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">
                                        Fechar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- Activities -->
                    <div class="tab-pane fade text-heading" id="tab-activity" role="tabpanel">
                        <div id="notes-container">
                            <!-- As anotações serão inseridas aqui -->
                        </div>
                    </div>
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
                            <input type="hidden" id="contato_id" name="contato_id" class="form-control" />

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
                                    required placeholder="29/08/2024" />
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
    <!--/ Add New Address Modal -->

    <!-- Modal para descartar lead -->
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
                        <input type="hidden" id="leadIdInput" name="contato_id" value="">
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

    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="sheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sheduleModalLabel">CRIAR AGENDAMENTO</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('comercial.sendSchedule') }}" method="POST">
                    @csrf
                    <input type="hidden" id="leadIdInputSchedule" name="contato_id" value="">
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

    <!-- Modal para Fila Preditiva -->
    <div class="modal fade" id="modal-fila-preditiva" tabindex="-1" aria-labelledby="modalFilaPreditivaLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalFilaPreditivaLabel">Cliente na Fila Preditiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="loading-fila-preditiva" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2">Buscando próximo cliente...</p>
                    </div>

                    <div id="no-results-fila-preditiva" class="text-center d-none">
                        <div class="alert alert-info">
                            <i class="ri-information-line me-2"></i>
                            <span>Não há clientes disponíveis na fila preditiva no momento.</span>
                        </div>
                    </div>

                    <div id="cliente-preditiva-container" class="d-none">
                        <!-- Card com dados básicos do cliente -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="ri-user-line me-2"></i>
                                    Informações do Cliente
                                </h6>
                                <!-- Botão de consulta no header do card -->
                                <button type="button" class="btn btn-sm btn-info" id="btn-consultar-dados-cliente">
                                    <span class="spinner-border spinner-border-sm d-none"
                                        id="loading-consulta-cliente"></span>
                                    <i class="ri-search-line ri-16px me-1"></i>
                                    Consultar Dados Completos
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4 id="cliente-nome">-</h4>
                                        <p class="mb-1"><strong>Email:</strong> <span id="cliente-email">-</span></p>
                                        <p class="mb-1"><strong>Telefone:</strong> <span id="cliente-telefone">-</span>
                                        </p>
                                        <p class="mb-1"><strong>CPF:</strong> <span id="cliente-cpf">-</span></p>
                                        <p class="mb-1"><strong>Data de Nascimento:</strong> <span
                                                id="cliente-nascimento">-</span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Plano:</strong> <span id="cliente-plano">-</span></p>
                                        <p class="mb-1"><strong>Categoria:</strong> <span
                                                id="cliente-categoria">-</span></p>
                                        <p class="mb-1"><strong>Entidade:</strong> <span id="cliente-entidade">-</span>
                                        </p>
                                        <p class="mb-1"><strong>Valor Atual:</strong> <span id="cliente-valor">-</span>
                                        </p>
                                    </div>
                                </div>

                                <input type="hidden" id="cliente-id" value="">
                            </div>
                        </div>

                        <!-- Card de Tabulação -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="ri-clipboard-line me-2"></i>
                                    Tabulação do Atendimento
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="form-floating form-floating-outline">
                                    <select class="form-select" id="tabulacao-preditiva">
                                        <option value="">Selecione uma tabulação</option>
                                        <option value="NAO ATENDE">Não atende</option>
                                        <option value="NUMERO INEXISTENTE">Número inexistente</option>
                                        <option value="NAO INTERESSADO">Não interessado</option>
                                        <option value="JA POSSUI PLANO">Já possui plano</option>
                                    </select>
                                    <label for="tabulacao-preditiva">Resultado do Contato</label>
                                </div>
                            </div>
                        </div>

                        <!-- Seção para exibir dados da consulta -->
                        <div id="dados-consulta-cliente" class="d-none mt-3">
                            <!-- Dados da Pessoa -->
                            <div id="dados-pessoa-preditiva" class="d-none">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="text-primary mb-0">
                                            <i class="ri-user-search-line ri-16px me-1"></i>
                                            Dados Completos do Cliente
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Nome:</strong> <span id="nome-preditiva"
                                                        class="text-muted"></span></p>
                                                <p><strong>CPF:</strong> <span id="cpf-result-preditiva"
                                                        class="text-muted"></span></p>
                                                <p><strong>Data Nascimento:</strong> <span id="data-nascimento-preditiva"
                                                        class="text-muted"></span></p>
                                                <p><strong>Sexo:</strong> <span id="sexo-preditiva"
                                                        class="text-muted"></span></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Nome da Mãe:</strong> <span id="nome-mae-preditiva"
                                                        class="text-muted"></span></p>
                                                <p><strong>Situação CPF:</strong> <span id="situacao-cpf-preditiva"
                                                        class="text-muted"></span></p>
                                                <p><strong>Renda:</strong> <span id="renda-preditiva"
                                                        class="text-muted"></span></p>
                                                <p><strong>Ocupação:</strong> <span id="ocupacao-preditiva"
                                                        class="text-muted"></span></p>
                                            </div>
                                        </div>

                                        <!-- Contatos - Telefones -->
                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <h6><i class="ri-smartphone-line ri-16px me-1"></i>Celulares</h6>
                                                <div id="celulares-preditiva" class="border rounded p-2"
                                                    style="min-height: 60px; max-height: 200px; overflow-y: auto;"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6><i class="ri-phone-line ri-16px me-1"></i>Telefones Fixos</h6>
                                                <div id="fixos-preditiva" class="border rounded p-2"
                                                    style="min-height: 60px; max-height: 200px; overflow-y: auto;"></div>
                                            </div>
                                        </div>

                                        <!-- Contatos - E-mails -->
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <h6><i class="ri-mail-line ri-16px me-1"></i>E-mails</h6>
                                                <div id="emails-preditiva" class="border rounded p-2"
                                                    style="min-height: 60px; max-height: 200px; overflow-y: auto;"></div>
                                            </div>
                                        </div>

                                        <!-- Endereços -->
                                        <div class="mt-4">
                                            <h6><i class="ri-map-pin-line ri-16px me-1"></i>Endereços</h6>
                                            <div id="enderecos-preditiva" class="border rounded p-2"
                                                style="max-height: 300px; overflow-y: auto;"></div>
                                        </div>

                                        <!-- Risco de Crédito -->
                                        <div class="mt-4">
                                            <h6><i class="ri-shield-check-line ri-16px me-1"></i>Análise de Crédito</h6>
                                            <div id="risco-credito-preditiva" class="border rounded p-2"></div>
                                        </div>

                                        <div class="mt-4">
                                            <h6><i class="ri-building-2-line ri-16px me-1"></i>Participação Societária</h6>
                                            <div id="participacaoSocietaria" class="border rounded p-2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mensagem de Erro da Consulta -->
                        <div id="erro-consulta-cliente" class="alert alert-danger d-none mt-3">
                            <i class="ri-error-warning-line ri-16px me-1"></i>
                            <span id="mensagem-erro-cliente"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ri-close-line me-1"></i>Fechar
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-danger" id="btn-descartar-cliente" disabled>
                            <i class="ri-close-circle-line me-1"></i>Descartar
                        </button>
                        <button type="button" class="btn btn-success" id="btn-converter-cliente">
                            <i class="ri-check-double-line me-1"></i>Converter Lead
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
