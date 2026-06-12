@auth
    @inject('agradecimento5anos', 'App\Services\Agradecimento5AnosService')
    @php($lk5Dados = $agradecimento5anos->dadosPara(auth()->user(), (bool) session()->pull('lk5_tributo_rever', false)))
    @if ($lk5Dados)
        @vite(['resources/assets/vendor/scss/pages/agradecimento-5anos.scss', 'resources/assets/js/agradecimento-5anos.js'])

        <div id="lk5-tributo" class="lk5-tributo" aria-modal="true" role="dialog">
            <div class="lk5-tributo-aurora"></div>
            <div class="lk5-tributo-particulas"></div>

            <div class="lk5-tributo-palco">
                <!-- Cena 1: abertura -->
                <section class="lk5-cena" data-cena="abertura">
                    <div class="lk5-cinco-ouro">5</div>
                    <div class="lk5-cena-sub">anos de LK Brokers</div>
                </section>

                <!-- Cena 2: nome -->
                <section class="lk5-cena" data-cena="nome">
                    <div class="lk5-cena-overline">essa conquista também é sua</div>
                    <h1 class="lk5-cena-titulo">Obrigado, <span class="lk5-ouro-texto">{{ $lk5Dados['nome'] }}</span>.</h1>
                </section>

                @if ($lk5Dados['tipo'] === 'vendedor')
                    <!-- Cena 3: primeira venda -->
                    <section class="lk5-cena" data-cena="primeira-venda">
                        <div class="lk5-cena-overline">você lembra?</div>
                        <h2 class="lk5-cena-titulo-menor">Sua primeira venda foi em</h2>
                        <div class="lk5-data-destaque">{{ $lk5Dados['primeiraVendaData'] }}</div>
                        @if (!empty($lk5Dados['primeiraVendaOperadora']))
                            <div class="lk5-cena-sub">{{ $lk5Dados['primeiraVendaOperadora'] }}</div>
                        @endif
                    </section>

                    <!-- Cena 4: números da jornada -->
                    <section class="lk5-cena" data-cena="numeros">
                        <div class="lk5-cena-overline">de lá pra cá, sua jornada soma</div>
                        <div class="lk5-contadores">
                            <div class="lk5-contador">
                                <span class="lk5-contador-valor" data-contador="{{ $lk5Dados['totalVendas'] }}">0</span>
                                <span class="lk5-contador-label">{{ $lk5Dados['totalVendas'] === 1 ? 'venda' : 'vendas' }}</span>
                            </div>
                            <div class="lk5-contador-divisor"></div>
                            <div class="lk5-contador">
                                <span class="lk5-contador-valor" data-contador="{{ $lk5Dados['totalVidas'] }}">0</span>
                                <span class="lk5-contador-label">{{ $lk5Dados['totalVidas'] === 1 ? 'vida protegida' : 'vidas protegidas' }}</span>
                            </div>
                        </div>
                    </section>
                @else
                    <!-- Cena 3: empenho (backoffice / administrativo / demais papéis) -->
                    <section class="lk5-cena" data-cena="empenho">
                        <h2 class="lk5-cena-titulo-menor">Nenhuma venda acontece sozinha.</h2>
                        <p class="lk5-cena-texto">
                            Seu empenho nos bastidores sustenta cada contrato,
                            cada implantação e cada cliente bem atendido.
                        </p>
                    </section>
                @endif

                <!-- Cena final -->
                <section class="lk5-cena" data-cena="final">
                    <div class="lk5-confete"></div>
                    <h1 class="lk5-cena-titulo">Obrigado por fazer parte<br>destes <span class="lk5-ouro-texto">5 anos</span>.</h1>
                    <div class="lk5-cena-sub">LK Brokers · 2021 — 2026</div>
                    <button type="button" id="lk5-tributo-concluir" class="lk5-btn-concluir">
                        Vamos para os próximos 5 →
                    </button>
                </section>
            </div>
        </div>

        <script>
            window.lk5Tributo = {
                urlConcluir: @json(route('agradecimento-5anos.concluir')),
                tipo: @json($lk5Dados['tipo']),
            };
        </script>
    @endif
@endauth
