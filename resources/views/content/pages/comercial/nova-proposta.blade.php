@use('Illuminate\Support\Str')
@extends('layouts/layoutMaster')

@php
$isFlex = true;
$isFooter = false;
@endphp

@section('title', 'Comercial - Nova Proposta PME')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/toastr/toastr.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/nova-proposta.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/novaProposta.js'])
@endsection

@section('content')
<div class="nova-proposta-wrapper">

    {{-- Header com Info do Cliente --}}
    <div class="np-top-header">
        <div class="top-header-left">
            <a href="{{ route('comercial.openClient', $client->id) }}" class="btn-back">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            </a>
            <div class="top-header-info">
                <span class="header-badge">PME</span>
                <h1 class="page-title">Nova Proposta</h1>
            </div>
        </div>
        <div class="top-header-client">
            <div class="client-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </div>
            <div class="client-details">
                <span class="client-name">{{ $client->nome_cliente }}</span>
                <span class="client-contact">{{ $client->telefone1 }} @if($client->email)| {{ $client->email }}@endif</span>
            </div>
            <button type="button" class="btn-docs-checklist" id="btn-open-docs-modal" title="Ver documentacao necessaria">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                <span>Documentos</span>
            </button>
        </div>
        <div class="top-header-stats">
            <div class="stat-item">
                <span class="stat-value" id="total-titulares">0</span>
                <span class="stat-label">Titulares</span>
            </div>
            <div class="stat-item">
                <span class="stat-value" id="total-dependentes">0</span>
                <span class="stat-label">Dependentes</span>
            </div>
            <div class="stat-item stat-highlight">
                <span class="stat-value" id="total-vidas">0</span>
                <span class="stat-label">Vidas</span>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('comercial.createSale') }}" class="np-form" id="formNovaProposta">
        @csrf
        <input type="hidden" id="contato_id" name="contato_id" value="{{ $client->id }}" />
        <input type="hidden" name="tipo_contrato" value="PME" />

        {{-- Layout Principal em 2 Colunas --}}
        <div class="np-layout">

            {{-- ============================================ --}}
            {{-- COLUNA ESQUERDA --}}
            {{-- ============================================ --}}
            <div class="np-col-left">
                <div class="np-col-left-scroll">

                {{-- Dados da Empresa --}}
                <div class="np-section">
                    <div class="section-header">
                        <span class="section-icon icon-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        </span>
                        <span class="section-title">Dados da Empresa</span>
                    </div>
                    <div class="section-body">
                        <div class="np-grid grid-2">
                            <div class="np-field span-2">
                                <label>Razao Social</label>
                                <input type="text" id="nome_contrato" name="nome_contrato" class="np-input @error('nome_contrato') is-invalid @enderror" placeholder="RAZAO SOCIAL DA EMPRESA" value="{{ old('nome_contrato') }}" required />
                            </div>
                            <div class="np-field">
                                <label>CNPJ</label>
                                <input type="text" id="cpf_cnpj" name="cpf_cnpj" class="np-input mask-cnpj @error('cpf_cnpj') is-invalid @enderror" placeholder="00.000.000/0000-00" value="{{ old('cpf_cnpj') }}" required />
                            </div>
                            <div class="np-field">
                                <label>Tipo Empresa</label>
                                <select id="tipo_empresa" name="tipo_empresa" class="np-input @error('tipo_empresa') is-invalid @enderror" required>
                                    <option value="">Selecione...</option>
                                    <option value="MEI" {{ old('tipo_empresa') == 'MEI' ? 'selected' : '' }}>MEI</option>
                                    <option value="ME" {{ old('tipo_empresa') == 'ME' ? 'selected' : '' }}>ME</option>
                                    <option value="EPP" {{ old('tipo_empresa') == 'EPP' ? 'selected' : '' }}>EPP</option>
                                    <option value="LTDA" {{ old('tipo_empresa') == 'LTDA' ? 'selected' : '' }}>LTDA</option>
                                    <option value="SA" {{ old('tipo_empresa') == 'SA' ? 'selected' : '' }}>S/A</option>
                                    <option value="EIRELI" {{ old('tipo_empresa') == 'EIRELI' ? 'selected' : '' }}>EIRELI</option>
                                    <option value="SLU" {{ old('tipo_empresa') == 'SLU' ? 'selected' : '' }}>SLU</option>
                                </select>
                            </div>
                            <div class="np-field">
                                <label>Data Abertura</label>
                                <input type="text" id="data_abertura" name="data_abertura" class="np-input flatpickr-date" placeholder="DD/MM/AAAA" value="{{ old('data_abertura') }}" />
                            </div>
                            <div class="np-field">
                                <label>E-mail</label>
                                <input type="email" id="email" name="email" class="np-input" placeholder="email@empresa.com" value="{{ old('email') }}" />
                            </div>
                            <div class="np-field">
                                <label>Telefone 1</label>
                                <input type="text" id="telefone1" name="telefone1" class="np-input mask-telefone" placeholder="(11) 3000-0000" value="{{ old('telefone1') }}" />
                            </div>
                            <div class="np-field">
                                <label>Telefone 2</label>
                                <input type="text" id="telefone2" name="telefone2" class="np-input mask-telefone" placeholder="(11) 91234-5678" value="{{ old('telefone2') }}" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Operadora --}}
                <div class="np-section">
                    <div class="section-header">
                        <span class="section-icon icon-info">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        </span>
                        <span class="section-title">Operadora</span>
                    </div>
                    <div class="section-body">
                        <div class="np-grid grid-2">
                            <div class="np-field">
                                <label>Operadora</label>
                                <select id="operadora" name="operadora_id" class="np-input @error('operadora_id') is-invalid @enderror" required>
                                    <option value="">Selecione...</option>
                                    @foreach ($operadoras as $op)
                                        <option value="{{ $op->id }}" data-nome="{{ strtoupper($op->nome) }}" {{ old('operadora_id') == $op->id ? 'selected' : '' }}>{{ strtoupper($op->nome) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="np-field">
                                <label>Qtd. Titulares</label>
                                <input type="number" min="1" value="{{ old('qtd_titulares', 1) }}" id="qtd_titulares" name="qtd_titulares" class="np-input" />
                            </div>
                        </div>

                        {{-- Plano Dental --}}
                        <div class="np-inline-toggle" style="margin-top: 0.75rem;">
                            <div class="toggle-info">
                                <span class="toggle-label">Plano Dental</span>
                                <span class="status-badge {{ old('plano_dental', 'SIM') == 'SIM' ? 'badge-ativo' : 'badge-inativo' }}" id="plano-dental-badge">{{ old('plano_dental', 'SIM') == 'SIM' ? 'Ativo' : 'Inativo' }}</span>
                            </div>
                            <div class="toggle-controls">
                                <select id="plano_dental" name="plano_dental" class="np-input np-input-sm">
                                    <option value="SIM" {{ old('plano_dental', 'SIM') == 'SIM' ? 'selected' : '' }}>SIM</option>
                                    <option value="NAO" {{ old('plano_dental') == 'NAO' ? 'selected' : '' }}>NAO</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Valores e Configuracoes --}}
                <div class="np-section">
                    <div class="section-header">
                        <span class="section-icon icon-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </span>
                        <span class="section-title">Valores e Comissao</span>
                    </div>
                    <div class="section-body">
                        <div class="np-grid grid-2">
                            <div class="np-field">
                                <label>Valor Mensal</label>
                                <input type="text" id="valor_contrato" name="valor_contrato" value="{{ old('valor_contrato', '0') }}" class="np-input monetary-field" placeholder="R$ 0,00" />
                            </div>
                            <div class="np-field">
                                <label>Total Vidas</label>
                                <input type="number" id="vidas" name="vidas" class="np-input" value="{{ old('vidas', 0) }}" readonly />
                            </div>
                        </div>

                        {{-- Angariacao Inline --}}
                        <div class="np-inline-toggle">
                            <div class="toggle-info">
                                <span class="toggle-label">Angariacao</span>
                                <span class="status-badge {{ old('angariacao_status') == 'SIM' ? 'badge-ativo' : 'badge-inativo' }}" id="angariacao-badge">{{ old('angariacao_status') == 'SIM' ? 'Ativo' : 'Inativo' }}</span>
                            </div>
                            <div class="toggle-controls">
                                <input type="text" id="taxa_angariacao" name="taxa_angariacao" value="{{ old('taxa_angariacao', '0') }}" class="np-input np-input-sm monetary-field" placeholder="R$ 0,00" />
                                <select id="angariacao_status" name="angariacao_status" class="np-input np-input-sm">
                                    <option value="NAO" {{ old('angariacao_status') != 'SIM' ? 'selected' : '' }}>NAO</option>
                                    <option value="SIM" {{ old('angariacao_status') == 'SIM' ? 'selected' : '' }}>SIM</option>
                                </select>
                            </div>
                        </div>

                        {{-- Portabilidade Inline --}}
                        <div class="np-inline-toggle">
                            <div class="toggle-info">
                                <span class="toggle-label">Portabilidade</span>
                                <span class="status-badge {{ old('portabilidade_status') == 'SIM' ? 'badge-ativo' : 'badge-inativo' }}" id="portabilidade-badge">{{ old('portabilidade_status') == 'SIM' ? 'Ativo' : 'Inativo' }}</span>
                            </div>
                            <div class="toggle-controls">
                                <input type="number" id="qtd_portabilidade" name="qtd_portabilidade" min="0" value="{{ old('qtd_portabilidade', 0) }}" class="np-input np-input-sm" placeholder="Qtd" style="{{ old('portabilidade_status') == 'SIM' ? '' : 'display:none;' }}" />
                                <select id="portabilidade_status" name="portabilidade_status" class="np-input np-input-sm">
                                    <option value="NAO" {{ old('portabilidade_status') != 'SIM' ? 'selected' : '' }}>NAO</option>
                                    <option value="SIM" {{ old('portabilidade_status') == 'SIM' ? 'selected' : '' }}>SIM</option>
                                </select>
                            </div>
                        </div>

                        {{-- Lista Portabilidade --}}
                        <div id="portabilidade-container" class="portabilidade-list" style="display: none;"></div>

                        {{-- Observacoes --}}
                        <div class="np-field" style="margin-top: 0.75rem;">
                            <label>Observacoes</label>
                            <input type="text" id="obs_contrato" name="obs_contrato" class="np-input" placeholder="Observacoes do contrato" value="{{ old('obs_contrato') }}" />
                        </div>
                    </div>
                </div>

                </div>{{-- Fim np-col-left-scroll --}}
            </div>

            {{-- ============================================ --}}
            {{-- COLUNA DIREITA - TITULARES --}}
            {{-- ============================================ --}}
            <div class="np-col-right">
                <div class="titulares-header">
                    <div class="titulares-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <span>Titulares e Dependentes</span>
                    </div>
                    <button type="button" class="btn-add-titular-main" id="btn-add-titular">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Titular
                    </button>
                </div>
                <div id="titulares-container" class="titulares-scroll"></div>
            </div>

        </div>

        {{-- Botao Flutuante Salvar --}}
        <button type="submit" class="np-floating-save" title="Salvar Proposta">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            <span>Salvar</span>
        </button>
    </form>

