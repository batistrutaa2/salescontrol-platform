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
                    <form method="POST" action="{{ route('backoffice.updateSale') }}" class="row g-3" id="form-contrato">
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
                            <button class="btn btn-success ms-auto px-4">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                Atualizar Contrato
                            </button>
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

    </div>
@endsection
