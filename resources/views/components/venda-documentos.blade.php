@props([
    'vendaId' => null,
    'vendaNome' => null,
    'presentation' => 'inline',
])

@php
    $isModal = $presentation === 'modal';
    $instance = $vendaId ? 'venda-' . $vendaId : 'venda-dinamica';
    $titleId = 'vd-titulo-' . $instance;
    $rulesId = 'vd-regras-' . $instance;
    $liveId = 'vd-status-' . $instance;
    $modalId = 'vd-modal-' . $instance;
@endphp

@if ($isModal)
<div class="vd-shell" data-venda-documentos @if($vendaId) data-venda-id="{{ $vendaId }}" @endif>
    <button type="button" class="vd-launcher" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}"
        aria-haspopup="dialog" aria-controls="{{ $modalId }}">
        <span class="vd-launcher-icon" aria-hidden="true"><i class="ri-attachment-2"></i></span>
        <span class="vd-launcher-copy">
            <strong>Documentos</strong>
            <span data-vd-launcher-count>Carregando arquivos…</span>
        </span>
        <span class="vd-launcher-status" data-vd-launcher-status data-status="PENDENTE">Pendente</span>
        <span class="vd-launcher-action">Gerenciar documentos <i class="ri-arrow-right-s-line" aria-hidden="true"></i></span>
    </button>

    <div class="modal fade vd-modal" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $titleId }}"
        aria-describedby="{{ $rulesId }}" aria-hidden="true" data-vd-modal>
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-md-down">
            <div class="modal-content">
                <div class="vd-modal-header">
                    <div class="vd-modal-heading">
                        <span class="vd-heading-icon" aria-hidden="true"><i class="ri-folder-upload-line"></i></span>
                        <div>
                            <h2 id="{{ $titleId }}" tabindex="-1">Documentos da venda</h2>
                            <p>
                                Venda #{{ $vendaId }}
                                @if($vendaNome)
                                    <span aria-hidden="true">·</span> {{ $vendaNome }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="vd-modal-tools">
                        <span class="vd-summary" data-vd-summary data-status="PENDENTE">Pendente</span>
                        <button type="button" class="vd-modal-close" data-bs-dismiss="modal" aria-label="Fechar documentos da venda">
                            <i class="ri-close-line" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="modal-body vd-modal-body">
                    <section class="vd-panel" aria-labelledby="{{ $titleId }}">
@else
<section class="vd-panel" data-venda-documentos @if($vendaId) data-venda-id="{{ $vendaId }}" @endif aria-labelledby="{{ $titleId }}">
    <div class="vd-header">
        <div class="vd-heading">
            <span class="vd-heading-icon" aria-hidden="true"><i class="ri-folder-upload-line"></i></span>
            <div>
                <h3 id="{{ $titleId }}">Documentos da venda</h3>
                <p>Acompanhe o envio e o processamento dos arquivos.</p>
            </div>
        </div>
        <span class="vd-summary" data-vd-summary data-status="PENDENTE">Pendente</span>
    </div>
@endif

                        <div class="vd-path" data-vd-path hidden></div>
                        <input type="file" accept="application/pdf,image/*" multiple class="visually-hidden" data-vd-input
                            aria-describedby="{{ $rulesId }} {{ $liveId }}">

                        <div class="vd-dropzone" data-vd-dropzone>
                            <span class="vd-dropzone-icon" aria-hidden="true"><i class="ri-upload-cloud-2-line"></i></span>
                            <div class="vd-dropzone-copy">
                                <strong>Importe novos documentos</strong>
                                <span>Arraste os arquivos para esta área ou selecione no computador.</span>
                            </div>
                            <button type="button" class="vd-add" data-vd-add>Selecionar arquivos</button>
                        </div>
                        <p class="vd-rules" id="{{ $rulesId }}">
                            <i class="ri-information-line" aria-hidden="true"></i>
                            PDF ou imagem · até 25 MB por arquivo · máximo de 30
                        </p>
                        <p class="vd-live" id="{{ $liveId }}" data-vd-live role="status" aria-live="polite" aria-atomic="true"></p>

                        <div class="vd-loading" data-vd-loading role="status">
                            <span class="vd-spinner" aria-hidden="true"></span>
                            <span>Carregando documentos…</span>
                        </div>

                        <div class="vd-load-error" data-vd-load-error role="alert" hidden>
                            <span class="vd-load-error-icon" aria-hidden="true"><i class="ri-wifi-off-line"></i></span>
                            <div>
                                <strong>Não foi possível carregar os documentos</strong>
                                <p data-vd-load-error-message>Verifique a conexão e tente novamente.</p>
                            </div>
                            <button type="button" data-vd-reload>Tentar novamente</button>
                        </div>

                        <div class="vd-list-shell" data-vd-list-shell hidden>
                            <div class="vd-list-header">
                                <h4>Arquivos enviados</h4>
                                <span data-vd-list-count>0 documentos</span>
                            </div>
                            <ul class="vd-list" data-vd-list aria-label="Documentos da venda"></ul>
                            <div class="vd-empty" data-vd-empty hidden>
                                <span aria-hidden="true"><i class="ri-file-add-line"></i></span>
                                <strong>Nenhum documento enviado</strong>
                                <p>Use a área acima para adicionar os primeiros arquivos desta venda.</p>
                            </div>
                        </div>

@if ($isModal)
                    </section>
                </div>
                <div class="vd-modal-footer">
                    <span>Os envios continuam mesmo se esta janela for fechada.</span>
                    <button type="button" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</div>
@else
</section>
@endif
