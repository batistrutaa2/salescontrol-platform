@extends('layouts/layoutMaster')

@section('title', 'Configuração de Comissionamento')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/configuracoes-comissionamento.js')
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Lista de Configurações</h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateComissao">
                <i class="ri-add-line"></i> Nova Configuração
            </button>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="comissionamento-table" class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuário</th>
                            <th>Percentual</th>
                            <th>Imposto</th>
                            <th>Grade</th>
                            <th>Salário</th>
                            <th>Periodicidade</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Cadastro/Editar Comissão -->
    <div class="modal fade" id="modalCreateComissao" tabindex="-1" aria-labelledby="modalCreateComissaoLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="formComissao" method="POST" action="">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCreateComissaoLabel">Nova Configuração de Comissão</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body">
                        {{-- Usuário --}}
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Vendedor</label>
                            <select class="form-select select2" name="user_id" id="user_id" required>
                                <option value="">Selecione</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ strtoupper($user->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Percentual (%) --}}
                        <div class="mb-3">
                            <label for="percentual" class="form-label">Percentual de Comissão (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="percentual" id="percentual" step="0.01"
                                    min="0" max="100" placeholder="Ex.: 7.50" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Percentual da comissão do vendedor.</small>
                        </div>

                        {{-- Imposto (%) --}}
                        <div class="mb-3">
                            <label for="imposto" class="form-label">Imposto (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="imposto" id="imposto" step="0.01"
                                    min="0" max="100" placeholder="Ex.: 5.00" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Percentual de imposto incidente na comissão.</small>
                        </div>

                        {{-- Grade --}}
                        <div class="mb-3">
                            <label for="grade" class="form-label">Grade</label>
                            <select class="form-select" name="grade" id="grade" required>
                                <option value="JUNIOR">JÚNIOR</option>
                                <option value="SENIOR">SÊNIOR</option>
                                <option value="ADMIN">ADMIN</option>
                                <option value="COMERCIAL">COMERCIAL</option>
                            </select>
                        </div>

                        {{-- Salário (R$) --}}
                        <div class="mb-3">
                            <label for="salario" class="form-label">Salário (R$)</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" inputmode="decimal" class="form-control" name="salario" id="salario"
                                    placeholder="Ex.: 3.500,00" required>
                            </div>
                            <small class="text-muted">Salário base para a grade selecionada.</small>
                        </div>

                        {{-- Periodicidade --}}
                        <div class="mb-3">
                            <label for="periodicidade" class="form-label">Periodicidade</label>
                            <select class="form-select" name="periodicidade" id="periodicidade" required>
                                <option value="mensal">Mensal</option>
                                <option value="trimestral">Trimestral</option>
                                <option value="semestral">Semestral</option>
                                <option value="anual">Anual</option>
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
