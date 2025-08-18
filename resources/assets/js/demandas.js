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

    // serão criados via JS
    let $fDateField, $fDateStart, $fDateEnd;

    const $cols = {
        'ABERTA': $(`.kanban-dropzone[data-status="ABERTA"]`),
        'EM_ANDAMENTO': $(`.kanban-dropzone[data-status="EM_ANDAMENTO"]`),
        'CONCLUIDA': $(`.kanban-dropzone[data-status="CONCLUIDA"]`),
        'CANCELADA': $(`.kanban-dropzone[data-status="CANCELADA"]`)
    };

    // ====== CSRF ======
    $.ajaxSetup({
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    });

    // ====== helpers ======
    const badgeStatus = (s) => {
        const map = {
            'ABERTA': 'bg-label-primary',
            'EM_ANDAMENTO': 'bg-label-warning',
            'CONCLUIDA': 'bg-label-success',
            'CANCELADA': 'bg-label-secondary'
        };
        return `<span class="badge ${map[s] || 'bg-label-primary'}">${s.replace('_', ' ')}</span>`;
    };

    const badgePrioridade = (p) => {
        const map = { 'ALTA': 'bg-label-danger', 'MEDIA': 'bg-label-info', 'BAIXA': 'bg-label-secondary' };
        return `<span class="badge ${map[p] || 'bg-label-info'}">${p}</span>`;
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
        $card.removeClass('kanban-overdue');
        $card.find('.overdue-badge').remove();

        if (isOverdue(d)) {
            $card.addClass('kanban-overdue');
            const days = Math.max(1, moment().diff(moment(d.data_limite, 'YYYY-MM-DD'), 'days'));
            const $badge = $(
                `<span class="badge bg-danger ms-2 overdue-badge" title="Atrasada ${days} dia(s)">
           <i class="ri-time-line me-1"></i>Atrasada
         </span>`
            );
            $card.find('.right-badges').append($badge);
        }
    }

    // === Filtro por data (client-side) ===
    function injectDateFilters() {
        const $hdr = $('.card-header');
        const html = `
      <div class="d-flex align-items-center gap-2 ms-auto" id="date-filter-wrapper">
        <select id="filtro-data-field" class="form-select" style="min-width:170px">
          <option value="limite">Por data limite</option>
          <option value="criacao">Por data de criação</option>
        </select>
        <input id="filtro-data-inicio" class="form-control" type="date" style="min-width:150px" placeholder="Início">
        <input id="filtro-data-fim" class="form-control" type="date" style="min-width:150px" placeholder="Fim">
        <button id="btn-clear-dates" type="button" class="btn btn-outline-secondary">
          <i class="ri-close-line"></i>
        </button>
      </div>
    `;
        // evita duplicar
        if (!$('#date-filter-wrapper').length) $hdr.append(html);

        $fDateField = $('#filtro-data-field');
        $fDateStart = $('#filtro-data-inicio');
        $fDateEnd = $('#filtro-data-fim');

        const trigger = debounce(loadBoard, 250);
        $fDateField.on('change', trigger);
        $fDateStart.on('input change', trigger);
        $fDateEnd.on('input change', trigger);

        $('#btn-clear-dates').on('click', function () {
            $fDateStart.val('');
            $fDateEnd.val('');
            loadBoard();
        });
    }

    function passesStatusFilter(d) {
        const s = $fStatus.val();
        return !(s && s !== 'TODOS' && d.status !== s);
    }

    function passesDateFilter(d) {
        if (!$fDateField || !$fDateStart || !$fDateEnd) return true;

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
            // back costuma enviar 'YYYY-MM-DD HH:mm:ss'
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

    // ====== renderização ======
    function createCard(d) {
        const due = d.data_limite ? moment(d.data_limite).format('DD/MM/YYYY') : '-';
        const resp = d.responsavel || '-';

        const $card = $(`
    <div class="card mb-2 kanban-card" draggable="true" data-id="${d.id}">
      <div class="card-body p-2">
        <div class="d-flex justify-content-between align-items-start">
          <div class="me-2">
            <div class="fw-semibold">${d.titulo}</div>
            ${d.descricao ? `<div class="text-muted small">${d.descricao}</div>` : ''}
          </div>
          <div class="text-nowrap right-badges">
            ${badgePrioridade(d.prioridade)}
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2">
          <div class="small text-muted">
            <i class="ri-user-3-line me-1"></i>${resp}
            <span class="mx-2">•</span>
            <i class="ri-calendar-line me-1"></i>${due}
          </div>
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-primary js-editar" title="Editar"><i class="ri-edit-2-line"></i></button>
            <button class="btn btn-outline-success js-concluir" title="Concluir"><i class="ri-check-double-line"></i></button>
            <button class="btn btn-outline-danger js-excluir" title="Excluir"><i class="ri-delete-bin-line"></i></button>
          </div>
        </div>
      </div>
    </div>
  `);

        $card.data('demanda', d);
        applyOverdueVisual($card, d);   // <= aplica aqui
        return $card;
    }


    function clearBoard() {
        Object.values($cols).forEach($c => $c.empty());
        Object.keys($cols).forEach(s => $(`#count-${s}`).text('0'));
    }

    function updateCounts() {
        Object.keys($cols).forEach(s => $(`#count-${s}`).text($cols[s].children('.kanban-card').length));
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
        $(document).on('dragstart', '.kanban-card', function (e) {
            e.originalEvent.dataTransfer.setData('text/plain', $(this).data('id'));
            $(this).addClass('opacity-50');
        });
        $(document).on('dragend', '.kanban-card', function () {
            $(this).removeClass('opacity-50');
        });

        $('.kanban-dropzone')
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
                        const $card = $(`.kanban-card[data-id="${id}"]`);
                        const d = $card.data('demanda') || {};
                        d.status = newStatus;
                        $card.data('demanda', d);

                        // se filtros atuais ocultariam o card, remove-o; senão, move-o
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

    // ====== carregar lista ======
    function loadBoard() {
        const params = {
            prioridade: $fPrio.val(),
            assigned_to: $fResp.val()
        };
        // status pode vir do servidor ou filtramos no front; mantive envio só se for específico
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
    $('#assigned_to').select2({ width: '100%', dropdownParent: $('#modal-demanda') });
    $('#filtro-responsavel').select2({ width: 'resolve', dropdownParent: $('body') });

    injectDateFilters();  // <= cria os campos de data no header
    bindDnD();
    // filtros existentes
    [$fStatus, $fPrio].forEach($el => $el.on('change', loadBoard));
    $fResp.on('change', loadBoard);

    // nova demanda
    $('#btn-nova').on('click', () => {
        $form[0].reset();
        $demandaId.val('');
        $assigned.val('').trigger('change');
        $prioridade.val('MEDIA');
        $modal.find('.modal-title').text('Nova Demanda');
        $modal.modal('show');
        setTimeout(() => $titulo.trigger('focus'), 120);
    });

    // submit
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
