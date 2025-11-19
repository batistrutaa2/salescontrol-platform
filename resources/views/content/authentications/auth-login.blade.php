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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quotes = [
                { text: "O sucesso nasce do querer, da determinação e da persistência.", author: "José de Alencar" },
                { text: "Acredite em si próprio e chegará um dia em que os outros não terão outra escolha senão acreditar com você.", author: "Cynthia Kersey" },
                { text: "O único lugar onde o sucesso vem antes do trabalho é no dicionário.", author: "Albert Einstein" },
                { text: "Não espere por oportunidades, crie-as.", author: "George Bernard Shaw" },
                { text: "A persistência é o caminho do êxito.", author: "Charles Chaplin" },
                { text: "Sua única limitação é aquela que você impõe a si mesmo.", author: "Napoleão Hill" }
            ];

            let currentQuoteIndex = 0;
            const quoteTextEl = document.getElementById('quote-text');
            const quoteAuthorEl = document.getElementById('quote-author');
            const quoteContainer = document.getElementById('quote-container');

            function changeQuote() {
                // Fade out
                quoteContainer.classList.remove('animate__fadeInUp');
                quoteContainer.classList.add('animate__fadeOutDown');

                setTimeout(() => {
                    currentQuoteIndex = (currentQuoteIndex + 1) % quotes.length;
                    quoteTextEl.textContent = `"${quotes[currentQuoteIndex].text}"`;
                    quoteAuthorEl.textContent = `- ${quotes[currentQuoteIndex].author}`;

                    // Fade in
                    quoteContainer.classList.remove('animate__fadeOutDown');
                    quoteContainer.classList.add('animate__fadeInUp');
                }, 1000); // Wait for fade out to finish
            }

            // Initial set
            quoteTextEl.textContent = `"${quotes[0].text}"`;
            quoteAuthorEl.textContent = `- ${quotes[0].author}`;

            // Change quote every 6 seconds
            setInterval(changeQuote, 6000);
        });
    </script>
@endsection

@section('content')
    <div class="auth-modern-cover row m-0">
        <!-- Left Panel: Animated Background & Quotes -->
        <div class="d-none d-lg-flex col-lg-7 col-xl-8 auth-left-panel">
            <div class="auth-illustration-wrapper animate__animated animate__fadeInDown">
                <img src="{{ asset('assets/img/illustrations/auth-basic-mask-' . $configData['style'] . '.png') }}"
                    alt="auth-illustration"
                    data-app-light-img="illustrations/auth-basic-mask-light.png"
                    data-app-dark-img="illustrations/auth-basic-mask-dark.png" />
            </div>
            
            <div id="quote-container" class="quote-container animate__animated animate__fadeInUp delay-500">
                <p id="quote-text" class="quote-text">"Carregando inspiração..."</p>
                <p id="quote-author" class="quote-author"></p>
            </div>
        </div>

        <!-- Right Panel: Login Form -->
        <div class="col-12 col-lg-5 col-xl-4 auth-right-panel">
            <div class="login-form-wrapper">
                <!-- Logo -->
                <div class="app-brand justify-content-center mb-4 animate__animated animate__fadeInDown">
                    <a href="{{ url('/') }}" class="app-brand-link gap-2">
                        <span class="app-brand-logo demo animate__animated animate__bounceIn">@include('_partials.macros', ['height' => 40, 'withbg' => 'fill: var(--bs-primary);'])</span>
                        <span class="app-brand-text demo text-body fw-bold" style="background: linear-gradient(45deg, #666, #333); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 1.8rem;">LK'Brokers</span>
                    </a>
                </div>
                <!-- /Logo -->

                <h4 class="mb-2 text-center animate__animated animate__fadeInUp delay-100">Bem-vindo de volta! 👋</h4>
                <p class="mb-4 text-center animate__animated animate__fadeInUp delay-200">Faça login para continuar sua jornada de sucesso.</p>

                @if ($errors->has('email'))
                    <div class="alert alert-danger mb-3 animate__animated animate__shakeX">
                        {{ $errors->first('email') }}
                    </div>
                @endif

                <form id="formAuthentication" class="mb-3" action="{{ route('login.autentication') }}" method="POST">
                    @csrf
                    <div class="form-floating form-floating-outline mb-5 animate__animated animate__fadeInUp delay-300" style="margin-bottom: 1rem !important;">
                        <input type="text" class="form-control" id="email" name="email"
                            placeholder="Digite seu email" autofocus>
                        <label for="email">Email</label>
                    </div>
                    
                    <div class="mb-5 animate__animated animate__fadeInUp delay-400" style="margin-bottom: 1rem !important;">
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

                    <div class="mb-5 d-flex justify-content-between animate__animated animate__fadeInUp delay-500" style="margin-bottom: 1rem !important;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember-me">
                            <label class="form-check-label" for="remember-me">
                                Lembrar-me
                            </label>
                        </div>
                    </div>

                    <div class="mb-3 animate__animated animate__fadeInUp delay-500">
                        <button class="btn btn-primary d-grid w-100" type="submit">
                            Entrar
                        </button>
                    </div>
                </form>


            </div>
        </div>
    </div>
@endsection
