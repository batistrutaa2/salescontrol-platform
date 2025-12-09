@extends('layouts/layoutMaster')

@section('title', 'Backoffice - Fila de Contratos')

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
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

        .light-style .modern-card {
            background: white;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }

        .dark-style .modern-card {
            background: #2b2c40;
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

        .light-style .metric-card {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .dark-style .metric-card {
            background: #2b2c40;
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

        .metric-card.primary::before { background: linear-gradient(90deg, #696cff, #8b5cf6); }
        .metric-card.success::before { background: linear-gradient(90deg, #28a745, #20c997); }
        .metric-card.warning::before { background: linear-gradient(90deg, #ffab00, #fd7e14); }
        .metric-card.danger::before { background: linear-gradient(90deg, #ff3e1d, #e83e8c); }
        .metric-card.info::before { background: linear-gradient(90deg, #03c3ec, #20c997); }

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
            background: linear-gradient(135deg, rgba(105, 108, 255, 0.1), rgba(139, 92, 246, 0.15));
            color: #696cff;
        }
        .metric-card.success .metric-icon {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.15));
            color: #28a745;
        }
        .metric-card.warning .metric-icon {
            background: linear-gradient(135deg, rgba(255, 171, 0, 0.1), rgba(253, 126, 20, 0.15));
            color: #ffab00;
        }
        .metric-card.danger .metric-icon {
            background: linear-gradient(135deg, rgba(255, 62, 29, 0.1), rgba(232, 62, 140, 0.15));
            color: #ff3e1d;
        }

        .metric-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0.25rem 0;
            line-height: 1;
        }

        .metric-card.primary .metric-value { color: #696cff; }
        .metric-card.success .metric-value { color: #28a745; }
        .metric-card.warning .metric-value { color: #ffab00; }
        .metric-card.danger .metric-value { color: #ff3e1d; }

        .metric-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.8;
        }

        /* Fluxo Visual do Pipeline */
        .pipeline-flow-diagram {
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            overflow-x: auto;
        }

        .light-style .pipeline-flow-diagram {
            background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
            border: 1px solid #e9ecef;
        }

        .dark-style .pipeline-flow-diagram {
            background: linear-gradient(135deg, #2b2c40 0%, #32334a 100%);
            border: 1px solid #3b3c54;
        }

        .flow-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 1rem;
            opacity: 0.7;
        }

        .flow-container {
            display: flex;
            align-items: flex-start;
            gap: 0;
            min-width: max-content;
        }

        .flow-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 100px;
            text-align: center;
        }

        .flow-step-badge {
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 0.375rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .flow-step-badge:hover {
            transform: scale(1.05);
        }

        .flow-step-badge i {
            font-size: 0.875rem;
        }

        .flow-step-badge.step-venda { background: linear-gradient(135deg, #696cff, #8b5cf6); }
        .flow-step-badge.step-analise-docs { background: linear-gradient(135deg, #03c3ec, #06b6d4); }
        .flow-step-badge.step-analise-operadora { background: linear-gradient(135deg, #ffab00, #fbbf24); color: #1e293b; }
        .flow-step-badge.step-contrato { background: linear-gradient(135deg, #a855f7, #c084fc); }
        .flow-step-badge.step-assinatura { background: linear-gradient(135deg, #eab308, #facc15); color: #1e293b; }
        .flow-step-badge.step-boleto { background: linear-gradient(135deg, #14b8a6, #2dd4bf); }
        .flow-step-badge.step-implantado { background: linear-gradient(135deg, #10b981, #34d399); }
        .flow-step-badge.step-pendencia { background: linear-gradient(135deg, #ff3e1d, #f87171); }
        .flow-step-badge.step-regularizado { background: linear-gradient(135deg, #22c55e, #4ade80); }
        .flow-step-badge.step-estorno { background: linear-gradient(135deg, #dc2626, #ef4444); }
        .flow-step-badge.step-declinado { background: linear-gradient(135deg, #64748b, #94a3b8); }

        .flow-arrow {
            display: flex;
            align-items: center;
            padding: 0 0.5rem;
            color: #696cff;
            font-size: 1.125rem;
            opacity: 0.6;
        }

        .flow-desvio {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 2px dashed rgba(255, 62, 29, 0.3);
        }

        .flow-desvio-label {
            font-size: 0.5625rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #ff3e1d;
            margin-bottom: 0.5rem;
        }

        .flow-desvio-steps {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .flow-exits {
            margin-left: 1.5rem;
            padding-left: 1.5rem;
            border-left: 2px solid rgba(255, 62, 29, 0.2);
        }

        .flow-exits-label {
            font-size: 0.5625rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #dc2626;
            margin-bottom: 0.5rem;
        }

        .flow-exits-steps {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
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
            background: linear-gradient(135deg, #696cff 0%, #8b5cf6 100%);
            color: white;
        }

        .modal-modern .modal-header .btn-close {
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

    <!-- Fluxo Visual do Pipeline -->
    <div class="pipeline-flow-diagram">
        <div class="flow-title">
            <i class="ri-flow-chart me-1"></i>
            Fluxo do Processo de Implantação
        </div>
        <div class="d-flex align-items-start">
            <div class="flow-container">
                <div class="flow-step">
                    <span class="flow-step-badge step-venda" onclick="filterByStatus('VENDA')">
                        <i class="ri-shopping-cart-2-line"></i> Venda
                    </span>
                </div>
                <div class="flow-arrow"><i class="ri-arrow-right-s-line"></i></div>
                <div class="flow-step">
                    <span class="flow-step-badge step-analise-docs" onclick="filterByStatus('ANALISE DE DOCUMENTOS')">
                        <i class="ri-file-search-line"></i> Análise Docs
                    </span>
                </div>
                <div class="flow-arrow"><i class="ri-arrow-right-s-line"></i></div>
                <div class="flow-step">
                    <span class="flow-step-badge step-analise-operadora" onclick="filterByStatus('ANALISE OPERADORA')">
                        <i class="ri-building-line"></i> Análise Operadora
                    </span>
                    <div class="flow-desvio">
                        <span class="flow-desvio-label"><i class="ri-arrow-down-s-line"></i> Desvio</span>
                        <div class="flow-desvio-steps">
                            <span class="flow-step-badge step-pendencia" onclick="filterByStatus('PENDENCIA')">
                                <i class="ri-error-warning-line"></i> Pendência
                            </span>
                            <i class="ri-arrow-left-right-line" style="color: #ff3e1d; font-size: 0.875rem;"></i>
                            <span class="flow-step-badge step-regularizado" onclick="filterByStatus('REGULARIZADO')">
                                <i class="ri-checkbox-circle-line"></i> Regularizado
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flow-arrow"><i class="ri-arrow-right-s-line"></i></div>
                <div class="flow-step">
                    <span class="flow-step-badge step-contrato" onclick="filterByStatus('CONTR. GERADO - AGUARDANDO ASSINATURA')">
                        <i class="ri-file-text-line"></i> Contrato Gerado
                    </span>
                </div>
                <div class="flow-arrow"><i class="ri-arrow-right-s-line"></i></div>
                <div class="flow-step">
                    <span class="flow-step-badge step-assinatura" onclick="filterByStatus('AGUARD. ASSINATURA DA DS')">
                        <i class="ri-edit-line"></i> Aguard. DS
                    </span>
                </div>
                <div class="flow-arrow"><i class="ri-arrow-right-s-line"></i></div>
                <div class="flow-step">
                    <span class="flow-step-badge step-boleto" onclick="filterByStatus('BOLETO DISPONIVEL')">
                        <i class="ri-bank-card-line"></i> Boleto Disponível
                    </span>
                </div>
                <div class="flow-arrow"><i class="ri-arrow-right-s-line"></i></div>
                <div class="flow-step">
                    <span class="flow-step-badge step-implantado" onclick="filterByStatus('IMPLANTADO')">
                        <i class="ri-check-double-line"></i> Implantado
                    </span>
                </div>
            </div>
            <div class="flow-exits">
                <span class="flow-exits-label"><i class="ri-close-circle-line"></i> Saídas</span>
                <div class="flow-exits-steps">
                    <span class="flow-step-badge step-estorno" onclick="filterByStatus('ESTORNO')">
                        <i class="ri-arrow-go-back-line"></i> Estorno
                    </span>
                    <span class="flow-step-badge step-declinado" onclick="filterByStatus('DECLINADO')">
                        <i class="ri-close-circle-line"></i> Declinado
                    </span>
                </div>
            </div>
        </div>
    </div>

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
                    <option value="VENDA">VENDA</option>
                    <option value="ANALISE DE DOCUMENTOS">ANÁLISE DE DOCUMENTOS</option>
                    <option value="ANALISE OPERADORA">ANÁLISE OPERADORA</option>
                    <option value="PENDENCIA">PENDÊNCIA</option>
                    <option value="REGULARIZADO">REGULARIZADO</option>
                    <option value="CONTR. GERADO - AGUARDANDO ASSINATURA">CONTR. GERADO - AGUARDANDO ASSINATURA</option>
                    <option value="AGUARD. ASSINATURA DA DS">AGUARD. ASSINATURA DA DS</option>
                    <option value="BOLETO DISPONIVEL">BOLETO DISPONÍVEL</option>
                    <option value="IMPLANTADO">IMPLANTADO</option>
                    <option value="ESTORNO">ESTORNO</option>
                    <option value="DECLINADO">DECLINADO</option>
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
                <button id="btn_limpar_filtro" class="btn btn-primary" type="button">
                    <i class="ri-refresh-line me-1"></i> Limpar Filtros
                </button>
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
                        <th>Última Atualização</th>
                        <th>Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <!--/ Ajax Sourced Server-side -->

    <!-- Modal Alterar Status -->
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
                            <div class="mb-3">
                                <label for="label" class="form-label fw-semibold">Novo Status</label>
                                <select class="form-select" id="label" name="tabulacao_id" required>
                                    <option value="">Selecione o Status</option>
                                    @foreach ($tabulacoes as $tabulation)
                                        <option value="{{ $tabulation->id }}">{{ strtoupper($tabulation->descricao) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="proof-group-data-implantacao" class="mb-3" style="display: none;">
                                <label for="data_implantacao" class="form-label fw-semibold">Data de Implantação</label>
                                <input type="date" id="data_implantacao" name="data_implantacao" class="form-control">
                            </div>

                            <div id="proof-group-numero_proposta" class="mb-3" style="display: none;">
                                <label for="numero_proposta" class="form-label fw-semibold">Número da Proposta</label>
                                <input type="text" id="numero_proposta" name="numero_proposta" class="form-control">
                            </div>

                            <div id="proof-group-data-pendencia" class="mb-3" style="display: none;">
                                <label for="data_pendencia" class="form-label fw-semibold">Motivo da Pendência</label>
                                <textarea id="data_pendencia" name="motivo_pendencia" class="form-control" rows="4"
                                    placeholder="Descreva o motivo da pendência..." style="min-height: 120px; resize: vertical;"></textarea>
                            </div>

                            <div id="proof-group" class="mb-3" style="display: none;">
                                <label for="comprovante" class="form-label fw-semibold">Comprovante de Pagamento</label>
                                <input type="file" id="comprovante" name="comprovante" class="form-control"
                                    accept="image/*,application/pdf">
                            </div>

                            <div id="proof-group-boleto-disponivel" class="mb-3" style="display: none;">
                                <label for="boleto_disponivel" class="form-label fw-semibold">Boleto Disponível</label>
                                <input type="file" id="boleto_disponivel" name="boleto_disponivel" class="form-control"
                                    accept="image/*,application/pdf">
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-check-line me-1"></i> Confirmar Alteração
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Função para filtrar por status ao clicar no fluxo
        function filterByStatus(status) {
            const select = document.getElementById('status_filter');
            if (select) {
                // Encontrar a opção que começa com o status (para lidar com variações)
                for (let option of select.options) {
                    if (option.value.toUpperCase().includes(status.toUpperCase()) ||
                        status.toUpperCase().includes(option.value.toUpperCase())) {
                        select.value = option.value;
                        break;
                    }
                }
                // Disparar evento change para atualizar a tabela
                select.dispatchEvent(new Event('change'));
            }
        }
    </script>
@endsection
