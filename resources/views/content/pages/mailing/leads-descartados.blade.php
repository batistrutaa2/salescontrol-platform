@extends('layouts/layoutMaster')

@section('title', 'Leads Descartados')

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/dropzone/dropzone.js', 'resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/assets/js/leads-descartados.js'])
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

    .metric-card.danger::before { background: linear-gradient(90deg, #dc3545, #e83e8c); }
    .metric-card.warning::before { background: linear-gradient(90deg, #ffc107, #fd7e14); }
    .metric-card.info::before { background: linear-gradient(90deg, #17a2b8, #20c997); }
    .metric-card.secondary::before { background: linear-gradient(90deg, #6c757d, #868e96); }

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

    .metric-card.danger .metric-icon {
        background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(232, 62, 140, 0.15));
        color: #dc3545;
    }

    .metric-card.warning .metric-icon {
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(253, 126, 20, 0.15));
        color: #ffc107;
    }

    .metric-card.info .metric-icon {
        background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(32, 201, 151, 0.15));
        color: #17a2b8;
    }

    .metric-card.secondary .metric-icon {
        background: linear-gradient(135deg, rgba(108, 117, 125, 0.1), rgba(134, 142, 150, 0.15));
        color: #6c757d;
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

    <!-- Cards de Métricas -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="metric-card danger">
                <div class="metric-icon">
                    <i class="ri-close-circle-line"></i>
                </div>
                <div class="metric-value" id="total-descartados">0</div>
                <div class="metric-label">Total Descartados</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="metric-card info">
                <div class="metric-icon">
                    <i class="ri-calendar-line"></i>
                </div>
                <div class="metric-value" id="total-mes-atual">0</div>
                <div class="metric-label">Descartados no Mês</div>
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
            <div class="metric-card secondary">
                <div class="metric-icon">
                    <i class="ri-database-2-line"></i>
                </div>
                <div class="metric-value" id="total-bases">0</div>
                <div class="metric-label">Bases de Origem</div>
            </div>
        </div>
    </div>

    <!-- Painel de Filtros -->
    <div class="card modern-card mb-3">
        <div class="card-body">
            <h6 class="mb-3"><i class="ri-filter-3-line me-2"></i>Filtros Avançados</h6>
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label">Mês</label>
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

                <div class="col-12 col-md-2">
                    <label class="form-label">Base de Origem</label>
                    <select id="filtro_base" class="form-select">
                        <option value="">Todas as bases</option>
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <button id="limpar_filtros" class="btn btn-outline-secondary w-100">
                        <i class="ri-refresh-line me-1"></i>Limpar Filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Leads -->
    <div class="card modern-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="ri-list-check-2 me-2"></i>Leads Descartados</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped w-100" id="leadsDescartadosTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Telefone</th>
                            <th>Valor do Plano</th>
                            <th>Base Origem</th>
                            <th>Data Criação</th>
                            <th>Última Atualização</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>


    <!-- Modal de Comentários -->
    <div class="modal fade" id="modalComentarios" tabindex="-1" aria-labelledby="modalComentariosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalComentariosLabel">Histórico de Comentários</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group" id="comentariosList">
                        <li class="list-group-item">Carregando...</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

@endsection
