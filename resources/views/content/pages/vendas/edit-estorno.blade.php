@use('Illuminate\Support\Str')
@extends('layouts/layoutMaster')

@php
$isFlex = true;
$isFooter = false;

$tipoContratoAtual = old('tipo_contrato', $venda->tipo_contrato ?? 'PME');
$planoDentalAtual = old('plano_dental', $venda->plano_dental ?? 'SIM');
$angariacaoStatus = old('angariacao_status', $venda->angariacao_status ?? 'NAO');
$angariacaoStatus = $angariacaoStatus === 'SIM' ? 'SIM' : 'NAO';
$portabilidadeStatus = old('portabilidade_status', $venda->portabilidade_status ?? 'NAO');
$portabilidadeStatus = $portabilidadeStatus === 'SIM' ? 'SIM' : 'NAO';

// Formata valor decimal pra string brasileira (R$ 1.234,56)
$valorContrato = number_format((float) old('valor_contrato', $venda->valor_contrato ?? 0), 2, ',', '.');
$taxaAngariacao = number_format((float) old('taxa_angariacao', $venda->angariacao_valor ?? 0), 2, ',', '.');

$dataAberturaAtual = old('data_abertura');
if (!$dataAberturaAtual && $venda->data_abertura) {
    $dataAberturaAtual = \Carbon\Carbon::parse($venda->data_abertura)->format('d/m/Y');
}

// Hidratação dos titulares para o JS de novaProposta restaurar.
$titularesPayload = $venda->titulares->map(function ($t) {
    return [
        'nome' => $t->nome,
        'cpf' => $t->cpf,
        'data_nascimento' => $t->data_nascimento ? \Carbon\Carbon::parse($t->data_nascimento)->format('d/m/Y') : '',
        'email' => $t->email,
        'telefone1' => $t->telefone,
        'telefone2' => $t->telefone2,
        'cargo' => $t->cargo,
        'plano_id' => $t->plano_id,
        'coparticipacao' => $t->coparticipacao,
        'plano_anterior' => $t->plano_anterior ?: 'NAO',
        'operadora_anterior_id' => $t->operadora_anterior_id,
        'dependentes' => $t->dependentes->map(function ($d) {
            return [
                'nome' => $d->nome,
                'cpf' => $d->cpf,
                'data_nascimento' => $d->data_nascimento ? \Carbon\Carbon::parse($d->data_nascimento)->format('d/m/Y') : '',
                'email' => $d->email,
                'telefone1' => $d->telefone1,
                'telefone2' => $d->telefone2,
                'parentesco' => $d->parentesco,
                'plano_anterior' => $d->plano_anterior ?: 'NAO',
                'operadora_anterior_id' => $d->operadora_anterior_id,
            ];
        })->values(),
    ];
})->values();

$portabilidadesPayload = $venda->portabilidades->map(function ($p) {
    return [
        'nome' => $p->nome,
        'operadora_destino_id' => $p->operadora_destino_id,
        'plano_destino_id' => $p->plano_destino_id,
    ];
})->values();

$titularesParaJs = old('titulares') ?: $titularesPayload;
$portabilidadesParaJs = old('portabilidades') ?: $portabilidadesPayload;
$operadoraIdAtual = old('operadora_id', $venda->operadora_id);

$nomeCliente = $cliente->nome_cliente ?? $venda->nome_contrato ?? 'Cliente';
$telefoneCliente = $cliente->telefone1 ?? $venda->telefone1 ?? '';
$emailCliente = $cliente->email ?? $venda->email ?? '';
$cpfClienteData = $cliente->cpf ?? $venda->cpf_cnpj ?? '';
@endphp

@section('title', 'Corrigir Estorno - Venda #' . $venda->id)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/toastr/toastr.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/nova-proposta.scss'])
    @vite(['resources/assets/vendor/scss/pages/edit-estorno.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/novaProposta.js'])
    @vite(['resources/assets/js/edit-estorno.js'])
@endsection

