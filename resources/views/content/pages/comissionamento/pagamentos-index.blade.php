@extends('layouts/layoutMaster')

@section('title', 'Pagamentos de Comissão')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/toastr/toastr.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss'
    ])
    <style>
      /* Mantém ações numa linha e garante clique nos botões/ícones */
      #tabela-pagamentos td:last-child { white-space: nowrap; }
      #tabela-pagamentos .btn i { pointer-events: none; }
      #tabela-pagamentos .btn, #tabela-pagamentos .btn * { pointer-events: auto !important; }
      #tabela-pagamentos .btn { cursor: pointer; }

      /* Cards de métricas */
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
      .card-metric.primary::before { background: linear-gradient(90deg, #5a67d8, #667eea); }
      .card-metric.warning::before { background: linear-gradient(90deg, #ffc107, #fd7e14); }
      .card-metric.success::before { background: linear-gradient(90deg, #28a745, #20c997); }
      .card-metric.info::before { background: linear-gradient(90deg, #17a2b8, #20c997); }

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
      .card-metric.primary .card-metric-icon {
          background: linear-gradient(135deg, rgba(90, 103, 216, 0.1), rgba(102, 126, 234, 0.2));
          color: #5a67d8;
      }
      .card-metric.warning .card-metric-icon {
          background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(253, 126, 20, 0.2));
          color: #ffc107;
      }
      .card-metric.success .card-metric-icon {
          background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.2));
          color: #28a745;
      }
      .card-metric.info .card-metric-icon {
          background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(32, 201, 151, 0.2));
          color: #17a2b8;
      }

      .card-metric h4 {
          margin: 0.5rem 0;
          font-weight: 700;
          font-size: 1.5rem;
      }
      .card-metric h4.text-primary { color: #5a67d8 !important; }
      .card-metric h4.text-warning { color: #ffc107 !important; }
      .card-metric h4.text-success { color: #28a745 !important; }
      .card-metric h4.text-info { color: #17a2b8 !important; }

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

      /* Tabela moderna */
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
          transform: scale(1.005);
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

      .badge-modern {
          padding: 0.5rem 1rem;
          border-radius: 20px;
          font-weight: 600;
          font-size: 0.75rem;
          text-transform: uppercase;
          letter-spacing: 0.5px;
      }

      /* Card de filtros */
      .filter-card {
          border-radius: 12px;
          border: none;
          box-shadow: 0 4px 12px rgba(0,0,0,0.08);
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

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/toastr/toastr.js',
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js'
    ])
@endsection

@section('page-script')
    @vite('resources/assets/js/comissionamento-pagamentos-index.js')
@endsection

@section('content')

    {{-- raiz com endpoints/flags pro JS --}}
    <div
        id="pgmts-root"
        data-url="{{ route('comissionamento.pagamentos.data') }}"
        data-pdf-base="{{ route('comissionamento.pagamento.pdf', ['pagamento' => 'PAYMENT_ID']) }}"
        data-estornar-url="{{ route('comissionamento.pagamentos.estornar', ['id' => 'PAYMENT_ID']) }}"
        data-contas-base="{{ route('contas.byUser') }}?user_id=USER_ID"
        data-pagar-base="{{ route('comissao.pagar', ['id' => 'PAYMENT_ID']) }}"
        data-role="{{ (string) auth()->user()->user_role_id }}"
    ></div>

    {{-- Cards de Métricas --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-metric primary">
                <div class="card-metric-icon">
                    <i class="ri-money-dollar-circle-line"></i>
                </div>
                <h4 class="text-primary" id="total-bruto">R$ 0,00</h4>
                <span>Total Bruto</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-metric warning">
                <div class="card-metric-icon">
                    <i class="ri-percent-line"></i>
                </div>
                <h4 class="text-warning" id="total-imposto">R$ 0,00</h4>
                <span>Total Imposto</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-metric success">
                <div class="card-metric-icon">
                    <i class="ri-wallet-line"></i>
                </div>
                <h4 class="text-success" id="total-liquido">R$ 0,00</h4>
                <span>Total Líquido</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-metric info">
                <div class="card-metric-icon">
                    <i class="ri-bank-card-line"></i>
                </div>
                <h4 class="text-info" id="total-receber">R$ 0,00</h4>
                <span>Total a Receber</span>
            </div>
        </div>
    </div>

    <div class="card mb-4 filter-card">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Mês</label>
                    <input type="month" id="filtro-mes" class="form-control"
                        value="{{ \Carbon\Carbon::now('America/Sao_Paulo')->format('Y-m') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vendedor</label>
                    <select id="filtro-vendedor" class="form-select select2" data-placeholder="Todos">
                        <option value="">Todos</option>
                        @foreach ($vendedores as $v)
                            <option value="{{ $v->id }}">{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Criado por</label>
                    <select id="filtro-criado-por" class="form-select select2" data-placeholder="Todos">
                        <option value="">Todos</option>
                        @foreach ($criadores as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="btn-aplicar" class="btn btn-primary w-100">
                        <i class="ri-filter-3-line me-1"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card filter-card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabela-pagamentos" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Vendedor</th>
                            <th>Mês</th>
                            <th>Data Pagto</th> {{-- agora é pago_em --}}
                            <th class="text-end">% Com.</th>
                            <th class="text-end">% Imp.</th>
                            <th class="text-end">Bruto</th>
                            <th class="text-end">Imposto</th>
                            <th class="text-end">Líquido</th>
                            <th class="text-end">Total a Receber</th>
                            <th>Lançado por</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr class="fw-semibold">
                            <td colspan="6" class="text-end">Totais:</td>
                            <td id="ft-bruto" class="text-end">R$ 0,00</td>
                            <td id="ft-imp" class="text-end">R$ 0,00</td>
                            <td id="ft-liq" class="text-end">R$ 0,00</td>
                            <td id="ft-total" class="text-end">R$ 0,00</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal: Registrar Pagamento --}}
    <div class="modal fade modal-modern" id="modalPagar" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <form id="formPagar" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
                <i class="ri-bank-card-line me-2"></i>
                Registrar pagamento
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>

          <div class="modal-body">
            <input type="hidden" id="pg_pagamento_id">
            <input type="hidden" id="pg_user_id">

            <div class="mb-3">
              <label for="pg_conta" class="form-label">Conta de pagamento</label>
              <select id="pg_conta" class="form-select" required>
                <option value="" selected disabled>Carregando contas...</option>
              </select>
              <div class="form-text">Se não selecionar, será usada a conta padrão do vendedor (se existir).</div>
            </div>

            <div class="mb-3">
              <label for="pg_pago_em" class="form-label">Data do pagamento</label>
              <input type="date" id="pg_pago_em" class="form-control" required>
            </div>

            <div class="alert alert-warning d-none" id="pg_alert_no_accounts">
              Este vendedor não possui contas cadastradas. Cadastre uma conta padrão antes de pagar.
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success">
              <i class="ri-check-line me-1"></i> Confirmar pagamento
            </button>
          </div>
        </form>
      </div>
    </div>

@endsection
