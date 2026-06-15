@auth
    @if (auth()->user()->user_role_id == \App\Enums\UserRole::VENDEDOR && session('crm_mode', 'saude') === 'saude')
        @vite(['resources/assets/vendor/scss/pages/mascote-assistente.scss', 'resources/assets/js/mascote-assistente.js'])

        @php($mascoteParabens = session()->pull('mascote_parabens'))

        <div id="mascote-assistente" class="mascote-assistente is-dormindo"
            data-endpoint="{{ route('comercial.sugestaoContato') }}"
            data-avisos="{{ route('comercial.avisosMascote') }}"
            data-aviso-lido-base="{{ url('/comercial/avisos-mascote') }}"
            data-abrir-base="{{ url('/comercial/abrir-cliente') }}"
            data-nome="{{ \Illuminate\Support\Str::of(auth()->user()->name)->trim()->explode(' ')->first() }}"
            data-dias="5"
            data-saudar="{{ session()->pull('mascote_saudar', false) ? '1' : '0' }}"
            @if ($mascoteParabens) data-parabens="{{ json_encode($mascoteParabens) }}" @endif
            aria-live="polite">

            {{-- Balão de fala --}}
            <div class="mascote-balao" role="dialog" aria-label="Sugestão do assistente">
                <button type="button" class="mascote-balao-fechar" aria-label="Fechar">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
                <div class="mascote-balao-titulo"></div>
                <div class="mascote-balao-texto"></div>
                <div class="mascote-balao-acoes">
                    <a href="#" class="mascote-btn mascote-btn-primario" data-acao="primaria">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z" />
                        </svg>
                        <span class="mascote-btn-label">Ligar agora</span>
                    </a>
                    <button type="button" class="mascote-btn mascote-btn-fantasma" data-acao="adiar">Agora não</button>
                </div>
            </div>

            {{-- Personagem: atendente com headset --}}
            <button type="button" class="mascote-corpo" aria-label="Assistente de vendas">
                <span class="mascote-anel"></span>
                <span class="mascote-zzz" aria-hidden="true">z<i>z</i><b>z</b></span>
                <span class="mascote-badge" hidden>0</span>
                <svg class="mascote-svg" viewBox="0 0 140 140" width="64" height="64" aria-hidden="true">
                    <defs>
                        <radialGradient id="mascRosto" cx="38%" cy="32%" r="80%">
                            <stop offset="0%" stop-color="#A78BFA" />
                            <stop offset="60%" stop-color="#7C3AED" />
                            <stop offset="100%" stop-color="#5B21B6" />
                        </radialGradient>
                        <linearGradient id="mascHeadset" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#312E81" />
                            <stop offset="100%" stop-color="#1E1B4B" />
                        </linearGradient>
                    </defs>

                    {{-- glow --}}
                    <circle class="mascote-glow" cx="70" cy="74" r="50" />

                    {{-- corpo/rosto --}}
                    <circle class="masc-rosto" cx="70" cy="74" r="44" fill="url(#mascRosto)" />

                    {{-- bochechas --}}
                    <circle class="masc-bochecha" cx="48" cy="88" r="6" />
                    <circle class="masc-bochecha" cx="92" cy="88" r="6" />

                    {{-- olhos --}}
                    <g class="masc-olhos">
                        <g class="masc-olho">
                            <ellipse cx="58" cy="72" rx="6.5" ry="8.5" fill="#1E1B4B" />
                            <circle cx="60" cy="69" r="2.2" fill="#fff" />
                        </g>
                        <g class="masc-olho">
                            <ellipse cx="82" cy="72" rx="6.5" ry="8.5" fill="#1E1B4B" />
                            <circle cx="84" cy="69" r="2.2" fill="#fff" />
                        </g>
                    </g>

                    {{-- sorriso --}}
                    <path class="masc-boca" d="M58 90 Q70 100 82 90" fill="none" stroke="#1E1B4B" stroke-width="3.5" stroke-linecap="round" />

                    {{-- headset: arco + conchas + microfone --}}
                    <path class="masc-arco" d="M26 70 Q70 14 114 70" fill="none" stroke="url(#mascHeadset)" stroke-width="8" stroke-linecap="round" />
                    <rect class="masc-concha" x="18" y="60" width="16" height="30" rx="8" fill="url(#mascHeadset)" />
                    <rect class="masc-concha" x="106" y="60" width="16" height="30" rx="8" fill="url(#mascHeadset)" />
                    <path class="masc-mic-haste" d="M114 86 Q120 108 96 108" fill="none" stroke="url(#mascHeadset)" stroke-width="5" stroke-linecap="round" />
                    <circle class="masc-mic" cx="94" cy="108" r="5.5" fill="#A78BFA" />

                    {{-- mãozinha que acena --}}
                    <g class="masc-mao">
                        <circle cx="118" cy="92" r="9" fill="url(#mascRosto)" />
                    </g>
                </svg>
            </button>

            {{-- Tour de boas-vindas (spotlight) --}}
            <div class="mascote-tour" hidden>
                <div class="mascote-tour-overlay"></div>
                <div class="mascote-tour-card" role="dialog" aria-label="Conheça seu assistente">
                    <span class="mascote-tour-prog" aria-hidden="true"></span>
                    <h4 class="mascote-tour-titulo"></h4>
                    <p class="mascote-tour-texto"></p>
                    <div class="mascote-tour-acoes">
                        <button type="button" class="mascote-tour-pular">Pular</button>
                        <div class="mascote-tour-nav">
                            <button type="button" class="mascote-tour-prev">Anterior</button>
                            <button type="button" class="mascote-tour-next mascote-btn mascote-btn-primario">Próximo</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endauth
