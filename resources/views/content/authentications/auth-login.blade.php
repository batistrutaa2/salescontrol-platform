@php
    $configData = Helper::appClasses();
    $customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Entrar')

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
        <section class="col-12 col-lg-7 col-xl-8 auth-left-panel" aria-label="M &amp; L — Licas Consultoria Seguros">
            @include('_partials.macros', ['full' => true])
        </section>

        <section class="col-12 col-lg-5 col-xl-4 auth-right-panel" aria-labelledby="login-title">
            <div class="login-form-wrapper">
                <header class="auth-form-header">
                    <h2 id="login-title">Acesse sua operação</h2>
                    <p>Use seu e-mail e senha para continuar de onde parou.</p>
                </header>

                @if ($errors->has('email'))
                    <div class="alert alert-danger mb-4" role="alert">
                        {{ $errors->first('email') }}
                    </div>
                @endif

                <form id="formAuthentication" action="{{ route('login.autentication') }}" method="POST">
                    @csrf
                    <div class="mb-5">
                        <label class="form-label" for="email">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}"
                            placeholder="seu@email.com" autocomplete="email" required autofocus>
                    </div>

                    <div class="mb-4 form-password-toggle">
                        <label class="form-label" for="password">Senha</label>
                        <div class="input-group input-group-merge">
                            <input type="password" id="password" class="form-control" name="password"
                                    placeholder="Digite sua senha" autocomplete="current-password"
                                    aria-describedby="password-toggle" required>
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

                    <button class="btn btn-primary d-grid w-100" type="submit">Entrar no SalesControl</button>
                </form>
            </div>
        </section>
    </main>
@endsection
