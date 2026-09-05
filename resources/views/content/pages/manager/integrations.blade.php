@extends('layouts/layoutMaster')

@section('title', 'Integrações por empresa')

@section('vendor-style')
    @vite('resources/assets/vendor/scss/pages/page-integrations-manager.scss')
@endsection

@section('content')
    <main class="integration-manager">
        @if (session('message'))
            <div class="alert alert-{{ session('status') === 'success' ? 'success' : 'danger' }}" role="status">
                {{ session('message') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Não foi possível salvar a integração.</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="integration-hero" aria-labelledby="integration-page-title">
            <div class="integration-hero__main">
                <h1 id="integration-page-title">Conecte cada empresa à conta certa.</h1>
                <p>Credenciais nunca são compartilhadas entre corretoras. Toda alteração abaixo afeta somente a empresa ativa e o token salvo não volta ao navegador.</p>
                <div class="integration-company">
                    <span><i class="ri-building-4-line" aria-hidden="true"></i> Empresa ativa</span>
                    <strong>{{ $empresa->nome_fantasia }}</strong>
                </div>
            </div>
            <aside class="integration-hero__guardrail" aria-label="Proteções das integrações">
                <h2>Segredos sob custódia</h2>
                <ul>
                    <li><i class="ri-lock-password-line" aria-hidden="true"></i><span>Tokens são criptografados antes de chegar ao banco.</span></li>
                    <li><i class="ri-shield-keyhole-line" aria-hidden="true"></i><span>Somente o master da plataforma administra credenciais.</span></li>
                    <li><i class="ri-route-line" aria-hidden="true"></i><span>Chamadas usam o tenant ativo e falham fechadas sem configuração.</span></li>
                </ul>
            </aside>
        </section>

        <section class="integration-ledger" aria-labelledby="voip-title">
            <header class="integration-ledger__head">
                <div class="integration-ledger__identity">
                    <span class="integration-ledger__icon"><i class="ri-phone-line" aria-hidden="true"></i></span>
                    <div>
                        <h2 id="voip-title">Telefonia · MaisVoIP</h2>
                        <p>Inicia ligações a partir do ramal do usuário da empresa ativa.</p>
                    </div>
                </div>
                <span class="integration-status {{ $voip?->active ? 'integration-status--active' : '' }}">
                    {{ $voip?->active ? 'Ativa' : ($voip ? 'Pausada' : 'Não configurada') }}
                </span>
            </header>

            <form method="POST" action="{{ route('manager.integrations.voip.save') }}" class="integration-form">
                @csrf
                @method('PUT')
                <div class="integration-field integration-field--wide">
                    <label for="voip-endpoint">Endpoint HTTPS</label>
                    <input class="form-control" type="url" id="voip-endpoint" name="endpoint"
                        value="{{ old('endpoint', $voip?->endpoint) }}" maxlength="2048" required
                        placeholder="https://telefonia.exemplo.com/api/click-to-call" autocomplete="url">
                    <small>Use o endpoint de click-to-call fornecido pela operadora.</small>
                </div>
                <div class="integration-field">
                    <label for="voip-token">Token {{ $voip ? '— deixe vazio para manter' : '' }}</label>
                    <input class="form-control" type="password" id="voip-token" name="token"
                        minlength="16" maxlength="512" @required(! $voip) autocomplete="new-password">
                    <small>{{ $voip ? 'Há um token criptografado salvo.' : 'O token será exigido na primeira configuração.' }}</small>
                </div>
                <label class="integration-switch" for="voip-active">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" id="voip-active" name="active" value="1" @checked(old('active', $voip?->active ?? true))>
                    <span class="integration-switch__track" aria-hidden="true"><span></span></span>
                    <span><strong>Integração ativa</strong><small>Desative para suspender novas ligações sem apagar a credencial.</small></span>
                </label>
                <div class="integration-actions">
                    <button class="btn btn-primary" type="submit"><i class="ri-save-line" aria-hidden="true"></i> Salvar para esta empresa</button>
                </div>
            </form>

            @if ($voip)
                <footer class="integration-ledger__danger">
                    <div>
                        <strong>Remover configuração</strong>
                        <p>As ligações ficam indisponíveis até um novo endpoint e token serem cadastrados.</p>
                    </div>
                    <form method="POST" action="{{ route('manager.integrations.voip.delete') }}" onsubmit="return confirm('Remover a telefonia somente desta empresa?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger" type="submit">Remover</button>
                    </form>
                </footer>
            @endif
        </section>
    </main>
@endsection
