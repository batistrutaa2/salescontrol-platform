@extends('layouts/layoutMaster')

@section('title', 'Configuração de Comissionamento')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/toastr/toastr.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/scss/pages/configuracoes-comissionamento.scss'
    ])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/configuracoes-comissionamento.js')
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="configuracoes-comissionamento-wrapper">
        {{-- Header Section --}}
        <div class="cc-header">
            <div class="header-content">
                <div class="header-text">
                    <span class="greeting-label">Comissionamento</span>
                    <h1 class="main-title">Configuracoes de Comissao</h1>
                    <p class="subtitle">Gerencie as configuracoes de comissionamento dos vendedores</p>
                </div>
                <div class="header-actions">
                    <button type="button" class="btn-dash btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateComissao">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Nova Configuracao
                    </button>
                </div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="table-card">
            <div class="table-header">
                <div class="table-title-group">
                    <div class="table-icon configuracoes">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="table-title">Configuracoes</h3>
                        <span class="table-subtitle">Lista de configuracoes de comissionamento</span>
                    </div>
                </div>
                <div class="table-badge configuracoes">
                    <span id="badge-total-count">0</span> registros
                </div>
            </div>

            <div class="table-body">
                <div class="table-responsive">
                    <table id="comissionamento-table" class="custom-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Vendedor</th>
                                <th class="text-end">Percentual</th>
                                <th class="text-end">Angariação</th>
                                <th class="text-end">Imposto</th>
                                <th>Grade</th>
                                <th class="text-end">Salario</th>
                                <th>Periodicidade</th>
                                <th class="text-end">Acoes</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

    {{-- Modal Cadastro/Editar Comissao --}}
    <div class="modal fade modal-modern" id="modalCreateComissao" tabindex="-1" aria-labelledby="modalCreateComissaoLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formComissao" method="POST" action="">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCreateComissaoLabel">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            Nova Configuracao de Comissao
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body">
                        {{-- Vendedor --}}
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
                            <label for="percentual" class="form-label">Percentual de Comissao (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="percentual" id="percentual" step="0.01"
                                    min="0" max="100" placeholder="Ex.: 7.50" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Percentual da comissao do vendedor.</small>
                        </div>

                        <div class="mb-3">
                            <label for="percentual_angariacao" class="form-label">Percentual de Angariação (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="percentual_angariacao" id="percentual_angariacao"
                                    step="0.01" min="0" max="100" placeholder="Ex.: 25.00" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Regra individual do vendedor, sem inferência pela grade.</small>
                        </div>

                        {{-- Imposto (%) --}}
                        <div class="mb-3">
                            <label for="imposto" class="form-label">Imposto (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="imposto" id="imposto" step="0.01"
                                    min="0" max="100" placeholder="Ex.: 5.00" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Percentual de imposto incidente na comissao.</small>
                        </div>

                        {{-- Grade --}}
                        <div class="mb-3">
                            <label for="grade" class="form-label">Grade</label>
                            <select class="form-select" name="grade" id="grade" required>
                                <option value="JUNIOR">JUNIOR</option>
                                <option value="SENIOR">SENIOR</option>
                                <option value="ADMIN">ADMIN</option>
                                <option value="COMERCIAL">COMERCIAL</option>
                            </select>
                        </div>

                        {{-- Salario (R$) --}}
                        <div class="mb-3">
                            <label for="salario" class="form-label">Salario (R$)</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" inputmode="decimal" class="form-control" name="salario" id="salario"
                                    placeholder="Ex.: 3.500,00" required>
                            </div>
                            <small class="text-muted">Salario base para a grade selecionada.</small>
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
                        <button type="button" class="btn-dash btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-dash btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Salvar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    </div>
@endsection
