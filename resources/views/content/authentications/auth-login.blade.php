@php
    $configData = Helper::appClasses();
    $customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Login - Bem-vindo')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/page-auth.scss', 'resources/assets/vendor/scss/pages/page-auth-modern.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/pages-auth.js'])
@endsection

@section('content')
    <main class="auth-modern-cover row m-0">
        <section class="d-none d-lg-flex col-lg-7 col-xl-8 auth-left-panel" aria-label="LK Brokers">
            <div class="auth-brand-panel">
                <div class="auth-brand-symbol" aria-hidden="true">
                    @include('_partials.macros', ['height' => 112, 'withbg' => 'fill: #ffffff;'])
                </div>
                <p class="auth-brand-name">LK'Brokers</p>
                <h1>Bem-vindo ao seu ambiente de trabalho.</h1>
                <p class="auth-brand-copy">Acesse sua conta para continuar.</p>
            </div>
        </section>

        <section class="col-12 col-lg-5 col-xl-4 auth-right-panel" aria-labelledby="login-title">
            <div class="login-form-wrapper">
                <div class="app-brand justify-content-center d-lg-none mb-5">
                    <a href="{{ url('/') }}" class="app-brand-link gap-2" aria-label="LK Brokers">
                        <span class="app-brand-logo demo">@include('_partials.macros', ['height' => 40, 'withbg' => 'fill: var(--bs-primary);'])</span>
                        <span class="app-brand-text demo text-heading fw-bold">LK'Brokers</span>
                    </a>
                </div>

                <header class="auth-form-header">
                    <h2 id="login-title">Bem-vindo de volta</h2>
                    <p>Entre com seus dados para acessar a plataforma.</p>
                </header>

                @if ($errors->has('email'))
                    <div class="alert alert-danger mb-4" role="alert">
                        {{ $errors->first('email') }}
                    </div>
                @endif

                <form id="formAuthentication" action="{{ route('login.autentication') }}" method="POST">
                    @csrf
                    <div class="form-floating form-floating-outline mb-4">
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}"
                            placeholder="seu@email.com" autocomplete="email" required autofocus>
                        <label for="email">Email</label>
                    </div>

                    <div class="mb-4 form-password-toggle">
                        <div class="input-group input-group-merge">
                            <div class="form-floating form-floating-outline">
                                <input type="password" id="password" class="form-control" name="password"
                                    placeholder="Digite sua senha" autocomplete="current-password"
                                    aria-describedby="password-toggle" required>
                                <label for="password">Senha</label>
                            </div>
                            <button id="password-toggle" class="input-group-text cursor-pointer" type="button"
                                aria-label="Mostrar ou ocultar senha">
                                <i class="ri-eye-off-line ri-20px" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-check mb-5">
                        <input class="form-check-input" type="checkbox" id="remember-me" name="remember">
                        <label class="form-check-label" for="remember-me">Lembrar-me</label>
                    </div>

                    <button class="btn btn-primary d-grid w-100" type="submit">Entrar</button>
                </form>
            </div>
        </section>
    </main>
@endsection
