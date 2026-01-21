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

    <div class="pme-contract-wrapper">

        {{-- Header --}}
        <div class="pme-header">
            <div class="pme-header-left">
                <a href="{{ route('backoffice.index') }}" class="btn-back">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                </a>
                <div class="pme-header-info">
                    <span class="header-badge">PME</span>
                    <h1 class="page-title">{{ $contract->nome_contrato }}</h1>
                    <span class="page-subtitle">CNPJ: {{ $contract->cpf_cnpj }} | Contrato #{{ $contract->id }}</span>
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
                        <form method="POST" action="{{ route('backoffice.updateSale') }}" id="form-empresa">
                            @csrf
                            <input type="hidden" name="id" value="{{ $contract->id }}">

                            <div class="pme-grid grid-2">
                                <div class="pme-field span-2">
                                    <label>Razao Social</label>
                                    <input type="text" name="nome_contrato" class="pme-input" value="{{ $contract->nome_contrato }}">
                                </div>
                                <div class="pme-field">
                                    <label>CNPJ</label>
                                    <input type="text" name="cpf_cnpj" class="pme-input mask-cnpj" value="{{ $contract->cpf_cnpj }}">
                                </div>
                                <div class="pme-field">
                                    <label>Tipo Empresa</label>
                                    <select name="tipo_empresa" class="pme-input">
                                        <option value="">Selecione...</option>
                                        @foreach($tiposEmpresa as $key => $label)
                                            <option value="{{ $key }}" {{ $contract->tipo_empresa === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="pme-field">
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
                            </div>
                        </div>

                        {{-- Lista de Beneficiarios Portabilidade (inline) --}}
                        @if($portabilidades->count() > 0)
                        <div class="portabilidades-inline">
                            <div class="port-inline-title">Beneficiarios da Portabilidade</div>
                            <div class="port-inline-list">
                                @foreach($portabilidades as $port)
                                <div class="port-inline-item">
                                    <span class="port-inline-num">{{ $port->sequencial }}</span>
                                    <span class="port-inline-nome">{{ $port->nome }}</span>
                                    @if($port->operadoraAnterior)
                                        <span class="port-inline-op">De: {{ $port->operadoraAnterior->nome }}</span>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Observacoes --}}
                        <div class="pme-field" style="margin-top: 0.75rem;">
                            <label>Observacoes</label>
                            <textarea name="obs_contrato" class="pme-input" rows="2" form="form-empresa">{{ $contract->obs_contrato }}</textarea>
                        </div>

                        {{-- Botao Salvar --}}
                        <div class="pme-actions">
                            <button type="submit" form="form-empresa" class="pme-btn pme-btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                Atualizar Contrato
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Coluna Direita - Titulares e Dependentes --}}
            <div class="pme-col-right">
                <div class="titulares-header">
                    <div class="titulares-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <span>Titulares e Dependentes</span>
                    </div>
                </div>

                <div class="titulares-scroll">
                    @forelse($titulares as $i => $titular)
                    <div class="titular-card">
                        <div class="titular-header">
                            <div class="titular-badge">
                                <span class="badge-number">{{ $i + 1 }}</span>
                                <span class="badge-text">Titular</span>
                            </div>
                            <div class="titular-meta">
                                @if($titular->cargo)
                                    <span class="meta-cargo">{{ $cargos[$titular->cargo] ?? $titular->cargo }}</span>
                                @endif
                                @if($titular->plano_anterior === 'SIM')
                                    <span class="meta-portabilidade">Portabilidade</span>
                                @endif
                            </div>
                        </div>
                        <div class="titular-body">
                            <div class="titular-info">
                                <div class="info-row">
                                    <span class="info-label">Nome</span>
                                    <span class="info-value">{{ $titular->nome }}</span>
                                </div>
                                @if($titular->cpf)
                                <div class="info-row">
                                    <span class="info-label">CPF</span>
                                    <span class="info-value">{{ $titular->cpf }}</span>
                                </div>
                                @endif
                                @if($titular->data_nascimento)
                                <div class="info-row">
                                    <span class="info-label">Nascimento</span>
                                    <span class="info-value">{{ $titular->data_nascimento->format('d/m/Y') }}</span>
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
                                    <span class="info-value">{{ $titular->telefone }}@if($titular->telefone2) / {{ $titular->telefone2 }}@endif</span>
                                </div>
                                @endif
                                @if($titular->plano)
                                <div class="info-row">
                                    <span class="info-label">Plano</span>
                                    <span class="info-value info-plano">{{ strtoupper($titular->plano->nome) }}</span>
                                </div>
                                @endif
                                @if($titular->plano_anterior === 'SIM' && $titular->operadoraAnterior)
                                <div class="info-row">
                                    <span class="info-label">Op. Anterior</span>
                                    <span class="info-value info-operadora">{{ $titular->operadoraAnterior->nome }}</span>
                                </div>
                                @endif
                            </div>
                            <button type="button" class="btn-edit-titular" data-titular-id="{{ $titular->id }}"
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
                                data-operadora-anterior-id="{{ $titular->operadora_anterior_id }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                <span>Editar</span>
                            </button>
                        </div>

                        {{-- Dependentes do Titular --}}
                        @php
                            $depsTitular = $dependentes->where('titular_id', $titular->id);
                        @endphp
                        @if($depsTitular->count() > 0)
                        <div class="dependentes-container">
                            <div class="dependentes-header">
                                <span class="dep-title">Dependentes ({{ $depsTitular->count() }})</span>
                            </div>
                            @foreach($depsTitular as $j => $dep)
                            <div class="dependente-card">
                                <div class="dependente-header">
                                    <span class="dep-badge">Dep. {{ $j + 1 }}</span>
                                    @if($dep->parentesco)
                                        <span class="dep-parentesco">{{ $parentescos[$dep->parentesco] ?? $dep->parentesco }}</span>
                                    @endif
                                    @if($dep->plano_anterior === 'SIM')
                                        <span class="dep-port">Port.</span>
                                    @endif
                                </div>
                                <div class="dependente-body">
                                    <div class="dep-info">
                                        <span class="dep-nome">{{ $dep->nome }}</span>
                                        @if($dep->cpf)
                                            <span class="dep-cpf">CPF: {{ $dep->cpf }}</span>
                                        @endif
                                        @if($dep->data_nascimento)
                                            <span class="dep-nascimento">Nasc: {{ $dep->data_nascimento->format('d/m/Y') }}</span>
                                        @endif
                                        @if($dep->email)
                                            <span class="dep-email">{{ $dep->email }}</span>
                                        @endif
                                        @if($dep->telefone1)
                                            <span class="dep-telefone">{{ $dep->telefone1 }}@if($dep->telefone2) / {{ $dep->telefone2 }}@endif</span>
                                        @endif
                                    </div>
                                    @if($dep->plano_anterior === 'SIM' && $dep->operadoraAnterior)
                                        <div class="dep-operadora-anterior">
                                            <span>De: {{ $dep->operadoraAnterior->nome }}</span>
                                        </div>
                                    @endif
                                    <button type="button" class="btn-edit-dependente" data-dependente-id="{{ $dep->id }}"
                                        data-nome="{{ $dep->nome }}"
                                        data-cpf="{{ $dep->cpf }}"
                                        data-data-nascimento="{{ $dep->data_nascimento ? $dep->data_nascimento->format('d/m/Y') : '' }}"
                                        data-email="{{ $dep->email }}"
                                        data-telefone1="{{ $dep->telefone1 }}"
                                        data-telefone2="{{ $dep->telefone2 }}"
                                        data-parentesco="{{ $dep->parentesco }}"
                                        data-plano-anterior="{{ $dep->plano_anterior }}"
                                        data-operadora-anterior-id="{{ $dep->operadora_anterior_id }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
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

    {{-- Modal: Editar Titular --}}
    <div class="modal fade" id="modalEditTitular" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content pme-modal">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Titular</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form id="form-edit-titular">
                    <input type="hidden" name="titular_id" id="edit_titular_id" value="">
                    <input type="hidden" name="venda_id" value="{{ $contract->id }}">
                    <div class="modal-body">
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
                            <div class="pme-field">
                                <label>E-mail</label>
                                <input type="email" name="email" id="edit_titular_email" class="pme-input">
                            </div>
                            <div class="pme-field">
                                <label>Telefone</label>
                                <input type="text" name="telefone" id="edit_titular_telefone" class="pme-input mask-telefone-modal">
                            </div>
                            <div class="pme-field">
                                <label>Telefone 2</label>
                                <input type="text" name="telefone2" id="edit_titular_telefone2" class="pme-input mask-telefone-modal">
                            </div>
                            <div class="pme-field">
                                <label>Cargo</label>
                                <select name="cargo" id="edit_titular_cargo" class="pme-input">
                                    <option value="">Selecione...</option>
                                    @foreach($cargos as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
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
                                    <option value="SIM">Sim (Portabilidade)</option>
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
                    <div class="modal-footer">
                        <button type="button" class="pme-btn pme-btn-outline" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="pme-btn pme-btn-primary" id="btn-save-titular">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Editar Dependente --}}
    <div class="modal fade" id="modalEditDependente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content pme-modal">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Dependente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form id="form-edit-dependente">
                    <input type="hidden" name="dependente_id" id="edit_dependente_id" value="">
                    <input type="hidden" name="venda_id" value="{{ $contract->id }}">
                    <div class="modal-body">
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
                            <div class="pme-field">
                                <label>E-mail</label>
                                <input type="email" name="email" id="edit_dependente_email" class="pme-input">
                            </div>
                            <div class="pme-field">
                                <label>Telefone 1</label>
                                <input type="text" name="telefone1" id="edit_dependente_telefone1" class="pme-input mask-telefone-modal">
                            </div>
                            <div class="pme-field">
                                <label>Telefone 2</label>
                                <input type="text" name="telefone2" id="edit_dependente_telefone2" class="pme-input mask-telefone-modal">
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
                                <label>Plano Anterior</label>
                                <select name="plano_anterior" id="edit_dependente_plano_anterior" class="pme-input">
                                    <option value="NAO">Nao</option>
                                    <option value="SIM">Sim (Portabilidade)</option>
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
                    <div class="modal-footer">
                        <button type="button" class="pme-btn pme-btn-outline" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="pme-btn pme-btn-primary" id="btn-save-dependente">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
