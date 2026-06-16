@extends('layouts/layoutMaster')

@section('title', 'Comercial - Visualizar Cliente')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/quill/editor.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/tagify/tagify.scss'])
    @vite(['resources/assets/vendor/js/template-customizer.js', 'resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/toastr/toastr.scss'])
@endsection

@section('page-style')
    @vite([
        'resources/assets/vendor/scss/pages/open-client.scss',
        'resources/assets/vendor/scss/pages/comercial-open-client.scss',
        'resources/assets/vendor/scss/pages/pos-venda.scss',
        'resources/assets/vendor/scss/pages/kanban-modal.scss'
    ])
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
                        @php $isOwner = $comment->user_id === auth()->id(); @endphp
                        <div class="timeline-item {{ $comment->fixado_proprio ? 'is-pinned' : '' }}" data-comment-id="{{ $comment->id }}">
                            <div class="timeline-dot {{ ($comment->tipo_usuario === 'DEVELOPER' || $comment->tipo_usuario === 'ADMIN') ? 'dot-admin' : 'dot-user' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-author">{{ $comment->name }}</span>
                                    <span class="timeline-badge {{ ($comment->tipo_usuario === 'DEVELOPER' || $comment->tipo_usuario === 'ADMIN') ? 'badge-admin' : 'badge-user' }}">
                                        {{ $comment->tipo_usuario }}
                                    </span>
                                    @if ($comment->fixado_proprio)
                                    <span class="timeline-badge badge-pinned">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="17" x2="12" y2="22"></line><path d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1v4.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24Z"></path></svg>
                                        Fixado
                                    </span>
                                    @endif
                                    @if ($isOwner)
                                    <div class="timeline-actions">
                                        <button type="button" class="timeline-action-btn btn-pin-comment {{ $comment->fixado_proprio ? 'active' : '' }}" title="{{ $comment->fixado_proprio ? 'Desafixar comentario' : 'Fixar comentario' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="17" x2="12" y2="22"></line><path d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1v4.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24Z"></path></svg>
                                        </button>
                                        <button type="button" class="timeline-action-btn btn-edit-comment" title="Editar comentario">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path><path d="m15 5 4 4"></path></svg>
                                        </button>
                                    </div>
                                    @endif
                                </div>
                                <div class="timeline-text">{!! $comment->anotacao !!}</div>
                                <div class="timeline-date">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    {!! $comment->created_at !!}
                                    @if ($comment->editado_em)
                                    <span class="timeline-edited" title="Editado em {{ \Carbon\Carbon::parse($comment->editado_em)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') }}">(editado)</span>
                                    @endif
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

    {{-- Modal Consulta — redesenhada com mesmo design da Fila Preditiva --}}
    <div class="modal fade" id="consultaModal" tabindex="-1" aria-labelledby="consultaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl oc-consulta-dialog modal-dialog-centered">
            <div class="modal-content pv-modal-modern">

                {{-- Header --}}
                <div class="pv-modal-header pv-modal-header-primary">
                    <div class="pv-modal-header-content">
                        <div class="pv-modal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </div>
                        <div class="pv-modal-title-group">
                            <h5 class="pv-modal-title" id="consultaModalLabel">Consultar Dados</h5>
                            <span class="pv-modal-subtitle" id="oc-consulta-subtitle">CPF ou CNPJ</span>
                        </div>
                        <span class="oc-fonte-badge d-none" id="oc-fonte-badge"></span>
                    </div>
                    <button type="button" class="pv-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>

                {{-- Corpo: dois painéis (esquerdo: histórico + busca / direito: resultados) --}}
                <div class="oc-consulta-panels">

                    {{-- Painel esquerdo: histórico de consultas --}}
                    <div class="oc-history-panel">
                        <div class="oc-hist-header">
                            <span class="oc-hist-title">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                </svg>
                                Consultas
                            </span>
                            <span class="oc-hist-count" id="oc-hist-count">0</span>
                        </div>

                        {{-- Campo de busca --}}
                        <div class="oc-search-area">
                            <div class="oc-fonte-seg" id="oc-fonte-seg">
                                <button type="button" class="oc-fonte-btn active" data-fonte="lemit">Lemit</button>
                                <button type="button" class="oc-fonte-btn" data-fonte="assertiva">Assertiva</button>
                            </div>
                            <div class="oc-tipo-seg" id="oc-tipo-seg">
                                <button type="button" class="oc-tipo-btn active" data-tipo="documento">Documento</button>
                                <button type="button" class="oc-tipo-btn" data-tipo="telefone">Telefone</button>
                                <button type="button" class="oc-tipo-btn" data-tipo="email">E-mail</button>
                                <button type="button" class="oc-tipo-btn" data-tipo="nome">Nome</button>
                            </div>
                            <div class="oc-search-wrap">
                                <input type="text" id="oc-doc-input" class="oc-search-input"
                                       placeholder="CPF ou CNPJ..." maxlength="18" autocomplete="off">
                                <button type="button" id="btn-oc-consultar" class="oc-search-btn" title="Consultar">
                                    <span class="spinner-border spinner-border-sm d-none" id="oc-consulta-loading"
                                          style="width:.875rem;height:.875rem" role="status"></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="oc-consulta-icon">
                                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Lista de histórico --}}
                        <div class="oc-hist-list" id="oc-hist-list">
                            <div class="oc-hist-empty" id="oc-hist-empty">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:.3">
                                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                                <p>Nenhuma consulta ainda</p>
                                <small>As buscas aparecem aqui</small>
                            </div>
                        </div>
                    </div>

                    {{-- Painel direito: resultados --}}
                    <div class="oc-results-panel">

                        {{-- Card fixo do lead — sempre visível, nunca some ao pesquisar --}}
                        <div id="oc-lead-card" class="oc-lead-card d-none"></div>

                        {{-- Estado inicial --}}
                        <div class="kp-empty-state" id="oc-consulta-initial">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:.25">
                                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                            <p>Digite um CPF ou CNPJ para consultar</p>
                            <small>Os dados são buscados em tempo real</small>
                        </div>

                        {{-- Estado dinâmico: buscando / nada encontrado --}}
                        <div id="consulta-estado" class="d-none"></div>

                        {{-- Erro de consulta --}}
                        <div id="erro-consulta-cliente" class="d-none"
                             style="color:var(--km-danger);font-size:.875rem;padding:1rem 1.5rem;display:flex;align-items:center;gap:.5rem">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            <span id="mensagem-erro-cliente"></span>
                        </div>

                        {{-- Resultados em tabs --}}
                        <div class="kp-consulta-section d-none" id="dados-consulta-cliente">

                            <div class="kp-consulta-header">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                                <span id="oc-resultado-titulo">Dados Encontrados</span>
                            </div>

                            <div class="kp-info-tabs-wrapper d-none" id="dados-pessoa-preditiva">

                                {{-- Nav das tabs --}}
                                <nav class="kp-info-tabs-nav" id="kp-tabs-nav">
                                    <button class="kp-info-tab-btn active" data-kp-tab="tab-pessoa">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                                        </svg>
                                        Dados
                                    </button>
                                    <button class="kp-info-tab-btn" data-kp-tab="tab-telefones">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72"/>
                                        </svg>
                                        Telefones
                                        <span class="kp-tab-count" id="kp-count-telefones">0</span>
                                    </button>
                                    <button class="kp-info-tab-btn" data-kp-tab="tab-emails">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                                        </svg>
                                        E-mails
                                        <span class="kp-tab-count" id="kp-count-emails">0</span>
                                    </button>
                                    <button class="kp-info-tab-btn" data-kp-tab="tab-enderecos">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                                        </svg>
                                        Endereços
                                        <span class="kp-tab-count" id="kp-count-enderecos">0</span>
                                    </button>
                                    <button class="kp-info-tab-btn" data-kp-tab="tab-credito">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                        </svg>
                                        Crédito
                                    </button>
                                    <button class="kp-info-tab-btn" data-kp-tab="tab-societario">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                        </svg>
                                        Societário
                                    </button>
                                    <button class="kp-info-tab-btn" data-kp-tab="tab-vinculos">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                        </svg>
                                        Vínculos
                                        <span class="kp-tab-count" id="kp-count-vinculos">0</span>
                                    </button>
                                </nav>

                                {{-- Panes --}}
                                <div class="kp-info-tab-panes">

                                    <div class="kp-info-tab-pane active" id="tab-pessoa">
                                        <div class="kp-pessoa-grid">
                                            <div class="kp-pessoa-field"><label>Nome</label><span id="nome-preditiva">—</span></div>
                                            <div class="kp-pessoa-field kp-field-mono"><label>CPF / CNPJ</label><span id="cpf-result-preditiva">—</span></div>
                                            <div class="kp-pessoa-field"><label>Nascimento / Fundação</label><span id="data-nascimento-preditiva">—</span></div>
                                            <div class="kp-pessoa-field"><label>Idade / Tipo</label><span id="idade">—</span></div>
                                            <div class="kp-pessoa-field"><label>Sexo</label><span id="sexo-preditiva">—</span></div>
                                            <div class="kp-pessoa-field"><label>Nome da Mãe / Fantasia</label><span id="nome-mae-preditiva">—</span></div>
                                            <div class="kp-pessoa-field"><label>Situação</label><span id="situacao-cpf-preditiva">—</span></div>
                                            <div class="kp-pessoa-field kp-field-mono"><label>Renda / CNAE</label><span id="renda-preditiva">—</span></div>
                                            <div class="kp-pessoa-field"><label>Ocupação / Segmento</label><span id="ocupacao-preditiva">—</span></div>
                                        </div>
                                    </div>

                                    <div class="kp-info-tab-pane" id="tab-telefones">
                                        <div class="kp-tab-list" id="celulares-preditiva-wrap">
                                            <div class="kp-tab-list-header">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>
                                                </svg>
                                                Celulares
                                            </div>
                                            <div id="celulares-preditiva"></div>
                                        </div>
                                        <div class="kp-tab-list" id="fixos-preditiva-wrap">
                                            <div class="kp-tab-list-header">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13"/>
                                                </svg>
                                                Telefones Fixos
                                            </div>
                                            <div id="fixos-preditiva"></div>
                                        </div>
                                    </div>

                                    <div class="kp-info-tab-pane" id="tab-emails">
                                        <div class="kp-tab-list">
                                            <div class="kp-tab-list-header">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                                                </svg>
                                                Endereços de E-mail
                                            </div>
                                            <div id="emails-preditiva"></div>
                                        </div>
                                    </div>

                                    <div class="kp-info-tab-pane" id="tab-enderecos">
                                        <div id="enderecos-preditiva"></div>
                                    </div>

                                    <div class="kp-info-tab-pane" id="tab-credito">
                                        <div id="risco-credito-preditiva"></div>
                                    </div>

                                    <div class="kp-info-tab-pane" id="tab-societario">
                                        <div id="participacaoSocietaria"></div>
                                    </div>

                                    <div class="kp-info-tab-pane" id="tab-vinculos">
                                        <div id="vinculos-preditiva"></div>
                                    </div>

                                </div>{{-- /kp-info-tab-panes --}}
                            </div>{{-- /dados-pessoa-preditiva --}}

                            {{-- Render completo Assertiva (preenchido pelo consulta.js) --}}
                            <div id="dados-assertiva-preditiva" class="assv-wrap d-none"></div>
                        </div>{{-- /dados-consulta-cliente --}}

                    </div>{{-- /oc-results-panel --}}
                </div>{{-- /oc-consulta-panels --}}

                {{-- Footer --}}
                <div class="lim-modal-footer-bar">
                    <button type="button" class="pv-btn pv-btn-ghost" data-bs-dismiss="modal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                        Fechar
                    </button>
                </div>

            </div>{{-- /modal-content --}}
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
