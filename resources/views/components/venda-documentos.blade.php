@props(['vendaId' => null])

<section class="vd-panel" data-venda-documentos @if($vendaId) data-venda-id="{{ $vendaId }}" @endif aria-labelledby="vd-titulo-{{ $vendaId ?? 'modal' }}">
    <div class="vd-header">
        <div class="vd-heading">
            <span class="vd-heading-icon" aria-hidden="true"><i class="ri-folder-upload-line"></i></span>
            <div>
            <h3 id="vd-titulo-{{ $vendaId ?? 'modal' }}">Documentos da venda</h3>
            <p>Acompanhe o envio e o processamento dos arquivos.</p>
            </div>
        </div>
        <span class="vd-summary" data-vd-summary>Pendente</span>
    </div>
    <div class="vd-path" data-vd-path hidden></div>
    <input type="file" accept="application/pdf,image/*" multiple class="visually-hidden" data-vd-input>
    <button type="button" class="vd-add" data-vd-add>
        <i class="ri-upload-cloud-2-line" aria-hidden="true"></i>
        Adicionar documentos
    </button>
    <p class="vd-rules"><i class="ri-information-line" aria-hidden="true"></i> PDF ou imagem · até 25 MB por arquivo · máximo de 30</p>
    <p class="vd-live" data-vd-live role="status" aria-live="polite"></p>
    <ul class="vd-list" data-vd-list aria-label="Documentos da venda"></ul>
    <div class="vd-empty" data-vd-empty>Nenhum documento enviado. A venda permanece com documentação pendente.</div>
</section>
