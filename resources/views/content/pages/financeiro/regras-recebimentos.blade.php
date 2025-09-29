@extends('layouts/layoutMaster')

@section('title', 'Financeiro - Regras de Recebimentos')

{{-- Vendor Styles --}}
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    'resources/assets/vendor/libs/quill/editor.scss'
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
    .modal .required:after { content: ' *'; color: #dc3545; }
    .badge-pme { background:#eef6ff; color:#0d6efd; }
    .badge-adesao { background:#fff4e5; color:#fd7e14; }
    .table-sm td, .table-sm th{ padding:.4rem .5rem; }
  </style>
@endsection

@section('page-script')
  @vite(['resources/assets/js/regras-recebimentos.js'])
@endsection

@section('content')

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- Cabeçalho e Ações --}}
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-1">Regras de Recebimentos</h4>
      <div class="text-muted">Cadastre percentuais por parcela, pagador e vitalício por Operadora & Modalidade.</div>
    </div>
    <div class="d-flex gap-2">
      <button id="btnNovaRegra" class="btn btn-primary">
        <i class="ri-add-line me-1"></i> Nova Regra
      </button>
      <button id="btnRecarregar" class="btn btn-outline-secondary">
        <i class="ri-refresh-line me-1"></i> Recarregar
      </button>
    </div>
  </div>

  {{-- Filtros --}}
  <div class="card mb-3">
    <div class="card-body">
      <form id="formFiltros" class="row g-3">
        @csrf
        <div class="col-md-5">
          <label class="form-label">Operadora</label>
          <select id="filtroOperadora" class="form-select">
            <option value="">Todas</option>
            @foreach(($operadoras ?? []) as $op)
              <option value="{{ $op->id }}">{{ strtoupper($op->nome) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Modalidade</label>
          <select id="filtroModalidade" class="form-select">
            <option value="">Todas</option>
            <option value="PME">PME</option>
            <option value="ADESAO">ADESÃO</option>
          </select>
        </div>
        <div class="col-md-4 d-flex align-items-end gap-2">
          <button type="button" id="btnAplicarFiltros" class="btn btn-secondary">
            <i class="ri-filter-3-line me-1"></i> Aplicar
          </button>
          <button type="button" id="btnLimparFiltros" class="btn btn-link">
            Limpar
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Tabela de Regras --}}
  <div class="card">
    <div class="card-datatable table-responsive">
      <table id="rulesTable" class="table table-striped table-sm" width="100%">
        <thead>
          <tr>
            <th style="width: 40px;">#</th>
            <th>Operadora</th>
            <th>Modalidade</th>
            <th>Vitalício</th>
            <th>Observação</th>
            <th style="width: 140px;">Ações</th>
          </tr>
        </thead>
        <tbody>
          {{-- DataTables (ajax) vai preencher --}}
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- ========================= MODAL: CADASTRAR / EDITAR REGRA ========================= --}}
<div class="modal fade" id="modalRegra" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form id="formRegra" class="modal-content" method="post" action="">
      @csrf
      <input type="hidden" name="id" id="regraId">
      <div class="modal-header">
        <h5 class="modal-title">
          <span id="tituloModalRegra">Nova Regra</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label required">Operadora</label>
            <select name="operadora_id" id="regraOperadora" class="form-select" required>
              <option value="">Selecione</option>
              @foreach(($operadoras ?? []) as $op)
                <option value="{{ $op->id }}">{{ strtoupper($op->nome) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label required">Modalidade</label>
            <select name="modalidade" id="regraModalidade" class="form-select" required>
              <option value="">Selecione</option>
              <option value="PME">PME</option>
              <option value="ADESAO">ADESÃO</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Vitalício ativo?</label>
            <select name="vitalicio_active" id="regraVitalicioAtivo" class="form-select">
              <option value="0">Não</option>
              <option value="1">Sim</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Percentual vitalício (%)</label>
            <input type="number" step="0.01" class="form-control" name="vitalicio_percent" id="regraVitalicioPercent" placeholder="ex: 2.00">
          </div>
          <div class="col-md-3">
            <label class="form-label">A partir da parcela</label>
            <input type="number" min="1" class="form-control" name="vitalicio_starts_at_installment" id="regraVitalicioStart" placeholder="ex: 4">
          </div>
          <div class="col-md-12">
            <label class="form-label">Observação</label>
            <input type="text" class="form-control" name="observacao" id="regraObs" placeholder="Ex.: Pago pela Amil">
          </div>
        </div>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-outline-danger" id="btnExcluirRegra" data-action="" style="display:none;">
          <i class="ri-delete-bin-6-line me-1"></i> Excluir
        </button>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="ri-save-3-line me-1"></i> Salvar
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- ========================= MODAL: PARCELAS DA REGRA ========================= --}}
<div class="modal fade" id="modalParcelas" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title">Parcelas da Regra</h5>
          <div class="small text-muted" id="parcelasLegenda"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="text-muted">
            Defina <b>nº da parcela</b>, <b>%</b>, <b>pagador</b> e <b>offset (meses)</b>.  
            Para dividir a mesma parcela entre dois pagadores, cadastre duas linhas com o mesmo <b>nº</b>.
          </div>
          <button class="btn btn-primary" id="btnAddParcela">
            <i class="ri-add-line me-1"></i> Adicionar Parcela
          </button>
        </div>

        <div class="table-responsive">
          <table id="parcelasTable" class="table table-striped table-sm">
            <thead>
              <tr>
                <th style="width:80px;">Parcela</th>
                <th style="width:140px;">Percentual (%)</th>
                <th style="width:220px;">Pagador</th>
                <th style="width:140px;">Offset (meses)</th>
                <th>Observação</th>
                <th style="width:120px;">Ações</th>
              </tr>
            </thead>
            <tbody>
              {{-- preenchido via JS --}}
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

{{-- ========================= MODAL: FORM PARCELA ========================= --}}
<div class="modal fade" id="modalParcelaForm" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form id="formParcela" class="modal-content" method="post" action="">
      @csrf
      <input type="hidden" name="commission_rule_id" id="parcelaRuleId">
      <input type="hidden" name="id" id="parcelaId">
      <div class="modal-header">
        <h5 class="modal-title">
          <span id="tituloModalParcela">Adicionar Parcela</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label required">Nº Parcela</label>
            <input type="number" min="1" class="form-control" name="installment_number" id="parcelaNumero" required>
          </div>
          <div class="col-md-4">
            <label class="form-label required">Percentual (%)</label>
            <input type="number" step="0.01" class="form-control" name="percent" id="parcelaPercent" required>
          </div>
          <div class="col-md-4">
            <label class="form-label required">Offset (meses)</label>
            <input type="number" min="0" class="form-control" name="month_offset" id="parcelaOffset" required>
          </div>
          <div class="col-md-6">
            <label class="form-label required">Pagador</label>
            <select class="form-select" name="payer" id="parcelaPagador" required>
              <option value="">Selecione</option>
              <option value="BRAZIL_HEALTH">Brazil Health</option>
              <option value="OPERADORA">Operadora</option>
              <option value="AMIL">Amil</option>
              <option value="SUL_AMERICA">Sul América</option>
              <option value="GNDI">GNDI</option>
              <option value="BRADESCO">Bradesco</option>
              <option value="OMINT">Omint</option>
              <option value="PORTO">Porto Seguro</option>
              <option value="SEGUROS_UNIMED">Seguros Unimed</option>
              <option value="ALICE">Alice</option>
              <option value="MEDSENIOR">MedSenior</option>
              <option value="QUALICORP">Qualicorp</option>
              <option value="SUPERMED">Supermed</option>
              <option value="CLIENTE">Cliente</option>
              <option value="OUTRO">Outro</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Observação</label>
            <input type="text" class="form-control" name="note" id="parcelaObs" placeholder="Ex.: direto amil / pago pelo cliente">
          </div>
        </div>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-outline-danger" id="btnExcluirParcela" data-action="" style="display:none;">
          <i class="ri-delete-bin-6-line me-1"></i> Excluir
        </button>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="ri-save-3-line me-1"></i> Salvar
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- Rotas usadas via data-* para o JS (evita hardcode) --}}
{{-- <div id="regrasRecebimentosRoutes"
     data-index="{{ route('financeiro.regras.index') }}"
     data-store="{{ route('financeiro.regras.store') }}"
     data-update="{{ route('financeiro.regras.update', ['id' => '__ID__']) }}"
     data-destroy="{{ route('financeiro.regras.destroy', ['id' => '__ID__']) }}"
     data-installments-index="{{ route('financeiro.regras.parcelas.index', ['ruleId' => '__RULE_ID__']) }}"
     data-installments-store="{{ route('financeiro.regras.parcelas.store') }}"
     data-installments-update="{{ route('financeiro.regras.parcelas.update', ['id' => '__ID__']) }}"
     data-installments-destroy="{{ route('financeiro.regras.parcelas.destroy', ['id' => '__ID__']) }}">
</div> --}}

@endsection
