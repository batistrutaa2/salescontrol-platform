'use strict';

(function () {
    // ====== refs ======
    const $modal = $('#modal-demanda');
    const $form = $('#form-demanda');
    const $titulo = $('#titulo');
    const $descricao = $('#descricao');
    const $prioridade = $('#prioridade');
    const $assigned = $('#assigned_to');
    const $dataLimite = $('#data_limite');
    const $demandaId = $('#demanda_id');

    const $fStatus = $('#filtro-status');
    const $fPrio = $('#filtro-prioridade');
    const $fResp = $('#filtro-responsavel');
    const $fDateField = $('#filtro-data-field');
    const $fDateStart = $('#filtro-data-inicio');
    const $fDateEnd = $('#filtro-data-fim');

    const $cols = {
        'ABERTA': $(`.dm-dropzone[data-status="ABERTA"]`),
        'EM_ANDAMENTO': $(`.dm-dropzone[data-status="EM_ANDAMENTO"]`),
        'CONCLUIDA': $(`.dm-dropzone[data-status="CONCLUIDA"]`),
        'CANCELADA': $(`.dm-dropzone[data-status="CANCELADA"]`)
    };

    // ====== CSRF ======
    $.ajaxSetup({
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    });

    // ====== helpers ======
    const priorityConfig = {
        'ALTA': { class: 'priority-alta', label: 'Alta' },
        'MEDIA': { class: 'priority-media', label: 'Média' },
        'BAIXA': { class: 'priority-baixa', label: 'Baixa' }
    };

    function debounce(fn, wait = 300) {
        let t;
        return function (...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    function isOverdue(d) {
        if (!d?.data_limite) return false;
        if (d.status === 'CONCLUIDA' || d.status === 'CANCELADA') return false;
        const limite = moment(d.data_limite, 'YYYY-MM-DD').endOf('day');
        return moment().isAfter(limite);
    }

    function applyOverdueVisual($card, d) {
        $card.removeClass('dm-card-overdue');
        $card.find('.dm-overdue-badge').hide();

        if (isOverdue(d)) {
            $card.addClass('dm-card-overdue');
            $card.find('.dm-overdue-badge').show();
        }
    }

    // === Filtro por data (client-side) ===
    function passesStatusFilter(d) {
        const s = $fStatus.val();
        return !(s && s !== 'TODOS' && d.status !== s);
    }

    function passesDateFilter(d) {
        const field = $fDateField.val(); // 'limite' | 'criacao'
        const start = $fDateStart.val();
        const end = $fDateEnd.val();

        if (!start && !end) return true;

        let value;
        if (field === 'limite') {
            if (!d.data_limite) return false;
            value = moment(d.data_limite, 'YYYY-MM-DD');
        } else {
            if (!d.created_at) return false;
            value = moment(d.created_at, 'YYYY-MM-DD HH:mm:ss');
        }
        if (!value.isValid()) return true;

        if (start && value.isBefore(moment(start, 'YYYY-MM-DD').startOf('day'))) return false;
        if (end && value.isAfter(moment(end, 'YYYY-MM-DD').endOf('day'))) return false;
        return true;
    }

    // ====== select2 ======
    $('#assigned_to').select2({ width: '100%', dropdownParent: $('#modal-demanda') });
    $('#filtro-responsavel').select2({ width: 'resolve', dropdownParent: $('body') });

    // ====== helpers de escape ======
    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[m]));
    }

    // ====== renderização ======
    function createCard(d) {
        const due = d.data_limite ? moment(d.data_limite).format('DD/MM/YYYY') : '-';
        const isVendedor = d.origem === 'VENDEDOR';
        // Em solicitações de vendedor a pessoa relevante é quem abriu (o vendedor).
        const resp = isVendedor ? (d.criador || '-') : (d.responsavel || '-');
        const prio = priorityConfig[d.prioridade] || priorityConfig['MEDIA'];

        // Bloco extra das solicitações de vendedor: cliente + tipo.
        const vendorBlock = isVendedor ? `
                <div class="dm-vendor-info">
                    <span class="dm-vendor-cliente">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16"/><path d="M3 21h18"/>
                        </svg>
                        ${esc(d.cliente || 'Contrato')}${d.numero_proposta ? ` · #${esc(d.numero_proposta)}` : ''}
                    </span>
                    ${d.tipo_label ? `<span class="dm-vendor-tipo">${esc(d.tipo_label)}</span>` : ''}
                </div>` : '';

        // Vendedor: admin não edita/exclui a solicitação, só dá baixa.
        const actions = isVendedor ? `
                    <button class="dm-action-btn action-complete js-concluir" title="Concluir">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </button>` : `
                    <button class="dm-action-btn action-edit js-editar" title="Editar">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    <button class="dm-action-btn action-complete js-concluir" title="Concluir">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </button>
                    <button class="dm-action-btn action-delete js-excluir" title="Excluir">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                    </button>`;

        const $card = $(`
            <div class="dm-kanban-card ${isVendedor ? 'dm-card-vendedor' : ''}" draggable="true" data-id="${d.id}">
                <div class="dm-card-actions">${actions}</div>
                <div class="dm-card-header">
                    <h4 class="dm-card-title">${esc(d.titulo)}</h4>
                    <span class="dm-priority-badge ${prio.class}">${prio.label}</span>
                </div>
                ${vendorBlock}
                ${d.descricao ? `<p class="dm-card-description">${esc(d.descricao)}</p>` : ''}
                <div class="dm-card-meta">
                    <span class="dm-meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        ${esc(resp)}
                    </span>
                    <span class="dm-meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        ${due}
                    </span>
                    <span class="dm-overdue-badge" style="display: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        Atrasada
                    </span>
                </div>
            </div>
        `);

        $card.data('demanda', d);
        applyOverdueVisual($card, d);
        return $card;
    }

    function clearBoard() {
        Object.values($cols).forEach($c => $c.empty());
        Object.keys($cols).forEach(s => $(`#count-${s}`).text('0'));
        // Limpa stats
        $('#stat-aberta, #stat-andamento, #stat-concluida, #stat-cancelada').text('0');
    }

    function updateCounts() {
        const counts = {
            'ABERTA': $cols['ABERTA'].children('.dm-kanban-card').length,
            'EM_ANDAMENTO': $cols['EM_ANDAMENTO'].children('.dm-kanban-card').length,
            'CONCLUIDA': $cols['CONCLUIDA'].children('.dm-kanban-card').length,
            'CANCELADA': $cols['CANCELADA'].children('.dm-kanban-card').length
        };

        // Atualiza contadores das colunas
        Object.keys(counts).forEach(s => $(`#count-${s}`).text(counts[s]));

        // Atualiza cards de estatísticas
        $('#stat-aberta').text(counts['ABERTA']);
        $('#stat-andamento').text(counts['EM_ANDAMENTO']);
        $('#stat-concluida').text(counts['CONCLUIDA']);
        $('#stat-cancelada').text(counts['CANCELADA']);
    }

    function renderBoard(items) {
        clearBoard();
        items.forEach(d => {
            if (!passesStatusFilter(d)) return;
            if (!passesDateFilter(d)) return;

            const $card = createCard(d);
            bindCardEvents($card, d);
            $cols[d.status]?.append($card);
        });
        updateCounts();
    }

    // ====== Drag & Drop ======
    function bindDnD() {
        $(document).on('dragstart', '.dm-kanban-card', function (e) {
            e.originalEvent.dataTransfer.setData('text/plain', $(this).data('id'));
            $(this).addClass('dragging');
        });
        $(document).on('dragend', '.dm-kanban-card', function () {
            $(this).removeClass('dragging');
        });

        $('.dm-dropzone')
            .on('dragover', function (e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            })
            .on('dragleave', function () {
                $(this).removeClass('drag-over');
            })
            .on('drop', function (e) {
                e.preventDefault();
                $(this).removeClass('drag-over');

                const id = e.originalEvent.dataTransfer.getData('text/plain');
                const newStatus = $(this).data('status');

                $.ajax({
                    url: `/comercial/demandas/${id}/status`,
                    type: 'PATCH',
                    contentType: 'application/json',
                    data: JSON.stringify({ status: newStatus }),
                    success: () => {
                        toastr.success('Status atualizado.');
                        const $card = $(`.dm-kanban-card[data-id="${id}"]`);
                        const d = $card.data('demanda') || {};
                        d.status = newStatus;
                        $card.data('demanda', d);

                        if (!passesStatusFilter(d) || !passesDateFilter(d)) {
                            $card.remove();
                        } else {
                            $(e.currentTarget).append($card);
                            applyOverdueVisual($card, d);
                        }
                        updateCounts();
                    },
                    error: (xhr) => showAjaxError(xhr, 'Erro ao mover card.')
                });
            });
    }

    function bindCardEvents($card, data) {
        // Editar
        $card.find('.js-editar').on('click', function () {
            $demandaId.val(data.id);
            $titulo.val(data.titulo);
            $descricao.val(data.descricao || '');
            $prioridade.val(data.prioridade);

            if (typeof data.assigned_to_id !== 'undefined') {
                $assigned.val(data.assigned_to_id || '').trigger('change');
            } else {
                const $opt = $('#assigned_to option')
                    .filter(function () { return $(this).text() === (data.responsavel || ''); })
                    .first();
                $assigned.val($opt.val() || '').trigger('change');
            }

            $dataLimite.val(data.data_limite || '');
            $modal.find('.modal-title').text(`Editar Demanda #${data.id}`);
            $modal.modal('show');
        });

        // Concluir
        $card.find('.js-concluir').on('click', function () {
            $.ajax({
                url: `/comercial/demandas/${data.id}/status`,
                type: 'PATCH',
                contentType: 'application/json',
                data: JSON.stringify({ status: 'CONCLUIDA' }),
                success: () => {
                    toastr.success('Demanda concluída.');
                    const d = $card.data('demanda') || data;
                    d.status = 'CONCLUIDA';
                    $card.data('demanda', d);

                    if (!passesStatusFilter(d) || !passesDateFilter(d)) {
                        $card.remove();
                    } else {
                        $cols['CONCLUIDA'].append($card);
                        applyOverdueVisual($card, d);
                    }
                    updateCounts();
                },
                error: (xhr) => showAjaxError(xhr, 'Erro ao atualizar status.')
            });
        });

        // Excluir
        $card.find('.js-excluir').on('click', function () {
            if (!confirm('Excluir esta demanda?')) return;
            $.ajax({
                url: `/comercial/demandas/${data.id}`,
                type: 'DELETE',
                success: () => {
                    toastr.success('Demanda excluída.');
                    $card.remove();
                    updateCounts();
                },
                error: (xhr) => showAjaxError(xhr, 'Erro ao excluir.')
            });
        });
    }

    // ====== aba ativa (origem) ======
    let currentOrigem = 'INTERNA';

    // ====== carregar lista ======
    function loadBoard() {
        const params = {
            origem: currentOrigem,
            prioridade: $fPrio.val(),
            assigned_to: $fResp.val()
        };
        if ($fStatus.val() && $fStatus.val() !== 'TODOS') params.status = $fStatus.val();

        $.get('/comercial/demandas/list', params, function (res) {
            const items = Array.isArray(res?.data) ? res.data : [];
            renderBoard(items);
        }).fail((xhr) => showAjaxError(xhr, 'Não foi possível carregar as demandas.'));
    }

    // ====== util erro ======
    function showAjaxError(xhr, fallback) {
        const status = xhr?.status;
        const data = xhr?.responseJSON || {};
        const firstError = data?.errors ? Object.values(data.errors).flat()[0] : null;

        if (status === 419) toastr.error('Sessão expirada (CSRF). Recarregue a página.');
        else if (status === 422 && firstError) toastr.error(`Erro de validação: ${firstError}`);
        else toastr.error(`${fallback}${data?.message ? ` (${data.message})` : ''}`);
        console.error('AJAX error:', { status, data, xhr });
    }

    // ====== init ======
    bindDnD();

    // Abas: Internas x Demanda vendedores
    $('.dm-tab').on('click', function () {
        const origem = $(this).data('origem');
        if (origem === currentOrigem) return;
        currentOrigem = origem;
        $('.dm-tab').removeClass('is-active');
        $(this).addClass('is-active');
        // Admin não cria/edita solicitação de vendedor — só dá baixa.
        $('#btn-nova').toggle(origem === 'INTERNA');
        loadBoard();
    });

    // Filtros
    const debouncedLoad = debounce(loadBoard, 250);
    [$fStatus, $fPrio].forEach($el => $el.on('change', loadBoard));
    $fResp.on('change', loadBoard);
    $fDateField.on('change', debouncedLoad);
    $fDateStart.on('input change', debouncedLoad);
    $fDateEnd.on('input change', debouncedLoad);

    $('#btn-clear-dates').on('click', function () {
        $fDateStart.val('');
        $fDateEnd.val('');
        loadBoard();
    });

    // Nova demanda
    $('#btn-nova').on('click', () => {
        $form[0].reset();
        $demandaId.val('');
        $assigned.val('').trigger('change');
        $prioridade.val('MEDIA');
        $modal.find('.modal-title').text('Nova Demanda');
        $modal.modal('show');
        setTimeout(() => $titulo.trigger('focus'), 120);
    });

    // Submit
    $form.on('submit', function (e) {
        e.preventDefault();

        const payload = {
            titulo: $titulo.val(),
            descricao: $descricao.val(),
            prioridade: $prioridade.val(),
            assigned_to: $assigned.val() || null,
            data_limite: $dataLimite.val() || null
        };

        const id = $demandaId.val();
        const isEdit = !!id;

        $.ajax({
            url: isEdit ? `/comercial/demandas/${id}` : '/comercial/demandas',
            type: isEdit ? 'PUT' : 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: () => {
                toastr.success(isEdit ? 'Demanda atualizada.' : 'Demanda criada.');
                $modal.modal('hide');
                loadBoard();
            },
            error: (xhr) => showAjaxError(xhr, 'Erro ao salvar. Verifique os dados.')
        });
    });

    loadBoard();
})();
