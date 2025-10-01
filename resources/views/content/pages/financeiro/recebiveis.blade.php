@extends('layouts/layoutMaster')

@section('title', 'Financeiro - Recebíveis')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('page-style')
    <style>
        .card-metric {
            text-align: center;
            padding: 1.5rem;
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }
        .card-metric:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        .card-metric::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        .card-metric.success::before { background: linear-gradient(90deg, #28a745, #20c997); }
        .card-metric.warning::before { background: linear-gradient(90deg, #ffc107, #fd7e14); }
        .card-metric.danger::before { background: linear-gradient(90deg, #dc3545, #e83e8c); }

        .card-metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        .card-metric.success .card-metric-icon {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.2));
            color: #28a745;
        }
        .card-metric.warning .card-metric-icon {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(253, 126, 20, 0.2));
            color: #ffc107;
        }
        .card-metric.danger .card-metric-icon {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(232, 62, 140, 0.2));
            color: #dc3545;
        }

        .card-metric h4 {
            margin: 0.5rem 0;
            font-weight: 700;
            font-size: 1.75rem;
        }
        .card-metric h4.text-success { color: #28a745 !important; }
        .card-metric h4.text-warning { color: #ffc107 !important; }
        .card-metric h4.text-danger { color: #dc3545 !important; }

        .card-metric span {
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        [data-theme="light"] .card-metric span {
            color: #6c757d;
        }
        [data-theme="dark"] .card-metric span {
            color: #a1a5b7;
        }

        .table-modern {
            border-radius: 8px;
            overflow: hidden;
        }
        .table-modern thead th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border: none;
            padding: 1rem;
        }

        [data-theme="light"] .table-modern thead th {
            background: #f8f9fa;
            color: #495057 !important;
            border-bottom: 2px solid #dee2e6;
        }

        [data-theme="dark"] .table-modern thead th {
            background: #5a67d8;
            color: white !important;
        }
        .table-modern tbody tr {
            transition: all 0.2s;
            border-bottom: 1px solid #f0f0f0;
        }
        .table-modern tbody tr:hover {
            background: #f8f9ff;
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .table-modern tbody td {
            padding: 1rem;
            vertical-align: middle;
            border: none;
        }

        [data-theme="light"] .table-modern tbody td {
            color: #4b5563;
        }
        [data-theme="light"] .table-modern tbody td strong {
            color: #1f2937;
        }
        [data-theme="light"] .table-modern tbody td.text-success {
            color: #28a745 !important;
        }
        [data-theme="light"] .table-modern tbody td.text-warning {
            color: #ffc107 !important;
        }

        .badge-modern {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-modern {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
        }
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

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
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }

        [data-theme="dark"] .modal-modern .modal-header {
            background: #5a67d8;
            color: white;
        }

        [data-theme="dark"] .modal-modern .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        .modal-modern .modal-title {
            font-weight: 700;
            font-size: 1.25rem;
        }
        .modal-modern .modal-body {
            padding: 2rem;
        }
    </style>
@endsection

@section('page-script')
    @vite(['resources/assets/js/recebiveis.js'])
@endsection

@section('content')
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card card-metric success">
            <div class="card-metric-icon">
                <i class="ri-checkbox-circle-line"></i>
            </div>
            <h4 class="text-success">R$ {{ number_format($totais['pago'], 2, ',', '.') }}</h4>
            <span>Valor Pago</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-metric warning">
            <div class="card-metric-icon">
                <i class="ri-time-line"></i>
            </div>
            <h4 class="text-warning">R$ {{ number_format($totais['pendente'], 2, ',', '.') }}</h4>
            <span>Valor Pendente</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-metric danger">
            <div class="card-metric-icon">
                <i class="ri-alert-line"></i>
            </div>
            <h4 class="text-danger">R$ {{ number_format($totais['atraso'], 2, ',', '.') }}</h4>
            <span>Em Atraso</span>
        </div>
    </div>
</div>

<!-- DataTable -->
<div class="card" style="border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
    <div class="card-datatable table-responsive">
        <table id="recebiveisTable" class="table table-modern">
            <thead>
                <tr>
                    <th>Contrato</th>
                    <th>Operadora</th>
                    <th>Vendedor</th>
                    <th>Valor Apolice</th>
                    <th>Valor Total</th>
                    <th>Pago</th>
                    <th>Pendente</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contratos as $contrato)
                    <tr>
                        <td><strong>{{ $contrato->venda->nome_contrato ?? '—' }}</strong></td>
                        <td>{{ $contrato->operadora }}</td>
                        <td>{{ $contrato->vendedor->name ?? '—' }}</td>
                        <td>R$ {{ number_format($contrato->venda->valor_contrato, 2, ',', '.') }}</td>
                        <td><strong>R$ {{ number_format($contrato->valor_total, 2, ',', '.') }}</strong></td>
                        <td class="text-success">R$ {{ number_format($contrato->valor_pago, 2, ',', '.') }}</td>
                        <td class="text-warning">R$ {{ number_format($contrato->valor_pendente, 2, ',', '.') }}</td>
                        <td>
                            @if($contrato->valor_pendente == 0)
                                <span class="badge badge-modern bg-success">Quitado</span>
                            @elseif($contrato->em_atraso)
                                <span class="badge badge-modern bg-danger">Atrasado</span>
                            @else
                                <span class="badge badge-modern bg-warning">Pendente</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-modern view-parcelas"
                                data-id="{{ $contrato->venda->id }}">
                                <i class="ri-eye-line"></i> Ver Parcelas
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Parcelas -->
<div class="modal fade modal-modern" id="parcelasModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">
                <i class="ri-file-list-3-line me-2"></i>
                Parcelas do Contrato
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <table class="table table-modern" id="parcelasTable">
                <thead>
                    <tr>
                        <th>Parcela</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- preenchido via JS -->
                </tbody>
            </table>
        </div>
    </div>
  </div>
</div>
@endsection
