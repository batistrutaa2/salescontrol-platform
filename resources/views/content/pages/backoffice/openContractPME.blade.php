@extends('layouts/layoutMaster')

@section('title', 'Gestao de Contrato PME')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
    @vite(['resources/assets/vendor/scss/pages/backoffice-contract-pme.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/openContractPME.js'])
@endsection

@section('content')
    @php
        $selectedOperadoraId = $selectedOperadoraId ?? optional(($operadoras ?? collect())->firstWhere('nome', $contract->operadora))->id;
        $planosDaOperadora = $planosDaOperadora ?? collect();

        $isAmil = false;
        if ($selectedOperadoraId) {
            $nomeOpSel = optional(($operadoras ?? collect())->firstWhere('id', $selectedOperadoraId))->nome;
            $isAmil = stripos((string) $nomeOpSel, 'AMIL') !== false;
        } else {
            $isAmil = stripos((string) $contract->operadora, 'AMIL') !== false;
        }

        // Funções de formatação
        $formatCpf = function($cpf) {
            $cpf = preg_replace('/\D/', '', $cpf);
            if (strlen($cpf) !== 11) return $cpf;
            return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
        };

        $formatTelefone = function($tel) {
            $tel = preg_replace('/\D/', '', $tel);
            if (strlen($tel) === 11) {
                return '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 5) . '-' . substr($tel, 7, 4);
            } elseif (strlen($tel) === 10) {
                return '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 4) . '-' . substr($tel, 6, 4);
            }
            return $tel;
        };

        $tiposEmpresa = [
            'MEI' => 'MEI - Microempreendedor Individual',
            'ME' => 'ME - Microempresa',
            'EPP' => 'EPP - Empresa de Pequeno Porte',
            'LTDA' => 'LTDA - Sociedade Limitada',
            'SA' => 'S/A - Sociedade Anonima',
            'EIRELI' => 'EIRELI',
            'SLU' => 'SLU - Sociedade Limitada Unipessoal',
        ];

        $cargos = [
            'SOCIO' => 'Socio',
            'DIRETOR' => 'Diretor',
            'GERENTE' => 'Gerente',
            'FUNCIONARIO' => 'Funcionario',
            'PRESTADOR' => 'Prestador',
            'ESTAGIARIO' => 'Estagiario',
        ];

        $parentescos = [
            'CONJUGE' => 'Conjuge',
            'FILHO' => 'Filho(a)',
            'PAI_MAE' => 'Pai/Mae',
            'SOBRINHO' => 'Sobrinho(a)',
            'OUTROS' => 'Outros',
        ];

        $coparticipacoes = [
            'Y' => 'Sim',
            'N' => 'Nao',
            'PARCIAL' => 'Parcial',
            'COMPLETA' => 'Completa',
        ];

        $calcularIdade = function($dataNascimento) {
            if (!$dataNascimento) return null;
            return $dataNascimento->age;
        };
    @endphp

    {{-- Flash Messages --}}
    @if (session('status') && session('message'))
        <div class="alert alert-{{ session('status') === 'success' ? 'success' : 'danger' }} alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Ops!</strong> Verifique os pontos abaixo:
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    {{-- Backoffice: Modal obrigatorio para assumir contrato --}}
    @if ($showAssumirDialog ?? false)
    <div class="modal fade" id="modalAssumirContrato" tabindex="-1" aria-labelledby="modalAssumirLabel"
         aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAssumirLabel">Contrato sem responsavel</h5>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                             class="text-primary">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                    <p class="mb-1"><strong>Este contrato ainda nao possui um backoffice responsavel.</strong></p>
                    <p class="text-muted mb-0">Deseja assumir a responsabilidade ou apenas visualizar?</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" id="btn-apenas-visualizar">
                        Apenas Visualizar
                    </button>
                    <button type="button" class="btn btn-primary" id="btn-assumir-contrato"
                            data-venda-id="{{ $contract->id }}">
                        Assumir Contrato
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Backoffice: Badge do responsavel --}}
    @if ($backofficeUser ?? null)
    <div class="responsavel-card-pme">
        <div class="responsavel-left">
            <div class="responsavel-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
            </div>
            <div class="responsavel-info">
                <span class="responsavel-label">Backoffice Responsável</span>
                <span class="responsavel-name">
                    {{ $backofficeUser->name }}
                    @if ($isOwner ?? false)
                        <span class="responsavel-badge-you">Você</span>
                    @endif
                </span>
            </div>
        </div>
        @if ($canReassign ?? false)
        <div class="responsavel-right">
            <div class="responsavel-reassign">
                <select id="reatribuir-select" class="responsavel-select">
                    <option value="">Reatribuir para...</option>
                    @foreach ($backofficeUsers ?? collect() as $bu)
                        <option value="{{ $bu->id }}" {{ $contract->backoffice_id == $bu->id ? 'selected' : '' }}>
                            {{ $bu->name }}
                        </option>
                    @endforeach
                </select>
                <button type="button" class="responsavel-btn" id="btn-reatribuir"
                        data-venda-id="{{ $contract->id }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="17 1 21 5 17 9"></polyline>
                        <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                        <polyline points="7 23 3 19 7 15"></polyline>
                        <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                    </svg>
                    <span>Reatribuir</span>
                </button>
            </div>
        </div>
        @endif
    </div>
    @endif

    <div class="pme-contract-wrapper">

        {{-- Header --}}
        <div class="pme-header">
            <div class="pme-header-left">
                <a href="{{ route('backoffice.index') }}" class="btn-back">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                </a>
                <div class="pme-header-info">
                    <span class="header-badge" id="header-badge-tipo">{{ ($contract->tipo_contrato ?? 'PME') === 'ADESAO' ? 'ADESAO' : 'PME' }}</span>
                    <h1 class="page-title">{{ $contract->nome_contrato }}</h1>
                    <span class="page-subtitle" id="page-subtitle-doc">{{ ($contract->tipo_contrato ?? 'PME') === 'ADESAO' ? 'CPF' : 'CNPJ' }}: {{ $contract->cpf_cnpj }} | Contrato #{{ $contract->id }}</span>
                </div>
            </div>
            <div class="pme-header-stats">
                <div class="stat-item">
                    <span class="stat-value">{{ $titulares->count() }}</span>
                    <span class="stat-label">Titulares</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">{{ $dependentes->count() }}</span>
                    <span class="stat-label">Dependentes</span>
                </div>
                <div class="stat-item stat-highlight">
                    <span class="stat-value">{{ $contract->vidas }}</span>
                    <span class="stat-label">Vidas</span>
                </div>
                <div class="stat-item stat-money">
                    <span class="stat-value">R$ {{ number_format((float) $contract->valor_contrato, 2, ',', '.') }}</span>
                    <span class="stat-label">Valor Mensal</span>
                </div>
            </div>
        </div>

        {{-- Layout Principal --}}
        <div class="pme-layout">

            {{-- Coluna Esquerda --}}
            <div class="pme-col-left">

                {{-- Dados da Empresa --}}
                <div class="pme-section">
                    <div class="section-header">
                        <span class="section-icon icon-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        </span>
                        <span class="section-title">Dados da Empresa</span>
                    </div>
                    <div class="section-body">
                        <form method="POST" action="{{ route('backoffice.updateSale') }}" id="form-empresa" @if (!($canEdit ?? true)) data-readonly="true" @endif>
                            @csrf
                            <input type="hidden" name="id" value="{{ $contract->id }}">
                            <input type="hidden" id="tipo_contrato" name="tipo_contrato" value="{{ $contract->tipo_contrato ?? 'PME' }}">
                            <input type="hidden" id="layout_venda" name="layout_venda" value="{{ $contract->layout_venda ?? 'ANTIGO' }}">

                            {{-- Toggle Tipo de Proposta --}}
                            <div class="pme-inline-toggle" style="margin-bottom: 1rem;">
                                <div class="toggle-info">
                                    <span class="toggle-label">Tipo de Proposta</span>
                                    <span class="status-badge {{ ($contract->tipo_contrato ?? 'PME') === 'ADESAO' ? 'badge-warning' : 'badge-ativo' }}" id="tipo-proposta-badge">
                                        {{ ($contract->tipo_contrato ?? 'PME') === 'ADESAO' ? 'Adesao' : 'PME' }}
                                    </span>
                                </div>
                                <div class="toggle-controls">
                                    <select id="tipo_proposta_toggle" class="pme-input pme-input-sm">
                                        <option value="PME" {{ ($contract->tipo_contrato ?? 'PME') !== 'ADESAO' ? 'selected' : '' }}>PME (Empresa)</option>
                                        <option value="ADESAO" {{ ($contract->tipo_contrato ?? '') === 'ADESAO' ? 'selected' : '' }}>Adesao (Pessoa Fisica)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="pme-grid grid-2">
                                <div class="pme-field span-2">
                                    <label id="label-razao-social">{{ ($contract->tipo_contrato ?? 'PME') === 'ADESAO' ? 'Nome do Cliente' : 'Razao Social' }}</label>
                                    <input type="text" name="nome_contrato" class="pme-input" value="{{ $contract->nome_contrato }}">
                                </div>
                                <div class="pme-field">
                                    <label id="label-cpf-cnpj">{{ ($contract->tipo_contrato ?? 'PME') === 'ADESAO' ? 'CPF' : 'CNPJ' }}</label>
                                    <input type="text" id="cpf_cnpj" name="cpf_cnpj" class="pme-input {{ ($contract->tipo_contrato ?? 'PME') === 'ADESAO' ? 'mask-cpf-dynamic' : 'mask-cnpj' }}" value="{{ $contract->cpf_cnpj }}">
                                </div>
                                <div class="pme-field" id="field-tipo-empresa" style="{{ ($contract->tipo_contrato ?? 'PME') === 'ADESAO' ? 'display:none;' : '' }}">
                                    <label>Tipo Empresa</label>
                                    <select id="tipo_empresa" name="tipo_empresa" class="pme-input">
                                        <option value="">Selecione...</option>
                                        @foreach($tiposEmpresa as $key => $label)
                                            <option value="{{ $key }}" {{ $contract->tipo_empresa === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="pme-field" id="field-data-abertura" style="{{ ($contract->tipo_contrato ?? 'PME') === 'ADESAO' ? 'display:none;' : '' }}">
                                    <label>Data Abertura</label>
                                    <input type="text" name="data_abertura" class="pme-input flatpickr-date" value="{{ $contract->data_abertura ? $contract->data_abertura->format('d/m/Y') : '' }}">
                                </div>
                                <div class="pme-field">
                                    <label>E-mail</label>
                                    <input type="email" name="email" class="pme-input" value="{{ $contract->email }}">
                                </div>
                                <div class="pme-field">
                                    <label>Telefone 1</label>
                                    <input type="text" name="telefone1" class="pme-input mask-telefone" value="{{ $contract->telefone1 }}">
                                </div>
                                <div class="pme-field">
                                    <label>Telefone 2</label>
                                    <input type="text" name="telefone2" class="pme-input mask-telefone" value="{{ $contract->telefone2 }}">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Operadora e Plano --}}
                <div class="pme-section">
                    <div class="section-header">
                        <span class="section-icon icon-info">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        </span>
                        <span class="section-title">Operadora e Plano</span>
                    </div>
                    <div class="section-body">
                        <div class="pme-grid grid-2">
                            <div class="pme-field">
                                <label>Operadora</label>
                                <select id="operadoraSelect" name="operadora" class="pme-input" form="form-empresa">
                                    <option value="">Selecione</option>
                                    @foreach ($operadoras ?? collect() as $op)
                                        <option value="{{ $op->id }}" data-nome="{{ strtoupper($op->nome) }}" {{ (int) $selectedOperadoraId === (int) $op->id ? 'selected' : '' }}>
                                            {{ $op->nome }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="pme-field">
                                <label>Plano Base</label>
                                <select id="planoSelect" name="plano_id" class="pme-input" form="form-empresa">
                                    @if (($planosDaOperadora ?? collect())->count())
                                        <option value="">Selecione...</option>
                                        @foreach ($planosDaOperadora as $p)
                                            <option value="{{ $p->id }}" data-acomodacao="{{ $p->acomodacao }}" {{ (int) $contract->plano_id === (int) $p->id ? 'selected' : '' }}>
                                                {{ strtoupper($p->nome) }}
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="">Selecione a operadora primeiro</option>
                                    @endif
                                </select>
                            </div>
                            <div class="pme-field">
                                <label>Valor Mensal</label>
                                <input type="text" name="valor_contrato" class="pme-input monetary-field" value="{{ number_format((float) $contract->valor_contrato, 2, ',', '.') }}" form="form-empresa">
                            </div>
                            <div class="pme-field">
                                <label>Total Vidas</label>
                                <input type="number" name="vidas" class="pme-input" value="{{ $contract->vidas }}" form="form-empresa">
                            </div>
                        </div>

                        {{-- Plano Dental --}}
                        <div class="pme-inline-toggle" style="margin-top: 0.75rem;">
                            <div class="toggle-info">
                                <span class="toggle-label">Plano Dental</span>
                                <span class="status-badge {{ ($contract->plano_dental ?? 'SIM') === 'SIM' ? 'badge-ativo' : 'badge-inativo' }}" id="plano-dental-badge">
                                    {{ ($contract->plano_dental ?? 'SIM') === 'SIM' ? 'Ativo' : 'Inativo' }}
                                </span>
                            </div>
                            <div class="toggle-controls">
                                <select id="plano_dental" name="plano_dental" class="pme-input pme-input-sm" form="form-empresa">
                                    <option value="SIM" {{ ($contract->plano_dental ?? 'SIM') === 'SIM' ? 'selected' : '' }}>SIM</option>
                                    <option value="NAO" {{ ($contract->plano_dental ?? '') === 'NAO' ? 'selected' : '' }}>NAO</option>
                                </select>
                            </div>
                        </div>

                        {{-- Observacoes --}}
                        <div class="pme-field" style="margin-top: 0.75rem;">
                            <label>Observacoes</label>
                            <textarea name="obs_contrato" class="pme-input" rows="2" form="form-empresa">{{ $contract->obs_contrato }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Angariacao e Portabilidade --}}
                <div class="pme-section">
                    <div class="section-header">
                        <span class="section-icon icon-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                        </span>
                        <span class="section-title">Angariacao e Portabilidade</span>
                    </div>
                    <div class="section-body">
                        {{-- Angariacao --}}
                        <div class="pme-inline-toggle">
                            <div class="toggle-info">
                                <span class="toggle-label">Angariacao</span>
                                <span class="status-badge {{ ($contract->angariacao_status ?? 'NAO') === 'SIM' ? 'badge-ativo' : 'badge-inativo' }}" id="angariacao-badge">
                                    {{ ($contract->angariacao_status ?? 'NAO') === 'SIM' ? 'Ativo' : 'Inativo' }}
                                </span>
                            </div>
                            <div class="toggle-controls">
                                <input type="text" name="angariacao_valor" class="pme-input pme-input-sm monetary-field" value="{{ number_format((float) $contract->angariacao_valor, 2, ',', '.') }}" form="form-empresa">
                                <select id="angariacao_status" name="angariacao_status" class="pme-input pme-input-sm" form="form-empresa">
                                    <option value="NAO" {{ ($contract->angariacao_status ?? 'NAO') === 'NAO' ? 'selected' : '' }}>NAO</option>
                                    <option value="SIM" {{ ($contract->angariacao_status ?? '') === 'SIM' ? 'selected' : '' }}>SIM</option>
                                </select>
                            </div>
                        </div>

                        {{-- Portabilidade --}}
                        <div class="pme-inline-toggle">
                            <div class="toggle-info">
                                <span class="toggle-label">Portabilidade</span>
                                <span class="status-badge {{ ($contract->portabilidade_status ?? 'NAO') === 'SIM' ? 'badge-ativo' : 'badge-inativo' }}" id="portabilidade-badge">
                                    {{ ($contract->portabilidade_status ?? 'NAO') === 'SIM' ? 'Ativo' : 'Inativo' }}
                                </span>
                            </div>
                            <div class="toggle-controls">
                                <span class="toggle-count">{{ $portabilidades->count() }} beneficiario(s)</span>
                                <button type="button" class="btn-icon btn-add-port" data-bs-toggle="modal" data-bs-target="#modalEditPortabilidade" title="Adicionar Beneficiario">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Lista de Beneficiarios Portabilidade --}}
                        <div class="portabilidade-list" id="portabilidades-container">
                            @if($portabilidades->count() > 0)
                                @foreach($portabilidades as $port)
                                <div class="portabilidade-card">
                                    <div class="portabilidade-card-header">
                                        <div class="portabilidade-seq">{{ $port->sequencial }}</div>
                                        <div class="portabilidade-nome">{{ $port->nome }}</div>
                                        <div class="portabilidade-actions">
                                            <button type="button" class="portabilidade-btn portabilidade-btn-edit"
                                                data-port-id="{{ $port->id }}"
                                                data-nome="{{ $port->nome }}"
                                                data-cpf="{{ $port->cpf }}"
                                                data-data-nascimento="{{ $port->data_nascimento ? $port->data_nascimento->format('d/m/Y') : '' }}"
                                                data-operadora-anterior-id="{{ $port->operadora_anterior_id }}"
                                                data-plano-anterior="{{ $port->plano_anterior }}"
                                                data-numero-carteirinha="{{ $port->numero_carteirinha }}"
                                                title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                            </button>
                                            <button type="button" class="portabilidade-btn portabilidade-btn-delete" data-port-id="{{ $port->id }}" data-nome="{{ $port->nome }}" title="Excluir">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="portabilidade-card-body">
                                        <div class="portabilidade-info-grid">
                                            @if($port->cpf)
                                            <div class="portabilidade-info-item">
                                                <span class="portabilidade-info-label">CPF</span>
                                                <span class="portabilidade-info-value">{{ $formatCpf($port->cpf) }}</span>
                                            </div>
                                            @endif
                                            @if($port->data_nascimento)
                                            <div class="portabilidade-info-item">
                                                <span class="portabilidade-info-label">Nascimento</span>
                                                <span class="portabilidade-info-value">{{ $port->data_nascimento->format('d/m/Y') }} <span class="text-muted">({{ $calcularIdade($port->data_nascimento) }} anos)</span></span>
                                            </div>
                                            @endif
                                            @if($port->operadoraAnterior)
                                            <div class="portabilidade-info-item">
                                                <span class="portabilidade-info-label">Operadora Anterior</span>
                                                <span class="portabilidade-info-value portabilidade-info-highlight">{{ $port->operadoraAnterior->nome }}</span>
                                            </div>
                                            @endif
                                            @if($port->plano_anterior)
                                            <div class="portabilidade-info-item">
                                                <span class="portabilidade-info-label">Plano Anterior</span>
                                                <span class="portabilidade-info-value">{{ $port->plano_anterior }}</span>
                                            </div>
                                            @endif
                                            @if($port->numero_carteirinha)
                                            <div class="portabilidade-info-item">
                                                <span class="portabilidade-info-label">Carteirinha</span>
                                                <span class="portabilidade-info-value">{{ $port->numero_carteirinha }}</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            @else
                            <div class="portabilidade-empty">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                <span>Nenhum beneficiario cadastrado</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Botao Salvar - Fixed ao final da coluna --}}
                <div class="pme-save-section">
                    @if ($canEdit ?? true)
                    <button type="submit" form="form-empresa" class="pme-btn-save">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        <span>Salvar Alteracoes</span>
                    </button>
                    @else
                    <div class="alert alert-warning mb-0">
                        <small>Somente o backoffice responsavel pode editar este contrato.</small>
                    </div>
                    @endif
                </div>

            </div>

            {{-- Coluna Direita - Titulares e Dependentes --}}
            <div class="pme-col-right">
                <div class="titulares-header">
                    <div class="titulares-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <span>Titulares e Dependentes</span>
                    </div>
                    @if($canEdit)
                    <button type="button" class="btn-add-titular-pme" id="btn-open-add-titular" title="Adicionar Titular">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <line x1="19" y1="8" x2="19" y2="14"/>
                            <line x1="22" y1="11" x2="16" y2="11"/>
                        </svg>
                        <span>Adicionar Titular</span>
                    </button>
                    @endif
                </div>

                <div class="titulares-scroll">
                    @forelse($titulares as $i => $titular)
                    <div class="titular-card">
                        {{-- Header com numero, badges e acoes --}}
                        <div class="titular-header">
                            <div class="titular-header-left">
                                <span class="titular-number">{{ $i + 1 }}</span>
                                <div class="titular-identity">
                                    <span class="titular-nome">{{ $titular->nome }}</span>
                                    <div class="titular-tags">
                                        @if($titular->cargo && ($contract->tipo_contrato ?? 'PME') !== 'ADESAO')
                                            <span class="tag tag-cargo">{{ $cargos[$titular->cargo] ?? $titular->cargo }}</span>
                                        @endif
                                        @if($titular->plano_anterior === 'SIM')
                                            <span class="tag tag-plano-ant">P. Anterior</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="titular-actions">
                                <button type="button" class="btn-icon btn-add-dep" data-titular-id="{{ $titular->id }}" title="Adicionar Dependente">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
                                </button>
                                <button type="button" class="btn-icon btn-delete-titular" data-titular-id="{{ $titular->id }}" data-titular-nome="{{ $titular->nome }}" title="Excluir Titular">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                                <button type="button" class="btn-icon btn-edit" data-titular-id="{{ $titular->id }}"
                                    data-nome="{{ $titular->nome }}"
                                    data-cpf="{{ $titular->cpf }}"
                                    data-data-nascimento="{{ $titular->data_nascimento ? $titular->data_nascimento->format('d/m/Y') : '' }}"
                                    data-email="{{ $titular->email }}"
                                    data-telefone="{{ $titular->telefone }}"
                                    data-telefone2="{{ $titular->telefone2 }}"
                                    data-plano-id="{{ $titular->plano_id }}"
                                    data-cargo="{{ $titular->cargo }}"
                                    data-coparticipacao="{{ $titular->coparticipacao }}"
                                    data-plano-anterior="{{ $titular->plano_anterior }}"
                                    data-operadora-anterior-id="{{ $titular->operadora_anterior_id }}"
                                    title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Informacoes do Titular --}}
                        <div class="titular-body">
                            <div class="info-list">
                                @if($titular->cpf)
                                <div class="info-row">
                                    <span class="info-label">CPF</span>
                                    <span class="info-value">{{ $formatCpf($titular->cpf) }}</span>
                                </div>
                                @endif
                                @if($titular->data_nascimento)
                                <div class="info-row">
                                    <span class="info-label">Data Nasc.</span>
                                    <span class="info-value">{{ $titular->data_nascimento->format('d/m/Y') }} <span class="text-muted">({{ $calcularIdade($titular->data_nascimento) }} anos)</span></span>
                                </div>
                                @endif
                                @if($titular->email)
                                <div class="info-row">
                                    <span class="info-label">E-mail</span>
                                    <span class="info-value">{{ $titular->email }}</span>
                                </div>
                                @endif
                                @if($titular->telefone)
                                <div class="info-row">
                                    <span class="info-label">Telefone</span>
                                    <span class="info-value">{{ $formatTelefone($titular->telefone) }}@if($titular->telefone2), {{ $formatTelefone($titular->telefone2) }}@endif</span>
                                </div>
                                @endif
                                @if($titular->plano)
                                <div class="info-row">
                                    <span class="info-label">Plano</span>
                                    <span class="info-value info-highlight">{{ strtoupper($titular->plano->nome) }}@if($titular->plano->acomodacao) <span class="text-muted">| {{ $titular->plano->acomodacao }}</span>@endif</span>
                                </div>
                                @endif
                                @if($titular->coparticipacao)
                                <div class="info-row">
                                    <span class="info-label">Coparticipacao</span>
                                    <span class="info-value">{{ $coparticipacoes[$titular->coparticipacao] ?? $titular->coparticipacao }}</span>
                                </div>
                                @endif
                                @if($titular->plano_anterior === 'SIM' && $titular->operadoraAnterior)
                                <div class="info-row">
                                    <span class="info-label">Plano Anterior</span>
                                    <span class="info-value info-warning">{{ $titular->operadoraAnterior->nome }}</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Dependentes do Titular --}}
                        @php
                            $depsTitular = $dependentes->where('titular_id', $titular->id);
                        @endphp
                        @if($depsTitular->count() > 0)
                        <div class="dependentes-section">
                            <div class="dependentes-divider">
                                <span>{{ $depsTitular->count() }} Dependente{{ $depsTitular->count() > 1 ? 's' : '' }}</span>
                            </div>
                            @foreach($depsTitular as $j => $dep)
                            <div class="dependente-card">
                                <div class="dep-header">
                                    <div class="dep-header-left">
                                        <span class="dep-number">{{ $j + 1 }}</span>
                                        <span class="dep-nome">{{ $dep->nome }}</span>
                                        @if($dep->parentesco)
                                            <span class="dep-tag">{{ $parentescos[$dep->parentesco] ?? $dep->parentesco }}</span>
                                        @endif
                                    </div>
                                    <div class="dep-actions">
                                        <button type="button" class="btn-icon-sm btn-edit-dep" data-dependente-id="{{ $dep->id }}"
                                            data-nome="{{ $dep->nome }}"
                                            data-cpf="{{ $dep->cpf }}"
                                            data-data-nascimento="{{ $dep->data_nascimento ? $dep->data_nascimento->format('d/m/Y') : '' }}"
                                            data-email="{{ $dep->email }}"
                                            data-telefone1="{{ $dep->telefone1 }}"
                                            data-telefone2="{{ $dep->telefone2 }}"
                                            data-parentesco="{{ $dep->parentesco }}"
                                            data-plano-id="{{ $dep->plano_id }}"
                                            data-coparticipacao="{{ $dep->coparticipacao }}"
                                            data-plano-anterior="{{ $dep->plano_anterior }}"
                                            data-operadora-anterior-id="{{ $dep->operadora_anterior_id }}"
                                            title="Editar">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        </button>
                                        <button type="button" class="btn-icon-sm btn-delete-dep" data-dependente-id="{{ $dep->id }}" data-nome="{{ $dep->nome }}" title="Excluir">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="dep-body">
                                    <div class="info-list info-list-compact">
                                        @if($dep->cpf)
                                        <div class="info-row">
                                            <span class="info-label">CPF</span>
                                            <span class="info-value">{{ $formatCpf($dep->cpf) }}</span>
                                        </div>
                                        @endif
                                        @if($dep->data_nascimento)
                                        <div class="info-row">
                                            <span class="info-label">Data Nasc.</span>
                                            <span class="info-value">{{ $dep->data_nascimento->format('d/m/Y') }} <span class="text-muted">({{ $calcularIdade($dep->data_nascimento) }} anos)</span></span>
                                        </div>
                                        @endif
                                        @if($dep->email)
                                        <div class="info-row">
                                            <span class="info-label">E-mail</span>
                                            <span class="info-value">{{ $dep->email }}</span>
                                        </div>
                                        @endif
                                        @if($dep->telefone1)
                                        <div class="info-row">
                                            <span class="info-label">Telefone</span>
                                            <span class="info-value">{{ $formatTelefone($dep->telefone1) }}@if($dep->telefone2), {{ $formatTelefone($dep->telefone2) }}@endif</span>
                                        </div>
                                        @endif
                                        @if($dep->plano)
                                        <div class="info-row">
                                            <span class="info-label">Plano</span>
                                            <span class="info-value info-highlight">{{ strtoupper($dep->plano->nome) }}@if($dep->plano->acomodacao) <span class="text-muted">| {{ $dep->plano->acomodacao }}</span>@endif</span>
                                        </div>
                                        @endif
                                        @if($dep->coparticipacao)
                                        <div class="info-row">
                                            <span class="info-label">Coparticipacao</span>
                                            <span class="info-value">{{ $coparticipacoes[$dep->coparticipacao] ?? $dep->coparticipacao }}</span>
                                        </div>
                                        @endif
                                        @if($dep->plano_anterior === 'SIM' && $dep->operadoraAnterior)
                                        <div class="info-row">
                                            <span class="info-label">Plano Anterior</span>
                                            <span class="info-value info-warning">{{ $dep->operadoraAnterior->nome }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="empty-titulares">
                        <p>Nenhum titular cadastrado</p>
                    </div>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- Seção de Acessos --}}
        <div class="pme-section pme-section-full">
            <div class="section-header">
                <span class="section-icon icon-info">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </span>
                <span class="section-title">Acessos da Empresa</span>
                <span class="section-count" id="acessos-count">0</span>
            </div>
            <div class="section-body">
                <div class="acessos-toolbar">
                    <button type="button" class="pme-btn pme-btn-sm pme-btn-outline" data-bs-toggle="modal" data-bs-target="#modalAddAcesso">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                        Adicionar Acesso
                    </button>
                </div>
                <div class="acessos-grid" id="acessos-grid"></div>
                <div class="acessos-empty" id="acessos-empty" style="display: none;">
                    <p>Nenhum acesso cadastrado</p>
                </div>
                <div class="acessos-loading" id="acessos-loading">
                    <div class="loading-spinner"></div>
                    <span>Carregando acessos...</span>
                </div>
            </div>
        </div>


    </div>

    {{-- Modal: Adicionar/Editar Acesso --}}
    <div class="modal fade" id="modalAddAcesso" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content pme-modal">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Acesso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form id="form-acesso">
                    <input type="hidden" name="venda_id" value="{{ $contract->id }}">
                    <input type="hidden" name="acesso_id" id="acesso_id" value="">
                    <div class="modal-body">
                        <div class="pme-field">
                            <label>E-mail <span class="required">*</span></label>
                            <input type="email" name="email" id="acesso_email" class="pme-input" required>
                        </div>
                        <div class="pme-field">
                            <label>Senha <span class="required">*</span></label>
                            <input type="text" name="senha" id="acesso_senha" class="pme-input" required>
                        </div>
                        <div class="pme-field">
                            <label>CPF <span class="optional">(opcional)</span></label>
                            <input type="text" name="cpf" id="acesso_cpf" class="pme-input mask-cpf">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="pme-btn pme-btn-outline" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="pme-btn pme-btn-primary" id="btn-save-acesso">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Confirmação de Exclusão --}}
    <div class="modal fade" id="modalDeleteAcesso" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content pme-modal">
                <div class="modal-body text-center">
                    <div class="delete-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </div>
                    <h5>Remover Acesso?</h5>
                    <p class="text-muted">Esta acao nao podera ser desfeita.</p>
                    <input type="hidden" id="delete_acesso_id" value="">
                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <button type="button" class="pme-btn pme-btn-outline" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="pme-btn pme-btn-danger" id="btn-confirm-delete">Remover</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Confirmação de Exclusão de Dependente --}}
    <div class="modal fade" id="modalDeleteDependente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content pme-modal">
                <div class="modal-body text-center">
                    <div class="delete-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </div>
                    <h5>Remover Dependente?</h5>
                    <p class="text-muted" id="delete-dependente-nome"></p>
                    <input type="hidden" id="delete_dependente_id" value="">
                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <button type="button" class="pme-btn pme-btn-outline" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="pme-btn pme-btn-danger" id="btn-confirm-delete-dependente">Remover</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Adicionar Titular --}}
    <div class="modal fade" id="modalAddTitularPME" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-add-titular-pme">
                <div class="add-titular-header">
                    <div class="add-titular-header-left">
                        <div class="add-titular-icon-wrapper">
                            <div class="add-titular-icon-ring"></div>
                            <div class="add-titular-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <line x1="19" y1="8" x2="19" y2="14"/>
                                    <line x1="22" y1="11" x2="16" y2="11"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h5 class="add-titular-title">Adicionar Titular</h5>
                            <span class="add-titular-subtitle">Cadastre um novo beneficiario ao contrato</span>
                        </div>
                    </div>
                    <button type="button" class="add-titular-close" data-bs-dismiss="modal" aria-label="Fechar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                        </svg>
                    </button>
                </div>
                <form id="form-add-titular-pme">
                    <input type="hidden" name="venda_id" value="{{ $contract->id }}">
                    <div class="add-titular-body">
                        {{-- Dados Pessoais --}}
                        <div class="add-titular-section">
                            <div class="add-titular-section-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                Dados Pessoais
                            </div>
                            <div class="pme-grid grid-2">
                                <div class="pme-field span-2">
                                    <label>Nome <span class="required">*</span></label>
                                    <input type="text" name="nome" id="add_titular_nome" class="pme-input" required>
                                </div>
                                <div class="pme-field">
                                    <label>CPF</label>
                                    <input type="text" name="cpf" id="add_titular_cpf" class="pme-input mask-cpf-modal">
                                </div>
                                <div class="pme-field">
                                    <label>Data Nascimento</label>
                                    <input type="text" name="data_nascimento" id="add_titular_data_nascimento" class="pme-input flatpickr-modal">
                                </div>
                            </div>
                        </div>

                        {{-- Contato --}}
                        <div class="add-titular-section">
                            <div class="add-titular-section-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                </svg>
                                Contato
                            </div>
                            <div class="pme-grid grid-2">
                                <div class="pme-field">
                                    <label>E-mail</label>
                                    <input type="email" name="email" id="add_titular_email" class="pme-input">
                                </div>
                                <div class="pme-field field-cargo-modal" @if(($contract->tipo_contrato ?? 'PME') === 'ADESAO') style="display:none;" @endif>
                                    <label>Cargo</label>
                                    <select name="cargo" id="add_titular_cargo" class="pme-input">
                                        <option value="">Selecione...</option>
                                        @foreach($cargos as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="pme-field">
                                    <label>Telefone</label>
                                    <input type="text" name="telefone" id="add_titular_telefone" class="pme-input mask-telefone-modal">
                                </div>
                                <div class="pme-field">
                                    <label>Telefone 2</label>
                                    <input type="text" name="telefone2" id="add_titular_telefone2" class="pme-input mask-telefone-modal">
                                </div>
                            </div>
                        </div>

                        {{-- Plano --}}
                        <div class="add-titular-section">
                            <div class="add-titular-section-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                    <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/>
                                </svg>
                                Plano
                            </div>
                            <div class="pme-grid grid-2">
                                <div class="pme-field">
                                    <label>Plano <span class="required">*</span></label>
                                    <select name="plano_id" id="add_titular_plano_id" class="pme-input" required>
                                        <option value="">Selecione...</option>
                                        @foreach ($planosDaOperadora as $p)
                                            <option value="{{ $p->id }}">{{ strtoupper($p->nome) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="pme-field">
                                    <label>Coparticipacao <span class="required">*</span></label>
                                    <select name="coparticipacao" id="add_titular_coparticipacao" class="pme-input" required>
                                        <option value="">Selecione...</option>
                                        @if($isAmil)
                                            <option value="PARCIAL">Parcial</option>
                                            <option value="COMPLETA">Completa</option>
                                        @else
                                            <option value="Y">Sim</option>
                                            <option value="N">Nao</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="pme-field">
                                    <label>Plano Anterior</label>
                                    <select name="plano_anterior" id="add_titular_plano_anterior" class="pme-input">
                                        <option value="NAO">Nao</option>
                                        <option value="SIM">Sim</option>
                                    </select>
                                </div>
                                <div class="pme-field" id="add_titular_operadora_anterior_container" style="display: none;">
                                    <label>Operadora Anterior</label>
                                    <select name="operadora_anterior_id" id="add_titular_operadora_anterior_id" class="pme-input">
                                        <option value="">Selecione...</option>
                                        @foreach ($operadoras ?? collect() as $op)
                                            <option value="{{ $op->id }}">{{ $op->nome }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="add-titular-footer">
                        <button type="button" class="btn-cancel-add-titular" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-save-add-titular" id="btn-submit-add-titular">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <line x1="19" y1="8" x2="19" y2="14"/>
                                <line x1="22" y1="11" x2="16" y2="11"/>
                            </svg>
                            Adicionar Titular
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Editar Titular --}}
    <div class="modal fade" id="modalEditTitular" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-edit-titular-pme">
                <div class="edit-titular-header">
                    <div class="edit-titular-header-left">
                        <div class="edit-titular-icon-wrapper">
                            <div class="edit-titular-icon-ring"></div>
                            <div class="edit-titular-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                    <path d="m15 5 4 4"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h5 class="edit-titular-title">Editar Titular</h5>
                            <span class="edit-titular-subtitle">Atualize os dados do beneficiario</span>
                        </div>
                    </div>
                    <button type="button" class="edit-titular-close" data-bs-dismiss="modal" aria-label="Fechar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                        </svg>
                    </button>
                </div>
                <form id="form-edit-titular">
                    <input type="hidden" name="titular_id" id="edit_titular_id" value="">
                    <input type="hidden" name="venda_id" value="{{ $contract->id }}">
                    <div class="edit-titular-body">
                        {{-- Dados Pessoais --}}
                        <div class="edit-titular-section">
                            <div class="edit-titular-section-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                Dados Pessoais
                            </div>
                            <div class="pme-grid grid-2">
                                <div class="pme-field span-2">
                                    <label>Nome <span class="required">*</span></label>
                                    <input type="text" name="nome" id="edit_titular_nome" class="pme-input" required>
                                </div>
                                <div class="pme-field">
                                    <label>CPF</label>
                                    <input type="text" name="cpf" id="edit_titular_cpf" class="pme-input mask-cpf-modal">
                                </div>
                                <div class="pme-field">
                                    <label>Data Nascimento</label>
                                    <input type="text" name="data_nascimento" id="edit_titular_data_nascimento" class="pme-input flatpickr-modal">
                                </div>
                            </div>
                        </div>

                        {{-- Contato --}}
                        <div class="edit-titular-section">
                            <div class="edit-titular-section-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                </svg>
                                Contato
                            </div>
                            <div class="pme-grid grid-2">
                                <div class="pme-field">
                                    <label>E-mail</label>
                                    <input type="email" name="email" id="edit_titular_email" class="pme-input">
                                </div>
                                <div class="pme-field field-cargo-modal" @if(($contract->tipo_contrato ?? 'PME') === 'ADESAO') style="display:none;" @endif>
                                    <label>Cargo</label>
                                    <select name="cargo" id="edit_titular_cargo" class="pme-input">
                                        <option value="">Selecione...</option>
                                        @foreach($cargos as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="pme-field">
                                    <label>Telefone</label>
                                    <input type="text" name="telefone" id="edit_titular_telefone" class="pme-input mask-telefone-modal">
                                </div>
                                <div class="pme-field">
                                    <label>Telefone 2</label>
                                    <input type="text" name="telefone2" id="edit_titular_telefone2" class="pme-input mask-telefone-modal">
                                </div>
                            </div>
                        </div>

                        {{-- Plano --}}
                        <div class="edit-titular-section">
                            <div class="edit-titular-section-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                    <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/>
                                </svg>
                                Plano
                            </div>
                            <div class="pme-grid grid-2">
                                <div class="pme-field">
                                    <label>Plano <span class="required">*</span></label>
                                    <select name="plano_id" id="edit_titular_plano_id" class="pme-input" required>
                                        <option value="">Selecione...</option>
                                        @foreach ($planosDaOperadora as $p)
                                            <option value="{{ $p->id }}">{{ strtoupper($p->nome) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="pme-field">
                                    <label>Coparticipacao</label>
                                    <select name="coparticipacao" id="edit_titular_coparticipacao" class="pme-input">
                                        @if($isAmil)
                                            <option value="PARCIAL">Parcial</option>
                                            <option value="COMPLETA">Completa</option>
                                        @else
                                            <option value="Y">Sim</option>
                                            <option value="N">Nao</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="pme-field">
                                    <label>Plano Anterior</label>
                                    <select name="plano_anterior" id="edit_titular_plano_anterior" class="pme-input">
                                        <option value="NAO">Nao</option>
                                        <option value="SIM">Sim</option>
                                    </select>
                                </div>
                                <div class="pme-field" id="edit_titular_operadora_anterior_container" style="display: none;">
                                    <label>Operadora Anterior</label>
                                    <select name="operadora_anterior_id" id="edit_titular_operadora_anterior_id" class="pme-input">
                                        <option value="">Selecione...</option>
                                        @foreach ($operadoras ?? collect() as $op)
                                            <option value="{{ $op->id }}">{{ $op->nome }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="edit-titular-footer">
                        <button type="button" class="btn-cancel-edit-titular" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-save-edit-titular" id="btn-save-titular">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Salvar Alteracoes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Adicionar/Editar Dependente --}}
    <div class="modal fade" id="modalEditDependente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-dependente-pme">
                <div class="dependente-modal-header">
                    <div class="dependente-modal-header-left">
                        <div class="dependente-modal-icon-wrapper">
                            <div class="dependente-modal-icon-ring"></div>
                            <div class="dependente-modal-icon" id="dependenteModalIcon">
                                {{-- Icone padrao: adicionar (user-plus) --}}
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-add-dep">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <line x1="19" y1="8" x2="19" y2="14"/>
                                    <line x1="22" y1="11" x2="16" y2="11"/>
                                </svg>
                                {{-- Icone editar (pencil) --}}
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-edit-dep" style="display:none;">
                                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                    <path d="m15 5 4 4"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h5 class="dependente-modal-title" id="modalDependenteTitle">Adicionar Dependente</h5>
                            <span class="dependente-modal-subtitle" id="modalDependenteSubtitle">Cadastre um novo dependente ao titular</span>
                        </div>
                    </div>
                    <button type="button" class="dependente-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                        </svg>
                    </button>
                </div>
                <form id="form-edit-dependente">
                    <input type="hidden" name="dependente_id" id="edit_dependente_id" value="">
                    <input type="hidden" name="titular_id" id="edit_dependente_titular_id" value="">
                    <input type="hidden" name="venda_id" value="{{ $contract->id }}">
                    <div class="dependente-modal-body">
                        {{-- Dados Pessoais --}}
                        <div class="dependente-modal-section">
                            <div class="dependente-modal-section-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                Dados Pessoais
                            </div>
                            <div class="pme-grid grid-2">
                                <div class="pme-field span-2">
                                    <label>Nome <span class="required">*</span></label>
                                    <input type="text" name="nome" id="edit_dependente_nome" class="pme-input" required>
                                </div>
                                <div class="pme-field">
                                    <label>CPF</label>
                                    <input type="text" name="cpf" id="edit_dependente_cpf" class="pme-input mask-cpf-modal">
                                </div>
                                <div class="pme-field">
                                    <label>Data Nascimento</label>
                                    <input type="text" name="data_nascimento" id="edit_dependente_data_nascimento" class="pme-input flatpickr-modal">
                                </div>
                            </div>
                        </div>

                        {{-- Contato & Vinculo --}}
                        <div class="dependente-modal-section">
                            <div class="dependente-modal-section-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                </svg>
                                Contato & Vinculo
                            </div>
                            <div class="pme-grid grid-2">
                                <div class="pme-field">
                                    <label>E-mail</label>
                                    <input type="email" name="email" id="edit_dependente_email" class="pme-input">
                                </div>
                                <div class="pme-field">
                                    <label>Parentesco</label>
                                    <select name="parentesco" id="edit_dependente_parentesco" class="pme-input">
                                        <option value="">Selecione...</option>
                                        @foreach($parentescos as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="pme-field">
                                    <label>Telefone 1</label>
                                    <input type="text" name="telefone1" id="edit_dependente_telefone1" class="pme-input mask-telefone-modal">
                                </div>
                                <div class="pme-field">
                                    <label>Telefone 2</label>
                                    <input type="text" name="telefone2" id="edit_dependente_telefone2" class="pme-input mask-telefone-modal">
                                </div>
                            </div>
                        </div>

                        {{-- Plano --}}
                        <div class="dependente-modal-section">
                            <div class="dependente-modal-section-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                    <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/>
                                </svg>
                                Plano
                            </div>
                            <div class="pme-grid grid-2">
                                <div class="pme-field">
                                    <label>Plano</label>
                                    <select name="plano_id" id="edit_dependente_plano_id" class="pme-input">
                                        <option value="">Mesmo do titular</option>
                                        @foreach ($planosDaOperadora as $p)
                                            <option value="{{ $p->id }}">{{ strtoupper($p->nome) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="pme-field">
                                    <label>Coparticipacao</label>
                                    <select name="coparticipacao" id="edit_dependente_coparticipacao" class="pme-input">
                                        <option value="">Mesmo do titular</option>
                                        @if($isAmil)
                                            <option value="PARCIAL">Parcial</option>
                                            <option value="COMPLETA">Completa</option>
                                        @else
                                            <option value="Y">Sim</option>
                                            <option value="N">Nao</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="pme-field">
                                    <label>Plano Anterior</label>
                                    <select name="plano_anterior" id="edit_dependente_plano_anterior" class="pme-input">
                                        <option value="NAO">Nao</option>
                                        <option value="SIM">Sim</option>
                                    </select>
                                </div>
                                <div class="pme-field" id="edit_dependente_operadora_anterior_container" style="display: none;">
                                    <label>Operadora Anterior</label>
                                    <select name="operadora_anterior_id" id="edit_dependente_operadora_anterior_id" class="pme-input">
                                        <option value="">Selecione...</option>
                                        @foreach ($operadoras ?? collect() as $op)
                                            <option value="{{ $op->id }}">{{ $op->nome }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="dependente-modal-footer">
                        <button type="button" class="btn-cancel-dependente" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-save-dependente" id="btn-save-dependente">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span id="btnSaveDependenteText">Adicionar</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Adicionar/Editar Portabilidade --}}
    <div class="modal fade" id="modalEditPortabilidade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content pme-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPortabilidadeTitle">Adicionar Beneficiario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form id="form-edit-portabilidade">
                    <input type="hidden" name="portabilidade_id" id="edit_port_id" value="">
                    <input type="hidden" name="venda_id" value="{{ $contract->id }}">
                    <div class="modal-body">
                        <div class="pme-grid grid-2">
                            <div class="pme-field span-2">
                                <label>Nome <span class="required">*</span></label>
                                <input type="text" name="nome" id="edit_port_nome" class="pme-input" required>
                            </div>
                            <div class="pme-field">
                                <label>CPF</label>
                                <input type="text" name="cpf" id="edit_port_cpf" class="pme-input mask-cpf-modal">
                            </div>
                            <div class="pme-field">
                                <label>Data Nascimento</label>
                                <input type="text" name="data_nascimento" id="edit_port_data_nascimento" class="pme-input flatpickr-modal">
                            </div>
                            <div class="pme-field">
                                <label>Operadora Anterior <span class="required">*</span></label>
                                <select name="operadora_anterior_id" id="edit_port_operadora_anterior_id" class="pme-input" required>
                                    <option value="">Selecione...</option>
                                    @foreach ($operadoras ?? collect() as $op)
                                        <option value="{{ $op->id }}">{{ $op->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="pme-field">
                                <label>Plano Anterior</label>
                                <input type="text" name="plano_anterior" id="edit_port_plano_anterior" class="pme-input" placeholder="Nome do plano anterior">
                            </div>
                            <div class="pme-field span-2">
                                <label>Numero da Carteirinha</label>
                                <input type="text" name="numero_carteirinha" id="edit_port_numero_carteirinha" class="pme-input">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="pme-btn pme-btn-outline" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="pme-btn pme-btn-primary" id="btn-save-portabilidade">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Confirmação de Exclusão de Portabilidade --}}
    <div class="modal fade" id="modalDeletePortabilidade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content pme-modal">
                <div class="modal-body text-center">
                    <div class="delete-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </div>
                    <h5>Remover Beneficiario?</h5>
                    <p class="text-muted" id="delete-port-nome"></p>
                    <input type="hidden" id="delete_port_id" value="">
                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <button type="button" class="pme-btn pme-btn-outline" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="pme-btn pme-btn-danger" id="btn-confirm-delete-port">Remover</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Excluir Titular --}}
    <div class="modal fade" id="modalDeleteTitular" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content modal-delete-titular-pme">
                <div class="modal-body text-center">
                    <div class="delete-titular-icon-wrapper">
                        <div class="delete-titular-icon-ring"></div>
                        <div class="delete-titular-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <line x1="17" y1="11" x2="23" y2="11"/>
                            </svg>
                        </div>
                    </div>
                    <h5 class="delete-titular-title">Remover Titular?</h5>
                    <p class="delete-titular-text" id="delete-titular-text">O titular e todos os seus dependentes serão removidos.</p>
                    <div class="delete-titular-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <span>Esta ação não poderá ser desfeita</span>
                    </div>
                    <input type="hidden" id="delete_titular_id" value="">
                    <div class="delete-titular-actions">
                        <button type="button" class="btn-cancel-titular" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn-confirm-delete-titular" id="btn-confirm-delete-titular">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            Remover Titular
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
