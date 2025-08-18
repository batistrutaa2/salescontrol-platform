@extends('layouts/layoutMaster')

@section('title', 'Fila de Demandas')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/demandas.js')
@endsection


@section('page-style')
    <style>
        .kanban-card.kanban-overdue {
            border: 1px solid var(--bs-danger);
            animation: pulse-danger 2s infinite;
        }

        .kanban-card.kanban-overdue::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 4px;
            background: var(--bs-danger);
            border-top-left-radius: .5rem;
            border-bottom-left-radius: .5rem;
        }

        .kanban-card {
            position: relative;
        }

        .overdue-badge {
            font-size: .675rem;
        }

        @keyframes pulse-danger {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, .35);
            }

            70% {
                box-shadow: 0 0 0 8px rgba(220, 53, 69, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }
    </style>

@section('content')
    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
            <h5 class="mb-0 me-auto">Fila de Demandas (Kanban)</h5>

            <select id="filtro-status" class="form-select" style="min-width:160px">
                <option value="TODOS">Todos status</option>
                <option value="ABERTA">Aberta</option>
                <option value="EM_ANDAMENTO">Em andamento</option>
                <option value="CONCLUIDA">Concluída</option>
                <option value="CANCELADA">Cancelada</option>
            </select>

            <select id="filtro-prioridade" class="form-select" style="min-width:160px">
                <option value="TODAS">Todas prioridades</option>
                <option value="ALTA">Alta</option>
                <option value="MEDIA">Média</option>
                <option value="BAIXA">Baixa</option>
            </select>

            <select id="filtro-responsavel" class="form-select" style="min-width:220px">
                <option value="">Responsável (todos)</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ strtoupper($u->name) }}</option>
                @endforeach
            </select>

            <button id="btn-nova" class="btn btn-primary">
                <i class="ri-add-line me-1"></i> Nova Demanda
            </button>
        </div>

        <div class="card-body">
            <div class="row g-3 kanban-row">
                @php
                    $cols = [
                        'ABERTA' => 'primary',
                        'EM_ANDAMENTO' => 'warning',
                        'CONCLUIDA' => 'success',
                        'CANCELADA' => 'secondary',
                    ];
                @endphp

                @foreach ($cols as $status => $color)
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="kanban-col h-100 d-flex flex-column">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="mb-0">
                                    <span class="badge bg-label-{{ $color }} me-1">&nbsp;</span>
                                    {{ str_replace('_', ' ', $status) }}
                                </h6>
                                <span class="text-muted small" id="count-{{ $status }}">0</span>
                            </div>
                            <div class="kanban-dropzone border rounded p-2" data-status="{{ $status }}"
                                style="min-height: 60vh; overflow-y: auto;">
                                <!-- cards renderizados via JS -->
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Modal de cadastro/edição (o mesmo que você já tem) --}}
    <div class="modal fade" id="modal-demanda" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form id="form-demanda" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nova Demanda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <input type="hidden" id="demanda_id">
                    <div class="col-12">
                        <label class="form-label">Título *</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Prioridade *</label>
                        <select class="form-select" id="prioridade" name="prioridade" required>
                            <option value="ALTA">Alta</option>
                            <option value="MEDIA" selected>Média</option>
                            <option value="BAIXA">Baixa</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Responsável</label>
                        <select class="form-select" id="assigned_to" name="assigned_to">
                            <option value="">-- sem responsável --</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data limite</label>
                        <input type="date" class="form-control" id="data_limite" name="data_limite">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" type="submit">
                        <i class="ri-save-2-line me-1"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection
