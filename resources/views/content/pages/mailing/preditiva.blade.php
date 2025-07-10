@extends('layouts/layoutMaster')

@section('title', 'Fila Preditiva')

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
    
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/app-kanban.scss')
    <style>
    .updated-highlight {
    background-color: #d1e7dd !important; /* verde clarinho */
    transition: background-color 0.4s ease;
    border-radius: 4px;
    padding: 0.2em 0.4em;
    }

    </style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/dropzone/dropzone.js'])
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])

@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/assets/js/preditiva.js'])
@endsection

@section('content')
<div class="row">
    <!-- Total de Leads na Fila -->
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Leads na Fila</h6>
                <h4 class="fw-bold mb-0" id="total-leads">--</h4>
            </div>
        </div>
    </div>

    <!-- Tentativas Hoje -->
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Tentativas Hoje</h6>
                <h4 class="fw-bold mb-0" id="tentativas-hoje">--</h4>
            </div>
        </div>
    </div>

    <!-- Conversões Hoje -->
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Conversões</h6>
                <h4 class="fw-bold text-success mb-0" id="conversoes-hoje">--</h4>
            </div>
        </div>
    </div>

    <!-- Recusas Hoje -->
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Recusas</h6>
                <h4 class="fw-bold text-danger mb-0" id="recusas-hoje">--</h4>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
  <div class="card-header">
    <h5 class="mb-0">Leads na Fila Preditiva</h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tabela-fila-preditiva" class="table table-striped table-bordered mb-0" style="width:100%">
        <thead class="table-light">
          <tr>
            <th style="width: 60px;">ID</th>
            <th>Cliente</th>
            <th style="width: 160px;">Valor Plano Atual</th>
            <th style="width: 100px;">Tentativas</th>
            <th style="width: 200px;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <!-- preenchido via JS -->
        </tbody>
      </table>
    </div>
  </div>
</div>



@endsection
