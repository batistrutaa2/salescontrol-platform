@extends('layouts/layoutMaster')

@section('title', 'Cofre de Acessos')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/scss/pages/dashboard-analytics.scss',
        'resources/assets/vendor/scss/pages/credenciais.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/select2/select2.js',
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/credenciais-acesso.js'])
@endsection

@section('content')
<div class="cred-wrapper">

    {{-- Header --}}
    <header class="cred-header">
        <div class="cred-header-text">
            <span class="cred-eyebrow">Back-office · Segurança</span>
            <h1 class="cred-title">Cofre de Acessos</h1>
            <p class="cred-subtitle">Logins e senhas das empresas nos portais das operadoras, com histórico de cada alteração.</p>
        </div>
        <div class="cred-header-actions">
            <button type="button" class="cred-btn cred-btn-ghost" id="btnImportar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Importar Excel
            </button>
            <button type="button" class="cred-btn cred-btn-primary" id="btnNovaCredencial">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nova Credencial
            </button>
        </div>
    </header>

    {{-- KPIs --}}
    <section class="kpi-grid cred-kpi-grid">
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                </div>
                <div class="kpi-pulse" style="background: rgba(var(--dash-primary-rgb), 0.2);"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total de acessos</span>
                <h2 class="kpi-value">{{ number_format($resumo['total'], 0, ',', '.') }}</h2>
            </div>
        </div>

        <div class="kpi-card kpi-success">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="kpi-pulse" style="background: rgba(var(--dash-success-rgb), 0.2);"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Ativos</span>
                <h2 class="kpi-value">{{ number_format($resumo['ativos'], 0, ',', '.') }}</h2>
            </div>
        </div>

        <div class="kpi-card kpi-info">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                </div>
                <div class="kpi-pulse" style="background: rgba(var(--dash-info-rgb), 0.2);"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Operadoras</span>
                <h2 class="kpi-value">{{ number_format($resumo['operadoras'], 0, ',', '.') }}</h2>
            </div>
        </div>

        <div class="kpi-card kpi-warning">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div class="kpi-pulse" style="background: rgba(var(--dash-warning-rgb), 0.2);"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Inativos</span>
                <h2 class="kpi-value">{{ number_format($resumo['inativos'], 0, ',', '.') }}</h2>
            </div>
        </div>
    </section>

    {{-- Tabela --}}
    <section class="table-card cred-table-card">
        <div class="table-header cred-toolbar">
            <div class="table-title-group">
                <div class="table-icon cadastrados">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <div>
                    <h3 class="table-title">Credenciais cadastradas</h3>
                    <span class="table-subtitle">Filtre por operadora ou status</span>
                </div>
            </div>
            <div class="cred-filters">
                <select class="cred-select" id="filtroOperadora" aria-label="Filtrar por operadora">
                    <option value="">Todas as operadoras</option>
                    @foreach ($operadoras as $operadora)
                        <option value="{{ $operadora->id }}">{{ $operadora->nome }}</option>
                    @endforeach
                </select>
                <select class="cred-select" id="filtroStatus" aria-label="Filtrar por status">
                    <option value="">Todos os status</option>
                    <option value="Y">Ativos</option>
                    <option value="N">Inativos</option>
                </select>
            </div>
        </div>
        <div class="table-body">
            <div class="table-responsive">
                <table class="table cred-table" id="tabela-credenciais" style="width:100%">
                    <thead>
                        <tr>
                            <th>Operadora</th>
                            <th>Tipo</th>
                            <th>Nome</th>
                            <th>Login / Documento</th>
                            <th>Senha</th>
                            <th>Observação</th>
                            <th>Última edição</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>
</div>