@section('content')
<div class="nova-proposta-wrapper estorno-mode">

    {{-- Header com Info do Cliente --}}
    <div class="np-top-header">
        <div class="top-header-left">
            <a href="{{ route('sale.meusEstornos') }}" class="btn-back" title="Voltar para Meus Estornos">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            </a>
            <div class="top-header-info">
                <span class="header-badge header-badge-danger">ESTORNO</span>
                <h1 class="page-title">Corrigir e Reenviar Venda #{{ $venda->id }}</h1>
            </div>
        </div>
        <div class="top-header-client">
            <div class="client-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </div>
            <div class="client-details">
                <span class="client-name">{{ $nomeCliente }}</span>
                <span class="client-contact">{{ $telefoneCliente }}@if($emailCliente) | {{ $emailCliente }}@endif</span>
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

    {{-- Banner: motivo do estorno --}}
    <div class="estorno-banner">
        <div class="estorno-banner-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <div class="estorno-banner-content">
            <span class="estorno-banner-eyebrow">Motivo informado pelo backoffice</span>
            <p class="estorno-banner-text">{{ $venda->motivo_pendencia ?: 'Sem motivo informado.' }}</p>
        </div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('sale.reenviarEstorno', $venda->id) }}" class="np-form" id="formNovaProposta" data-modo="estorno">
        @csrf
        <input type="hidden" id="contato_id" name="contato_id" value="{{ $venda->contato_id }}" />
        <input type="hidden" id="tipo_contrato" name="tipo_contrato" value="{{ $tipoContratoAtual }}" />

        {{-- Layout Principal em 2 Colunas --}}
        <div class="np-layout">

            {{-- COLUNA ESQUERDA --}}
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
                        <div class="np-inline-toggle" style="margin-bottom: 1rem;">
                            <div class="toggle-info">
                                <span class="toggle-label">Tipo de Proposta</span>
                                <span class="status-badge badge-ativo" id="tipo-proposta-badge">{{ $tipoContratoAtual }}</span>
                            </div>
                            <div class="toggle-controls">
                                <select id="tipo_proposta_toggle" class="np-input np-input-sm">
                                    <option value="PME" {{ $tipoContratoAtual === 'PME' ? 'selected' : '' }}>PME (Empresa)</option>
                                    <option value="ADESAO" {{ $tipoContratoAtual === 'ADESAO' ? 'selected' : '' }}>Adesao (Pessoa Fisica)</option>
                                </select>
                            </div>
                        </div>

                        <div class="np-grid grid-2">
                            <div class="np-field span-2">
                                <label id="label-razao-social">{{ $tipoContratoAtual === 'ADESAO' ? 'Nome do Titular' : 'Razao Social' }}</label>
                                <input type="text" id="nome_contrato" name="nome_contrato" class="np-input @error('nome_contrato') is-invalid @enderror" placeholder="RAZAO SOCIAL DA EMPRESA" value="{{ old('nome_contrato', $venda->nome_contrato) }}" required />
                            </div>
                            <div class="np-field">
                                <label id="label-cpf-cnpj">{{ $tipoContratoAtual === 'ADESAO' ? 'CPF' : 'CNPJ' }}</label>
                                <input type="text" id="cpf_cnpj" name="cpf_cnpj" class="np-input @error('cpf_cnpj') is-invalid @enderror" placeholder="00.000.000/0000-00" value="{{ old('cpf_cnpj', $venda->cpf_cnpj) }}" required />
                            </div>
                            <div class="np-field" id="field-tipo-empresa">
                                <label>Tipo Empresa</label>
                                <select id="tipo_empresa" name="tipo_empresa" class="np-input">
                                    <option value="">Selecione...</option>
                                    @foreach (['MEI','ME','EPP','LTDA','SA','EIRELI','SLU'] as $tipo)
                                        <option value="{{ $tipo }}" {{ old('tipo_empresa', $venda->tipo_empresa) === $tipo ? 'selected' : '' }}>{{ $tipo === 'SA' ? 'S/A' : $tipo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="np-field" id="field-data-abertura">
                                <label>Data Abertura</label>
                                <input type="text" id="data_abertura" name="data_abertura" class="np-input flatpickr-date" placeholder="DD/MM/AAAA" value="{{ $dataAberturaAtual }}" />
                            </div>
                            <div class="np-field">
                                <label>E-mail</label>
                                <input type="email" id="email" name="email" class="np-input" placeholder="email@empresa.com" value="{{ old('email', $venda->email) }}" />
                            </div>
                            <div class="np-field">
                                <label>Telefone 1</label>
                                <input type="text" id="telefone1" name="telefone1" class="np-input mask-telefone" placeholder="(11) 3000-0000" value="{{ old('telefone1', $venda->telefone1) }}" />
                            </div>
                            <div class="np-field">
                                <label>Telefone 2</label>
                                <input type="text" id="telefone2" name="telefone2" class="np-input mask-telefone" placeholder="(11) 91234-5678" value="{{ old('telefone2', $venda->telefone2) }}" />
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
                                        <option value="{{ $op->id }}" data-coparticipacao-formato="{{ $op->coparticipacao_formato }}" data-angariacao-padrao="{{ $op->angariacao_padrao ? '1' : '0' }}" {{ (string) $operadoraIdAtual === (string) $op->id ? 'selected' : '' }}>{{ strtoupper($op->nome) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="np-field">
                                <label>Qtd. Titulares</label>
                                <input type="number" min="1" value="{{ old('qtd_titulares', max(1, $venda->titulares->count())) }}" id="qtd_titulares" name="qtd_titulares" class="np-input" />
                            </div>
                        </div>

                        <div class="np-inline-toggle" style="margin-top: 0.75rem;">
                            <div class="toggle-info">
                                <span class="toggle-label">Plano Dental</span>
                                <span class="status-badge {{ $planoDentalAtual === 'SIM' ? 'badge-ativo' : 'badge-inativo' }}" id="plano-dental-badge">{{ $planoDentalAtual === 'SIM' ? 'Ativo' : 'Inativo' }}</span>
                            </div>
                            <div class="toggle-controls">
                                <select id="plano_dental" name="plano_dental" class="np-input np-input-sm">
                                    <option value="SIM" {{ $planoDentalAtual === 'SIM' ? 'selected' : '' }}>SIM</option>
                                    <option value="NAO" {{ $planoDentalAtual === 'NAO' ? 'selected' : '' }}>NAO</option>
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
                                <input type="text" id="valor_contrato" name="valor_contrato" value="{{ $valorContrato }}" class="np-input monetary-field" placeholder="R$ 0,00" />
                            </div>
                            <div class="np-field">
                                <label>Total Vidas</label>
                                <input type="number" id="vidas" name="vidas" class="np-input" value="{{ old('vidas', $venda->vidas) }}" readonly />
                            </div>
                        </div>

                        <div class="np-inline-toggle">
                            <div class="toggle-info">
                                <span class="toggle-label">Angariacao</span>
                                <span class="status-badge {{ $angariacaoStatus === 'SIM' ? 'badge-ativo' : 'badge-inativo' }}" id="angariacao-badge">{{ $angariacaoStatus === 'SIM' ? 'Ativo' : 'Inativo' }}</span>
                            </div>
                            <div class="toggle-controls">
                                <input type="text" id="taxa_angariacao" name="taxa_angariacao" value="{{ $taxaAngariacao }}" class="np-input np-input-sm monetary-field" placeholder="R$ 0,00" />
                                <select id="angariacao_status" name="angariacao_status" class="np-input np-input-sm">
                                    <option value="NAO" {{ $angariacaoStatus !== 'SIM' ? 'selected' : '' }}>NAO</option>
                                    <option value="SIM" {{ $angariacaoStatus === 'SIM' ? 'selected' : '' }}>SIM</option>
                                </select>
                            </div>
                        </div>

                        <x-proposta-portabilidades :operadoras="$operadoras" />

                        <div class="np-field" style="margin-top: 0.75rem;">
                            <label>Observacoes</label>
                            <input type="text" id="obs_contrato" name="obs_contrato" class="np-input" placeholder="Observacoes do contrato" value="{{ old('obs_contrato', $venda->obs_contrato) }}" />
                        </div>
                    </div>
                </div>

                {{-- Observação do Reenvio (opcional) --}}
                <div class="np-section np-section-reenvio">
                    <div class="section-header">
                        <span class="section-icon icon-danger">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 14 4 9l5-5"></path><path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5v0a5.5 5.5 0 0 1-5.5 5.5H11"></path></svg>
                        </span>
                        <span class="section-title">O que foi corrigido? <span class="text-muted" style="font-weight: 400; font-size: 0.85rem;">(opcional)</span></span>
                    </div>
                    <div class="section-body">
                        <div class="np-field">
                            <label>Se preferir, deixe em branco — quando o alinhamento já foi feito por outro canal.</label>
                            <textarea name="observacao_reenvio" id="observacao_reenvio" class="np-input @error('observacao_reenvio') is-invalid @enderror" rows="3" maxlength="500" placeholder="Ex.: CPF do titular corrigido. Plano alterado para Premium conforme alinhamento com o cliente.">{{ old('observacao_reenvio') }}</textarea>
                        </div>
                    </div>
                </div>

                </div>{{-- Fim np-col-left-scroll --}}
            </div>

            {{-- COLUNA DIREITA - TITULARES --}}
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

        {{-- Botao Flutuante: Reenviar --}}
        <button type="submit" class="np-floating-save np-floating-save-danger" title="Reenviar para o backoffice" id="btn-reenviar">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
            <span>Reenviar</span>
        </button>
    </form>

</div>

{{-- Templates idênticos ao da nova-proposta --}}
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
                <div class="field-cargo-wrapper">
                    <select name="titulares[__INDEX__][cargo]" class="np-input select-cargo-titular" required>
                        <option value="">Cargo...</option>
                        <option value="SOCIO">Socio</option>
                        <option value="DIRETOR">Diretor</option>
                        <option value="GERENTE">Gerente</option>
                        <option value="FUNCIONARIO">Funcionario</option>
                        <option value="PRESTADOR">Prestador</option>
                        <option value="ESTAGIARIO">Estagiario</option>
                    </select>
                </div>
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

{{-- O novaProposta.js usa este modal tanto para criar quanto para corrigir dependentes. --}}
<div class="dep-modal-overlay" id="dep-modal-overlay">
    <div class="dep-modal">
        <div class="dep-modal-header">
            <div class="dep-modal-title">
                <span class="dep-modal-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
                </span>
                <div>
                    <h3 id="dep-modal-title">Adicionar Dependente</h3>
                    <span>Preencha todos os dados do beneficiario</span>
                </div>
            </div>
            <button type="button" class="dep-modal-close" id="btn-dep-modal-close">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        <div class="dep-modal-body">
            <div class="np-grid grid-2">
                <div class="np-field span-2">
                    <label class="required" for="dep_nome">Nome completo</label>
                    <input type="text" id="dep_nome" class="np-input" placeholder="Nome completo do dependente" autocomplete="off">
                </div>
                <div class="np-field">
                    <label class="required" for="dep_cpf">CPF</label>
                    <input type="text" id="dep_cpf" class="np-input" placeholder="000.000.000-00" autocomplete="off">
                </div>
                <div class="np-field">
                    <label class="required" for="dep_data_nascimento">Data de Nascimento</label>
                    <input type="text" id="dep_data_nascimento" class="np-input" placeholder="DD/MM/AAAA" autocomplete="off">
                </div>
                <div class="np-field span-2">
                    <label class="required" for="dep_email">E-mail</label>
                    <input type="email" id="dep_email" class="np-input" placeholder="email@exemplo.com" autocomplete="off">
                </div>
                <div class="np-field">
                    <label class="required" for="dep_telefone1">Telefone 1</label>
                    <input type="text" id="dep_telefone1" class="np-input" placeholder="(11) 91234-5678" autocomplete="off">
                </div>
                <div class="np-field">
                    <label for="dep_telefone2">Telefone 2</label>
                    <input type="text" id="dep_telefone2" class="np-input" placeholder="(11) 91234-5678" autocomplete="off">
                </div>
                <div class="np-field">
                    <label class="required" for="dep_parentesco">Parentesco</label>
                    <select id="dep_parentesco" class="np-input">
                        <option value="">Selecione...</option>
                        <option value="CONJUGE">Conjuge</option>
                        <option value="COMPANHEIRO">Companheiro(a)</option>
                        <option value="FILHO">Filho(a)</option>
                        <option value="ENTEADO">Enteado(a)</option>
                        <option value="PAI_MAE">Pai/Mae</option>
                        <option value="SOGRO">Sogro(a)</option>
                        <option value="IRMAO">Irmao(a)</option>
                        <option value="NETO">Neto(a)</option>
                        <option value="AVO">Avo(o)</option>
                        <option value="BISNETO">Bisneto(a)</option>
                        <option value="BISAVO">Bisavo(o)</option>
                        <option value="TIO">Tio(a)</option>
                        <option value="SOBRINHO">Sobrinho(a)</option>
                        <option value="PRIMO">Primo(a)</option>
                        <option value="GENRO_NORA">Genro/Nora</option>
                        <option value="CUNHADO">Cunhado(a)</option>
                        <option value="TUTELADO">Tutelado(a)/Menor sob guarda</option>
                        <option value="OUTROS">Outros</option>
                    </select>
                </div>
                <div class="np-field">
                    <label for="dep_plano_anterior">Possui plano anterior?</label>
                    <select id="dep_plano_anterior" class="np-input">
                        <option value="NAO">Nao</option>
                        <option value="SIM">Sim</option>
                    </select>
                </div>
                <div class="np-field span-2" id="dep-op-anterior-row" style="display:none;">
                    <label class="required" for="dep_operadora_anterior_id">Qual era a operadora?</label>
                    <select id="dep_operadora_anterior_id" class="np-input">
                        <option value="">Selecione a operadora anterior...</option>
                        @foreach ($operadoras as $op)
                            <option value="{{ $op->id }}">{{ strtoupper($op->nome) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="dep-modal-footer">
            <button type="button" class="dep-btn dep-btn-ghost" id="btn-dep-modal-cancel">Cancelar</button>
            <button type="button" class="dep-btn dep-btn-primary" id="btn-dep-modal-save">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                <span id="dep-modal-save-label">Adicionar dependente</span>
            </button>
        </div>
    </div>
</div>

{{-- Modal de Documentacao PME (mesmo da nova-proposta) --}}
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
                        <div class="doc-item-mini"><span class="check-icon"></span><span>Contrato Social</span></div>
                        <div class="doc-item-mini"><span class="check-icon"></span><span>Cartao CNPJ</span></div>
                    </div>
                </div>
            </div>
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
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Hidratação dos dados antigos para o JS de novaProposta restaurar --}}
<script>
    window.oldTitulares = @json($titularesParaJs);
    window.oldOperadoraId = @json($operadoraIdAtual);
    window.oldPortabilidades = @json($portabilidadesParaJs);
    window.portabilidadePlanos = @json($planosPortabilidade);

    window.clientData = {
        nome: @json($nomeCliente),
        cpf: @json($cpfClienteData)
    };

    // Modal de documentação (cópia idêntica da nova-proposta)
    document.addEventListener('DOMContentLoaded', function () {
        const btnOpen = document.getElementById('btn-open-docs-modal');
        const btnClose = document.getElementById('btn-close-docs-modal');
        const overlay = document.getElementById('docs-modal-overlay');

        if (btnOpen && overlay) {
            btnOpen.addEventListener('click', function () {
                overlay.classList.add('docs-modal-open');
                document.body.style.overflow = 'hidden';
            });
        }
        if (btnClose && overlay) {
            btnClose.addEventListener('click', function () {
                overlay.classList.remove('docs-modal-open');
                document.body.style.overflow = '';
            });
        }
        if (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) {
                    overlay.classList.remove('docs-modal-open');
                    document.body.style.overflow = '';
                }
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay && overlay.classList.contains('docs-modal-open')) {
                overlay.classList.remove('docs-modal-open');
                document.body.style.overflow = '';
            }
        });

        const planoDentalSelect = document.getElementById('plano_dental');
        const planoDentalBadge = document.getElementById('plano-dental-badge');
        if (planoDentalSelect && planoDentalBadge) {
            planoDentalSelect.addEventListener('change', function () {
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

    document.addEventListener('DOMContentLoaded', function () {
        @if(session('status') === 'error' && session('message'))
            if (typeof toastr !== 'undefined') toastr.error(@json(session('message')));
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
