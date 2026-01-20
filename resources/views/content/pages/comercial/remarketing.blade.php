@extends('layouts/layoutMaster')

@section('title', 'Fila de Remarketing')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/remarketing.js')
@endsection

@php
    $meses = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];
@endphp

@section('page-style')
<style>
    .metric-card {
        border-radius: 12px;
        border: none;
        padding: 1.25rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        height: 100%;
    }

    [data-theme="light"] .metric-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    [data-theme="dark"] .metric-card {
        background: #2d3748;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }

    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.12);
    }

    .metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
    }

    .metric-card.primary::before { background: linear-gradient(90deg, #5a67d8, #667eea); }
    .metric-card.warning::before { background: linear-gradient(90deg, #ffc107, #fd7e14); }
    .metric-card.info::before { background: linear-gradient(90deg, #17a2b8, #20c997); }
    .metric-card.danger::before { background: linear-gradient(90deg, #dc3545, #e83e8c); }

    .metric-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 0.75rem;
    }

    .metric-card.primary .metric-icon {
        background: linear-gradient(135deg, rgba(90, 103, 216, 0.1), rgba(102, 126, 234, 0.15));
        color: #5a67d8;
    }

    .metric-card.warning .metric-icon {
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(253, 126, 20, 0.15));
        color: #ffc107;
    }

    .metric-card.info .metric-icon {
        background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(32, 201, 151, 0.15));
        color: #17a2b8;
    }

    .metric-card.danger .metric-icon {
        background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(232, 62, 140, 0.15));
        color: #dc3545;
    }

    .metric-value {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        line-height: 1.2;
    }

    [data-theme="light"] .metric-value {
        color: #2d3748;
    }

    [data-theme="dark"] .metric-value {
        color: #f7fafc;
    }

    .metric-label {
        font-size: 0.875rem;
        opacity: 0.8;
        font-weight: 500;
    }

    .modern-card {
        border-radius: 16px;
        border: none;
        transition: all 0.3s ease;
    }

    [data-theme="light"] .modern-card {
        background: white;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }

    [data-theme="dark"] .modern-card {
        background: #2d3748;
        box-shadow: 0 2px 12px rgba(0,0,0,0.3);
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

    <!-- Cards de Métricas -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="metric-card primary">
                <div class="metric-icon">
                    <i class="ri-refresh-line"></i>
                </div>
                <div class="metric-value" id="total-remarketing">0</div>
                <div class="metric-label">Total em Remarketing</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="metric-card info">
                <div class="metric-icon">
                    <i class="ri-calendar-line"></i>
                </div>
                <div class="metric-value" id="total-mes-atual">0</div>
                <div class="metric-label">Leads do Mês Atual</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="metric-card warning">
                <div class="metric-icon">
                    <i class="ri-filter-line"></i>
                </div>
                <div class="metric-value" id="total-filtrado">0</div>
                <div class="metric-label">Resultados Filtrados</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="metric-card danger">
                <div class="metric-icon">
                    <i class="ri-user-line"></i>
                </div>
                <div class="metric-value" id="total-bases">0</div>
                <div class="metric-label">Corretores</div>
            </div>
        </div>
    </div>

    <!-- Painel de Filtros -->
    <div class="card modern-card mb-3">
        <div class="card-body">
            <h6 class="mb-3"><i class="ri-filter-3-line me-2"></i>Filtros Avançados</h6>
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-2">
                    <label class="form-label">Mês de Importação</label>
                    <select id="filtro_mes" class="form-select">
                        <option value="">Todos os meses</option>
                        @foreach ($meses as $num => $nome)
                            <option value="{{ $num }}">{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label">Ano</label>
                    <select id="filtro_ano" class="form-select">
                        <option value="">Todos</option>
                        @for ($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label">Período Personalizado</label>
                    <input type="text" id="filtro_periodo" class="form-control flatpickr-range" placeholder="Selecione o período">
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label">Corretor</label>
                    <select id="filtro_corretor" class="form-select">
                        <option value="">Todos os corretores</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->name }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <button id="limpar_filtros" class="btn btn-outline-secondary w-100">
                        <i class="ri-refresh-line me-1"></i>Limpar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Leads -->
    <div class="card modern-card">
        <div class="card-header">
            <h5 class="mb-0"><i class="ri-list-check-2 me-2"></i>Fila de Remarketing</h5>
        </div>
        <div class="card-body">
            <div class="card-datatable table-responsive">
                <table class="datatables-customers table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="form-check-input" id="selectAll"></th>
                            <th>ID</th>
                            <th>NOME</th>
                            <th>MOTIVO</th>
                            <th>TELEFONE PRINCIPAL</th>
                            <th>PLANO</th>
                            <th>CATEGORIA</th>
                            <th>CORRETOR</th>
                            <th>DATA IMPORTAÇÃO</th>
                            <th>ÚLTIMA ATUALIZAÇÃO</th>
                            <th></th>
                        </tr>
                    </thead>
                </table>
            </div>
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
                                    @foreach ($tabulations as $tabulation)
                                        <option value="{{ $tabulation->id }}">
                                            {{ strtoupper($tabulation->descricao) }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="ecommerce-product-name">Selecionar o status</label>
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
