@extends('layouts/layoutMaster')

@section('title', 'DataTables - Advanced Tables')

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/assets/js/ligacoes.js'])
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

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Filtro:</h5>
            <div class="row pt-4">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Data Inicial</label>
                    <input type="date" id="start_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Data Final</label>
                    <input type="date" id="end_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Selecione o vendedor</label>
                  <select class="select2  form-select" id="label" name="tabulacao_id">
                      <option value="">Selecione o Status</option>
                      @foreach ($users as $user)
                        <option value="{{$user->id}}">{{$user->name}}</option>
                      @endforeach
                  </select>
                </div>


                <div class="col-md-3 d-flex align-items-end ">
                    <button id="filter_button" class="btn btn-primary w-65 r">Filtrar</button>

                    <button id="clear_filter" class="btn btn-success w-65">Limpar</button>
                </div>
            </div>
        </div>
    </div>
@endsection
