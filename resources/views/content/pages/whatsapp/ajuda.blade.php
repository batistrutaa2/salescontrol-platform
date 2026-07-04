@extends('layouts/layoutMaster')

@section('title', 'WhatsApp - Como Usar')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/whatsapp-ajuda.scss'])
@endsection

@section('content')
    <div class="wa-ajuda" data-pode-conectar="{{ $podeConectar ? '1' : '0' }}">

        {{-- ============ Hero ============ --}}
        <div class="wa-ajuda-hero">
            <div class="wa-ajuda-hero-icone">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21" />
                </svg>
            </div>
            <div>
                <h4>WhatsApp no CRM — Como Usar</h4>
                <p>Atenda seus clientes, gerencie o funil e suba vendas sem sair do SalesControl.
                    Siga os passos abaixo para testar tudo com segurança.</p>
            </div>
            <div class="wa-ajuda-hero-links">
                <a href="{{ route('whatsapp.kanban') }}" class="btn btn-sm btn-outline-primary">
                    <i class="ri-kanban-view me-1"></i> Funil
                </a>
                <a href="{{ route('whatsapp.chat') }}" class="btn btn-sm btn-success">
                    <i class="ri-chat-3-line me-1"></i> Conversas
                </a>
            </div>
        </div>

        {{-- ============ Diagnóstico rápido (só vendedor) ============ --}}
        @if ($podeConectar)
            <div class="wa-ajuda-card wa-diagnostico">
                <div class="wa-ajuda-card-titulo">
                    <i class="ri-stethoscope-line"></i>
                    <h5>Diagnóstico rápido</h5>
                    <button type="button" class="btn btn-sm btn-primary ms-auto" id="wa-diag-rodar">
                        <i class="ri-refresh-line me-1"></i> Testar agora
                    </button>
                </div>
                <p class="wa-ajuda-muted">Verificações ao vivo do seu ambiente. Se algo estiver vermelho, o "como resolver" aparece ao lado.</p>

                <div class="wa-diag-lista">
                    <div class="wa-diag-item" id="diag-conexao">
                        <span class="wa-diag-status"></span>
                        <div class="wa-diag-corpo">
                            <strong>Conexão do WhatsApp</strong>
                            <span class="wa-diag-detalhe">—</span>
                        </div>
                    </div>
                    <div class="wa-diag-item" id="diag-realtime">
                        <span class="wa-diag-status"></span>
                        <div class="wa-diag-corpo">
                            <strong>Tempo real (mensagens instantâneas)</strong>
                            <span class="wa-diag-detalhe">—</span>
                        </div>
                    </div>
                    <div class="wa-diag-item" id="diag-som">
                        <span class="wa-diag-status"></span>
                        <div class="wa-diag-corpo">
                            <strong>Som de notificação</strong>
                            <span class="wa-diag-detalhe">—</span>
                        </div>
                    </div>
                    <div class="wa-diag-item" id="diag-microfone">
                        <span class="wa-diag-status"></span>
                        <div class="wa-diag-corpo">
                            <strong>Microfone (áudios de voz)</strong>
                            <span class="wa-diag-detalhe">—</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ============ Passo a passo ============ --}}
        <div class="wa-ajuda-card">
            <div class="wa-ajuda-card-titulo">
                <i class="ri-guide-line"></i>
                <h5>Passo a passo para testar</h5>
            </div>

            <div class="wa-passos">
                <div class="wa-passo">
                    <span class="wa-passo-numero">1</span>
                    <div class="wa-passo-corpo">
                        <strong>Conecte o seu número</strong>
                        <p>Abra o <a href="{{ route('whatsapp.kanban') }}">Funil de Conversas</a> e clique em
                            <span class="wa-chip verde"><i class="ri-qr-code-line"></i> Conectar</span> no topo direito.
                            No celular: <em>WhatsApp → Configurações → Aparelhos conectados → Conectar aparelho</em> e escaneie o QR code.
                            Quando o selo ficar <span class="wa-chip verde">● Conectado</span> com o seu número, está pronto.</p>
                    </div>
                </div>

                <div class="wa-passo">
                    <span class="wa-passo-numero">2</span>
                    <div class="wa-passo-corpo">
                        <strong>Receba a primeira mensagem</strong>
                        <p>Peça para alguém (ou use um segundo número seu) mandar um "oi" para o número conectado.
                            A conversa aparece sozinha em <a href="{{ route('whatsapp.chat') }}">Conversas</a> e como card na
                            primeira coluna do funil — com um toque de notificação. Se o número já for de um lead seu,
                            o vínculo acontece automaticamente (selo <span class="wa-chip verde"><i class="ri-links-line"></i> Lead</span>).</p>
                    </div>
                </div>

                <div class="wa-passo">
                    <span class="wa-passo-numero">3</span>
                    <div class="wa-passo-corpo">
                        <strong>Responda com texto, áudio e mídia</strong>
                        <p>No chat: digite e tecle <kbd>Enter</kbd>; segure conversas com figurinhas, fotos, vídeos e documentos
                            pelo clipe 📎; grave áudio de voz pelo microfone 🎤 (o navegador vai pedir permissão na primeira vez).
                            Os tiques funcionam como no WhatsApp: ✓ enviado, ✓✓ entregue, ✓✓ azul lido.</p>
                    </div>
                </div>

                <div class="wa-passo">
                    <span class="wa-passo-numero">4</span>
                    <div class="wa-passo-corpo">
                        <strong>Vincule (ou crie) o lead</strong>
                        <p>Conversa sem cliente mostra a faixa <em>"não está vinculada"</em> — clique em
                            <span class="wa-chip roxo"><i class="ri-link"></i> Vincular lead</span> para buscar entre os seus clientes,
                            ou use o <span class="wa-chip verde"><i class="ri-chat-new-line"></i> +</span> na lista de conversas para
                            iniciar papo com número novo já criando o lead no funil.</p>
                    </div>
                </div>

                <div class="wa-passo">
                    <span class="wa-passo-numero">5</span>
                    <div class="wa-passo-corpo">
                        <strong>Trabalhe o lead sem sair do chat</strong>
                        <p>Com o lead vinculado, a barra no topo da conversa mostra a etapa do funil e a temperatura
                            (❄️ Frio / ☀️ Morno / 🔥 Quente — um clique troca). No painel 👤 você registra e fixa anotações.
                            <strong>Subir venda</strong> abre a proposta do cliente e <strong>Abrir Cliente</strong> leva à ficha completa.</p>
                    </div>
                </div>

                <div class="wa-passo">
                    <span class="wa-passo-numero">6</span>
                    <div class="wa-passo-corpo">
                        <strong>Organize o funil</strong>
                        <p>No <a href="{{ route('whatsapp.kanban') }}">Funil de Conversas</a>, arraste os cards entre as colunas
                            (as mesmas do kanban comercial). Ao mover uma conversa com lead, o sistema oferece mover o lead junto.
                            Conversa pessoal? Menu ⋮ → <em>Descartar do funil</em> — ela sai do kanban mas fica acessível
                            no ícone 📥 da lista de conversas, de onde pode ser restaurada.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ Solução de problemas ============ --}}
        <div class="wa-ajuda-card">
            <div class="wa-ajuda-card-titulo">
                <i class="ri-tools-line"></i>
                <h5>Problemas comuns e como resolver</h5>
            </div>

            <div class="accordion" id="waTroubleshooting">
                @php
                    $problemas = [
                        [
                            'titulo' => 'O QR code não aparece ou expira antes de eu escanear',
                            'corpo' => 'O QR se renova sozinho a cada ~30 segundos — deixe a janela aberta que o código novo troca na tela.
                                Se ficar só o carregando: feche o modal, clique em <strong>Conectar</strong> de novo. Persistindo,
                                chame o suporte — o serviço de WhatsApp pode estar reiniciando.',
                        ],
                        [
                            'titulo' => 'As mensagens não estão chegando no CRM',
                            'corpo' => '1) Confira o selo no Funil de Conversas: precisa estar <strong>● Conectado</strong>. Se estiver
                                "Desconectado", reconecte pelo QR (acontece se o WhatsApp foi desconectado pelo celular em
                                <em>Aparelhos conectados</em>).<br>
                                2) Selo verde mas nada chega? Atualize a página (F5). O sistema também se auto-corrige: a cada 10 minutos
                                uma rotina recupera mensagens perdidas.<br>
                                3) Mensagens de <strong>grupos não entram no CRM</strong> — só conversas individuais. É proposital.',
                        ],
                        [
                            'titulo' => 'A mensagem chega, mas só depois que eu atualizo a página',
                            'corpo' => 'O tempo real (WebSocket) não está conectando. Rode o <strong>Diagnóstico rápido</strong> no topo desta
                                página: se "Tempo real" estiver vermelho, geralmente é rede/VPN ou proxy bloqueando WebSocket — teste em outra
                                rede e avise o suporte. As mensagens nunca se perdem: elas aparecem ao recarregar.',
                        ],
                        [
                            'titulo' => 'Não consigo ouvir áudio / ver foto, vídeo ou figurinha',
                            'corpo' => 'Clique na mídia de novo após atualizar a página. Se continuar quebrado para <em>todas</em> as mídias,
                                é configuração do servidor (link de armazenamento) — acione o suporte informando "storage link".
                                Mídia antiga específica que não abre pode ter expirado no celular antes de o CRM baixar — peça para reenviarem.',
                        ],
                        [
                            'titulo' => 'O som de notificação não toca',
                            'corpo' => 'Confira o alto-falante 🔊 no topo da lista de conversas (pode estar silenciado — a preferência fica salva).
                                Os navegadores também exigem <strong>uma interação na página</strong> (qualquer clique) antes de liberar som —
                                depois do primeiro clique, os próximos toques funcionam.',
                        ],
                        [
                            'titulo' => 'Mandei mensagem de um lead meu e não vinculou automaticamente',
                            'corpo' => 'O vínculo automático compara DDD + número do telefone do lead (telefones 1, 2 e 3 do cadastro).
                                Se o telefone do cadastro estiver sem DDD, com número antigo ou de outra pessoa, não há como casar.
                                Solução: abra a conversa → faixa superior → <strong>Vincular lead</strong> e escolha o cliente manualmente
                                (fica vinculado para sempre).',
                        ],
                        [
                            'titulo' => 'Não consigo gravar áudio de voz',
                            'corpo' => 'O navegador precisa de permissão de microfone: clique no cadeado ao lado do endereço do site →
                                Permissões → Microfone → Permitir. No Diagnóstico rápido acima dá para ver o estado atual da permissão.',
                        ],
                        [
                            'titulo' => 'Uma conversa sumiu da lista / do funil',
                            'corpo' => 'Provavelmente foi <strong>descartada</strong> (menu ⋮ → Descartar do funil). Clique no ícone 📥 no topo
                                da lista de conversas para ver as descartadas — abra a conversa e use ⋮ → <em>Restaurar ao funil</em>.',
                        ],
                        [
                            'titulo' => 'O WhatsApp desconectou sozinho',
                            'corpo' => 'Acontece se: o celular ficou muito tempo sem internet; alguém removeu o aparelho em
                                <em>Aparelhos conectados</em> no celular; ou o WhatsApp foi reinstalado. É só escanear o QR de novo —
                                as conversas e o histórico do CRM continuam intactos.',
                        ],
                        [
                            'titulo' => 'Posso usar o mesmo número em dois vendedores?',
                            'corpo' => 'Não — cada vendedor conecta o próprio número (1 número por vendedor). As conversas de cada número
                                pertencem ao vendedor que o conectou; supervisores enxergam tudo em modo leitura.',
                        ],
                    ];
                @endphp

                @foreach ($problemas as $i => $problema)
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#waProblema{{ $i }}">
                                {{ $problema['titulo'] }}
                            </button>
                        </h2>
                        <div id="waProblema{{ $i }}" class="accordion-collapse collapse" data-bs-parent="#waTroubleshooting">
                            <div class="accordion-body">{!! $problema['corpo'] !!}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ============ Boas práticas ============ --}}
        <div class="wa-ajuda-card">
            <div class="wa-ajuda-card-titulo">
                <i class="ri-lightbulb-line"></i>
                <h5>Boas práticas</h5>
            </div>
            <ul class="wa-ajuda-dicas">
                <li><strong>Mantenha o celular com internet</strong> — a conexão do CRM depende do aparelho, como no WhatsApp Web.</li>
                <li><strong>Registre anotações no lead</strong> (painel 👤) em vez de depender só do histórico do chat — elas aparecem também na ficha do cliente e no funil comercial.</li>
                <li><strong>Atualize a temperatura</strong> a cada contato relevante: a régua ❄️/☀️/🔥 alimenta a priorização no kanban comercial.</li>
                <li><strong>Descarta sem medo</strong>: conversa pessoal descartada não some — só sai do funil.</li>
                <li><strong>Continue usando o celular normalmente</strong> — mensagens enviadas por ele também entram no histórico do CRM.</li>
            </ul>
        </div>
    </div>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/whatsapp-ajuda.js'])
@endsection
