@extends('layouts/layoutMaster')

@section('title', 'Estudo de Planos de Saúde')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bootstrap-select/bootstrap-select.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bootstrap-select/bootstrap-select.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/estudo.js'])
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Criar Estudo</h5>
        </div>

        <div class="card-body">
            <div class="mb-3">
                <label for="nomeEmpresa" class="form-label">Nome da Empresa</label>
                <input type="text" class="form-control" id="nomeEmpresa" placeholder="Digite o nome da empresa">
            </div>
            <!-- Seleção Operadora e Plano -->
            <div class="row mb-3">
                <div class="col-md-5">
                    <label class="form-label">Operadora</label>
                    <select id="operadoraSelect" class="form-select select2">
                        <option value="">Selecione</option>
                        @foreach ($operadoras as $op)
                            <option value="{{ $op->id }}">{{ $op->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Plano</label>
                    <select id="planoSelect" class="form-select select2" disabled>
                        <option value="">Selecione</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button id="addEstudo" class="btn btn-primary w-100">Adicionar</button>
                </div>
            </div>

            <!-- Container onde os estudos serão adicionados -->
            <div id="estudosContainer" class="row g-3"></div>
        </div>
    </div>
@endsection
