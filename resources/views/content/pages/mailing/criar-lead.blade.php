@extends('layouts/layoutMaster')

@section('title', 'Criar Lead')

<!-- Vendor Styles -->
@section('vendor-style')
    @vite([
        'resources/assets/vendor/scss/pages/criar-lead.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
    ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/cleavejs/cleave.js',
        'resources/assets/vendor/libs/cleavejs/cleave-phone.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js'
    ])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/assets/js/criarlead.js'])
@endsection

@section('content')

<div class="criar-lead-wrapper">

    {{-- Flash Messages --}}
    @if (session('status') == 'success')
        <div class="alert-modern alert-success d-flex align-items-center gap-2" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            {{ session('message') }}
        </div>
    @elseif(session('status') == 'error')
        <div class="alert-modern alert-danger d-flex align-items-center gap-2" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            {{ session('message') }}
        </div>
    @endif

    {{-- Header Section --}}
    <div class="cl-header">
        <div class="header-content">
            <div class="header-text">
                <span class="greeting-label">Comercial</span>
                <h1 class="main-title">Criar Lead</h1>
                <p class="subtitle">Cadastre um novo lead manualmente no sistema</p>
            </div>
            <div class="header-actions">
                <a href="{{ url()->previous() }}" class="btn-dash btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    Voltar
                </a>
            </div>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="table-card">
        <div class="table-header">
            <div class="table-title-group">
                <div class="table-icon lead">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div>
                    <h3 class="table-title">Dados do Lead</h3>
                    <span class="table-subtitle">Preencha as informacoes do novo lead</span>
                </div>
            </div>
        </div>

        <div class="table-body">
            <form id="formCriarLead" action="{{ route('comercial.createLead') }}" method="POST">
                @csrf
                <div class="edit-form-body">

                    {{-- Campanha Alice Toggle --}}
                    <div class="campanha-alice-toggle" id="campanhaAliceContainer">
                        <div class="campanha-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
                                <path d="M22 12A10 10 0 0 0 12 2v10z"/>
                            </svg>
                        </div>
                        <div class="campanha-text">
                            <span class="campanha-label">Campanha Alice</span>
                            <span class="campanha-description">Ativar para marcar este lead como parte da Campanha Alice</span>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="campanhaAlice">
                        </div>
                    </div>

                    {{-- Secao: Dados Pessoais --}}
                    <div class="form-section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        Dados Pessoais
                    </div>

                    <div class="row g-4 mb-4">
                        {{-- Nome Completo --}}
                        <div class="col-md-6">
                            <label class="form-label" for="nome_cliente">Nome Completo</label>
                            <input type="text" class="form-control" id="nome_cliente" name="nome_cliente"
                                placeholder="Nome completo do lead" required value="{{ old('nome_cliente') }}" />
                        </div>

                        {{-- CPF / CNPJ --}}
                        <div class="col-md-6">
                            <label class="form-label" for="cpf">CPF / CNPJ</label>
                            <input type="text" class="form-control" id="cpf" name="cpf"
                                placeholder="000.000.000-00" required value="{{ old('cpf') }}" />
                        </div>

                        {{-- Email --}}
                        <div class="col-md-6">
                            <label class="form-label" for="email">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email"
                                placeholder="email@exemplo.com.br" value="{{ old('email') }}" />
                        </div>

                        {{-- Data de Nascimento --}}
                        <div class="col-md-6">
                            <label class="form-label" for="data_nascimento">Data de Nascimento</label>
                            <input type="text" class="form-control flatpickr-date" id="data_nascimento" name="data_nascimento"
                                placeholder="dd/mm/aaaa" value="{{ old('data_nascimento') }}" />
                        </div>
                    </div>

                    {{-- Secao: Contato --}}
                    <div class="form-section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        Contato
                    </div>

                    <div class="row g-4 mb-4">
                        {{-- Telefone Principal --}}
                        <div class="col-md-4">
                            <label class="form-label" for="telefone1">Telefone Principal</label>
                            <input type="text" class="form-control mask-telefone" id="telefone1" name="telefone1"
                                placeholder="(11) 91234-5678" required value="{{ old('telefone1') }}" />
                        </div>

                        {{-- Telefone Comercial --}}
                        <div class="col-md-4">
                            <label class="form-label" for="telefone2">Telefone Comercial</label>
                            <input type="text" class="form-control mask-telefone" id="telefone2" name="telefone2"
                                placeholder="(11) 91234-5678" value="{{ old('telefone2') }}" />
                        </div>

                        {{-- Telefone Opcional --}}
                        <div class="col-md-4">
                            <label class="form-label" for="telefone3">Telefone Opcional</label>
                            <input type="text" class="form-control mask-telefone" id="telefone3" name="telefone3"
                                placeholder="(11) 91234-5678" value="{{ old('telefone3') }}" />
                        </div>
                    </div>

                    {{-- Secao: Plano Atual --}}
                    <div class="form-section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                            <line x1="8" y1="21" x2="16" y2="21"/>
                            <line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                        Plano Atual
                    </div>

                    <div class="row g-4">
                        {{-- Plano --}}
                        <div class="col-md-6">
                            <label class="form-label" for="plano">Plano</label>
                            <input type="text" class="form-control" id="plano" name="plano"
                                placeholder="Ex: SUL AMERICA" value="{{ old('plano') }}" />
                        </div>

                        {{-- Categoria --}}
                        <div class="col-md-6">
                            <label class="form-label" for="categoria">Categoria</label>
                            <input type="text" class="form-control" id="categoria" name="categoria"
                                placeholder="Ex: EXECUTIVO" value="{{ old('categoria') }}" />
                        </div>

                        {{-- Entidade --}}
                        <div class="col-md-4">
                            <label class="form-label" for="entidade">Entidade</label>
                            <input type="text" class="form-control" id="entidade" name="entidade"
                                placeholder="Ex: CAASP" value="{{ old('entidade') }}" />
                        </div>

                        {{-- Idades --}}
                        <div class="col-md-4">
                            <label class="form-label" for="idades">Idades</label>
                            <input type="text" class="form-control" id="idades" name="idades"
                                placeholder="Ex: 11/35/98/12" value="{{ old('idades') }}" />
                        </div>

                        {{-- Valor Plano Atual --}}
                        <div class="col-md-4">
                            <label class="form-label" for="valor_plano_atual">Valor Atual</label>
                            <input type="text" class="form-control monetary-field monetary-value" id="valor_plano_atual" name="valor_plano_atual"
                                placeholder="R$ 0,00" value="{{ old('valor_plano_atual') }}" />
                        </div>
                    </div>

                    {{-- Form Actions --}}
                    <div class="form-actions">
                        <button type="submit" class="btn-dash btn-success">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Cadastrar Lead
                        </button>
                        <a href="{{ url()->previous() }}" class="btn-dash btn-secondary">Cancelar</a>
                    </div>

                </div>
            </form>
        </div>
    </div>

</div>

@endsection
