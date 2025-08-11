@extends('layouts/layoutMaster')

@section('title', 'Gestão de contrato')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/openContract.js'])
@endsection

@section('content')
    @php
        // Fallbacks caso o controller não tenha enviado estes dados prontos
        $selectedOperadoraId =
            $selectedOperadoraId ?? optional(($operadoras ?? collect())->firstWhere('nome', $contract->operadora))->id;

        $planosDaOperadora = $planosDaOperadora ?? collect();

        $isAmil = false;
        if ($selectedOperadoraId) {
            $nomeOpSel = optional(($operadoras ?? collect())->firstWhere('id', $selectedOperadoraId))->nome;
            $isAmil = strtoupper((string) $nomeOpSel) === 'AMIL - PME';
        } else {
            $isAmil = strtoupper((string) $contract->operadora) === 'AMIL - PME';
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


    <div class="row g-4">

        {{-- COLUNA ESQUERDA: APÓLICE (Operadora/Plano base) + DADOS DA EMPRESA --}}
        <div class="col-12 col-xl-5">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-light py-2">
                    <h5 class="mb-0">Dados da Apólice</h5>
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

                        <div class="col-md-4">
                            <label class="form-label">Telefone 1</label>
                            <input type="text" class="form-control mask-telefone" name="telefone1"
                                value="{{ $contract->telefone1 }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Telefone 2</label>
                            <input type="text" class="form-control mask-telefone" name="telefone2"
                                value="{{ $contract->telefone2 }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Valor do Contrato</label>
                            <input type="text" class="form-control monetary-field" name="valor_contrato"
                                value="{{ number_format((float) $contract->valor_contrato, 2, ',', '.') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Número de Vidas</label>
                            <input type="number" class="form-control" name="vidas" value="{{ (int) $contract->vidas }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="obs_contrato" rows="3">{{ $contract->obs_contrato }}</textarea>
                        </div>

                        <div class="d-flex mt-3">
                            <button class="btn btn-success ms-auto px-4">💾 Atualizar Contrato</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- COLUNA DIREITA: TITULARES (Edição individual) --}}
        <div class="col-12 col-xl-7">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Titulares da Apólice</h5>
                    <small class="text-muted">Atualize os dados de cada titular individualmente.</small>
                </div>

                <div class="card-body">
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

                            {{-- Plano por titular: já pré-carrega se a lista vier do controller; senão, JS buscará --}}
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
                        <div class="alert alert-warning mb-0 mt-4">Nenhum titular encontrado para esta venda.</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
@endsection
