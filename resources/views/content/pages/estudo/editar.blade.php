@extends('layouts/layoutMaster')

@section('title', 'Editar Estudo de Planos de Saúde')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bootstrap-select/bootstrap-select.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bootstrap-select/bootstrap-select.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/estudo-edit.js'])
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Editar Estudo</h5>
            <a href="{{ url('/estudo-lista') }}" class="btn btn-sm btn-secondary">Voltar</a>
        </div>

        <div class="card-body">
            <!-- guardamos o id do estudo -->
            <input type="hidden" id="estudoId" value="{{ $estudoId }}">

            <!-- Nome da empresa -->
            <div class="mb-3">
                <label for="nomeEmpresa" class="form-label">Nome da Empresa</label>
                <input type="text" class="form-control" id="nomeEmpresa" placeholder="Digite o nome da empresa">
            </div>

            <!-- Seleção Operadora e Plano para adicionar novos cards (opcional na edição) -->
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

            <!-- Container dos cards (carregado do backend + novos) -->
            <div id="estudosContainer" class="row g-3"></div>

            <div class="d-flex gap-2 mt-3">
                <button id="salvarEdicao" class="btn btn-success">Salvar Alterações</button>
                <a href="{{ url('/estudo-lista') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </div>
    </div>

    <!-- Template para card -->
    <template id="estudoCardTemplate">
        <div class="col-md-4 estudo-wrapper">
            <div class="card mb-4 estudo">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 titulo-estudo"></h6>
                    <button type="button" class="btn btn-sm btn-danger remover-estudo">Remover</button>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col">
                            <label>Categoria</label>
                            <input type="text" class="form-control categoria">
                        </div>
                        <div class="col">
                            <label>Coparticipação</label>
                            <input type="text" class="form-control coparticipacao">
                        </div>
                        <div class="col">
                            <label>Reembolso Consulta (R$)</label>
                            <input type="number" class="form-control reembolso" step="0.01" min="0"
                                value="0">
                        </div>
                    </div>

                    <table class="table table-bordered text-center align-middle">
                        <thead>
                            <tr>
                                <th>Faixa Etária</th>
                                <th>Qtde Vidas</th>
                                <th>Valor Unitário (R$)</th>
                                <th>Total (R$)</th>
                            </tr>
                        </thead>
                        <tbody class="tbody-faixas"></tbody>
                        <tfoot>
                            <tr class="table-secondary">
                                <th>Total</th>
                                <th class="totalVidas">0</th>
                                <th></th>
                                <th class="totalGeral">0,00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </template>
@endsection