</div>

{{-- Template Titular --}}
<template id="template-titular">
    <div class="titular-card" data-titular-index="__INDEX__">
        <div class="titular-header">
            <div class="titular-badge">
                <span class="badge-number">__NUMBER__</span>
                <span class="badge-text">Titular</span>
            </div>
            <div class="titular-actions">
                <button type="button" class="btn-action btn-add-dep" title="Adicionar Dependente">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
                </button>
                <button type="button" class="btn-action btn-remove-titular" title="Remover">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
        </div>
        <div class="titular-body">
            <div class="field-row">
                <input type="text" name="titulares[__INDEX__][nome]" class="np-input" placeholder="Nome completo" required>
                <input type="text" name="titulares[__INDEX__][cpf]" class="np-input mask-cpf" placeholder="CPF (000.000.000-00)">
            </div>
            <div class="field-row">
                <input type="text" name="titulares[__INDEX__][data_nascimento]" class="np-input flatpickr-nascimento" placeholder="Data Nascimento">
                <input type="email" name="titulares[__INDEX__][email]" class="np-input" placeholder="E-mail">
            </div>
            <div class="field-row">
                <input type="text" name="titulares[__INDEX__][telefone1]" class="np-input mask-telefone" placeholder="Telefone 1">
                <input type="text" name="titulares[__INDEX__][telefone2]" class="np-input mask-telefone" placeholder="Telefone 2">
            </div>
            <div class="field-row">
                <select name="titulares[__INDEX__][cargo]" class="np-input" required>
                    <option value="">Cargo...</option>
                    <option value="SOCIO">Socio</option>
                    <option value="DIRETOR">Diretor</option>
                    <option value="GERENTE">Gerente</option>
                    <option value="FUNCIONARIO">Funcionario</option>
                    <option value="PRESTADOR">Prestador</option>
                    <option value="ESTAGIARIO">Estagiario</option>
                </select>
                <select name="titulares[__INDEX__][plano_id]" class="np-input select-plano-titular" required>
                    <option value="">Plano...</option>
                </select>
            </div>
            <div class="field-row">
                <select name="titulares[__INDEX__][coparticipacao]" class="np-input select-coparticipacao-titular" required>
                    <option value="">Coparticipacao...</option>
                    <option value="Y">Sim</option>
                    <option value="N">Nao</option>
                </select>
                <select name="titulares[__INDEX__][plano_anterior]" class="np-input select-plano-anterior-titular">
                    <option value="NAO">Plano anterior: Nao</option>
                    <option value="SIM">Plano anterior: Sim</option>
                </select>
            </div>
            <div class="field-row field-row-op-anterior" style="display:none;">
                <select name="titulares[__INDEX__][operadora_anterior_id]" class="np-input field-op-anterior-titular">
                    <option value="">Operadora anterior...</option>
                    @foreach ($operadoras as $op)
                        <option value="{{ $op->id }}">{{ strtoupper($op->nome) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="dependentes-container" data-titular-index="__INDEX__"></div>
    </div>
</template>

{{-- Template Dependente --}}
<template id="template-dependente">
    <div class="dependente-card" data-dependente-index="__DEP_INDEX__">
        <div class="dependente-header">
            <span class="dep-badge">Dep. __DEP_NUMBER__</span>
            <button type="button" class="btn-remove-dep" title="Remover">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="dependente-body">
            <div class="field-row">
                <input type="text" name="titulares[__INDEX__][dependentes][__DEP_INDEX__][nome]" class="np-input" placeholder="Nome" required>
                <input type="text" name="titulares[__INDEX__][dependentes][__DEP_INDEX__][cpf]" class="np-input mask-cpf" placeholder="CPF (000.000.000-00)">
            </div>
            <div class="field-row">
                <input type="text" name="titulares[__INDEX__][dependentes][__DEP_INDEX__][data_nascimento]" class="np-input flatpickr-nascimento" placeholder="Data Nascimento">
                <input type="email" name="titulares[__INDEX__][dependentes][__DEP_INDEX__][email]" class="np-input" placeholder="E-mail">
            </div>
            <div class="field-row">
                <input type="text" name="titulares[__INDEX__][dependentes][__DEP_INDEX__][telefone1]" class="np-input mask-telefone" placeholder="Telefone 1">
                <input type="text" name="titulares[__INDEX__][dependentes][__DEP_INDEX__][telefone2]" class="np-input mask-telefone" placeholder="Telefone 2">
            </div>
            <div class="field-row">
                <select name="titulares[__INDEX__][dependentes][__DEP_INDEX__][parentesco]" class="np-input" required>
                    <option value="">Parentesco...</option>
                    <option value="CONJUGE">Conjuge</option>
                    <option value="FILHO">Filho(a)</option>
                    <option value="PAI_MAE">Pai/Mae</option>
                    <option value="SOBRINHO">Sobrinho(a)</option>
                    <option value="OUTROS">Outros</option>
                </select>
            </div>
            <div class="field-row">
                <select name="titulares[__INDEX__][dependentes][__DEP_INDEX__][plano_anterior]" class="np-input select-plano-anterior">
                    <option value="NAO">Plano anterior: Nao</option>
                    <option value="SIM">Plano anterior: Sim</option>
                </select>
                <select name="titulares[__INDEX__][dependentes][__DEP_INDEX__][operadora_anterior_id]" class="np-input field-op-anterior" style="display:none;">
                    <option value="">Operadora anterior...</option>
                    @foreach ($operadoras as $op)
                        <option value="{{ $op->id }}">{{ strtoupper($op->nome) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</template>

{{-- Template Portabilidade --}}
<template id="template-portabilidade">
    <div class="port-item" data-port-index="__PORT_INDEX__">
        <span class="port-num">__PORT_NUMBER__</span>
        <input type="text" name="portabilidades[__PORT_INDEX__][nome]" class="np-input" placeholder="Nome do beneficiario" required>
        <button type="button" class="btn-remove-port">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
</template>

{{-- Modal de Documentacao PME --}}
<div class="docs-modal-overlay" id="docs-modal-overlay">
    <div class="docs-modal docs-modal-wide">
        <div class="docs-modal-header">
            <div class="docs-modal-title">
                <span class="docs-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                </span>
                <div>
                    <h3>Checklist de Documentacao PME</h3>
                    <span>Documentos necessarios para emissao do plano</span>
                </div>
            </div>
            <button type="button" class="docs-modal-close" id="btn-close-docs-modal">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        <div class="docs-modal-body docs-two-columns">
            {{-- COLUNA ESQUERDA - TITULAR --}}
            <div class="docs-column">
                <div class="docs-category docs-titular">
                    <div class="category-header">
                        <span class="category-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </span>
                        <span class="category-title">Titular / Socio</span>
                    </div>

                    <div class="docs-items-compact">
                        <div class="doc-item-mini"><span class="check-icon"></span><span>RG ou CNH</span></div>
                        <div class="doc-item-mini"><span class="check-icon"></span><span>CPF</span></div>
                        <div class="doc-item-mini"><span class="check-icon"></span><span>Comprovante de Endereco</span><small>(luz, agua, telefone - 90 dias)</small></div>
                        <div class="doc-item-mini">
                            <span class="check-icon"></span>
                            <span>Contrato Social</span>
                            <a href="https://www.jucesponline.sp.gov.br/" target="_blank" class="mini-link">JUCESP (SP)</a>
                        </div>
                        <div class="doc-item-mini">
                            <span class="check-icon"></span>
                            <span>Cartao CNPJ</span>
                            <a href="https://solucoes.receita.fazenda.gov.br/servicos/cnpjreva/cnpjreva_solicitacao.asp" target="_blank" class="mini-link">Receita Federal</a>
                        </div>
                    </div>

                    <div class="docs-conditional-mini">
                        <span class="cond-badge cond-warning">Se possui plano anterior</span>
                        <div class="docs-items-compact">
                            <div class="doc-item-mini"><span class="check-icon warning"></span><span>Carteirinha do Plano</span></div>
                            <div class="doc-item-mini"><span class="check-icon warning"></span><span>Carta de Permanencia</span></div>
                        </div>
                        <div class="alert-box alert-danger">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            <span><strong>Cuidado!</strong> Operadoras podem fazer retencao. Oriente o cliente a ser firme!</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- COLUNA DIREITA - DEPENDENTES --}}
            <div class="docs-column">
                <div class="docs-category docs-dependente">
                    <div class="category-header">
                        <span class="category-icon category-icon-dep">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </span>
                        <span class="category-title">Dependentes</span>
                    </div>

                    <div class="docs-items-compact">
                        <div class="doc-item-mini"><span class="check-icon dep"></span><span>RG ou CNH</span></div>
                        <div class="doc-item-mini"><span class="check-icon dep"></span><span>CPF</span></div>
                    </div>

                    <div class="docs-conditional-mini">
                        <span class="cond-badge cond-warning">Se possui plano anterior</span>
                        <div class="docs-items-compact">
                            <div class="doc-item-mini"><span class="check-icon dep"></span><span>Carteirinha + Carta de Permanencia</span></div>
                        </div>
                    </div>

                    <div class="docs-conditional-mini">
                        <span class="cond-badge cond-purple">Filho menor sem RG</span>
                        <div class="docs-items-compact">
                            <div class="doc-item-mini"><span class="check-icon purple"></span><span>Certidao de Nascimento</span></div>
                        </div>
                    </div>

                    <div class="docs-conditional-mini">
                        <span class="cond-badge cond-green">Se for funcionario</span>
                        <div class="docs-items-compact">
                            <div class="doc-item-mini"><span class="check-icon green"></span><span>Guia FGTS ou E-Social</span><small>(comprovacao de vinculo)</small></div>
                        </div>
                    </div>

                    <div class="docs-conditional-mini">
                        <span class="cond-badge cond-pink">Se for conjuge</span>
                        <div class="docs-items-compact">
                            <div class="doc-item-mini"><span class="check-icon pink"></span><span>Avaliado pelo sobrenome de ambos</span></div>
                        </div>
                        <div class="alert-box alert-pink">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                            <span>Se nao tiver mesmo sobrenome: <strong>Carta de Uniao Estavel com firma reconhecida em cartorio</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Dados old para JavaScript --}}
