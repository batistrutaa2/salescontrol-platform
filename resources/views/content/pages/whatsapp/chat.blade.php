@extends('layouts/layoutMaster')

@section('title', 'WhatsApp - Conversas')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/toastr/toastr.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/whatsapp-chat.scss'])
@endsection

@section('content')
    <div class="wa-chat-app" id="wa-chat-app"
        data-conversa-inicial="{{ $conversaInicialId ?? '' }}"
        data-pode-enviar="{{ $podeEnviar ? '1' : '0' }}">

        {{-- ============ Coluna 1: Lista de conversas ============ --}}
        <aside class="wa-sidebar">
            <div class="wa-sidebar-header">
                <h5 class="wa-sidebar-title">Conversas</h5>
                <div class="wa-sidebar-acoes">
                    @if ($podeEnviar)
                        <button type="button" class="wa-btn-icon wa-btn-nova-conversa" id="wa-btn-nova-conversa" title="Nova conversa">
                            <i class="ri-chat-new-line"></i>
                        </button>
                    @endif
                    <button type="button" class="wa-btn-icon" id="wa-btn-som" title="Som de notificação">
                        <i class="ri-volume-up-line"></i>
                    </button>
                    <a href="{{ route('whatsapp.kanban') }}" class="wa-btn-icon" title="Funil de conversas">
                        <i class="ri-kanban-view"></i>
                    </a>
                    <a href="{{ route('whatsapp.ajuda') }}" class="wa-btn-icon" title="Como usar / resolver problemas"
                        onclick="if (window.mascote && window.mascote.tourWhatsapp) { window.mascote.tourWhatsapp(); return false; }">
                        <i class="ri-question-line"></i>
                    </a>
                </div>
            </div>
            <div class="wa-sidebar-busca">
                <i class="ri-search-line"></i>
                <input type="text" id="wa-busca" placeholder="Buscar conversa ou número..." autocomplete="off" />
            </div>

            {{-- Tabs: Funil (em negociação) | Carteira (clientes) | Descartadas --}}
            <div class="wa-lista-tabs" id="wa-lista-tabs">
                <button type="button" class="wa-lista-tab ativa" data-modo="ativas">
                    <i class="ri-chat-3-line"></i> Conversas
                    <span class="wa-tab-badge" id="wa-tab-badge-unread" hidden>0</span>
                </button>
                <button type="button" class="wa-lista-tab" data-modo="carteira">
                    <i class="ri-briefcase-4-line"></i> Carteira
                </button>
                <button type="button" class="wa-lista-tab" data-modo="arquivadas">
                    <i class="ri-archive-line"></i> Descartadas
                </button>
            </div>
            <div class="wa-conversas-lista" id="wa-conversas-lista">
                <div class="wa-lista-vazia">Carregando conversas...</div>
            </div>
        </aside>

        {{-- ============ Coluna 2: Thread ============ --}}
        <main class="wa-thread" id="wa-thread">
            <div class="wa-thread-vazia" id="wa-thread-vazia">
                <svg xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21" />
                </svg>
                <p>Selecione uma conversa para começar o atendimento</p>
            </div>

            <div class="wa-thread-conteudo" id="wa-thread-conteudo" style="display: none;">
                <header class="wa-thread-header">
                    <button type="button" class="wa-btn-icon wa-btn-voltar" id="wa-btn-voltar" title="Voltar">
                        <i class="ri-arrow-left-line"></i>
                    </button>
                    <div class="wa-thread-avatar" id="wa-thread-avatar"></div>
                    <div class="wa-thread-info">
                        <span class="wa-thread-nome" id="wa-thread-nome"></span>
                        <span class="wa-thread-numero" id="wa-thread-numero"></span>
                    </div>
                    <div class="wa-thread-acoes">
                        <button type="button" class="wa-btn-icon" id="wa-btn-painel-lead" title="Dados do cliente">
                            <i class="ri-user-3-line"></i>
                        </button>
                        <div class="dropdown" id="wa-menu-conversa" style="display: none;">
                            <button type="button" class="wa-btn-icon" data-bs-toggle="dropdown" aria-expanded="false" title="Mais opções">
                                <i class="ri-more-2-fill"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button type="button" class="dropdown-item" id="wa-btn-descartar-conversa">
                                        <i class="ri-archive-line me-2"></i>Descartar do funil
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item" id="wa-btn-restaurar-conversa" style="display: none;">
                                        <i class="ri-inbox-unarchive-line me-2"></i>Restaurar ao funil
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button type="button" class="dropdown-item" id="wa-btn-limpar-conversa">
                                        <i class="ri-eraser-line me-2"></i>Limpar conversa
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item text-danger" id="wa-btn-apagar-conversa">
                                        <i class="ri-delete-bin-6-line me-2"></i>Apagar conversa
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </header>

                {{-- Barra do lead — etapa, temperatura e ações rápidas sempre à vista --}}
                <div class="wa-lead-bar" id="wa-lead-bar" style="display: none;">
                    {{-- Com lead vinculado --}}
                    <div class="wa-lead-bar-com" id="wa-lead-bar-com" style="display: none;">
                        <div class="wa-lead-bar-esq">
                            <span class="wa-lead-bar-etapa" id="wa-lead-bar-etapa" title="Etapa no funil">
                                <i class="ri-flag-2-line"></i><span></span>
                            </span>
                            <div class="wa-temp-chips" id="wa-temp-chips">
                                <button type="button" class="wa-temp-chip temp-frio" data-temp="FRIO" title="Lead frio">
                                    <i class="ri-snowy-line"></i><span>Frio</span>
                                </button>
                                <button type="button" class="wa-temp-chip temp-morno" data-temp="MORNO" title="Lead morno">
                                    <i class="ri-sun-line"></i><span>Morno</span>
                                </button>
                                <button type="button" class="wa-temp-chip temp-quente" data-temp="QUENTE" title="Lead quente">
                                    <i class="ri-fire-line"></i><span>Quente</span>
                                </button>
                            </div>
                        </div>
                        <div class="wa-lead-bar-dir">
                            <a href="#" class="wa-lead-bar-btn venda" id="wa-btn-subir-venda-link">
                                <i class="ri-shopping-bag-3-line"></i><span>Subir venda</span>
                            </a>
                            <a href="#" class="wa-lead-bar-btn" id="wa-btn-abrir-cliente">
                                <i class="ri-external-link-line"></i><span>Abrir Cliente</span>
                            </a>
                        </div>
                    </div>

                    {{-- Sem lead vinculado --}}
                    <div class="wa-lead-bar-sem" id="wa-lead-bar-sem" style="display: none;">
                        <span class="wa-lead-bar-aviso">
                            <i class="ri-user-search-line"></i> Esta conversa ainda não está vinculada a um cliente
                        </span>
                        <button type="button" class="wa-lead-bar-btn vincular" id="wa-btn-vincular-lead-bar">
                            <i class="ri-link"></i><span>Vincular lead</span>
                        </button>
                    </div>
                </div>

                <div class="wa-mensagens" id="wa-mensagens">
                    <div class="wa-carregando-historico" id="wa-carregando-historico" style="display: none;">
                        <span class="spinner-border spinner-border-sm"></span>
                    </div>
                </div>

                {{-- Botão flutuante: novas mensagens abaixo do ponto de leitura --}}
                <button type="button" class="wa-btn-descer" id="wa-btn-descer" style="display: none;">
                    <i class="ri-arrow-down-line"></i> Novas mensagens
                </button>

                <footer class="wa-composer" id="wa-composer">
                    <label class="wa-btn-icon wa-btn-anexo" title="Anexar arquivo">
                        {{-- Clipe de anexo (estilo WhatsApp) --}}
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                            <path d="M1.816 15.556v.002c0 1.502.584 2.912 1.646 3.972s2.472 1.647 3.974 1.647a5.58 5.58 0 0 0 3.972-1.645l9.547-9.548c.769-.768 1.147-1.767 1.058-2.817-.079-.968-.548-1.927-1.319-2.698-1.594-1.592-4.068-1.711-5.517-.262l-7.916 7.915c-.881.881-.792 2.25.214 3.261.959.958 2.423 1.053 3.263.215l5.511-5.512c.28-.28.267-.722.053-.936l-.244-.244c-.191-.191-.567-.349-.957.04l-5.506 5.506c-.18.18-.635.127-.976-.214-.098-.097-.576-.613-.213-.973l7.915-7.917c.818-.817 2.267-.699 3.23.262.5.501.802 1.1.849 1.685.051.573-.156 1.111-.589 1.543l-9.547 9.549a3.97 3.97 0 0 1-2.829 1.171 3.975 3.975 0 0 1-2.83-1.173 3.973 3.973 0 0 1-1.172-2.828c0-1.071.415-2.076 1.172-2.83l7.209-7.211c.157-.157.264-.579.028-.814L11.5 4.36a.572.572 0 0 0-.834.018l-7.205 7.207a5.577 5.577 0 0 0-1.645 3.971z"/>
                        </svg>
                        <input type="file" id="wa-input-arquivo" hidden
                            accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx" />
                    </label>
                    <div class="wa-composer-input">
                        <textarea id="wa-input-texto" rows="1" placeholder="Digite uma mensagem"></textarea>
                    </div>
                    <button type="button" class="wa-btn-enviar" id="wa-btn-audio" title="Gravar áudio">
                        {{-- Microfone (estilo WhatsApp) --}}
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                            <path d="M11.999 14.942c2.001 0 3.531-1.53 3.531-3.531V4.35c0-2.001-1.53-3.531-3.531-3.531S8.469 2.35 8.469 4.35v7.061c0 2.001 1.53 3.531 3.53 3.531zm6.238-3.53c0 3.531-2.942 6.002-6.237 6.002s-6.237-2.471-6.237-6.002H3.761c0 4.001 3.178 7.297 7.061 7.885v3.884h2.354v-3.884c3.884-.588 7.061-3.884 7.061-7.885h-2z"/>
                        </svg>
                    </button>
                    <button type="button" class="wa-btn-enviar" id="wa-btn-enviar" title="Enviar" style="display: none;">
                        {{-- Avião de envio (estilo WhatsApp) --}}
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                            <path d="M1.101 21.757 23.8 12.028 1.101 2.3l.011 7.912 13.623 1.816-13.623 1.817-.011 7.912z"/>
                        </svg>
                    </button>
                </footer>

                <footer class="wa-composer wa-composer-gravando" id="wa-composer-gravando" style="display: none;">
                    <button type="button" class="wa-btn-icon wa-btn-cancelar-audio" id="wa-btn-cancelar-audio" title="Cancelar">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                    <div class="wa-gravando-indicador">
                        <span class="wa-gravando-dot"></span>
                        <span id="wa-gravando-tempo">0:00</span>
                    </div>
                    <button type="button" class="wa-btn-enviar" id="wa-btn-enviar-audio" title="Enviar áudio">
                        <i class="ri-send-plane-2-fill"></i>
                    </button>
                </footer>

                <div class="wa-composer wa-composer-leitura" id="wa-composer-leitura" style="display: none;">
                    <i class="ri-eye-line me-1"></i> Modo leitura — apenas o vendedor da conversa pode responder
                </div>
            </div>
        </main>

        {{-- ============ Coluna 3: Painel do lead ============ --}}
        <aside class="wa-painel-lead" id="wa-painel-lead" style="display: none;">
            <div class="wa-painel-header">
                <h6 id="wa-painel-titulo">Cliente</h6>
                <button type="button" class="wa-btn-icon" id="wa-btn-fechar-painel">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            {{-- Sem lead vinculado --}}
            <div class="wa-painel-sem-lead" id="wa-painel-sem-lead" style="display: none;">
                <div class="wa-sem-lead-icone">
                    <i class="ri-user-search-line"></i>
                </div>
                <p>Nenhum lead vinculado a esta conversa.</p>
                <button type="button" class="btn btn-sm btn-primary" id="wa-btn-vincular-lead">
                    <i class="ri-link me-1"></i> Vincular lead
                </button>
            </div>

            {{-- Lead vinculado — abas em altura total --}}
            <div class="wa-painel-com-lead" id="wa-painel-com-lead" style="display: none;">
                <div class="wa-painel-tabs">
                    <button type="button" class="wa-painel-tab" data-painel-tab="cliente">
                        <i class="ri-user-3-line"></i> Cliente
                    </button>
                    <button type="button" class="wa-painel-tab ativa" data-painel-tab="anotacoes">
                        <i class="ri-sticky-note-line"></i> Anotações <span class="wa-comentarios-count" id="wa-comentarios-count"></span>
                    </button>
                </div>

                {{-- Aba Cliente --}}
                <div class="wa-painel-tab-conteudo" data-painel-conteudo="cliente" style="display: none;">
                    <div class="wa-lead-nome" id="wa-lead-nome"></div>
                    <div class="wa-lead-dados" id="wa-lead-dados"></div>

                    {{-- Carteira: vendas realizadas --}}
                    <div class="wa-lead-secao" id="wa-lead-secao-vendas" style="display: none;">
                        <span class="wa-lead-secao-titulo">
                            <i class="ri-briefcase-4-line"></i> Vendas realizadas
                            <span class="wa-comentarios-count" id="wa-vendas-count"></span>
                        </span>
                        <div class="wa-vendas-lista" id="wa-vendas-lista"></div>
                    </div>

                    {{-- Dependentes do plano --}}
                    <div class="wa-lead-secao" id="wa-lead-secao-dependentes" style="display: none;">
                        <span class="wa-lead-secao-titulo">
                            <i class="ri-parent-line"></i> Dependentes
                            <span class="wa-comentarios-count" id="wa-dependentes-count"></span>
                        </span>
                        <div class="wa-dependentes-lista" id="wa-dependentes-lista"></div>
                    </div>

                    <div class="wa-lead-acoes">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="wa-btn-desvincular-lead">
                            <i class="ri-link-unlink me-1"></i> Desvincular cliente
                        </button>
                    </div>
                </div>

                {{-- Aba Anotações — timeline em altura total + composer fixo --}}
                <div class="wa-painel-tab-conteudo ativa" data-painel-conteudo="anotacoes">
                    <div class="wa-comentarios-lista" id="wa-comentarios-lista">
                        <div class="wa-lista-vazia">Carregando anotações...</div>
                    </div>
                    <div class="wa-comentario-composer" id="wa-comentario-composer" style="display: none;">
                        <textarea id="wa-comentario-texto" rows="2" placeholder="Atualize sua negociação..."></textarea>
                        <button type="button" class="btn btn-sm btn-primary w-100" id="wa-btn-comentar">
                            <i class="ri-chat-check-line me-1"></i> Salvar anotação
                        </button>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    {{-- Modal: vincular lead --}}
    <div class="modal fade" id="modalVincularLead" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-simple">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h4 class="mb-2">Vincular Lead</h4>
                        <p class="text-muted">Busque entre os seus clientes para vincular à conversa.</p>
                    </div>
                    <div class="form-floating form-floating-outline mb-3">
                        <input type="text" id="wa-vincular-busca" class="form-control" placeholder="Nome ou telefone" />
                        <label for="wa-vincular-busca">Nome ou telefone</label>
                    </div>
                    <div class="wa-vincular-resultados" id="wa-vincular-resultados"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: nova conversa --}}
    @if ($podeEnviar)
        <div class="modal fade" id="modalNovaConversa" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-simple">
                <div class="modal-content">
                    <div class="modal-body">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="text-center mb-4">
                            <h4 class="mb-2">Nova Conversa</h4>
                            <p class="text-muted">Informe o número do WhatsApp do cliente para iniciar o atendimento.</p>
                        </div>
                        <form id="wa-form-nova-conversa" class="row g-4">
                            <div class="col-12">
                                <div class="form-floating form-floating-outline">
                                    <input type="tel" id="wa-nova-numero" class="form-control" placeholder="(11) 98888-7777" required />
                                    <label for="wa-nova-numero">Número com DDD</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" id="wa-nova-nome" class="form-control" placeholder="Nome do cliente" />
                                    <label for="wa-nova-nome">Nome do cliente</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="wa-nova-criar-lead" checked />
                                    <label class="form-check-label" for="wa-nova-criar-lead">
                                        Criar lead no funil comercial com esse número
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success me-3" id="wa-nova-submit">
                                    <i class="ri-chat-new-line me-1"></i> Iniciar conversa
                                </button>
                                <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/toastr/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/whatsapp-chat.js'])
@endsection
