'use strict';

/**
 * Tela do vendedor para abrir e acompanhar solicitações de pós-venda sobre os
 * contratos dele em implantação ou já implantados. Inclui um onboarding (tour spotlight próprio,
 * mesmo padrão do mascote) exibido no primeiro acesso — reutilizável em features
 * futuras via a flag de localStorage.
 */
(function () {
    const $page = $('.mdv-page');
    if (!$page.length) return;

    const URL_LIST = $page.data('list');
    const URL_CONTRATOS = $page.data('contratos');
    const URL_STORE = $page.data('store');
    const TOUR_KEY = 'onboarding:demandasVendedor';

    const $modal = $('#mdv-modal');
    const $form = $('#mdv-form');
    const $venda = $('#mdv-venda');
    const $tipo = $('#mdv-tipo');
    const $titulo = $('#mdv-titulo');
    const $descricao = $('#mdv-descricao');
    const $lista = $('#mdv-lista');
    const $empty = $('#mdv-empty');
    const $count = $('#mdv-count');

    // ====== CSRF ======
    $.ajaxSetup({
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    });

    // ====== helpers ======
    const STATUS = {
        'ABERTA': { label: 'Aguardando', cls: 'is-aberta' },
        'EM_ANDAMENTO': { label: 'Em andamento', cls: 'is-andamento' },
        'CONCLUIDA': { label: 'Concluída', cls: 'is-concluida' },
        'CANCELADA': { label: 'Cancelada', cls: 'is-cancelada' }
    };

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[m]));
    }

    function fmtData(s) {
        if (!s) return '';
        const m = moment(s, 'YYYY-MM-DD HH:mm:ss');
        return m.isValid() ? m.format('DD/MM/YYYY') : '';
    }

    function showAjaxError(xhr, fallback) {
        const data = xhr?.responseJSON || {};
        const firstError = data?.errors ? Object.values(data.errors).flat()[0] : null;
        if (xhr?.status === 419) toastr.error('Sessão expirada. Recarregue a página.');
        else if (firstError) toastr.error(firstError);
        else toastr.error(`${fallback}${data?.message ? ` (${data.message})` : ''}`);
    }

    // ====== select2: contrato (autocomplete remoto) ======
    $venda.select2({
        width: '100%',
        dropdownParent: $modal,
        placeholder: 'Busque o contrato em implantação ou implantado…',
        minimumInputLength: 0,
        ajax: {
            url: URL_CONTRATOS,
            dataType: 'json',
            delay: 250,
            data: params => ({ termo: params.term || '' }),
            processResults: res => ({
                results: (res?.data || []).map(c => ({
                    id: c.venda_id,
                    text: `${c.nome_contrato || 'Sem nome'}${c.numero_proposta ? ' · #' + c.numero_proposta : ''}${c.operadora ? ' · ' + c.operadora : ''}`
                }))
            })
        }
    });

    $tipo.select2({ width: '100%', dropdownParent: $modal, minimumResultsForSearch: Infinity });

    // Prefill do título com o label do tipo, se ainda vazio.
    $tipo.on('select2:select', function (e) {
        const label = e.params?.data?.text || '';
        if (label && !$titulo.val().trim()) $titulo.val(label);
    });

    // ====== lista ======
    function updatesHtml(d) {
        const items = Array.isArray(d.atualizacoes) ? d.atualizacoes : [];
        if (!items.length) return '';

        return `
            <div class="mdv-card-updates">
                <div class="mdv-card-updates-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Atualizações do pós-venda
                </div>
                ${items.map(a => `
                    <div class="mdv-update">
                        <div class="mdv-update-texto">${escapeHtml(a.texto)}</div>
                        <div class="mdv-update-meta">${escapeHtml(a.autor)}${a.data ? ' · ' + escapeHtml(a.data) : ''}</div>
                    </div>`).join('')}
            </div>`;
    }

    function cardHtml(d) {
        const st = STATUS[d.status] || STATUS['ABERTA'];
        const cliente = escapeHtml(d.cliente || 'Contrato');
        const proposta = d.numero_proposta ? `<span class="mdv-card-prop">#${escapeHtml(d.numero_proposta)}</span>` : '';
        const tipoLabel = escapeHtml(d.tipo_label || d.titulo);
        const desc = d.descricao ? `<p class="mdv-card-desc">${escapeHtml(d.descricao)}</p>` : '';
        const concl = d.status === 'CONCLUIDA' && d.concluida_em
            ? `<span class="mdv-card-meta-item">Concluída em ${fmtData(d.concluida_em)}</span>` : '';

        // Em aberto, o badge acompanha a etapa do fluxo da central (Documentação,
        // Em andamento...). Na etapa inicial "Aberta" mantém o "Aguardando".
        const emEtapaAvancada = d.status === 'ABERTA' && d.etapa && d.etapa.toUpperCase() !== 'ABERTA';
        const badgeCls = emEtapaAvancada ? 'is-andamento' : st.cls;
        const badgeLabel = emEtapaAvancada ? escapeHtml(d.etapa) : st.label;
        const prazo = d.status === 'ABERTA' && d.data_limite
            ? `<span class="mdv-card-meta-item">Prazo: ${escapeHtml(d.data_limite)}</span>` : '';

        // Chamado aberto pelo pós-venda em contrato do vendedor: ele acompanha do mesmo jeito.
        const origem = d.origem && d.origem !== 'VENDEDOR'
            ? '<span class="mdv-card-meta-item mdv-card-origem">Aberta pelo pós-venda</span>' : '';

        return `
            <div class="mdv-card ${badgeCls}">
                <div class="mdv-card-top">
                    <span class="mdv-card-tipo">${tipoLabel}</span>
                    <span class="mdv-status-badge ${badgeCls}">${badgeLabel}</span>
                </div>
                <div class="mdv-card-cliente">${cliente} ${proposta}</div>
                <div class="mdv-card-titulo">${escapeHtml(d.titulo)}</div>
                ${desc}
                ${updatesHtml(d)}
                <div class="mdv-card-meta">
                    <span class="mdv-card-meta-item">Aberta em ${fmtData(d.created_at)}</span>
                    ${origem}
                    ${prazo}
                    ${concl}
                </div>
            </div>`;
    }

    function loadList() {
        $.get(URL_LIST, function (res) {
            const items = Array.isArray(res?.data) ? res.data : [];
            $count.text(items.length);
            if (!items.length) {
                $lista.empty();
                $empty.prop('hidden', false);
                return;
            }
            $empty.prop('hidden', true);
            $lista.html(items.map(cardHtml).join(''));
        }).fail(xhr => showAjaxError(xhr, 'Não foi possível carregar suas solicitações.'));
    }

    // ====== modal nova solicitação ======
    function abrirModal() {
        $form[0].reset();
        $venda.val(null).trigger('change');
        $tipo.val('').trigger('change');
        $modal.modal('show');
    }

    $('#mdv-nova').on('click', abrirModal);

    $form.on('submit', function (e) {
        e.preventDefault();

        const payload = {
            venda_id: $venda.val(),
            tipo: $tipo.val(),
            titulo: $titulo.val().trim(),
            descricao: $descricao.val().trim() || null
        };

        if (!payload.venda_id) { toastr.warning('Selecione o contrato.'); return; }
        if (!payload.tipo) { toastr.warning('Selecione o tipo de demanda.'); return; }
        if (!payload.titulo) { toastr.warning('Informe um título.'); return; }

        $.ajax({
            url: URL_STORE,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: () => {
                toastr.success('Solicitação enviada ao pós-venda!');
                $modal.modal('hide');
                loadList();
            },
            error: xhr => showAjaxError(xhr, 'Erro ao enviar a solicitação.')
        });
    });

    // ====== Onboarding (tour spotlight reutilizável) ======
    const PASSOS = [
        {
            target: null,
            titulo: 'Adeus, WhatsApp! 👋',
            texto: 'Agora os pedidos de pós-venda (boleto, portabilidade, cancelamento, reembolso…) saem daqui — com prazo e acompanhamento, sem se perder em mensagens.'
        },
        {
            target: '[data-tour="nova"]',
            titulo: 'Abra uma solicitação',
            texto: 'Clique aqui para abrir uma demanda. Você escolhe um dos seus contratos em implantação ou já implantados e o que precisa que o administrativo faça.'
        },
        {
            target: '[data-tour="lista"]',
            titulo: 'Acompanhe o andamento',
            texto: 'Suas solicitações aparecem aqui com o status. Quando o pós-venda concluir, o assistente te avisa na hora.'
        }
    ];

    let passoAtual = 0;
    const $tour = $(`
        <div class="mdv-tour" hidden>
            <div class="mdv-tour-overlay"></div>
            <div class="mdv-tour-spot" hidden></div>
            <div class="mdv-tour-card" role="dialog" aria-label="Como funciona">
                <span class="mdv-tour-prog"></span>
                <h4 class="mdv-tour-titulo"></h4>
                <p class="mdv-tour-texto"></p>
                <div class="mdv-tour-acoes">
                    <button type="button" class="mdv-tour-pular">Pular</button>
                    <button type="button" class="mdv-btn mdv-btn-primary mdv-tour-next">Próximo</button>
                </div>
            </div>
        </div>`).appendTo('body');

    function posicionarSpot($target) {
        const $spot = $tour.find('.mdv-tour-spot');
        const $card = $tour.find('.mdv-tour-card');
        if (!$target || !$target.length) {
            $spot.prop('hidden', true);
            $card.css({ top: '50%', left: '50%', transform: 'translate(-50%, -50%)' });
            return;
        }
        const r = $target[0].getBoundingClientRect();
        const pad = 8;
        $spot.prop('hidden', false).css({
            top: r.top - pad + window.scrollY,
            left: r.left - pad + window.scrollX,
            width: r.width + pad * 2,
            height: r.height + pad * 2
        });
        // Card abaixo do alvo (ou acima, se não couber).
        const espacoAbaixo = window.innerHeight - r.bottom;
        const top = espacoAbaixo > 200 ? r.bottom + 14 + window.scrollY : r.top - 14 + window.scrollY;
        const transform = espacoAbaixo > 200 ? 'none' : 'translateY(-100%)';
        $card.css({
            top: top,
            left: Math.max(16, Math.min(r.left + window.scrollX, window.innerWidth - 360)),
            transform: transform
        });
    }

    function renderPasso() {
        const p = PASSOS[passoAtual];
        const $target = p.target ? $(p.target) : null;
        if ($target && $target.length) {
            $target[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        $tour.find('.mdv-tour-titulo').text(p.titulo);
        $tour.find('.mdv-tour-texto').text(p.texto);
        $tour.find('.mdv-tour-prog').text(`${passoAtual + 1} de ${PASSOS.length}`);
        $tour.find('.mdv-tour-next').text(passoAtual === PASSOS.length - 1 ? 'Entendi!' : 'Próximo');
        setTimeout(() => posicionarSpot($target), 220);
    }

    function abrirTour() {
        passoAtual = 0;
        $tour.prop('hidden', false);
        renderPasso();
    }

    function fecharTour() {
        $tour.prop('hidden', true);
        try { localStorage.setItem(TOUR_KEY, '1'); } catch (e) {}
    }

    $tour.find('.mdv-tour-next').on('click', () => {
        if (passoAtual === PASSOS.length - 1) { fecharTour(); return; }
        passoAtual++;
        renderPasso();
    });
    $tour.find('.mdv-tour-pular, .mdv-tour-overlay').on('click', fecharTour);
    $(window).on('resize', () => { if (!$tour.prop('hidden')) renderPasso(); });

    $('#mdv-rever-tour').on('click', abrirTour);

    // ====== init ======
    loadList();

    let jaViu = false;
    try { jaViu = localStorage.getItem(TOUR_KEY) === '1'; } catch (e) {}
    if (!jaViu) setTimeout(abrirTour, 600);
})();
