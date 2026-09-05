@extends('layouts/layoutMaster')

@section('title', 'Enviar Cotação por E-mail')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/quill/typography.scss',
        'resources/assets/vendor/libs/quill/editor.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
    ])
@endsection

@section('page-style')
    @vite([
        'resources/assets/vendor/scss/pages/pos-venda.scss',
        'resources/assets/vendor/scss/pages/envio-cotacao.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/quill/quill.js',
    ])
@endsection

@section('page-script')
    @vite('resources/assets/js/envio-cotacao.js')
@endsection

@section('content')
<div class="ec-page">

    @php
        $configEnvioCotacao = [
            'urlEnviar' => route('comercial.envioCotacao.enviar'),
            'vendedorNome' => $vendedorNome,
            'vendedorEmail' => $vendedorEmail,
            'vendedorWhatsapp' => $vendedorWhatsapp,
            'dominioPermitido' => $dominioPermitido,
            'emailValido' => $emailValido,
        ];
        $inicial = strtoupper(mb_substr(trim($vendedorNome), 0, 1) ?: 'S');
        $iniciaisEmpresa = collect(preg_split('/\s+/', trim($nomeEmpresa)))->filter()->take(2)->map(fn ($parte) => mb_substr($parte, 0, 1))->implode('');
    @endphp
    <script>window.envioCotacao = @json($configEnvioCotacao);</script>

    {{-- ===================== TOP BAR ===================== --}}
    <header class="ec-topbar">
        <div class="ec-topbar-left">
            <span class="ec-title-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </span>
            <div class="ec-title-text">
                <h4>Enviar Cotação</h4>
                <span>Componha, anexe o PDF e envie do seu próprio e-mail</span>
            </div>
        </div>

        <div class="ec-topbar-right">
            <div class="ec-from-chip" title="Remetente (você)">
                <span class="ec-from-avatar">{{ $inicial }}</span>
                <div class="ec-from-info">
                    <span class="ec-from-name">{{ $vendedorNome }}</span>
                    <span class="ec-from-email">{{ $vendedorEmail }}</span>
                </div>
            </div>
            <button type="button" class="ec-btn-primary" id="ec-btn-enviar" {{ $emailValido ? '' : 'disabled' }}>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                <span>Enviar Cotação</span>
            </button>
        </div>
    </header>

    @unless($emailValido)
        <div class="ec-block-alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div>
                <strong>Envio bloqueado.</strong>
                @if($dominioPermitido)
                    Seu e-mail de acesso (<code>{{ $vendedorEmail }}</code>) não pertence ao domínio corporativo <strong>&#64;{{ $dominioPermitido }}</strong>.
                @else
                    A empresa ativa ainda não possui um e-mail corporativo válido configurado.
                @endif
                Procure o administrador da empresa.
            </div>
        </div>
    @endunless

    {{-- ===================== STUDIO (2 painéis conectados) ===================== --}}
    <div class="ec-studio">

        {{-- ---------- Painel de composição ---------- --}}
        <section class="ec-pane ec-compose">
            <div class="ec-pane-head">
                <span class="ec-pane-tag">Compor mensagem</span>
            </div>

            <div class="ec-compose-body">
                {{-- Destinatários --}}
                <div class="ec-field">
                    <label class="ec-label" for="ec-destinatarios">Para</label>
                    <input type="text" class="ec-input" id="ec-destinatarios" autocomplete="off" placeholder="cliente@email.com, outro@email.com">
                    <span class="ec-hint">Separe múltiplos e-mails por vírgula.</span>
                </div>

                {{-- Assunto --}}
                <div class="ec-field">
                    <label class="ec-label" for="ec-assunto">Assunto</label>
                    <input type="text" class="ec-input" id="ec-assunto" maxlength="200" placeholder="Ex: Sua cotação de plano de saúde">
                </div>

                {{-- Mensagem (Quill) --}}
                <div class="ec-field ec-field-grow">
                    <label class="ec-label">Mensagem</label>
                    <div class="ec-editor-wrap">
                        <div id="ec-toolbar" class="ec-toolbar">
                            <span class="ql-formats">
                                <button class="ql-bold"></button>
                                <button class="ql-italic"></button>
                                <button class="ql-underline"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-list" value="ordered"></button>
                                <button class="ql-list" value="bullet"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-link"></button>
                                <button class="ql-clean"></button>
                            </span>
                        </div>
                        <div id="ec-editor" class="ec-editor"></div>
                    </div>
                </div>

                {{-- Anexo PDF --}}
                <div class="ec-field">
                    <label class="ec-label">Anexo (PDF da cotação)</label>
                    <label class="ec-dropzone" id="ec-dropzone">
                        <input type="file" id="ec-anexo" accept="application/pdf" hidden>
                        <div class="ec-dropzone-empty" id="ec-dropzone-empty">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <span>Arraste o PDF aqui ou <em>clique para selecionar</em></span>
                            <small>até 10 MB</small>
                        </div>
                        <div class="ec-dropzone-file d-none" id="ec-dropzone-file">
                            <span class="ec-file-badge">PDF</span>
                            <div class="ec-file-info">
                                <span class="ec-file-name" id="ec-file-name"></span>
                                <span class="ec-file-size" id="ec-file-size"></span>
                            </div>
                            <button type="button" class="ec-file-remove" id="ec-file-remove" title="Remover">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    </label>
                </div>
            </div>
        </section>

        {{-- ---------- Painel de preview ---------- --}}
        <aside class="ec-pane ec-preview">
            <div class="ec-pane-head">
                <span class="ec-pane-tag">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    Como o cliente vai ver
                </span>
            </div>

            <div class="ec-preview-stage">
                <div class="ec-preview-frame">
                    {{-- Header do e-mail --}}
                    <div class="ec-mail-header">
                        <span class="ec-mail-logo">{{ $iniciaisEmpresa ?: 'SC' }}</span>
                        <span class="ec-mail-brand">{{ $nomeEmpresa }}</span>
                    </div>
                    {{-- Corpo --}}
                    <div class="ec-mail-body" id="ec-preview-body">
                        <p class="ec-preview-placeholder">A mensagem aparecerá aqui conforme você digita…</p>
                    </div>
                    {{-- Anexo --}}
                    <div class="ec-mail-attach d-none" id="ec-preview-attach">
                        <span class="ec-mail-attach-badge">PDF</span>
                        <span class="ec-mail-attach-name" id="ec-preview-attach-name"></span>
                    </div>
                    {{-- Assinatura --}}
                    <div class="ec-mail-signature">
                        <span class="ec-sign-avatar">{{ $inicial }}</span>
                        <div class="ec-sign-info">
                            <span class="ec-sign-name">{{ $vendedorNome }}</span>
                            <span class="ec-sign-role">Consultor(a) de Benefícios · {{ $nomeEmpresa }}</span>
                            <span class="ec-sign-contact">✉ {{ $vendedorEmail }}</span>
                            @if($vendedorWhatsapp)
                                <span class="ec-sign-contact">📱 {{ $vendedorWhatsapp }}</span>
                            @endif
                        </div>
                    </div>
                    {{-- Footer --}}
                    <div class="ec-mail-footer">
                        © {{ date('Y') }} {{ $nomeEmpresa }}. Todos os direitos reservados.
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
