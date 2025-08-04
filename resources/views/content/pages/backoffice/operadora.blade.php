@extends('layouts/layoutMaster')

@section('title', 'Cadastro de Operadoras')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/operadora.js'])
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Lista de Operadoras</h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novaOperadoraModal">
                <i class="ri-add-line"></i> Nova Operadora
            </button>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="tabela-operadoras">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>OPERADORA</th>
                            <th>STATUS</th>
                            <th>CRIADO EM:</th>
                            <th>AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Dados serão preenchidos via DataTables --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Nova Operadora -->
    <div class="modal fade" id="novaOperadoraModal" tabindex="-1" aria-labelledby="novaOperadoraLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="formNovaOperadora" method="POST" action="{{ route('backoffice.createOperation') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="novaOperadoraLabel">Cadastrar Nova Operadora</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nome_operadora" class="form-label">Nome da Operadora</label>
                            <input type="text" class="form-control" id="nome_operadora" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Y">Ativo</option>
                                <option value="N">Inativo</option>
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
