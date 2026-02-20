@extends('layouts/layoutMaster')

@section('title', 'Editar Usuario - Manager')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/scss/pages/usuarios.scss'
    ])
@endsection

@section('page-script')
    @vite('resources/assets/js/form-input-group.js')
@endsection

@section('content')

<div class="usuarios-wrapper">

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
    <div class="usr-header">
        <div class="header-content">
            <div class="header-text">
                <span class="greeting-label">Gerenciamento</span>
                <h1 class="main-title">Editar Usuario</h1>
                <p class="subtitle">Altere as informacoes do usuario <strong>{{ $user->name }}</strong></p>
            </div>
            <div class="header-actions">
                <a href="{{ route('usuarios.index') }}" class="btn-dash btn-secondary">
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
                <div class="table-icon usuarios">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="table-title">Dados do Usuario</h3>
                    <span class="table-subtitle">Atualize nome, status e senha</span>
                </div>
            </div>
        </div>

        <div class="table-body">
            <form action="{{ route('usuarios.updateUser') }}" method="POST">
                @csrf
                <div class="edit-form-body">
                    <div class="row g-4">
                        {{-- Nome --}}
                        <div class="col-md-6">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" name="name" value="{{ $user->name }}" placeholder="Nome completo do usuario" />
                        </div>

                        {{-- Email (readonly) --}}
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="{{ $user->email }}" readonly placeholder="Email do usuario" />
                        </div>

                        {{-- Status --}}
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="ativo">
                                <option {{ $user->ativo === 'Y' ? 'selected' : '' }} value="Y">ATIVADO</option>
                                <option {{ $user->ativo === 'N' ? 'selected' : '' }} value="N">DESATIVADO</option>
                            </select>
                        </div>

                        {{-- Observacoes do Contrato --}}
                        <div class="col-md-6">
                            <label class="form-label">Data de Nascimento</label>
                            <input type="date" class="form-control" name="birthdate" value="{{ $user->birthdate ?? '' }}" />
                        </div>

                        {{-- Senha --}}
                        <div class="col-md-6">
                            <label class="form-label">Nova Senha</label>
                            <div class="form-password-toggle">
                                <div class="input-group input-group-merge">
                                    <input type="password" class="form-control" name="senha" required
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                                    <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line ri-20px"></i></span>
                                </div>
                            </div>
                        </div>

                        {{-- Confirma Senha --}}
                        <div class="col-md-6">
                            <label class="form-label">Confirmar Senha</label>
                            <div class="form-password-toggle">
                                <div class="input-group input-group-merge">
                                    <input type="password" class="form-control" name="senhaConfirma" required
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                                    <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line ri-20px"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-dash btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Salvar Usuario
                        </button>
                        <a href="{{ route('usuarios.index') }}" class="btn-dash btn-secondary">Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
