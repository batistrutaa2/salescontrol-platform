@extends('layouts/layoutMaster')

@section('title', 'Cadastro de Planos')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/planos.js'])
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Lista de Planos</h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novoPlanoModal">
                <i class="ri-add-line"></i> Novo Plano
            </button>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="tabela-planos">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Operadora</th>
                            <th>Nome</th>
                            <th>Status</th>
                            <th>Coparticipação</th>
                            <th>Acomodação</th>
                            <th>Criado em:</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Dados via DataTables --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Novo Plano -->
    <div class="modal fade" id="novoPlanoModal" tabindex="-1" aria-labelledby="novoPlanoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="formNovoPlano" method="POST" action="{{ route('backoffice.createPlan') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="novoPlanoLabel">Cadastrar Novo Plano</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="operadora_id" class="form-label">Operadora</label>
                            <select class="form-select" id="operadora_id" name="operadora_id" required>
                                <option value="">Selecione...</option>
                                @foreach ($operadoras as $operadora)
                                    <option value="{{ $operadora->id }}">{{ strtoupper($operadora->nome) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome do Plano</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Y">Ativo</option>
                                <option value="N">Inativo</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="acomodacao" class="form-label">Acomodação</label>
                            <select class="form-select" id="acomodacao" name="acomodacao" required>
                                <option value="ENFERMARIA">Enfermaria</option>
                                <option value="APARTAMENTO">Apartamento</option>
                            </select>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
