@use('Illuminate\Support\Str')
@extends('layouts/layoutMaster')

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
                                <input type="text" id="nome_contrato" name="nome_contrato" class="np-input" placeholder="RAZAO SOCIAL DA EMPRESA" required />
                            </div>
                            <div class="np-field">
                                <label>CNPJ</label>
                                <input type="text" id="cpf_cnpj" name="cpf_cnpj" class="np-input mask-cnpj" placeholder="00.000.000/0000-00" required />
                            </div>
                            <div class="np-field">
                                <label>Tipo Empresa</label>
                                <select id="tipo_empresa" name="tipo_empresa" class="np-input" required>
                                    <option value="">Selecione...</option>
                                    <option value="MEI">MEI</option>
                                    <option value="ME">ME</option>
                                    <option value="EPP">EPP</option>
                                    <option value="LTDA">LTDA</option>
                                    <option value="SA">S/A</option>
                                    <option value="EIRELI">EIRELI</option>
                                    <option value="SLU">SLU</option>
                                </select>
                            </div>
                            <div class="np-field">
                                <label>Data Abertura</label>
                                <input type="text" id="data_abertura" name="data_abertura" class="np-input flatpickr-date" placeholder="DD/MM/AAAA" />
                            </div>
                            <div class="np-field">
                                <label>E-mail</label>
                                <input type="email" id="email" name="email" class="np-input" placeholder="email@empresa.com" />
                            </div>
                            <div class="np-field">
                                <label>Telefone 1</label>
                                <input type="text" id="telefone1" name="telefone1" class="np-input mask-telefone" placeholder="(11) 3000-0000" />
                            </div>
                            <div class="np-field">
                                <label>Telefone 2</label>
                                <input type="text" id="telefone2" name="telefone2" class="np-input mask-telefone" placeholder="(11) 91234-5678" />
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
                                <select id="operadora" name="operadora_id" class="np-input" required>
                                    <option value="">Selecione...</option>
                                    @foreach ($operadoras as $op)
                                        <option value="{{ $op->id }}" data-nome="{{ strtoupper($op->nome) }}">{{ strtoupper($op->nome) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="np-field">
                                <label>Qtd. Titulares</label>
                                <input type="number" min="1" value="1" id="qtd_titulares" name="qtd_titulares" class="np-input" />
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
                                <input type="text" id="valor_contrato" name="valor_contrato" value="0" class="np-input monetary-field" placeholder="R$ 0,00" />
                            </div>
                            <div class="np-field">
                                <label>Total Vidas</label>
                                <input type="number" id="vidas" name="vidas" class="np-input" value="0" readonly />
                            </div>
                        </div>

                        {{-- Angariacao Inline --}}
                        <div class="np-inline-toggle">
                            <div class="toggle-info">
                                <span class="toggle-label">Angariacao</span>
                                <span class="status-badge badge-inativo" id="angariacao-badge">Inativo</span>
                            </div>
                            <div class="toggle-controls">
                                <input type="text" id="taxa_angariacao" name="taxa_angariacao" value="0" class="np-input np-input-sm monetary-field" placeholder="R$ 0,00" />
                                <select id="angariacao_status" name="angariacao_status" class="np-input np-input-sm">
                                    <option value="NAO">NAO</option>
                                    <option value="SIM">SIM</option>
                                </select>
                            </div>
                        </div>

                        {{-- Portabilidade Inline --}}
                        <div class="np-inline-toggle">
                            <div class="toggle-info">
                                <span class="toggle-label">Portabilidade</span>
                                <span class="status-badge badge-inativo" id="portabilidade-badge">Inativo</span>
                            </div>
                            <div class="toggle-controls">
                                <input type="number" id="qtd_portabilidade" name="qtd_portabilidade" min="0" value="0" class="np-input np-input-sm" placeholder="Qtd" style="display:none;" />
                                <select id="portabilidade_status" name="portabilidade_status" class="np-input np-input-sm">
                                    <option value="NAO">NAO</option>
                                    <option value="SIM">SIM</option>
                                </select>
                            </div>
                        </div>

                        {{-- Lista Portabilidade --}}
                        <div id="portabilidade-container" class="portabilidade-list" style="display: none;"></div>

                        {{-- Observacoes --}}
                        <div class="np-field" style="margin-top: 0.75rem;">
                            <label>Observacoes</label>
                            <input type="text" id="obs_contrato" name="obs_contrato" class="np-input" placeholder="Observacoes do contrato" />
                        </div>
                    </div>
                </div>

                {{-- Botao Salvar --}}
                <div class="np-actions">
                    <button type="submit" class="np-btn np-btn-primary np-btn-full">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Salvar Proposta
                    </button>
                </div>
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
                <select name="titulares[__INDEX__][plano_anterior]" class="np-input select-plano-anterior-titular">
                    <option value="NAO">Plano anterior: Nao</option>
                    <option value="SIM">Plano anterior: Sim</option>
                </select>
                <select name="titulares[__INDEX__][operadora_anterior_id]" class="np-input field-op-anterior-titular" style="display:none;">
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
                <input type="email" name="titulares[__INDEX__][dependentes][__DEP_INDEX__][email]" class="np-input" placeholder="E-mail">
            </div>
            <div class="field-row">
                <input type="text" name="titulares[__INDEX__][dependentes][__DEP_INDEX__][telefone1]" class="np-input mask-telefone" placeholder="Telefone 1">
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

@endsection
