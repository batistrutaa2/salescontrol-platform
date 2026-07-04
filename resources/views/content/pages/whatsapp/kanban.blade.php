@extends('layouts/layoutMaster')

@section('title', 'WhatsApp - Funil de Conversas')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/jkanban/jkanban.scss', 'resources/assets/vendor/libs/toastr/toastr.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/app-kanban.scss', 'resources/assets/vendor/scss/pages/whatsapp-kanban.scss'])
@endsection

@section('content')
    <div class="wa-kanban-header">
        <div>
            <h4 class="wa-kanban-titulo">Funil de Conversas</h4>
            <span class="wa-kanban-subtitulo">Gerencie suas conversas de WhatsApp no mesmo funil do kanban comercial</span>
        </div>
        <div class="wa-kanban-header-acoes">
            @if ($podeConectar)
                {{-- Widget de conexão da instância --}}
                <div class="wa-conexao-widget" id="wa-conexao-widget"
                    data-status="{{ $statusConexao['status'] ?? 'SEM_INSTANCIA' }}">
                    <span class="wa-conexao-pill" id="wa-conexao-pill">
                        <span class="wa-conexao-dot"></span>
                        <span id="wa-conexao-label">...</span>
                        <span class="wa-conexao-numero" id="wa-conexao-numero"></span>
                    </span>
                    <button type="button" class="btn btn-sm btn-success" id="wa-btn-conectar">
                        <i class="ri-qr-code-line me-1"></i> Conectar
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="wa-btn-desconectar" style="display: none;">
                        <i class="ri-logout-box-line me-1"></i> Desconectar
                    </button>
                </div>
            @endif
            <a href="{{ route('whatsapp.chat') }}" class="btn btn-success">
                <i class="ri-chat-3-line me-1"></i> Abrir Conversas
            </a>
            <a href="{{ route('whatsapp.ajuda') }}" class="btn btn-icon btn-outline-secondary" title="Como usar / resolver problemas"
                onclick="if (window.mascote && window.mascote.tourWhatsapp) { window.mascote.tourWhatsapp(); return false; }">
                <i class="ri-question-line"></i>
            </a>
        </div>
    </div>

    @if ($podeConectar)
        {{-- Modal: QR code de conexão --}}
        <div class="modal fade" id="modalConexaoQr" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-simple modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="mb-4">
                            <h4 class="mb-2">Conectar WhatsApp</h4>
                            <p class="text-muted mb-0">
                                Abra o WhatsApp no celular &rarr; <strong>Aparelhos conectados</strong> &rarr;
                                <strong>Conectar aparelho</strong> e escaneie o código.
                            </p>
                        </div>
                        <div class="wa-qr-frame mx-auto" id="wa-qr-frame">
                            <div class="wa-qr-carregando" id="wa-qr-carregando">
                                <span class="spinner-border text-success"></span>
                            </div>
                            <img id="wa-qr-img" src="" alt="QR Code do WhatsApp" style="display: none;" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Tabs: Funil (negociações) | Carteira (clientes com venda) --}}
    <div class="wa-kb-tabs" id="wa-kb-tabs">
        <button type="button" class="wa-kb-tab ativa" data-aba="funil">
            <i class="ri-filter-3-line"></i> Funil
            <span class="wa-tab-badge" id="wa-kb-badge-unread" hidden>0</span>
        </button>
        <button type="button" class="wa-kb-tab" data-aba="carteira">
            <i class="ri-briefcase-4-line"></i> Carteira
            <span class="wa-kb-tab-count" id="wa-kb-carteira-count"></span>
        </button>
    </div>

    {{-- Grade da carteira (aparece na aba Carteira) --}}
    <div class="wa-carteira-grid" id="wa-carteira-grid" style="display: none;">
        <div class="wa-lista-vazia">Carregando carteira...</div>
    </div>

    <div class="app-kanban" id="wa-kanban-app">
        {{-- Filtros — mesmo padrão visual do kanban comercial --}}
        <div class="kanban-filters">
            <div class="filter-group" style="flex: 2;">
                <div class="form-floating form-floating-outline">
                    <input type="text" id="wa-kanban-search" class="form-control" placeholder="Pesquisar conversa..." />
                    <label for="wa-kanban-search">
                        <i class="ri-search-line me-1"></i>Pesquisar conversa...
                    </label>
                </div>
            </div>
        </div>

        <div class="kanban-wrapper"></div>
    </div>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/jkanban/jkanban.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/whatsapp-kanban.js'])
@endsection
