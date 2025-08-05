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
    <div class="row g-4">
        <form method="POST" action="{{ route('backoffice.updateSale') }}" class="row g-4">
            @csrf
            <input type="hidden" name="id" value="{{ $contract->id }}">
            <div class="col-12 col-xl-5">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-light py-2">
                        <h5 class="mb-0">Informações do Plano</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 mt-5">
                                <label class="form-label">Operadora</label>
                                <select id="operadoraSelect" class="form-select" name="operadora" required>
                                    <option value="">Selecione</option>
                                    @foreach ($operadoras as $op)
                                        <option value="{{ $op->id }}"
                                            {{ $contract->operadora == $op->nome ? 'selected' : '' }}>
                                            {{ $op->nome }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Plano</label>
                                <select id="planoSelect" class="form-select" name="plano_id" required>
                                    @if ($contract->plano_id)
                                        <option value="{{ $contract->plano_id }}" selected>{{ $contract->nome_plano }}
                                        </option>
                                    @else
                                        <option value="">Selecione a operadora primeiro</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Acomodação</label>
                                <input type="text" id="acomodacao" class="form-control" name="acomodacao"
                                    value="{{ $plano->acomodacao ?? '' }}" disabled>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Coparticipação</label>
                                <select class="form-select" name="coparticipacao" required>
                                    <option value="Y" {{ $contract->coparticipacao === 'Y' ? 'selected' : '' }}>Sim
                                    </option>
                                    <option value="N" {{ $contract->coparticipacao === 'N' ? 'selected' : '' }}>Não
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Coluna: Informações do Contrato --}}
            <div class="col-12 col-xl-7">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-light py-2">
                        <h5 class="mb-0">Informações do Contrato</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6 mt-5">
                                <label class="form-label">Nome do Contrato</label>
                                <input type="text" class="form-control" name="nome_contrato"
                                    value="{{ $contract->nome_contrato }}">
                            </div>
                            <div class="col-md-6 mt-5">
                                <label class="form-label">CPF / CNPJ</label>
                                <input type="text" id="cpf_cnpj" class="form-control" name="cpf_cnpj"
                                    value="{{ $contract->cpf_cnpj }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">E-mail</label>
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
                                    value="{{ number_format($contract->valor_contrato, 2, ',', '.') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Número de Vidas</label>
                                <input type="number" class="form-control" name="vidas" value="{{ $contract->vidas }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observações</label>
                                <textarea class="form-control" name="obs_contrato" rows="3">{{ $contract->obs_contrato }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex mt-4">
                            <button class="btn btn-success ms-auto px-4">💾 Atualizar Contrato</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
