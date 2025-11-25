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
                { text: "Sua única limitação é aquela que você impõe a si mesmo.", author: "Napoleão Hill" },
                { text: "O segredo do sucesso é a constância do propósito.", author: "Benjamin Disraeli" },
                { text: "A coragem não é ausência do medo; é a persistência apesar do medo.", author: "Desconhecido" },
                { text: "Só se pode alcançar um grande êxito quando nos mantemos fiéis a nós mesmos.", author: "Friedrich Nietzsche" },
                { text: "O homem que remove uma montanha começa carregando pequenas pedras.", author: "Provérbio Chinês" },
                { text: "Acredite que você pode, assim você já está no meio do caminho.", author: "Theodore Roosevelt" },
                { text: "O insucesso é apenas uma oportunidade para recomeçar com mais inteligência.", author: "Henry Ford" },
                { text: "A verdadeira motivação vem de realização, desenvolvimento pessoal, satisfação no trabalho e reconhecimento.", author: "Frederick Herzberg" },
                { text: "Pedras no caminho? Eu guardo todas. Um dia vou construir um castelo.", author: "Nemo Nox" },
                { text: "É parte da cura o desejo de ser curado.", author: "Sêneca" },
                { text: "Tudo o que um sonho precisa para ser realizado é alguém que acredite que ele possa ser realizado.", author: "Roberto Shinyashiki" },
                { text: "O que não provoca minha morte faz com que eu fique mais forte.", author: "Friedrich Nietzsche" },
                { text: "O sucesso é ir de fracasso em fracasso sem perder o entusiasmo.", author: "Winston Churchill" },
                { text: "A vida é 10% o que acontece a você e 90% como você reage a isso.", author: "Charles R. Swindoll" },
                { text: "Se você quer viver uma vida feliz, amarre-se a uma meta, não às pessoas nem às coisas.", author: "Albert Einstein" },
                { text: "A única maneira de fazer um excelente trabalho é amar o que você faz.", author: "Steve Jobs" },
                { text: "Não deixe que o ruído da opinião alheia impeça que você escute a sua voz interior.", author: "Steve Jobs" },
                { text: "O futuro pertence àqueles que acreditam na beleza de seus sonhos.", author: "Eleanor Roosevelt" },
                { text: "Você nunca é velho demais para definir outro objetivo ou sonhar um novo sonho.", author: "C.S. Lewis" },
                { text: "A maior glória de viver não está em nunca cair, mas em nos levantarmos toda vez que caímos.", author: "Nelson Mandela" },
                { text: "A maneira de começar é parar de falar e começar a fazer.", author: "Walt Disney" },
                { text: "Se você pode sonhar, você pode fazer.", author: "Walt Disney" },
                { text: "Não importa o quão devagar você vá, desde que você não pare.", author: "Confúcio" },
                { text: "A vida é o que acontece quando você está ocupado fazendo outros planos.", author: "John Lennon" },
                { text: "Definir um objetivo é o ponto de partida de toda a realização.", author: "W. Clement Stone" },
                { text: "A motivação é o que faz você começar. Hábito é o que faz você continuar.", author: "Jim Ryun" },
                { text: "Nossa maior fraqueza está em desistir. A maneira mais certa de ter sucesso é sempre tentar apenas mais uma vez.", author: "Thomas A. Edison" },
                { text: "Mude seus pensamentos e você muda seu mundo.", author: "Norman Vincent Peale" },
                { text: "A única coisa que se coloca entre um homem e o que ele quer na vida é a vontade de tentar e a fé de que é possível.", author: "Richard M. DeVos" },
                { text: "Eu não falhei. Apenas descobri 10 mil maneiras que não funcionam.", author: "Thomas A. Edison" },
                { text: "Se você não está disposto a arriscar, esteja disposto a uma vida comum.", author: "Jim Rohn" },
                { text: "O sucesso geralmente vem para aqueles que estão ocupados demais para procurar por ele.", author: "Henry David Thoreau" },
                { text: "As oportunidades não acontecem. Você as cria.", author: "Chris Grosser" },
                { text: "Não tenha medo de desistir do bom para perseguir o ótimo.", author: "John D. Rockefeller" },
                { text: "Eu descobri que quanto mais eu trabalho, mais sorte eu pareço ter.", author: "Thomas Jefferson" },
                { text: "O sucesso é a soma de pequenos esforços repetidos dia após dia.", author: "Robert Collier" },
                { text: "Todo progresso acontece fora da zona de conforto.", author: "Michael John Bobak" },
                { text: "A coragem é a primeira das qualidades humanas porque garante todas as outras.", author: "Aristóteles" },
                { text: "A única maneira de alcançar o impossível é acreditar que é possível.", author: "Charles Kingsleigh" },
                { text: "Não espere. O tempo nunca será o ideal.", author: "Napoleon Hill" },
                { text: "Ação é a chave fundamental para todo o sucesso.", author: "Pablo Picasso" },
                { text: "O sucesso não é o final, o fracasso não é fatal: é a coragem de continuar que conta.", author: "Winston Churchill" },
                { text: "Acredite que você pode e você já está no meio do caminho.", author: "Theodore Roosevelt" },
                { text: "O pessimista vê dificuldade em cada oportunidade; o otimista vê oportunidade em cada dificuldade.", author: "Winston Churchill" },
                { text: "Não deixe o ontem ocupar muito do hoje.", author: "Will Rogers" },
                { text: "Você aprende mais com o fracasso do que com o sucesso. Não deixe que ele o detenha.", author: "Desconhecido" },
                { text: "Não é o mais forte que sobrevive, nem o mais inteligente, mas o que melhor se adapta às mudanças.", author: "Charles Darwin" },
                { text: "A melhor vingança é um sucesso estrondoso.", author: "Frank Sinatra" },
                { text: "O segredo para sair na frente é começar.", author: "Mark Twain" },
                { text: "Seus clientes mais insatisfeitos são sua maior fonte de aprendizado.", author: "Bill Gates" }
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
