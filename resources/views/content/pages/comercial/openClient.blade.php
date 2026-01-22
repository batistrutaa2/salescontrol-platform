@extends('layouts/layoutMaster')

@section('title', 'Comercial - Visualizar Cliente')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/quill/editor.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/tagify/tagify.scss'])
    @vite(['resources/assets/vendor/js/template-customizer.js', 'resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/toastr/toastr.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/open-client.scss', 'resources/assets/vendor/scss/pages/comercial-open-client.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/quill/katex.js', 'resources/assets/vendor/libs/quill/quill.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/dropzone/dropzone.js', 'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/tagify/tagify.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/openClient.js'])
    @vite(['resources/assets/js/consulta.js'])
@endsection

@section('content')
<div class="container-fluid flex-grow-1 container-p-y open-client-wrapper">

    {{-- Alerts --}}
    @if (session('status') == 'success')
        <div class="oc-alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            {{ session('message') }}
        </div>
    @elseif(session('status') == 'error')
        <div class="oc-alert alert-error">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            {{ session('message') }}
        </div>
    @endif

    {{-- Client Header --}}
    <div class="client-header">
        <div class="header-content">
            <div class="client-avatar">
                {{ strtoupper(substr($client->nome_cliente ?? 'C', 0, 2)) }}
            </div>
            <div class="client-info">
                <h1 class="client-name">{{ $client->nome_cliente }}</h1>
                <div class="client-meta">
                    @if($client->email)
                    <span class="meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        {{ $client->email }}
                    </span>
                    @endif
                    @if($client->telefone1)
                    <span class="meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        {{ $client->telefone1 }}
                    </span>
                    @endif
                    @if($client->cpf)
                    <span class="meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        {{ $client->cpf }}
                    </span>
                    @endif
                </div>
            </div>
            <div class="header-actions">
                <button type="button" class="oc-btn oc-btn-info" data-bs-toggle="modal" data-bs-target="#consultaModal">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    Consultar
                </button>
                <button type="button" class="oc-btn oc-btn-outline" data-bs-toggle="modal" data-bs-target="#modalcomments">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    Legado
                </button>
                <button type="button" class="oc-btn oc-btn-success" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Agendar
                </button>
                <a href="{{ route('comercial.novaProposta', $client->id) }}" class="oc-btn oc-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    Nova Proposta
                </a>
                <button type="button" class="oc-btn oc-btn-danger" data-bs-toggle="modal" data-bs-target="#discardModal">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    Descartar
                </button>
            </div>
        </div>
    </div>

    {{-- Content Grid --}}
    <div class="content-grid">
        {{-- Main Content --}}
        <div class="main-content">
            {{-- Informacoes Pessoais --}}
            <div class="oc-card">
                <div class="oc-card-header">
                    <div class="header-left">
                        <div class="card-icon icon-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </div>
                        <div>
                            <h3 class="card-title">Informacoes Pessoais</h3>
                            <p class="card-subtitle">Dados cadastrais do cliente</p>
                        </div>
                    </div>
                </div>
                <div class="oc-card-body">
                    <form method="POST" action="{{ route('comercial.updateClient') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $client->id }}">

                        {{-- Status e Temperatura --}}
                        <div class="oc-form-grid grid-4" style="margin-bottom: 1.5rem;">
                            <div class="oc-field span-2">
                                <label class="oc-label">Status Atual</label>
                                <div class="oc-status-select">
                                    <select class="oc-input oc-select" name="tabulacao_id">
                                        @foreach ($tabulations as $tabulation)
                                            <option value="{{ $tabulation->id }}" {{ $tabulation->id == $tabulationCurrent ? 'selected' : '' }}>
                                                {{ $tabulation->descricao }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="oc-field span-2">
                                <label class="oc-label">Temperatura</label>
                                <div class="oc-temp-select">
                                    <label class="temp-option temp-quente {{ $client->temperatura == 'QUENTE' ? 'active' : '' }}">
                                        <input type="radio" name="temperatura" value="QUENTE" {{ $client->temperatura == 'QUENTE' ? 'checked' : '' }}>
                                        <span class="temp-icon">🔥</span>
                                        <span class="temp-label">Quente</span>
                                    </label>
                                    <label class="temp-option temp-morno {{ $client->temperatura == 'MORNO' ? 'active' : '' }}">
                                        <input type="radio" name="temperatura" value="MORNO" {{ $client->temperatura == 'MORNO' ? 'checked' : '' }}>
                                        <span class="temp-icon">☀️</span>
                                        <span class="temp-label">Morno</span>
                                    </label>
                                    <label class="temp-option temp-frio {{ ($client->temperatura == 'FRIO' || !$client->temperatura) ? 'active' : '' }}">
                                        <input type="radio" name="temperatura" value="FRIO" {{ ($client->temperatura == 'FRIO' || !$client->temperatura) ? 'checked' : '' }}>
                                        <span class="temp-icon">❄️</span>
                                        <span class="temp-label">Frio</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="section-divider">
                            <span class="divider-text">Dados Pessoais</span>
                        </div>

                        <div class="oc-form-grid">
                            <div class="oc-field span-2">
                                <label class="oc-label">Nome Completo</label>
                                <input type="text" class="oc-input" name="nome_cliente" value="{{ $client->nome_cliente }}" placeholder="Nome do cliente" {{ $editingPermission == false ? 'disabled' : '' }}>
                            </div>
                            <div class="oc-field">
                                <label class="oc-label">E-mail</label>
                                <input type="email" class="oc-input" name="email" value="{{ $client->email }}" placeholder="email@exemplo.com" {{ $editingPermission == false ? 'disabled' : '' }}>
                            </div>
                            <div class="oc-field">
                                <label class="oc-label">CPF / CNPJ</label>
                                <input type="text" class="oc-input" id="cpf" name="cpf" value="{{ $client->cpf }}" placeholder="000.000.000-00" {{ $editingPermission == false ? 'disabled' : '' }}>
                            </div>
                            <div class="oc-field">
                                <label class="oc-label">Data de Nascimento</label>
                                <input type="text" class="oc-input" name="data_nascimento" value="{{ $client->data_nascimento }}" placeholder="dd/mm/aaaa" {{ $editingPermission == false ? 'disabled' : '' }}>
                            </div>
                        </div>

                        <div class="section-divider">
                            <span class="divider-text">Plano Atual</span>
                        </div>

                        <div class="oc-form-grid grid-4">
                            <div class="oc-field">
                                <label class="oc-label">Plano</label>
                                <input type="text" class="oc-input" name="plano" value="{{ $client->plano }}" placeholder="Nome do plano" {{ $editingPermission == false ? 'disabled' : '' }}>
                            </div>
                            <div class="oc-field">
                                <label class="oc-label">Categoria</label>
                                <input type="text" class="oc-input" name="cartegoria" value="{{ $client->categoria }}" placeholder="Categoria" {{ $editingPermission == false ? 'disabled' : '' }}>
                            </div>
                            <div class="oc-field">
                                <label class="oc-label">Entidade</label>
                                <input type="text" class="oc-input" name="entidade" value="{{ $client->entidade }}" placeholder="Operadora" {{ $editingPermission == false ? 'disabled' : '' }}>
                            </div>
                            <div class="oc-field">
                                <label class="oc-label">Idades</label>
                                <input type="text" class="oc-input" name="idades" value="{{ $client->idades }}" placeholder="Idades" {{ $editingPermission == false ? 'disabled' : '' }}>
                            </div>
                        </div>

                        <div class="section-divider">
                            <span class="divider-text">Telefones</span>
                        </div>

                        <div class="oc-form-grid grid-3">
                            <div class="oc-field">
                                <label class="oc-label">Telefone Principal</label>
                                <div class="input-with-btn">
                                    <input type="text" class="oc-input mask-telefone" id="client_telefone1" name="telefone1" value="{{ $client->telefone1 }}" placeholder="(00) 00000-0000">
                                    <a href="https://wa.me/{{ preg_replace('/\D/', '', '55' . $client->telefone1) }}" target="_blank" class="input-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    </a>
                                </div>
                            </div>
                            <div class="oc-field">
                                <label class="oc-label">Telefone 2</label>
                                <div class="input-with-btn">
                                    <input type="text" class="oc-input mask-telefone" id="client_telefone2" name="telefone2" value="{{ $client->telefone2 }}" placeholder="(00) 00000-0000">
                                    <a href="https://wa.me/{{ preg_replace('/\D/', '', '55' . $client->telefone2) }}" target="_blank" class="input-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    </a>
                                </div>
                            </div>
                            <div class="oc-field">
                                <label class="oc-label">Telefone 3</label>
                                <div class="input-with-btn">
                                    <input type="text" class="oc-input mask-telefone" id="client_telefone3" name="telefone3" value="{{ $client->telefone3 }}" placeholder="(00) 00000-0000">
                                    <a href="https://wa.me/{{ preg_replace('/\D/', '', '55' . $client->telefone3) }}" target="_blank" class="input-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="section-divider">
                            <span class="divider-text">Valores</span>
                        </div>

                        <div class="oc-form-grid grid-3">
                            <div class="oc-field">
                                <label class="oc-label">Valor Atual Investido</label>
                                <input type="text" class="oc-input oc-input-monetary monetary-field" id="valor_plano_atual" name="valor_plano_atual" value="{{ old('valor_plano_atual', $client->valor_plano_atual) }}" placeholder="R$ 0,00" {{ $editingPermission == false ? 'disabled' : '' }}>
                            </div>
                            <div class="oc-field">
                                <label class="oc-label">Valor Negociacao</label>
                                <input type="text" class="oc-input oc-input-monetary monetary-field" id="valor_negociacao" name="valor_negociacao" value="{{ $client->valor_negociacao }}" placeholder="R$ 0,00">
                            </div>
                            @if ($client->tipo_layout != 'padrao')
                            <div class="oc-field">
                                <label class="oc-label">Total Familia</label>
                                <input type="text" class="oc-input oc-input-monetary monetary-field" id="total_familia" name="total_familia" value="{{ old('total_familia', $totalFamilyPlan) }}" placeholder="R$ 0,00" {{ $editingPermission == false ? 'disabled' : '' }}>
                            </div>
                            @endif
                        </div>

                        @if ($client->is_ads == 'Y')
                        <div class="section-divider">
                            <span class="divider-text">Dados do Anuncio</span>
                        </div>

                        <div class="oc-form-grid grid-4">
                            <div class="oc-field">
                                <label class="oc-label">Tipo de Anuncio</label>
                                <input type="text" class="oc-input" value="{{ $client->tipo_criativo }}" disabled>
                            </div>
                            <div class="oc-field">
                                <label class="oc-label">Possui CNPJ?</label>
                                <input type="text" class="oc-input" value="{{ $client->possui_cnpj == 'Y' ? 'SIM' : 'NAO' }}" disabled>
                            </div>
                            <div class="oc-field">
                                <label class="oc-label">Plano Ativo</label>
                                <input type="text" class="oc-input" value="{{ $client->plano_ativo == 'Y' ? 'SIM' : 'NAO' }}" disabled>
                            </div>
                            <div class="oc-field">
                                <label class="oc-label">Quantidade de Vidas</label>
                                <input type="text" class="oc-input" value="{{ $client->vidas }}" disabled>
                            </div>
                        </div>
                        @endif

                        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
                            <button type="submit" class="oc-btn oc-btn-success">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                Atualizar Informacoes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Dependentes --}}
            @if (
                ($client->tipo_layout ?? 'padrao') !== 'padrao' ||
                    (isset($dependentes) &&
                        (($dependentes instanceof \Illuminate\Support\Collection && $dependentes->isNotEmpty()) ||
                            (is_array($dependentes) && !empty($dependentes)))))
            <div class="oc-card">
                <div class="oc-card-header">
                    <div class="header-left">
                        <div class="card-icon icon-info">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                        <div>
                            <h3 class="card-title">Dependentes</h3>
                            <p class="card-subtitle">{{ count($dependentes) }} dependente(s) cadastrado(s)</p>
                        </div>
                    </div>
                </div>
                <div class="oc-card-body">
                    <div class="dependents-section">
                        @foreach ($dependentes as $index => $dependente)
                        <div class="dependent-item">
                            <div class="dependent-header" data-bs-toggle="collapse" data-bs-target="#dependent-{{ $index }}" aria-expanded="false">
                                <div class="dependent-info">
                                    <div class="dependent-avatar">D{{ $index + 1 }}</div>
                                    <div>
                                        <div class="dependent-name">{{ $dependente['nome'] ?? 'Dependente ' . ($index + 1) }}</div>
                                        <div class="dependent-relation">{{ $dependente['parentesco'] ?? '' }}</div>
                                    </div>
                                </div>
                                <button type="button" class="dependent-toggle">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </button>
                            </div>
                            <div class="collapse" id="dependent-{{ $index }}">
                                <div class="dependent-body">
                                    <form method="POST" action="{{ route('comercial.updateClientDependecies') }}">
                                        @csrf
                                        <input type="hidden" name="id_dependente" value="{{ $dependente->id }}">
                                        <input type="hidden" name="index_array" value="{{ $index }}">

                                        <div class="oc-form-grid">
                                            <div class="oc-field span-2">
                                                <label class="oc-label">Nome Completo</label>
                                                <input type="text" class="oc-input" name="dependentes[{{ $index }}][nome]" value="{{ $dependente['nome'] ?? '' }}" placeholder="Nome do dependente">
                                            </div>
                                            <div class="oc-field">
                                                <label class="oc-label">CPF</label>
                                                <div class="input-with-btn">
                                                    <input type="text" class="oc-input" id="cpfDependente{{ $index }}" name="dependentes[{{ $index }}][cpf]" value="{{ $dependente['cpf'] ?? '' }}" placeholder="000.000.000-00">
                                                    <button type="button" class="input-btn consulta-modal-trigger" data-bs-toggle="modal" data-bs-target="#consultaModal" data-cpf-from="#cpfDependente{{ $index }}" style="background: linear-gradient(135deg, var(--oc-info) 0%, var(--oc-info-light) 100%);">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="oc-field">
                                                <label class="oc-label">Idade</label>
                                                <input type="text" class="oc-input" name="dependentes[{{ $index }}][idade]" value="{{ $dependente['idade'] ?? '' }}" placeholder="Idade">
                                            </div>
                                            <div class="oc-field">
                                                <label class="oc-label">Telefone 1</label>
                                                <input type="text" class="oc-input phone-mask" name="dependentes[{{ $index }}][telefone1]" value="{{ $dependente['telefone_1'] ?? '' }}" placeholder="(00) 00000-0000">
                                            </div>
                                            <div class="oc-field">
                                                <label class="oc-label">Telefone 2</label>
                                                <input type="text" class="oc-input phone-mask" name="dependentes[{{ $index }}][telefone2]" value="{{ $dependente['telefone_2'] ?? '' }}" placeholder="(00) 00000-0000">
                                            </div>
                                            <div class="oc-field">
                                                <label class="oc-label">Telefone 3</label>
                                                <input type="text" class="oc-input phone-mask" name="dependentes[{{ $index }}][telefone3]" value="{{ $dependente['telefone_3'] ?? '' }}" placeholder="(00) 00000-0000">
                                            </div>
                                            <div class="oc-field">
                                                <label class="oc-label">Valor Dependente</label>
                                                <input type="text" class="oc-input oc-input-monetary" name="dependentes[{{ $index }}][valor_plano]" value="R$ {{ number_format($dependente['valor_plano'] ?? 0, 2, ',', '.') }}" placeholder="R$ 0,00">
                                            </div>
                                        </div>

                                        <div class="dependent-actions">
                                            <button type="submit" form="delete-dependent-{{ $index }}" class="oc-btn oc-btn-danger" onclick="return confirm('Tem certeza que deseja excluir este dependente?')">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                Excluir
                                            </button>
                                            <button type="submit" class="oc-btn oc-btn-success">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                Atualizar
                                            </button>
                                        </div>
                                    </form>

                                    <form id="delete-dependent-{{ $index }}" method="POST" action="{{ route('comercial.deletar.depedente') }}" class="d-none">
                                        @csrf
                                        <input type="hidden" name="id_dependente" value="{{ $dependente->id }}">
                                        <input type="hidden" name="index_array" value="{{ $index }}">
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Adicionar Comentario --}}
            <div class="oc-card">
                <div class="oc-card-header">
                    <div class="header-left">
                        <div class="card-icon icon-success">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        </div>
                        <div>
                            <h3 class="card-title">Adicionar Comentario</h3>
                            <p class="card-subtitle">Registre novas anotacoes</p>
                        </div>
                    </div>
                </div>
                <div class="oc-card-body">
                    <form action="{{ route('comercial.saveComment') }}" method="POST" id="saveComment" autocomplete="off">
                        @csrf
                        <input type="hidden" name="id_mailing" value="{{ $client->id }}">
                        <input type="hidden" value="{{ $tabulationCurrent }}" name="id_tabulacao">
                        <div class="comment-container form-control p-0 pt-1">
                            <div class="comment-toolbar border-0 border-bottom">
                                <div class="d-flex justify-content-start">
                                    <span class="ql-formats me-0">
                                        <button class="ql-bold"></button>
                                        <button class="ql-italic"></button>
                                        <button class="ql-underline"></button>
                                        <button class="ql-list" value="ordered"></button>
                                        <button class="ql-list" value="bullet"></button>
                                    </span>
                                </div>
                            </div>
                            <div class="comment-editor border-0 pb-1"></div>
                        </div>
                        <div style="margin-top: 1rem;">
                            <button type="submit" class="btn btn-primary">Salvar Comentario</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Cotacoes --}}
            <div class="oc-card">
                <div class="oc-card-header">
                    <div class="header-left">
                        <div class="card-icon icon-danger">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        </div>
                        <div>
                            <h3 class="card-title">Cotacoes</h3>
                            <p class="card-subtitle">Arquivos anexados</p>
                        </div>
                    </div>
                </div>
                <div class="oc-card-body">
                    <div class="cotacoes-wrapper">
                        <form action="{{ route('comercial.uploadCotacao', ['id_mailing' => $client->id]) }}" class="dropzone dropzone-area" id="cotacoes-dropzone">
                            @csrf
                            <div class="dropzone-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            </div>
                            <div class="dz-message">
                                <p class="dropzone-text">Arraste arquivos ou clique aqui para anexar</p>
                                <p class="dropzone-hint">PDF, JPG, PNG (max 10MB)</p>
                            </div>
                        </form>

                        <div style="margin-top: 1rem;">
                            <button type="button" class="oc-btn oc-btn-primary" id="cotacao-upload-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                Salvar Cotacoes
                            </button>
                        </div>

                        <input type="file" id="cotacao-replace-input" class="d-none" accept=".pdf,.jpg,.jpeg,.png">

                        @if(count($cotacoes) > 0)
                        <ul class="cotacoes-list" id="cotacoes-list">
                            @foreach ($cotacoes as $cotacao)
                            <li class="cotacao-item">
                                <div class="cotacao-info">
                                    <div class="cotacao-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                    </div>
                                    <span class="cotacao-name">{{ $cotacao['name'] }}</span>
                                </div>
                                <div class="cotacao-actions">
                                    <a href="{{ $cotacao['url'] }}" download class="action-btn btn-download download-cotacao" data-name="{{ $cotacao['name'] }}" title="Baixar">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                    </a>
                                    <button type="button" class="action-btn btn-replace replace-cotacao" data-name="{{ $cotacao['name'] }}" title="Trocar">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                                    </button>
                                    <button type="button" class="action-btn btn-delete delete-cotacao" data-name="{{ $cotacao['name'] }}" title="Excluir">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="sidebar-content">
            {{-- Anotacoes / Timeline --}}
            <div class="oc-card">
                <div class="oc-card-header">
                    <div class="header-left">
                        <div class="card-icon icon-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                        <div>
                            <h3 class="card-title">Ultimas Atividades</h3>
                            <p class="card-subtitle">Historico de anotacoes</p>
                        </div>
                    </div>
                </div>
                <div class="oc-card-body">
                    <div class="activity-timeline">
                        @forelse ($comments as $comment)
                        <div class="timeline-item">
                            <div class="timeline-dot {{ ($comment->tipo_usuario === 'DEVELOPER' || $comment->tipo_usuario === 'ADMIN') ? 'dot-admin' : 'dot-user' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-author">{{ $comment->name }}</span>
                                    <span class="timeline-badge {{ ($comment->tipo_usuario === 'DEVELOPER' || $comment->tipo_usuario === 'ADMIN') ? 'badge-admin' : 'badge-user' }}">
                                        {{ $comment->tipo_usuario }}
                                    </span>
                                </div>
                                <div class="timeline-text">{!! $comment->anotacao !!}</div>
                                <div class="timeline-date">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    {!! $comment->created_at !!}
                                </div>
                            </div>
                        </div>
                        @empty
                        <div style="text-align: center; padding: 2rem; color: var(--oc-text-muted);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.3; margin-bottom: 1rem;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            <p>Nenhuma anotacao registrada</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- ============================================ --}}
    {{-- MODAIS - Mantidas intactas --}}
    {{-- ============================================ --}}

    {{-- Modal Anotacoes Legado --}}
    <div class="modal-onboarding modal fade animate__animated" id="modalcomments" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content text-center">
                <div class="modal-header border-0">
                    <a class="text-muted close-label" href="javascript:void(0);" data-bs-dismiss="modal">Anotacoes Sistema (LEGADO)</a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body onboarding-horizontal p-0">
                    <div class="card col-10 p-5 m-5">
                        <div class="card-body mt-3">
                            <ul class="timeline pb-0 mb-0" id="timeline-list"></ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Descartar Lead --}}
    <div class="modal fade" id="discardModal" tabindex="-1" aria-labelledby="discardModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="discardModalLabel">Descartar Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('comercial.sendRemaketing') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p>Tem certeza de que deseja descartar este lead?</p>
                        <input type="hidden" id="leadIdInput" name="contato_id" value="{{ $client->id }}">
                        <div class="mb-3">
                            <label for="discardReason" class="form-label">Motivo do Descarte</label>
                            <select class="form-select" id="discardReason" name="sub_tabulacao_id" required>
                                @foreach ($subTabulacoes as $tabulation)
                                    <option value="{{ $tabulation->id }}">{{ $tabulation->descricao }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Descartar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Agendar --}}
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="sheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sheduleModalLabel">CRIAR AGENDAMENTO</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('comercial.sendSchedule') }}" method="POST">
                    @csrf
                    <input type="hidden" id="leadIdInputSchedule" name="contato_id" value="{{ $client->id }}">
                    <div class="modal-body">
                        <p>Escolha o horario e o dia do agendamento</p>
                        <div>
                            <label for="telefone1">Horario Agendamento</label>
                            <input type="datetime-local" id="horario_agendamento" name="horario_agendamento" class="form-control" placeholder="data agendamento" required />
                        </div>
                        <div class="mt-2">
                            <label for="observacao">Observacao de agendamento</label>
                            <input type="text" id="observacao" name="observacao" class="form-control" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Agendar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Consulta --}}
    <div class="modal fade" id="consultaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Consulta de Dados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Consulta por CPF</h6>
                                    <div class="form-floating form-floating-outline mb-3">
                                        <input type="text" id="cpfConsulta" class="form-control" placeholder="000.000.000-00" maxlength="14">
                                        <label for="cpfConsulta">CPF</label>
                                    </div>
                                    <button type="button" class="btn btn-primary" onclick="consultarPessoa()">
                                        <span class="spinner-border spinner-border-sm d-none" id="loadingPessoa"></span>
                                        <i class="ri-user-search-line ri-16px me-1"></i>
                                        Consultar CPF
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Consulta por CNPJ</h6>
                                    <div class="form-floating form-floating-outline mb-3">
                                        <input type="text" id="cnpjConsulta" class="form-control" placeholder="00.000.000/0000-00" maxlength="18">
                                        <label for="cnpjConsulta">CNPJ</label>
                                    </div>
                                    <button type="button" class="btn btn-success" onclick="consultarEmpresa()">
                                        <span class="spinner-border spinner-border-sm d-none" id="loadingEmpresa"></span>
                                        <i class="ri-building-line ri-16px me-1"></i>
                                        Consultar CNPJ
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="dadosEmpresa" class="d-none">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="text-success mb-0">
                                    <i class="ri-building-line ri-16px me-1"></i>
                                    Dados da Empresa Encontrados
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Razao Social:</strong> <span id="razaoSocial" class="text-muted"></span></p>
                                        <p><strong>Nome Fantasia:</strong> <span id="nomeFantasia" class="text-muted"></span></p>
                                        <p><strong>CNPJ:</strong> <span id="cnpjResult" class="text-muted"></span></p>
                                        <p><strong>Data Fundacao:</strong> <span id="dataFundacao" class="text-muted"></span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Tipo:</strong> <span id="tipoEmpresa" class="text-muted"></span></p>
                                        <p><strong>Situacao:</strong> <span id="situacaoEmpresa" class="text-muted"></span></p>
                                        <p><strong>CNAE:</strong> <span id="cnaeEmpresa" class="text-muted"></span></p>
                                        <p><strong>Atividade:</strong> <span id="atividadeEmpresa" class="text-muted"></span></p>
                                    </div>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <h6><i class="ri-smartphone-line ri-16px me-1"></i>Celulares da Empresa</h6>
                                        <div id="celularesEmpresa" class="border rounded p-2" style="min-height: 60px; max-height: 300px; overflow-y: auto;"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="ri-phone-line ri-16px me-1"></i>Telefones Fixos da Empresa</h6>
                                        <div id="fixosEmpresa" class="border rounded p-2" style="min-height: 60px; max-height: 300px; overflow-y: auto;"></div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h6><i class="ri-mail-line ri-16px me-1"></i>E-mails da Empresa</h6>
                                        <div id="emailsEmpresa" class="border rounded p-2" style="min-height: 60px; max-height: 300px; overflow-y: auto;"></div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <h6><i class="ri-map-pin-line ri-16px me-1"></i>Endereco da Empresa</h6>
                                    <div id="enderecoEmpresa" class="border rounded p-2"></div>
                                </div>
                                <div class="mt-4">
                                    <h6><i class="ri-group-line ri-16px me-1"></i>Quadro Societario</h6>
                                    <div id="sociosEmpresa" class="border rounded p-2" style="max-height: 400px; overflow-y: auto;"></div>
                                </div>
                                <div class="mt-4">
                                    <h6><i class="ri-car-line ri-16px me-1"></i>Veiculos da Empresa</h6>
                                    <div id="carrosEmpresa" class="border rounded p-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="resultadoConsulta" class="d-none">
                        <div id="dadosPessoa" class="d-none">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="text-primary mb-0">
                                        <i class="ri-user-line ri-16px me-1"></i>
                                        Dados Pessoais Encontrados
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Nome:</strong> <span id="nome" class="text-muted"></span></p>
                                            <p><strong>CPF:</strong> <span id="cpfResult" class="text-muted"></span></p>
                                            <p><strong>Data Nascimento:</strong> <span id="dataNascimento" class="text-muted"></span></p>
                                            <p><strong>Idade Atual:</strong> <span id="idade" class="text-muted"></span></p>
                                            <p><strong>Sexo:</strong> <span id="sexo" class="text-muted"></span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Nome da Mae:</strong> <span id="nomeMae" class="text-muted"></span></p>
                                            <p><strong>Situacao CPF:</strong> <span id="situacaoCpf" class="text-muted"></span></p>
                                            <p><strong>Renda:</strong> <span id="renda" class="text-muted"></span></p>
                                            <p><strong>Ocupacao:</strong> <span id="ocupacao" class="text-muted"></span></p>
                                        </div>
                                    </div>
                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <h6><i class="ri-smartphone-line ri-16px me-1"></i>Celulares</h6>
                                            <div id="celulares" class="border rounded p-2" style="min-height: 60px; max-height: 300px; overflow-y: auto;"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="ri-phone-line ri-16px me-1"></i>Telefones Fixos</h6>
                                            <div id="fixos" class="border rounded p-2" style="min-height: 60px; max-height: 300px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <h6><i class="ri-mail-line ri-16px me-1"></i>E-mails</h6>
                                            <div id="emails" class="border rounded p-2" style="min-height: 60px; max-height: 300px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <h6><i class="ri-map-pin-line ri-16px me-1"></i>Enderecos</h6>
                                        <div id="enderecos" class="border rounded p-2" style="max-height: 400px; overflow-y: auto;"></div>
                                    </div>
                                    <div class="mt-4">
                                        <h6><i class="ri-car-line ri-16px me-1"></i>Veiculos</h6>
                                        <div id="carros" class="border rounded p-2"></div>
                                    </div>
                                    <div class="mt-4">
                                        <h6><i class="ri-links-line ri-16px me-1"></i>Vinculos Familiares</h6>
                                        <div id="vinculos" class="border rounded p-2" style="max-height: 300px; overflow-y: auto;"></div>
                                    </div>
                                    <div class="mt-4">
                                        <h6><i class="ri-shield-check-line ri-16px me-1"></i>Analise de Credito</h6>
                                        <div id="riscoCredito" class="border rounded p-2"></div>
                                    </div>
                                    <div class="mt-4">
                                        <h6><i class="ri-building-2-line ri-16px me-1"></i>Participacao Societaria</h6>
                                        <div id="participacaoSocietaria" class="border rounded p-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="erroConsulta" class="alert alert-danger d-none">
                        <i class="ri-error-warning-line ri-16px me-1"></i>
                        <span id="mensagemErro"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    @if(config('services.anthropic.api_key'))
    <!-- Botão Flutuante IA -->
    <button type="button" class="btn-ia-flutuante" id="btn-ia-analise" title="Sugerir Estratégias com IA">
        <div class="btn-ia-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/>
                <circle cx="7.5" cy="14.5" r="1.5"/>
                <circle cx="16.5" cy="14.5" r="1.5"/>
            </svg>
        </div>
        <span class="btn-ia-text">IA</span>
        <div class="btn-ia-pulse"></div>
    </button>

    <!-- Modal Análise IA -->
    <div class="modal fade" id="modalAnaliseIA" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-ia">
                <div class="modal-ia-header">
                    <div class="modal-ia-header-content">
                        <div class="modal-ia-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/>
                                <circle cx="7.5" cy="14.5" r="1.5"/>
                                <circle cx="16.5" cy="14.5" r="1.5"/>
                            </svg>
                        </div>
                        <div class="modal-ia-title-group">
                            <h5 class="modal-ia-title">Assistente de Vendas IA</h5>
                            <span class="modal-ia-subtitle">Análise inteligente baseada no histórico do cliente</span>
                        </div>
                    </div>
                    <button type="button" class="modal-ia-close" data-bs-dismiss="modal" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-ia-body" id="modal-ia-content">
                    <!-- Loading State -->
                    <div class="ia-loading" id="ia-loading">
                        <div class="ia-loading-animation">
                            <div class="ia-loading-brain">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/>
                                </svg>
                            </div>
                            <div class="ia-loading-dots">
                                <span></span><span></span><span></span>
                            </div>
                        </div>
                        <p class="ia-loading-text">Analisando histórico do cliente...</p>
                        <span class="ia-loading-subtext">A IA está processando os comentários e gerando estratégias personalizadas</span>
                    </div>

                    <!-- Result State -->
                    <div class="ia-result" id="ia-result" style="display: none;">
                        <div class="ia-result-content" id="ia-result-text"></div>
                    </div>

                    <!-- Error State -->
                    <div class="ia-error" id="ia-error" style="display: none;">
                        <div class="ia-error-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </div>
                        <p class="ia-error-title">Erro na análise</p>
                        <span class="ia-error-text" id="ia-error-text"></span>
                    </div>
                </div>
                <div class="modal-ia-footer">
                    <button type="button" class="btn-ia-retry" id="btn-ia-retry" style="display: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="1 4 1 10 7 10"/>
                            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                        </svg>
                        Tentar novamente
                    </button>
                    <button type="button" class="btn-ia-close" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
