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
        .card-metric { text-align: center; padding: 1rem; }
        .card-metric h4 { margin: 0; font-weight: bold; }
        .card-metric span { font-size: 0.9rem; color: #6c757d; }
    </style>
@endsection

@section('page-script')
    @vite(['resources/assets/js/recebiveis.js'])
@endsection

@section('content')
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card card-metric">
            <h4 class="text-success">R$ {{ number_format($totais['pago'], 2, ',', '.') }}</h4>
            <span>Valor Pago</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-metric">
            <h4 class="text-warning">R$ {{ number_format($totais['pendente'], 2, ',', '.') }}</h4>
            <span>Valor Pendente</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-metric">
            <h4 class="text-danger">R$ {{ number_format($totais['atraso'], 2, ',', '.') }}</h4>
            <span>Em Atraso</span>
        </div>
    </div>
</div>

<!-- DataTable -->
<div class="card">
    <div class="card-datatable table-responsive">
        <table id="recebiveisTable" class="table table-bordered">
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
                        <td>{{ $contrato->venda->nome_contrato ?? '—' }}</td>
                        <td>{{ $contrato->operadora }}</td>
                        <td>{{ $contrato->vendedor->name ?? '—' }}</td>
                        <td>R$ {{ number_format($contrato->venda->valor_contrato, 2, ',', '.') }}</td>
                        <td>R$ {{ number_format($contrato->valor_total, 2, ',', '.') }}</td>
                        <td>R$ {{ number_format($contrato->valor_pago, 2, ',', '.') }}</td>
                        <td>R$ {{ number_format($contrato->valor_pendente, 2, ',', '.') }}</td>
                        <td>
                            @if($contrato->valor_pendente == 0)
                                <span class="badge bg-success">Quitado</span>
                            @elseif($contrato->em_atraso)
                                <span class="badge bg-danger">Atrasado</span>
                            @else
                                <span class="badge bg-warning">Pendente</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary view-parcelas"
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
<div class="modal fade" id="parcelasModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Parcelas do Contrato</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <table class="table table-bordered" id="parcelasTable">
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
