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
            <!-- Nome da empresa -->
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

            <!-- Container onde os cards de estudo serão adicionados -->
            <div id="estudosContainer" class="row g-3"></div>
        </div>
    </div>

    <!-- Template para cada card de estudo -->
    <template id="estudoCardTemplate">
        <div class="col-md-6 estudo-card">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title plano-nome"></h6>

                    <div class="mb-2">
                        <label class="form-label">Coparticipação</label>
                        <input type="text" class="form-control coparticipacao" placeholder="Digite a coparticipação">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Reembolso Consulta</label>
                        <input type="text" class="form-control reembolso" placeholder="Digite o valor do reembolso">
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="button" class="btn btn-danger btn-sm remove-card">Remover</button>
                </div>
            </div>
        </div>
    </template>
@endsection
