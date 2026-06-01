@extends('layouts/layoutMaster')

@section('title', 'Gestão de contrato')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/scss/pages/backoffice-contract.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/openContract.js'])
@endsection

@section('content')
    @php
        // resolve operadora selecionada por ID
        $selectedOperadoraId =
            $selectedOperadoraId ?? optional(($operadoras ?? collect())->firstWhere('nome', $contract->operadora))->id;

        $planosDaOperadora = $planosDaOperadora ?? collect();

        // AMIL: considera qualquer variação que contenha "AMIL"
        $isAmil = false;
        if ($selectedOperadoraId) {
            $nomeOpSel = optional(($operadoras ?? collect())->firstWhere('id', $selectedOperadoraId))->nome;
            $isAmil = stripos((string) $nomeOpSel, 'AMIL') !== false;
        } else {
            $isAmil = stripos((string) $contract->operadora, 'AMIL') !== false;
        }
    @endphp

    {{-- Flash de sucesso/erro --}}
    @if (session('status') && session('message'))
        <div class="alert alert-{{ session('status') === 'success' ? 'success' : 'danger' }} alert-dismissible fade show"
            role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    {{-- Validação: lista de erros --}}
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
    <div class="responsavel-card">
        <div class="responsavel-left">
            <div class="responsavel-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

    <div class="row g-4 contract-page">

        {{-- COLUNA ESQUERDA: Apólice + Empresa --}}
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header">
                    <h5>
                        <span class="header-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                        </span>
                        Dados da Apólice
                    </h5>
                </div>


                <div class="card-body mt-5">
                    <form method="POST" action="{{ route('backoffice.updateSale') }}" class="row g-3" id="form-contrato" @if (!($canEdit ?? true)) data-readonly="true" @endif>
                        @csrf
                        <input type="hidden" name="id" value="{{ $contract->id }}">

                        {{-- Operadora --}}
                        <div class="col-12 mt-2">
                            <label class="form-label">Operadora</label>
                            <select id="operadoraSelect" class="form-select" name="operadora" required>
                                <option value="">Selecione</option>
                                @foreach ($operadoras ?? collect() as $op)
                                    <option value="{{ $op->id }}" data-nome="{{ strtoupper($op->nome) }}"
                                        {{ (int) $selectedOperadoraId === (int) $op->id ? 'selected' : '' }}>
                                        {{ $op->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Plano base --}}
                        <div class="col-12">
                            <label class="form-label">Plano (base)</label>
                            <select id="planoSelect" class="form-select" name="plano_id">
                                @if (($planosDaOperadora ?? collect())->count())
                                    <option value="">Selecione...</option>
                                    @foreach ($planosDaOperadora as $p)
                                        <option value="{{ $p->id }}" data-acomodacao="{{ $p->acomodacao }}"
                                            {{ (int) $contract->plano_id === (int) $p->id ? 'selected' : '' }}>
                                            {{ strtoupper($p->nome) }}
                                        </option>
                                    @endforeach
                                @else
                                    @if ($contract->plano_id && $contract->nome_plano)
                                        <option value="{{ $contract->plano_id }}" selected>{{ $contract->nome_plano }}
                                        </option>
                                    @else
                                        <option value="">Selecione a operadora primeiro</option>
                                    @endif
                                @endif
                            </select>
                        </div>

                        {{-- Acomodação base (informativa) --}}
                        <div class="col-md-6">
                            <label class="form-label">Acomodação</label>
                            <input type="text" id="acomodacao" class="form-control" name="acomodacao"
                                value="{{ optional(($planosDaOperadora ?? collect())->firstWhere('id', $contract->plano_id))->acomodacao ?? ($plano->acomodacao ?? '') }}"
                                disabled>
                        </div>

                        <div class="col-12">
                            <hr class="my-2">
                            <h6 class="mb-2">Dados da empresa (contrato)</h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nome da Empresa</label>
                            <input type="text" class="form-control" name="nome_contrato"
                                value="{{ $contract->nome_contrato }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CNPJ</label>
                            <input type="text" id="cpf_cnpj" class="form-control" name="cpf_cnpj"
                                value="{{ $contract->cpf_cnpj }}">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">E-mail (empresa)</label>
                            <input type="email" class="form-control" name="email" value="{{ $contract->email }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Telefone 1</label>
                            <input type="text" class="form-control mask-telefone" name="telefone1"
                                value="{{ $contract->telefone1 }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Telefone 2</label>
                            <input type="text" class="form-control mask-telefone" name="telefone2"
                                value="{{ $contract->telefone2 }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Valor do Contrato</label>
                            <input type="text" class="form-control monetary-field" name="valor_contrato"
                                value="{{ number_format((float) $contract->valor_contrato, 2, ',', '.') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Número de Vidas</label>
                            <input type="number" class="form-control" name="vidas"
                                value="{{ (int) $contract->vidas }}">
                        </div>

                        {{-- Seção de Angariação Destacada --}}
                        <div class="col-12">
                            <div class="angariacao-section">
                                <div class="angariacao-header">
                                    <div class="angariacao-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="12" y1="1" x2="12" y2="23"></line>
                                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                        </svg>
                                    </div>
                                    <span class="angariacao-title">Comissão de Angariação</span>
                                    <span class="angariacao-badge {{ ($contract->angariacao_status ?? 'NAO') === 'SIM' ? 'badge-sim' : 'badge-nao' }}" id="angariacao-badge">
                                        {{ ($contract->angariacao_status ?? 'NAO') === 'SIM' ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </div>
                                <div class="angariacao-fields">
                                    <div class="field-group">
                                        <label class="field-label">
                                            <span class="field-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                                </svg>
                                            </span>
                                            Taxa de Angariação
                                        </label>
                                        <input type="text" class="form-control monetary-field" name="angariacao_valor"
                                            value="{{ number_format((float) $contract->angariacao_valor, 2, ',', '.') }}"
                                            placeholder="R$ 0,00">
                                    </div>
                                    <div class="field-group status-select">
                                        <label class="field-label">
                                            <span class="field-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                                </svg>
                                            </span>
                                            Aplicar Angariação?
                                        </label>
                                        <span class="status-indicator {{ ($contract->angariacao_status ?? 'NAO') === 'SIM' ? 'indicator-sim' : 'indicator-nao' }}" id="status-indicator"></span>
                                        <select id="angariacao_status" name="angariacao_status"
                                            class="form-select {{ ($contract->angariacao_status ?? 'NAO') === 'SIM' ? 'status-sim' : 'status-nao' }}">
                                            <option value="NAO" {{ ($contract->angariacao_status ?? 'NAO') === 'NAO' ? 'selected' : '' }}>NÃO</option>
                                            <option value="SIM" {{ ($contract->angariacao_status ?? '') === 'SIM' ? 'selected' : '' }}>SIM</option>
                                        </select>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label">
                                            <span class="field-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                                </svg>
                                            </span>
                                            Tipo de Comissão
                                        </label>
                                        <input type="text" class="form-control" value="Regra Supermed / Administrativa" readonly disabled>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="obs_contrato" rows="3">{{ $contract->obs_contrato }}</textarea>
                        </div>

                        <div class="d-flex mt-3">
                            @if ($canEdit ?? true)
                            <button class="btn btn-success ms-auto px-4">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                Atualizar Contrato
                            </button>
                            @else
                            <div class="alert alert-warning mt-3 mb-0 ms-auto">
                                <small>Somente o backoffice responsavel pode editar este contrato.</small>
                            </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- COLUNA DIREITA: Titulares --}}
        <div class="col-12 col-xl-7">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Titulares da Apólice</h5>
                    <small class="text-muted">Atualize os dados de cada titular individualmente.</small>
                </div>



                <div class="card-body">
                    <div class="mb-0 mt-4 d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                            data-bs-target="#modalAddTitular">
                            ➕ Adicionar Titular
                        </button>
                    </div>

                    @forelse(($titulares ?? $contract->titulares ?? collect()) as $i => $titular)
                        <form class="row g-3 align-items-end border rounded p-3 mb-3 form-titular-update mt-4"
                            action="{{ route('backoffice.titulares.update', $titular->id) }}" method="POST"
                            data-titular-id="{{ $titular->id }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="venda_id" value="{{ $contract->id }}" />

                            <div class="col-12">
                                <h6 class="mb-0">Titular #{{ $i + 1 }}</h6>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nome</label>
                                <input type="text" class="form-control" name="nome" value="{{ $titular->nome }}"
                                    required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">E-mail</label>
                                <input type="email" class="form-control" name="email"
                                    value="{{ $titular->email }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control mask-telefone" name="telefone"
                                    value="{{ $titular->telefone }}">
                            </div>

                            @if($titular->data_nascimento)
                            <div class="col-md-4">
                                <label class="form-label">Data Nasc.</label>
                                <input type="text" class="form-control" value="{{ $titular->data_nascimento->format('d/m/Y') }} ({{ $titular->data_nascimento->age }} anos)" disabled>
                            </div>
                            @endif

                            {{-- Plano por titular (limitado à operadora base) --}}
                            <div class="col-md-4">
                                <label class="form-label">Plano</label>
                                <select class="form-select select-plano-titular" name="plano_id"
                                    data-plano-selecionado="{{ $titular->plano_id ?? '' }}" required>
                                    @if (($planosDaOperadora ?? collect())->count())
                                        <option value="">Selecione...</option>
                                        @foreach ($planosDaOperadora as $p)
                                            <option value="{{ $p->id }}" data-acomodacao="{{ $p->acomodacao }}"
                                                {{ (int) $titular->plano_id === (int) $p->id ? 'selected' : '' }}>
                                                {{ strtoupper($p->nome) }}
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="">Carregando...</option>
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Acomodação</label>
                                <input type="text" class="form-control input-acomodacao"
                                    value="{{ optional($titular->plano)->acomodacao ?? '' }}" placeholder="Acomodação"
                                    readonly>
                            </div>

                            {{-- Coparticipação por titular (AMIL => PARCIAL/COMPLETA; demais => SIM/NÃO) --}}
                            <div class="col-md-4">
                                <label
                                    class="form-label label-coparticipacao">{{ $isAmil ? 'Coparticipação (Amil)' : 'Coparticipação' }}</label>
                                <select class="form-select select-coparticipacao" name="coparticipacao"
                                    data-current="{{ strtoupper((string) $titular->coparticipacao) }}" required>
                                    @if ($isAmil)
                                        <option value="">Selecione...</option>
                                        <option value="PARCIAL"
                                            {{ strtoupper((string) $titular->coparticipacao) === 'PARCIAL' ? 'selected' : '' }}>
                                            PARCIAL</option>
                                        <option value="COMPLETA"
                                            {{ strtoupper((string) $titular->coparticipacao) === 'COMPLETA' ? 'selected' : '' }}>
                                            COMPLETA</option>
                                    @else
                                        <option value="">Selecione...</option>
                                        <option value="Y"
                                            {{ strtoupper((string) $titular->coparticipacao) === 'Y' ? 'selected' : '' }}>
                                            SIM</option>
                                        <option value="N"
                                            {{ strtoupper((string) $titular->coparticipacao) === 'N' ? 'selected' : '' }}>
                                            NÃO</option>
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-8 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Salvar titular</button>
                            </div>
                        </form>

                        {{-- Dependentes do Titular --}}
                        <div class="dependentes-wrap border rounded p-3 mb-3" id="dependentes-titular-{{ $titular->id }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold text-muted" style="font-size: .8rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="9" cy="7" r="4"/>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                    </svg>
                                    Dependentes
                                    <span class="badge bg-label-secondary ms-1 dep-count-badge" data-titular-id="{{ $titular->id }}">{{ $titular->dependentes ? $titular->dependentes->count() : 0 }}</span>
                                </span>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-xs btn-outline-primary btn-add-dependente"
                                        data-titular-id="{{ $titular->id }}" data-venda-id="{{ $contract->id }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        Adicionar
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-danger btn-delete-titular"
                                        data-titular-id="{{ $titular->id }}" data-titular-nome="{{ $titular->nome }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        Excluir titular
                                    </button>
                                </div>
                            </div>

                            <div class="dependentes-list" data-titular-id="{{ $titular->id }}">
                                @if($titular->dependentes && $titular->dependentes->count())
                                    @foreach($titular->dependentes as $dep)
                                        <div class="dep-item d-flex align-items-center justify-content-between py-2 px-3 mb-1 rounded" data-dep-id="{{ $dep->id }}">
                                            <div class="dep-info">
                                                <span class="fw-semibold">{{ $dep->nome }}</span>
                                                @if($dep->parentesco)
                                                    <span class="badge bg-label-info ms-1" style="font-size: .65rem;">{{ $dep->parentesco }}</span>
                                                @endif
                                                @if($dep->cpf)
                                                    <span class="text-muted ms-2" style="font-size: .78rem;">CPF: {{ $dep->cpf }}</span>
                                                @endif
                                                @if($dep->data_nascimento)
                                                    <span class="text-muted ms-2" style="font-size: .78rem;">Nasc: {{ \Carbon\Carbon::parse($dep->data_nascimento)->format('d/m/Y') }}</span>
                                                @endif
                                            </div>
                                            @if ($canEdit ?? true)
                                            <button type="button" class="btn btn-xs btn-outline-danger btn-delete-dependente" data-dep-id="{{ $dep->id }}" title="Excluir dependente">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            </button>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-muted mb-0" style="font-size: .78rem;">Nenhum dependente cadastrado.</p>
                                @endif
                            </div>
                        </div>
                    @empty

                    @endforelse

                    {{-- Modal: Adicionar Titular (planos seguem a operadora base) --}}
                    <div class="modal fade" id="modalAddTitular" tabindex="-1" aria-labelledby="modalAddTitularLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <form method="POST" action="{{ route('backoffice.titulares.store') }}"
                                class="modal-content">
                                @csrf
                                <input type="hidden" name="venda_id" value="{{ $contract->id }}">

                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalAddTitularLabel">Cadastrar Titular</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Fechar"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nome</label>
                                            <input type="text" name="nome" class="form-control" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">E-mail</label>
                                            <input type="email" name="email" class="form-control">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Telefone</label>
                                            <input type="text" name="telefone" class="form-control mask-telefone">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Plano</label>
                                            <select name="plano_id" class="form-select select-plano-modal" required>
                                                @if (($planosDaOperadora ?? collect())->count())
                                                    <option value="">Selecione...</option>
                                                    @foreach ($planosDaOperadora as $p)
                                                        <option value="{{ $p->id }}"
                                                            data-acomodacao="{{ $p->acomodacao }}">
                                                            {{ strtoupper($p->nome) }}
                                                        </option>
                                                    @endforeach
                                                @else
                                                    <option value="">Carregando...</option> {{-- JS preencherá ao abrir a modal --}}
                                                @endif
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label
                                                class="form-label label-coparticipacao-modal">{{ $isAmil ? 'Coparticipação (Amil)' : 'Coparticipação' }}</label>
                                            <select name="coparticipacao" class="form-select select-coparticipacao-modal"
                                                required>
                                                @if ($isAmil)
                                                    <option value="">Selecione...</option>
                                                    <option value="PARCIAL">PARCIAL</option>
                                                    <option value="COMPLETA">COMPLETA</option>
                                                @else
                                                    <option value="">Selecione...</option>
                                                    <option value="Y">SIM</option>
                                                    <option value="N">NÃO</option>
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-success">Salvar Titular</button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- SEÇÃO: Acessos da Empresa --}}
        <div class="col-12">
            <div class="card acessos-empresa-card">
                <div class="card-header">
                    <h5>
                        <span class="header-icon header-icon-info">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </span>
                        Acessos da Empresa
                    </h5>
                    <span class="acessos-count" id="acessos-count">0 acessos cadastrados</span>
                </div>

                <div class="card-body">
                    {{-- Botão Adicionar --}}
                    <div class="acessos-toolbar">
                        <button type="button" class="btn-add-acesso" data-bs-toggle="modal" data-bs-target="#modalAddAcesso">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="16"></line>
                                <line x1="8" y1="12" x2="16" y2="12"></line>
                            </svg>
                            Adicionar Acesso
                        </button>
                        <span class="toolbar-hint">Uma empresa pode ter múltiplos acessos</span>
                    </div>

                    {{-- Lista de Acessos --}}
                    <div class="acessos-grid" id="acessos-grid">
                        {{-- Acessos serão carregados via JavaScript --}}
                    </div>

                    {{-- Empty State --}}
                    <div class="acessos-empty" id="acessos-empty" style="display: none;">
                        <div class="empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </div>
                        <p class="empty-title">Nenhum acesso cadastrado</p>
                        <p class="empty-text">Adicione os acessos do portal da empresa para facilitar o gerenciamento.</p>
                    </div>

                    {{-- Loading State --}}
                    <div class="acessos-loading" id="acessos-loading">
                        <div class="loading-spinner"></div>
                        <span>Carregando acessos...</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Modal: Adicionar/Editar Acesso --}}
    <div class="modal fade" id="modalAddAcesso" tabindex="-1" aria-labelledby="modalAddAcessoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-acesso">
                <div class="modal-header">
                    <div class="modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <div>
                        <h5 class="modal-title" id="modalAddAcessoLabel">Novo Acesso</h5>
                        <p class="modal-subtitle">Cadastre as credenciais de acesso da empresa</p>
                    </div>
                    <button type="button" class="btn-close-modal" data-bs-dismiss="modal" aria-label="Fechar">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <form id="form-acesso" novalidate>
                    <input type="hidden" name="venda_id" value="{{ $contract->id }}">
                    <input type="hidden" name="acesso_id" id="acesso_id" value="">

                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label-acesso">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                E-mail
                                <span class="required">*</span>
                            </label>
                            <input type="email" name="email" id="acesso_email" class="form-control-acesso" placeholder="email@empresa.com.br" required>
                            <span class="input-hint">E-mail de acesso ao portal</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label-acesso">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                                Senha
                                <span class="required">*</span>
                            </label>
                            <div class="password-input-wrapper">
                                <input type="password" name="senha" id="acesso_senha" class="form-control-acesso" placeholder="Digite a senha" required>
                                <button type="button" class="btn-toggle-password" id="btn-toggle-password">
                                    <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                        <line x1="1" y1="1" x2="23" y2="23"></line>
                                    </svg>
                                </button>
                            </div>
                            <span class="input-hint">Senha de acesso ao portal</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label-acesso">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                    <line x1="2" y1="10" x2="22" y2="10"></line>
                                </svg>
                                CPF
                                <span class="optional">(opcional)</span>
                            </label>
                            <input type="text" name="cpf" id="acesso_cpf" class="form-control-acesso mask-cpf" placeholder="000.000.000-00">
                            <span class="input-hint">CPF associado ao acesso (se aplicável)</span>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-save" id="btn-save-acesso">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            <span id="btn-save-text">Salvar Acesso</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Confirmação de Exclusão de Titular --}}
    <div class="modal fade" id="modalDeleteTitular" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content modal-delete-demanda">
                <div class="modal-body text-center">
                    <div class="delete-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </div>
                    <h5 class="delete-title">Remover Titular?</h5>
                    <p class="delete-text" id="delete-titular-text">O titular e todos os seus dependentes serão removidos.</p>
                    <input type="hidden" id="delete_titular_id" value="">
                    <div class="delete-actions">
                        <button type="button" class="btn-cancel-demanda" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn-delete-confirm" id="btn-confirm-delete-titular">Remover</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Adicionar Dependente --}}
    <div class="modal fade" id="modalAddDependente" tabindex="-1" aria-labelledby="modalAddDependenteLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddDependenteLabel">Adicionar Dependente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form id="form-dependente" novalidate>
                    <input type="hidden" name="venda_id" id="dep_venda_id" value="">
                    <input type="hidden" name="titular_id" id="dep_titular_id" value="">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Nome <span class="text-danger">*</span></label>
                                <input type="text" name="nome" id="dep_nome" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Parentesco</label>
                                <select name="parentesco" id="dep_parentesco" class="form-select">
                                    <option value="">Selecione...</option>
                                    <option value="CONJUGE">Cônjuge</option>
                                    <option value="FILHO">Filho(a)</option>
                                    <option value="PAI_MAE">Pai/Mãe</option>
                                    <option value="SOBRINHO">Sobrinho(a)</option>
                                    <option value="OUTROS">Outros</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">CPF</label>
                                <input type="text" name="cpf" id="dep_cpf" class="form-control mask-cpf-dep">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Data Nasc.</label>
                                <input type="text" name="data_nascimento" id="dep_data_nascimento" class="form-control" placeholder="dd/mm/aaaa">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="telefone1" id="dep_telefone1" class="form-control mask-telefone-dep">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" id="btn-save-dependente">
                            <span id="btn-save-dep-text">Salvar Dependente</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Confirmação de Exclusão de Dependente --}}
    <div class="modal fade" id="modalDeleteDependente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content modal-delete-demanda">
                <div class="modal-body text-center">
                    <div class="delete-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </div>
                    <h5 class="delete-title">Remover Dependente?</h5>
                    <p class="delete-text">Esta ação não poderá ser desfeita.</p>
                    <input type="hidden" id="delete_dep_id" value="">
                    <input type="hidden" id="delete_dep_titular_id" value="">
                    <div class="delete-actions">
                        <button type="button" class="btn-cancel-demanda" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn-delete-confirm" id="btn-confirm-delete-dep">Remover</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Confirmação de Exclusão --}}
    <div class="modal fade" id="modalDeleteAcesso" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content modal-delete">
                <div class="modal-body text-center">
                    <div class="delete-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                    </div>
                    <h5 class="delete-title">Remover Acesso?</h5>
                    <p class="delete-text">Esta ação não poderá ser desfeita.</p>
                    <input type="hidden" id="delete_acesso_id" value="">
                    <div class="delete-actions">
                        <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn-delete" id="btn-confirm-delete">Remover</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
