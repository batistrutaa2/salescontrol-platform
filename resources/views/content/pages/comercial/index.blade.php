@extends('layouts/layoutMaster')

@section('title', 'Kanban - Apps')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/jkanban/jkanban.scss', 'resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/quill/editor.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/app-kanban.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/jkanban/jkanban.js', 'resources/assets/vendor/libs/quill/katex.js', 'resources/assets/vendor/libs/quill/quill.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/comercialkanban.js')
@endsection

@section('content')
    <div class="app-kanban">
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

        <!-- Kanban Wrapper -->
        <div class="kanban-wrapper"></div>

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
                        <form>
                            <div class="form-floating form-floating-outline mb-5">
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
                                <input type="text" id="cpf" class="form-control" placeholder="476.338.528.36"
                                    disabled />
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
                                <input type="text" id="cartergoria" class="form-control" placeholder="MEDIA" disabled />
                                <label for="title">Cartegoria</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="telefone1" class="form-control" placeholder="(11) 99020-5484" />
                                <label for="title">Telefone Principal</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="telefone2" class="form-control" placeholder="(11) 99020-5484" />
                                <label for="title">Telefone Adicional</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="telefone3" class="form-control" placeholder="(11) 99020-5484" />
                                <label for="title">Telefone Adicional</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" id="valor_plano_atual" class="form-control" placeholder="R$ 197,84"
                                    disabled />
                                <label for="title">Valor do plano atual</label>
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <select class="select2  form-select" id="label">
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
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-5">
                                <div class="d-flex flex-wrap">
                                    <button type="button" class="btn btn-primary me-4" data-bs-dismiss="offcanvas">
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
                        <div class="media mb-4 d-flex align-items-center">
                            <div class="avatar me-3 flex-shrink-0">
                                <span class="avatar-initial bg-label-success rounded-circle">HJ</span>
                            </div>
                            <div class="media-body ms-1">
                                <p class="mb-0">Jordan Left the board.</p>
                                <small class="text-muted">Today 11:00 AM</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
