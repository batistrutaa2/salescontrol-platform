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

    function showAjaxError(xhr, fallback) {
        const status = xhr?.status;
        const data = xhr?.responseJSON || {};
        const firstError = data?.errors ? Object.values(data.errors).flat()[0] : null;

        if (status === 419) toastr.error('Sessão expirada (CSRF). Recarregue a página.');
        else if (status === 422 && firstError) toastr.error(`Erro de validação: ${firstError}`);
        else toastr.error(`${fallback}${data?.message ? ` (${data.message})` : ''}`);
        console.error('AJAX error:', { status, data, xhr });
    }

    // ====== select2 ======
    $('#assigned_to').select2({ width: '100%', dropdownParent: $('#modal-demanda') });
    $('#filtro-responsavel').select2({ width: 'resolve', dropdownParent: $('body') });

    // ====== renderização ======
    function createCard(d) {
        const due = d.data_limite ? moment(d.data_limite).format('DD/MM/YYYY') : '-';
        const created = d.created_at ? moment(d.created_at).format('DD/MM/YYYY HH:mm') : '-';
        const resp = d.responsavel || '-';

        return $(`
      <div class="card mb-2 kanban-card" draggable="true" data-id="${d.id}">
        <div class="card-body p-2">
          <div class="d-flex justify-content-between align-items-start">
            <div class="me-2">
              <div class="fw-semibold">${d.titulo}</div>
              ${d.descricao ? `<div class="text-muted small">${d.descricao}</div>` : ''}
            </div>
            <div class="text-nowrap">
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
            // Se filtro de status não for "TODOS", pule os diferentes
            const fStatus = $fStatus.val();
            if (fStatus && fStatus !== 'TODOS' && d.status !== fStatus) return;

            const $card = createCard(d);
            bindCardEvents($card, d); // click handlers
            $cols[d.status]?.append($card);
        });
        updateCounts();
    }

    // ====== Drag & Drop ======
    function bindDnD() {
        // Cards
        $(document).on('dragstart', '.kanban-card', function (e) {
            e.originalEvent.dataTransfer.setData('text/plain', $(this).data('id'));
            // estilo
            $(this).addClass('opacity-50');
        });
        $(document).on('dragend', '.kanban-card', function () {
            $(this).removeClass('opacity-50');
        });

        // Dropzones
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

                // Atualiza status no back
                $.ajax({
                    url: `/comercial/demandas/${id}/status`,
                    type: 'PATCH',
                    contentType: 'application/json',
                    data: JSON.stringify({ status: newStatus }),
                    success: function () {
                        toastr.success('Status atualizado.');
                        // Move visualmente (ou pode recarregar board)
                        const $card = $(`.kanban-card[data-id="${id}"]`);
                        $(e.currentTarget).append($card);
                        updateCounts();
                    },
                    error: function (xhr) {
                        showAjaxError(xhr, 'Erro ao mover card.');
                    }
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
                const $opt = $('#assigned_to option').filter(function () { return $(this).text() === (data.responsavel || ''); }).first();
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
                success: function () {
                    toastr.success('Demanda concluída.');
                    // move para coluna concluída
                    $cols['CONCLUIDA'].append($card);
                    updateCounts();
                },
                error: function (xhr) {
                    showAjaxError(xhr, 'Erro ao atualizar status.');
                }
            });
        });

        // Excluir
        $card.find('.js-excluir').on('click', function () {
            if (!confirm('Excluir esta demanda?')) return;
            $.ajax({
                url: `/comercial/demandas/${data.id}`,
                type: 'DELETE',
                success: function () {
                    toastr.success('Demanda excluída.');
                    $card.remove();
                    updateCounts();
                },
                error: function (xhr) {
                    showAjaxError(xhr, 'Erro ao excluir.');
                }
            });
        });
    }

    // ====== carregar lista ======
    function loadBoard() {
        const params = {
            // Para kanban queremos tudo; mas mantemos filtros de prioridade/responsável
            prioridade: $fPrio.val(),
            assigned_to: $fResp.val()
        };
        // Se quiser forçar “todos os status” sempre no Kanban, não envie status. Caso queira filtrar por 1 status, envie:
        if ($fStatus.val() && $fStatus.val() !== 'TODOS') params.status = $fStatus.val();

        $.get('/comercial/demandas/list', params, function (res) {
            // Ideal: back retornar também `assigned_to_id`
            const items = Array.isArray(res?.data) ? res.data : [];
            renderBoard(items);
        }).fail(function (xhr) {
            showAjaxError(xhr, 'Não foi possível carregar as demandas.');
        });
    }

    // ====== eventos filtros ======
    [$fStatus, $fPrio].forEach($el => $el.on('change', loadBoard));
    $fResp.on('change', loadBoard);

    // ====== nova demanda ======
    $('#btn-nova').on('click', () => {
        $form[0].reset();
        $demandaId.val('');
        $assigned.val('').trigger('change');
        $prioridade.val('MEDIA');
        $modal.find('.modal-title').text('Nova Demanda');
        $modal.modal('show');
        setTimeout(() => $titulo.trigger('focus'), 120);
    });

    // ====== submit form (criar/editar) ======
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
            success: function () {
                toastr.success(isEdit ? 'Demanda atualizada.' : 'Demanda criada.');
                $modal.modal('hide');
                loadBoard();
            },
            error: function (xhr) {
                showAjaxError(xhr, 'Erro ao salvar. Verifique os dados.');
            }
        });
    });

    // ====== init ======
    bindDnD();
    loadBoard();
})();