{{-- Modal Nova/Editar --}}
<div class="modal fade cred-modal" id="credencialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog cred-modal-wide modal-dialog-centered">
        <form id="formCredencial">
            <input type="hidden" id="credencial_id" name="id">
            <div class="modal-content cred-modal-content">
                <div class="modal-header cred-modal-head">
                    <div class="cred-modal-head-txt">
                        <span class="cred-modal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 8.3-8.3M17 5l3 3M14 8l3 3"/></svg>
                        </span>
                        <div>
                            <h5 class="modal-title" id="credencialModalLabel">Novo acesso</h5>
                            <span class="cred-modal-sub" id="credencialModalSub">Cadastre um ou vários logins do mesmo portal</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body cred-modal-body">
                    {{-- Contexto compartilhado por todos os acessos --}}
                    <div class="cred-ctx">
                        <div class="cred-field">
                            <label class="cred-label" for="operadora_id">Operadora</label>
                            <select class="form-select select2-operadora" id="operadora_id" name="operadora_id">
                                <option value="">— Selecione —</option>
                                @foreach ($operadoras as $operadora)
                                    <option value="{{ $operadora->id }}">{{ $operadora->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="cred-field">
                            <label class="cred-label" for="tipo">Tipo de acesso</label>
                            <input type="text" class="form-control" id="tipo" name="tipo"
                                list="tipoSugestoes" placeholder="Empresa, Pessoa Física...">
                            <datalist id="tipoSugestoes">
                                <option value="Empresa"></option>
                                <option value="Pessoa Física"></option>
                                <option value="Adesão"></option>
                            </datalist>
                        </div>
                        <div class="cred-field">
                            <label class="cred-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="Y">Ativo</option>
                                <option value="N">Inativo</option>
                            </select>
                        </div>
                        <div class="cred-field cred-field-full">
                            <label class="cred-label" for="observacao">Observação</label>
                            <textarea class="form-control" id="observacao" name="observacao" rows="2"
                                placeholder="Senha secundária, dia de vencimento, e-mail, etc."></textarea>
                        </div>
                    </div>

                    {{-- Acessos (repetidor) --}}
                    <div class="cred-acessos-head">
                        <span class="cred-acessos-title">Acessos <span class="cred-acessos-count" id="cred-acessos-count"></span></span>
                        <button type="button" class="cred-add-acesso" id="cred-add-acesso">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Adicionar acesso
                        </button>
                    </div>
                    <div class="cred-acessos" id="cred-acessos"></div>
                </div>

                <div class="modal-footer cred-modal-foot">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="cred-btn cred-btn-primary" id="cred-salvar-btn">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Template de uma linha de acesso (login/senha) --}}
<template id="cred-acesso-tpl">
    <div class="cred-acesso">
        <div class="cred-acesso-f cred-acesso-f-nome">
            <label class="cred-label">Nome / rótulo <span class="text-danger">*</span></label>
            <input type="text" class="form-control acesso-nome" placeholder="Ex.: MASTER, SUBMASTER" required>
        </div>
        <div class="cred-acesso-f">
            <label class="cred-label">Login</label>
            <input type="text" class="form-control cred-mono-input acesso-login" placeholder="CPF/CNPJ ou código">
        </div>
        <div class="cred-acesso-f">
            <label class="cred-label">Senha</label>
            <div class="cred-senha-wrap">
                <input type="password" class="form-control cred-mono-input acesso-senha" placeholder="••••••••">
                <button type="button" class="cred-eye" tabindex="-1" aria-label="Mostrar ou ocultar senha">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>
        <button type="button" class="cred-remove-acesso" aria-label="Remover acesso" title="Remover acesso">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </button>
    </div>
</template>

{{-- Modal Importar Excel --}}
<div class="modal fade cred-modal" id="importarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Importar credenciais via Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <ol class="cred-steps">
                    <li><span>1</span> Escolha a operadora e o arquivo</li>
                    <li><span>2</span> Diga qual coluna é cada campo</li>
                    <li><span>3</span> Importe</li>
                </ol>

                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" for="import_operadora">Operadora <span class="text-danger">*</span></label>
                        <select class="form-select" id="import_operadora">
                            <option value="">— Selecione —</option>
                            @foreach ($operadoras as $operadora)
                                <option value="{{ $operadora->id }}">{{ $operadora->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="import_arquivo">Arquivo (.xlsx, .xls, .csv) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="import_arquivo" accept=".xlsx,.xls,.csv,.txt">
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="import_cabecalho" checked>
                            <label class="form-check-label" for="import_cabecalho">Primeira linha é cabeçalho</label>
                        </div>
                        <button type="button" class="cred-btn cred-btn-ghost w-100 justify-content-center" id="btnPreview">
                            Pré-visualizar
                        </button>
                    </div>
                </div>

                <div id="importMapeamento" class="d-none">
                    <hr class="cred-divider">
                    <p class="cred-help">
                        Cada operadora tem colunas diferentes — relacione cada coluna da planilha ao campo do sistema.
                        <strong>Nome</strong> é obrigatório; os demais são opcionais.
                        <span id="importTotalLinhas" class="cred-pill-info"></span>
                    </p>
                    <div class="row g-3" id="importCamposRow"></div>

                    <h6 class="cred-section-label">Amostra da planilha</h6>
                    <div class="table-responsive cred-sample">
                        <table class="table table-sm mb-0" id="importAmostraTabela">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="cred-btn cred-btn-primary d-none" id="btnConfirmarImport">
                    Importar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Histórico --}}
<div class="modal fade cred-modal" id="historicoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Histórico — <span id="historicoNome"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="cred-timeline" id="historicoBody"></div>
            </div>
        </div>
    </div>
</div>
@endsection
