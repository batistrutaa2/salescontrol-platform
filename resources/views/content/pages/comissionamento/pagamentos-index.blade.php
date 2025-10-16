@extends('layouts/layoutMaster')

@section('title', 'Pagamentos de Comissão')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/toastr/toastr.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/select2/select2.scss'
    ])
    <style>
      /* Cards de métricas */
      .card-metric {
          text-align: center;
          padding: 1.5rem;
          border-radius: 12px;
          border: none;
          transition: transform 0.2s, box-shadow 0.2s;
          position: relative;
          overflow: hidden;
      }

      [data-style="light"] .card-metric {
          box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      }
      [data-style="dark"] .card-metric {
          box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      }

      [data-style="light"] .card-metric:hover {
          transform: translateY(-4px);
          box-shadow: 0 8px 20px rgba(0,0,0,0.12);
      }
      [data-style="dark"] .card-metric:hover {
          transform: translateY(-4px);
          box-shadow: 0 8px 20px rgba(0,0,0,0.4);
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

      [data-style="light"] .card-metric.primary .card-metric-icon {
          background: linear-gradient(135deg, rgba(90, 103, 216, 0.1), rgba(102, 126, 234, 0.2));
          color: #5a67d8;
      }
      [data-style="dark"] .card-metric.primary .card-metric-icon {
          background: linear-gradient(135deg, rgba(90, 103, 216, 0.2), rgba(102, 126, 234, 0.3));
          color: #8b95e8;
      }

      [data-style="light"] .card-metric.warning .card-metric-icon {
          background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(253, 126, 20, 0.2));
          color: #ffc107;
      }
      [data-style="dark"] .card-metric.warning .card-metric-icon {
          background: linear-gradient(135deg, rgba(255, 193, 7, 0.2), rgba(253, 126, 20, 0.3));
          color: #ffd54f;
      }

      [data-style="light"] .card-metric.success .card-metric-icon {
          background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.2));
          color: #28a745;
      }
      [data-style="dark"] .card-metric.success .card-metric-icon {
          background: linear-gradient(135deg, rgba(40, 167, 69, 0.2), rgba(32, 201, 151, 0.3));
          color: #66bb6a;
      }

      [data-style="light"] .card-metric.info .card-metric-icon {
          background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(32, 201, 151, 0.2));
          color: #17a2b8;
      }
      [data-style="dark"] .card-metric.info .card-metric-icon {
          background: linear-gradient(135deg, rgba(23, 162, 184, 0.2), rgba(32, 201, 151, 0.3));
          color: #4dd0e1;
      }

      .card-metric h4 {
          margin: 0.5rem 0;
          font-weight: 700;
          font-size: 1.5rem;
      }

      [data-style="light"] .card-metric h4.text-primary { color: #5a67d8 !important; }
      [data-style="dark"] .card-metric h4.text-primary { color: #8b95e8 !important; }

      [data-style="light"] .card-metric h4.text-warning { color: #ffc107 !important; }
      [data-style="dark"] .card-metric h4.text-warning { color: #ffd54f !important; }

      [data-style="light"] .card-metric h4.text-success { color: #28a745 !important; }
      [data-style="dark"] .card-metric h4.text-success { color: #66bb6a !important; }

      [data-style="light"] .card-metric h4.text-info { color: #17a2b8 !important; }
      [data-style="dark"] .card-metric h4.text-info { color: #4dd0e1 !important; }

      .card-metric span {
          font-size: 0.875rem;
          font-weight: 500;
          text-transform: uppercase;
          letter-spacing: 0.5px;
      }

      [data-style="light"] .card-metric span {
          color: #6c757d;
      }
      [data-style="dark"] .card-metric span {
          color: #a1a5b7;
      }

      /* Card de filtros */
      .filter-card {
          border-radius: 12px;
          border: none;
      }

      [data-style="light"] .filter-card {
          box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      }
      [data-style="dark"] .filter-card {
          box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      }

      /* Cards de pagamento */
      .payment-card {
          border-radius: 12px;
          border: none;
          transition: all 0.3s;
          margin-bottom: 1rem;
      }

      [data-style="light"] .payment-card {
          box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      }
      [data-style="dark"] .payment-card {
          box-shadow: 0 2px 8px rgba(0,0,0,0.25);
      }

      [data-style="light"] .payment-card:hover {
          box-shadow: 0 8px 20px rgba(0,0,0,0.12);
          transform: translateY(-2px);
      }
      [data-style="dark"] .payment-card:hover {
          box-shadow: 0 8px 20px rgba(0,0,0,0.35);
          transform: translateY(-2px);
      }

      .payment-card-header {
          padding: 1.25rem;
          display: flex;
          justify-content: space-between;
          align-items: center;
          flex-wrap: wrap;
          gap: 1rem;
      }

      [data-style="light"] .payment-card-header {
          border-bottom: 2px solid #f0f0f0;
      }
      [data-style="dark"] .payment-card-header {
          border-bottom: 2px solid rgba(255,255,255,0.1);
      }

      .payment-card-body {
          padding: 1.25rem;
      }

      .payment-info {
          display: flex;
          align-items: center;
          gap: 1rem;
      }

      .payment-avatar {
          width: 48px;
          height: 48px;
          border-radius: 50%;
          background: linear-gradient(135deg, #5a67d8, #667eea);
          display: flex;
          align-items: center;
          justify-content: center;
          color: white;
          font-weight: 700;
          font-size: 1.25rem;
      }

      .payment-details h5 {
          margin: 0;
          font-weight: 600;
          font-size: 1.1rem;
      }

      .payment-details small {
          font-size: 0.875rem;
      }

      [data-style="light"] .payment-details small {
          color: #6c757d;
      }
      [data-style="dark"] .payment-details small {
          color: #a1a5b7;
      }

      .payment-values {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
          gap: 1rem;
      }

      .payment-value-item {
          text-align: center;
      }

      .payment-value-item label {
          display: block;
          font-size: 0.75rem;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          margin-bottom: 0.25rem;
      }

      [data-style="light"] .payment-value-item label {
          color: #6c757d;
      }
      [data-style="dark"] .payment-value-item label {
          color: #a1a5b7;
      }

      .payment-value-item .value {
          font-size: 1.1rem;
          font-weight: 700;
      }

      [data-style="light"] .payment-value-item.primary .value { color: #5a67d8; }
      [data-style="dark"] .payment-value-item.primary .value { color: #8b95e8; }

      [data-style="light"] .payment-value-item.warning .value { color: #ffc107; }
      [data-style="dark"] .payment-value-item.warning .value { color: #ffd54f; }

      [data-style="light"] .payment-value-item.success .value { color: #28a745; }
      [data-style="dark"] .payment-value-item.success .value { color: #66bb6a; }

      [data-style="light"] .payment-value-item.info .value { color: #17a2b8; }
      [data-style="dark"] .payment-value-item.info .value { color: #4dd0e1; }

      .payment-actions {
          display: flex;
          gap: 0.5rem;
          flex-wrap: wrap;
      }

      .badge-payment {
          padding: 0.5rem 1rem;
          border-radius: 20px;
          font-weight: 600;
          font-size: 0.75rem;
          text-transform: uppercase;
          letter-spacing: 0.5px;
      }

      /* Date group header */
      .date-group-header {
          padding: 1rem 1.5rem;
          border-radius: 12px;
          margin-bottom: 1rem;
          cursor: pointer;
          transition: all 0.3s;
          user-select: none;
      }

      /* Tema Claro - Fundo claro com bordas e texto escuro */
      [data-style="light"] .date-group-header {
          background: linear-gradient(135deg, #f8f9fa, #e9ecef) !important;
          border: 2px solid #dee2e6;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
      }

      /* Tema Escuro - Fundo escuro com gradiente azulado */
      [data-style="dark"] .date-group-header {
          background: linear-gradient(135deg, #2c3e50, #34495e) !important;
          border: 2px solid rgba(255, 255, 255, 0.1);
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
      }

      [data-style="light"] .date-group-header:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
          border-color: #17a2b8;
      }
      [data-style="dark"] .date-group-header:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
          border-color: rgba(255, 255, 255, 0.2);
      }

      .date-group-header h4 {
          margin: 0;
          font-weight: 700;
          font-size: 1.25rem;
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 0.75rem;
      }

      /* Tema Claro - Texto e ícones em azul escuro */
      [data-style="light"] .date-group-header,
      [data-style="light"] .date-group-header h4,
      [data-style="light"] .date-group-header .date-info,
      [data-style="light"] .date-group-header .date-info i,
      [data-style="light"] .date-group-header .toggle-icon {
          color: #2c3e50 !important;
      }

      /* Tema Escuro - Texto e ícones em branco */
      [data-style="dark"] .date-group-header,
      [data-style="dark"] .date-group-header h4,
      [data-style="dark"] .date-group-header .date-info,
      [data-style="dark"] .date-group-header .date-info i,
      [data-style="dark"] .date-group-header .toggle-icon {
          color: #ffffff !important;
      }

      .date-group-header .date-info {
          display: flex;
          align-items: center;
          gap: 0.75rem;
          flex-wrap: wrap;
      }

      .date-group-header .badge {
          padding: 0.35rem 0.75rem;
          border-radius: 15px;
          font-size: 0.85rem;
          font-weight: 600;
      }

      /* Badges no tema claro - destaque com info */
      [data-style="light"] .date-group-header .badge {
          background: linear-gradient(135deg, #17a2b8, #20c997) !important;
          color: #ffffff !important;
      }

      /* Badges no tema escuro - destaque sutil */
      [data-style="dark"] .date-group-header .badge {
          background: linear-gradient(135deg, rgba(23, 162, 184, 0.4), rgba(32, 201, 151, 0.4)) !important;
          color: #ffffff !important;
          border: 1px solid rgba(255, 255, 255, 0.2);
      }

      .date-group-header .toggle-icon {
          font-size: 1.5rem;
          transition: transform 0.3s;
      }

      .date-group-header.collapsed .toggle-icon {
          transform: rotate(180deg);
      }

      .date-group-content {
          overflow: hidden;
          transition: max-height 0.3s ease-out, opacity 0.3s ease-out;
      }

      .date-group-content.collapsed {
          max-height: 0 !important;
          opacity: 0;
      }

      .date-group {
          margin-bottom: 1.5rem;
      }

      /* Loading state */
      .loading-container {
          min-height: 300px;
          display: flex;
          align-items: center;
          justify-content: center;
      }

      .empty-state {
          text-align: center;
          padding: 3rem;
      }

      [data-style="light"] .empty-state {
          color: #6c757d;
      }
      [data-style="dark"] .empty-state {
          color: #a1a5b7;
      }

      .empty-state i {
          font-size: 4rem;
          opacity: 0.3;
          margin-bottom: 1rem;
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

      [data-style="light"] .modal-modern .modal-header {
          background: #f8f9fa;
          color: #495057;
          border-bottom: 2px solid #dee2e6;
      }

      [data-style="dark"] .modal-modern .modal-header {
          background: #5a67d8;
          color: white;
      }

      [data-style="dark"] .modal-modern .modal-header .btn-close {
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
                <div class="col-md-2">
                    <label class="form-label">Mês</label>
                    <input type="month" id="filtro-mes" class="form-control"
                        value="{{ \Carbon\Carbon::now('America/Sao_Paulo')->format('Y-m') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Vendedor</label>
                    <select id="filtro-vendedor" class="form-select select2" data-placeholder="Todos">
                        <option value="">Todos</option>
                        @foreach ($vendedores as $v)
                            <option value="{{ $v->id }}">{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Criado por</label>
                    <select id="filtro-criado-por" class="form-select select2" data-placeholder="Todos">
                        <option value="">Todos</option>
                        @foreach ($criadores as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="filtro-status" class="form-select">
                        <option value="">Todos</option>
                        <option value="pendente">Pendente</option>
                        <option value="pago">Pago</option>
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

    {{-- Container de Pagamentos --}}
    <div id="pagamentos-container"></div>

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