<script>
    window.oldTitulares = @json(old('titulares', []));
    window.oldOperadoraId = @json(old('operadora_id'));

    // Modal de documentacao
    document.addEventListener('DOMContentLoaded', function() {
        const btnOpen = document.getElementById('btn-open-docs-modal');
        const btnClose = document.getElementById('btn-close-docs-modal');
        const overlay = document.getElementById('docs-modal-overlay');

        if (btnOpen && overlay) {
            btnOpen.addEventListener('click', function() {
                overlay.classList.add('docs-modal-open');
                document.body.style.overflow = 'hidden';
            });
        }

        if (btnClose && overlay) {
            btnClose.addEventListener('click', function() {
                overlay.classList.remove('docs-modal-open');
                document.body.style.overflow = '';
            });
        }

        if (overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    overlay.classList.remove('docs-modal-open');
                    document.body.style.overflow = '';
                }
            });
        }

        // Fechar com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && overlay && overlay.classList.contains('docs-modal-open')) {
                overlay.classList.remove('docs-modal-open');
                document.body.style.overflow = '';
            }
        });

        // Toggle badge visual para plano dental
        const planoDentalSelect = document.getElementById('plano_dental');
        const planoDentalBadge = document.getElementById('plano-dental-badge');
        if (planoDentalSelect && planoDentalBadge) {
            planoDentalSelect.addEventListener('change', function() {
                if (this.value === 'SIM') {
                    planoDentalBadge.classList.remove('badge-inativo');
                    planoDentalBadge.classList.add('badge-ativo');
                    planoDentalBadge.textContent = 'Ativo';
                } else {
                    planoDentalBadge.classList.remove('badge-ativo');
                    planoDentalBadge.classList.add('badge-inativo');
                    planoDentalBadge.textContent = 'Inativo';
                }
            });
        }
    });

    // Mostrar erros via toastr
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('status') === 'error' && session('message'))
            if (typeof toastr !== 'undefined') {
                toastr.error(@json(session('message')));
            }
        @endif

        @if($errors->any())
            if (typeof toastr !== 'undefined') {
                @foreach($errors->all() as $error)
                    toastr.error(@json($error));
                @endforeach
            }
        @endif
    });
</script>

@endsection
