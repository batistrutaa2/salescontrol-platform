@extends('layouts/layoutMaster')

@section('title', 'Fila de Leads')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/marketing.js')
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

<!-- customers List Table -->
<div class="card">

  <div class="card-datatable table-responsive">
      <table class="datatables-customers table">
          <thead>
              <tr>
                  <th>ID</th>
                  <th>NOME</th>
                  <th>EMAIL</th>
                  <th>TELEFONE</th>
                  <th>CADASTRO</th>
                  <th></th>
              </tr>
          </thead>
      </table>
  </div>
</div>


@endsection
