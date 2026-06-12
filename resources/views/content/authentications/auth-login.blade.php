@php
    $configData = Helper::appClasses();
    $customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Login - 5 Anos LK Brokers')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/page-auth-5anos.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/pages-auth.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Se o vídeo comemorativo ainda não foi renderizado/publicado,
            // esconde o player e mantém o fallback animado em CSS.
            const video = document.getElementById('lk5-video');
            const fallback = document.getElementById('lk5-fallback');
            if (video) {
                video.addEventListener('error', function () {
                    video.remove();
                }, true);
                video.addEventListener('canplay', function () {
                    if (fallback) fallback.style.display = 'none';
                });
            }
        });
    </script>
@endsection

@section('content')
    <div class="lk5-login-page row m-0">
        <!-- Painel do filme: animação 3D dos 5 anos (Remotion) -->
        <div class="d-none d-lg-flex col-lg-7 col-xl-8 lk5-cinema">
            <div id="lk5-fallback" class="lk5-fallback">
                <div class="lk5-fallback-cinco">5</div>
                <div class="lk5-fallback-anos">Anos</div>
                <div class="lk5-fallback-periodo">2021 — 2026</div>
            </div>

            <video id="lk5-video" class="lk5-video" autoplay muted loop playsinline
                poster="{{ asset('assets/img/illustrations/auth-basic-mask-dark.png') }}">
                <source src="{{ asset('assets/video/lk-5-anos.mp4') }}" type="video/mp4">
            </video>

            <div class="lk5-emblema-canto animate__animated animate__fadeInDown">
                <div class="lk5-selo">5</div>
                <div class="lk5-emblema-texto">
                    <strong>LK Brokers</strong>
                    <span>5 anos de história</span>
                </div>
            </div>
        </div>

        <!-- Painel do formulário -->
        <div class="col-12 col-lg-5 col-xl-4 lk5-form-panel">
            <div class="lk5-card animate__animated animate__fadeInUp">

                <div class="lk5-crista-mobile">
                    <div class="lk5-cinco">5</div>
                    <div class="lk5-anos">Anos</div>
                </div>

                <div class="lk5-faixa-aniversario animate__animated animate__fadeInDown">
                    <span class="lk5-faisca"></span>
                    Edição 5 anos · 2021—2026
                </div>

                <div class="lk5-marca">
                    <a href="{{ url('/') }}" class="app-brand-link gap-2 d-flex align-items-center">
                        <span class="app-brand-logo demo">@include('_partials.macros', ['height' => 36, 'withbg' => 'fill: #A78BFA;'])</span>
                        <span class="app-brand-text">LK'Brokers</span>
                    </a>
                </div>

                <h4 class="lk5-titulo animate__animated animate__fadeInUp delay-100">Bem-vindo ao ano 5 ✨</h4>
                <p class="lk5-subtitulo animate__animated animate__fadeInUp delay-200">
                    Cinco anos de história construída por vendedores como você.
                    Entre e escreva o próximo capítulo.
                </p>

                @if ($errors->has('email'))
                    <div class="alert alert-danger mb-3 animate__animated animate__shakeX">
                        {{ $errors->first('email') }}
                    </div>
                @endif

                <form id="formAuthentication" class="mb-0" action="{{ route('login.autentication') }}" method="POST">
                    @csrf
                    <div class="lk5-field form-floating form-floating-outline mb-4 animate__animated animate__fadeInUp delay-300">
                        <input type="text" class="form-control" id="email" name="email"
                            placeholder="Digite seu email" autofocus>
                        <label for="email">Email</label>
                    </div>

                    <div class="lk5-field mb-4 animate__animated animate__fadeInUp delay-400">
                        <div class="form-password-toggle">
                            <div class="input-group input-group-merge">
                                <div class="form-floating form-floating-outline">
                                    <input type="password" id="password" class="form-control" name="password"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        aria-describedby="password" />
                                    <label for="password">Senha</label>
                                </div>
                                <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line ri-20px"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 d-flex justify-content-between animate__animated animate__fadeInUp delay-500">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember-me">
                            <label class="form-check-label" for="remember-me">
                                Lembrar-me
                            </label>
                        </div>
                    </div>

                    <div class="animate__animated animate__fadeInUp delay-500">
                        <button class="lk5-btn-entrar" type="submit">
                            Entrar
                        </button>
                    </div>
                </form>

                <div class="lk5-rodape">
                    <strong>2021 — 2026</strong> · obrigado por fazer parte
                </div>
            </div>
        </div>
    </div>
@endsection
