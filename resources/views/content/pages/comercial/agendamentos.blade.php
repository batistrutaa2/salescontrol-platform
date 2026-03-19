@extends('layouts/layoutMaster')

@section('title', 'Lista de Agendamentos')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/agendamentos.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/agendamentos.js'])
@endsection

@section('content')
<div class="ag-wrapper">

    <!-- Page Header -->
    <div class="ag-page-header">
        <div class="ag-header-content">
            <div class="ag-header-text">
                <span class="ag-greeting-label">Comercial</span>
                <h1 class="ag-main-title">Agendamentos</h1>
                <p class="ag-subtitle">Gerencie seus agendamentos e compromissos</p>
            </div>
        </div>
    </div>

    <!-- Session Alerts -->
    @if (session('status') == 'success')
        <div class="ag-alert ag-alert-success">
            <span class="ag-alert-icon">
                <i class="ri-checkbox-circle-line ri-22px"></i>
            </span>
            {{ session('message') }}
        </div>
    @elseif(session('status') == 'error')
        <div class="ag-alert ag-alert-error">
            <span class="ag-alert-icon">
                <i class="ri-error-warning-line ri-22px"></i>
            </span>
            {{ session('message') }}
        </div>
    @endif

    <!-- Data Table -->
    <div class="ag-table-card">
        <div class="ag-table-header">
            <div class="ag-table-title-group">
                <div class="ag-table-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <div>
                    <h3 class="ag-table-title">Lista de Agendamentos</h3>
                    <span class="ag-table-subtitle">Agendamentos pendentes e futuros</span>
                </div>
            </div>
        </div>
        <div class="ag-table-body">
            <div class="table-responsive text-center">
                <table class="datatables-schedules table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Corretor</th>
                            <th>Cliente</th>
                            <th>Horário do Agendamento</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Modal Reagendar -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="sheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="ag-modal-header">
                <h5 class="ag-modal-title">
                    <span class="ag-modal-icon reschedule">
                        <i class="ri-calendar-schedule-line"></i>
                    </span>
                    Reagendar
                </h5>
                <button type="button" class="ag-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ri-close-line"></i>
                </button>
            </div>
            <form action="{{ route('comercial.sendSchedule') }}" method="POST">
                @csrf
                <input type="hidden" id="leadIdInputSchedule" name="contato_id" value="">
                <div class="ag-modal-body">
                    <p class="ag-modal-description">Escolha o horário e o dia do agendamento</p>
                    <div class="ag-form-group">
                        <label class="ag-form-label" for="horario_agendamento">Horário Agendamento</label>
                        <input type="datetime-local" id="horario_agendamento" name="horario_agendamento"
                            class="ag-form-input" required />
                    </div>
                    <div class="ag-form-group">
                        <label class="ag-form-label" for="observacao">Observação</label>
                        <input type="text" id="observacao" name="observacao" class="ag-form-input" />
                    </div>
                </div>
                <div class="ag-modal-footer">
                    <button type="button" class="ag-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="ag-btn-submit success">Reagendar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Descartar -->
<div class="modal fade" id="discardModal" tabindex="-1" aria-labelledby="discardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="ag-modal-header">
                <h5 class="ag-modal-title">
                    <span class="ag-modal-icon discard">
                        <i class="ri-delete-bin-line"></i>
                    </span>
                    Descartar Contato
                </h5>
                <button type="button" class="ag-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ri-close-line"></i>
                </button>
            </div>
            <form action="{{ route('comercial.sendRemaketing') }}" method="POST">
                @csrf
                <div class="ag-modal-body">
                    <p class="ag-modal-description">Tem certeza de que deseja descartar este lead?</p>
                    <input type="hidden" id="leadIdInputDescarte" name="contato_id" value="">
                    <div class="ag-form-group">
                        <label class="ag-form-label" for="discardReason">Motivo do Descarte</label>
                        <select class="ag-form-select" id="discardReason" name="sub_tabulacao_id" required>
                            @foreach ($subTabulacoes as $tabulation)
                                <option value="{{ $tabulation->id }}">{{ $tabulation->descricao }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="ag-modal-footer">
                    <button type="button" class="ag-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="ag-btn-submit danger">Descartar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Voltar para Fila -->
<div class="modal fade" id="backKanban" tabindex="-1" aria-labelledby="backKanbanLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="ag-modal-header">
                <h5 class="ag-modal-title">
                    <span class="ag-modal-icon queue">
                        <i class="ri-arrow-go-back-line"></i>
                    </span>
                    Voltar para Fila
                </h5>
                <button type="button" class="ag-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ri-close-line"></i>
                </button>
            </div>
            <form action="{{ route('comercial.backqueue') }}" method="POST">
                @csrf
                <div class="ag-modal-body">
                    <input type="hidden" id="leadInputVisto" name="contato_id" value="">
                    <div class="ag-form-group">
                        <label class="ag-form-label" for="tabulacaoSelect">Tabulação</label>
                        <select class="ag-form-select" id="tabulacaoSelect" name="tabulacao_id" required>
                            @foreach ($tabulacoes as $tabulation)
                                <option value="{{ $tabulation->id }}">{{ $tabulation->descricao }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="ag-modal-footer">
                    <button type="button" class="ag-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="ag-btn-submit primary">Enviar para Fila</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
