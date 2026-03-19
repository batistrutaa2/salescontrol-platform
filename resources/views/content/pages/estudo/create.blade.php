@extends('layouts/layoutMaster')

@section('title', 'Estudo de Planos de Saúde')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/criar-estudo.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/estudo.js'])
@endsection

@section('content')
<div class="ec-wrapper">

    <!-- Page Header -->
    <div class="ec-page-header">
        <div class="ec-header-content">
            <div class="ec-header-text">
                <span class="ec-greeting-label">Novo Estudo</span>
                <h1 class="ec-main-title">Criar Estudo</h1>
                <p class="ec-subtitle">Monte cotações comparativas de planos de saúde</p>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <div class="ec-form-card">
        <div class="ec-form-header">
            <div class="ec-form-title-group">
                <div class="ec-form-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="ec-form-title">Dados do Estudo</h3>
                    <span class="ec-form-subtitle">Preencha os dados e adicione planos para comparar</span>
                </div>
            </div>
        </div>

        <div class="ec-form-body card-body">
            <!-- Nome da Empresa -->
            <div class="ec-form-group">
                <label class="ec-form-label" for="nomeEmpresa">Nome da Empresa</label>
                <input type="text" class="ec-form-input" id="nomeEmpresa" placeholder="Digite o nome da empresa">
            </div>

            <!-- Seleção Operadora e Plano -->
            <div class="ec-selector-row">
                <div class="ec-form-group">
                    <label class="ec-form-label">Operadora</label>
                    <select id="operadoraSelect" class="ec-form-select select2">
                        <option value="">Selecione</option>
                        @foreach ($operadoras as $op)
                            <option value="{{ $op->id }}">{{ $op->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ec-form-group">
                    <label class="ec-form-label">Plano</label>
                    <select id="planoSelect" class="ec-form-select select2" disabled>
                        <option value="">Selecione</option>
                    </select>
                </div>
                <div class="ec-form-group">
                    <button id="addEstudo" class="ec-btn-add">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Adicionar
                    </button>
                </div>
            </div>

            <!-- Container dos estudos -->
            <div id="estudosContainer" class="row g-3"></div>
        </div>
    </div>

</div>

<!-- Template para cada card de estudo (mantido para compatibilidade) -->
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
