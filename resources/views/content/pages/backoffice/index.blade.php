@extends('layouts/layoutMaster')

@section('title', 'Backoffice - Contratos')

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/quill/editor.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/assets/js/backoffice.js'])
@endsection

@section('page-style')
    <style>
        tr.overdue {
            color: #dc3545 !important;
            font-weight: bold;
        }

        /* Cards modernos */
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

        .modern-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }

        /* Metric cards */
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

        .metric-card {
            cursor: pointer;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }

        .metric-card.active-filter {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            border: 2px solid;
        }

        .metric-card.primary.active-filter {
            border-color: #5a67d8;
        }

        .metric-card.success.active-filter {
            border-color: #28a745;
        }

        .metric-card.warning.active-filter {
            border-color: #ffc107;
        }

        .metric-card.danger.active-filter {
            border-color: #dc3545;
        }

        .metric-card.info.active-filter {
            border-color: #17a2b8;
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
        .metric-card.success::before { background: linear-gradient(90deg, #28a745, #20c997); }
        .metric-card.warning::before { background: linear-gradient(90deg, #ffc107, #fd7e14); }
        .metric-card.danger::before { background: linear-gradient(90deg, #dc3545, #e83e8c); }
        .metric-card.info::before { background: linear-gradient(90deg, #17a2b8, #20c997); }

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
        .metric-card.success .metric-icon {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.15));
            color: #28a745;
        }
        .metric-card.warning .metric-icon {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(253, 126, 20, 0.15));
            color: #ffc107;
        }
        .metric-card.danger .metric-icon {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(232, 62, 140, 0.15));
            color: #dc3545;
        }
        .metric-card.info .metric-icon {
            background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(32, 201, 151, 0.15));
            color: #17a2b8;
        }

        .metric-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0.25rem 0;
            line-height: 1;
        }

        .metric-card.primary .metric-value { color: #5a67d8; }
        .metric-card.success .metric-value { color: #28a745; }
        .metric-card.warning .metric-value { color: #ffc107; }
        .metric-card.danger .metric-value { color: #dc3545; }
        .metric-card.info .metric-value { color: #17a2b8; }

        .metric-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.8;
        }

        [data-theme="light"] .metric-label {
            color: #6c757d;
        }

        [data-theme="dark"] .metric-label {
            color: #a1a5b7;
        }

        /* Modal moderno */
        .modal-modern .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .modal-modern .modal-header {
            border-radius: 16px 16px 0 0;
            padding: 1.5rem;
            border: none;
        }

        [data-theme="light"] .modal-modern .modal-header {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        [data-theme="dark"] .modal-modern .modal-header {
            background: #5a67d8;
            color: white;
        }

        [data-theme="dark"] .modal-modern .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .modal-modern .modal-body {
            padding: 2rem;
        }

        /* Badges modernos */
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
    </style>


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

    <!-- Cards de Indicadores -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="metric-card primary">
                <div class="metric-icon">
                    <i class="ri-file-list-3-line"></i>
                </div>
                <div class="metric-value" id="total-contratos">0</div>
                <div class="metric-label">Total de Contratos</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="metric-card success">
                <div class="metric-icon">
                    <i class="ri-checkbox-circle-line"></i>
                </div>
                <div class="metric-value" id="total-implantado">0</div>
                <div class="metric-label">Implantados</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="metric-card warning">
                <div class="metric-icon">
                    <i class="ri-time-line"></i>
                </div>
                <div class="metric-value" id="total-analise">0</div>
                <div class="metric-label">Em Análise</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="metric-card danger">
                <div class="metric-icon">
                    <i class="ri-alert-line"></i>
                </div>
                <div class="metric-value" id="total-atrasados">0</div>
                <div class="metric-label">Atrasados</div>
            </div>
        </div>
    </div>

    <div class="card modern-card mb-3">
        <div class="card-body row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label">Status</label>
                <select id="status_filter" class="form-select">
                    <option value="">Todos</option>
                    <option value="IMPLANTADO">IMPLANTADO</option>
                    <option value="VENDA">VENDA</option>
                    <option value="ESTORNO">ESTORNO</option>
                    <option value="DECLINADO">DECLINADO</option>
                    <option value="ANALISE DOCUMENTO">ANALISE DOCUMENTO</option>
                    <option value="ANALISE OPERADORA">ANALISE OPERADORA</option>
                    <option value="PENDENCIA">PENDENCIA</option>
                    <option value="BOLETO DISPONIVEL">BOLETO DISPONIVEL</option>
                    <option value="REGULARIZADO">REGULARIZADO</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Mês</label>
                <select id="periodo_mes" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($meses as $num => $nome)
                        <option value="{{ $num }}">{{ $nome }}</option>
                    @endforeach
                </select>
            </div>


            <div class="col-6 col-md-2">
                <label class="form-label">Ano</label>
                <select id="periodo_ano" class="form-select">
                    <option value="">Todos</option>
                    @for ($y = now()->year; $y >= now()->year - 6; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="col-12 col-md-4 d-flex gap-2">
                <button id="btn_limpar_filtro" class="btn btn-primary mb-3  " type="button">Limpar</button>
            </div>
        </div>
    </div>


    <!-- Ajax Sourced Server-side -->
    <div class="card modern-card mt-4">
        <h5 class="card-header">
            <i class="ri-folder-open-line me-2"></i>
            Fila de Contratos
        </h5>
        <div class="card-datatable text-nowrap">
            <table class="datatables-ajax table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Corretor</th>
                        <th>Nome Contrato</th>
                        <th>Status</th>
                        <th>Valor Contrato</th>
                        <th>Data Criação</th>
                        <th>Prazo</th>
                        <th>ultima Atualização</th>
                        <th>Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <!--/ Ajax Sourced Server-side -->


    <div class="modal fade modal-modern" id="modalcomments" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ri-refresh-line me-2"></i>
                        Alterar Status do Contrato
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="transferLead" class="row" action="{{ route('backoffice.alterStatusContract') }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="idSale" name="idSale" value="">
                        <div class="col">
                            <div class="form-floating form-floating-outline">
                                <select class="select2  form-select" id="label" name="tabulacao_id">
                                    <option value="">Selecione o Status</option>
                                    @foreach ($tabulacoes as $tabulation)
                                        <option value="{{ $tabulation->id }}">{{ strtoupper($tabulation->descricao) }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="ecommerce-product-name">Selecione o status</label>
                            </div>
                            <div id="proof-group-data-implantacao" class="mt-3" style="display: none;">
                                <label for="data_implantacao" class="form-label">Data de Implantação</label>
                                <input type="date" id="data_implantacao" name="data_implantacao" class="form-control"
                                    required>
                            </div>
                            <div id="proof-group-numero_proposta" class="mt-3" style="display: none;">
                                <label for="numero_proposta" class="form-label">Numero da Proposta</label>
                                <input type="text" id="numero_proposta" name="numero_proposta" class="form-control"
                                    required>
                            </div>
                            <div id="proof-group-data-pendencia" class="mt-3" style="display: none;">
                                <label for="data_pendencia" class="form-label">Motivo da pendência</label>
                                <textarea id="data_pendencia" name="motivo_pendencia" class="form-control" rows="5"
                                    placeholder="Descreva o motivo da pendência..." required style="min-height: 120px; resize: vertical;"></textarea>
                            </div>

                            <div id="proof-group" class="mt-3" style="display: none;">
                                <label for="comprovante" class="form-label">Comprovante de Pagamento</label>
                                <input type="file" id="comprovante" name="comprovante" class="form-control"
                                    accept="image/*,application/pdf">
                            </div>

                            <div id="proof-group-boleto-disponivel" class="mt-3" style="display: none;">
                                <label for="boleto_pagamento" class="form-label">Boleto Disponivel</label>
                                <input type="file" id="boleto_disponivel" name="boleto_disponivel" class="form-control"
                                    accept="image/*,application/pdf">
                            </div>
                            <div>
                                <button class="btn btn-danger btn--twitter mt-5">Alterar Status</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


@endsection
