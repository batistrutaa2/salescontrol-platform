'use strict';

$(function () {
    let editingId = null;

    // Inicializa DataTable
    let tabelaFaqs = $('#tabela-faqs').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '/back-office/getFaqs',
            type: 'GET',
            dataSrc: '',
            data: function (d) {
                d.operadora_id = $('#filtro-operadora').val();
            }
        },
        columns: [
            { data: 'id', title: 'ID' },
            {
                data: 'operadora',
                title: 'Operadora',
                render: function (data) {
                    return data ? data.nome : '<span style="color:var(--faq-text-muted)">-</span>';
                }
            },
            {
                data: 'titulo',
                title: 'Título',
                render: function (data) {
                    return '<span style="font-weight:600">' + data + '</span>';
                }
            },
            {
                data: 'status',
                title: 'Status',
                render: function (data) {
                    if (data === 'Y') {
                        return '<span class="faq-status-badge active">Ativo</span>';
                    } else {
                        return '<span class="faq-status-badge inactive">Inativo</span>';
                    }
                }
            },
            { data: 'created_at', title: 'Criado em' },
            {
                data: null,
                title: 'Ações',
                orderable: false,
                render: function (data, type, row) {
                    let respostaEscapada = $('<div>').text(row.resposta).html().replace(/"/g, '&quot;');
                    let tituloEscapado = $('<div>').text(row.titulo).html().replace(/"/g, '&quot;');
                    return `
                        <div style="display:flex;gap:0.5rem">
                            <button class="faq-action-btn edit btn-editar"
                                data-id="${row.id}"
                                data-operadora="${row.operadora_id}"
                                data-titulo="${tituloEscapado}"
                                data-resposta="${respostaEscapada}"
                                data-status="${row.status}"
                                data-ordem="${row.ordem}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </button>
                            <button class="faq-action-btn delete btn-excluir" data-id="${row.id}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[0, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/pt-BR.json'
        }
    });

    // Filtro por operadora
    $('#filtro-operadora').on('change', function () {
        tabelaFaqs.ajax.reload(null, false);
    });

    // Reset modal ao abrir para criar
    $('#btnNovoFaq').on('click', function () {
        editingId = null;
        $('#formFaq')[0].reset();
        $('#faqModalLabel').text('Cadastrar Novo FAQ');
        $('#faqModal').modal('show');
    });

    // Editar FAQ
    $('#tabela-faqs').on('click', '.btn-editar', function () {
        let btn = $(this);
        editingId = btn.data('id');
        $('#faq_operadora_id').val(btn.data('operadora'));
        $('#faq_titulo').val(btn.data('titulo'));
        $('#faq_resposta').val(btn.data('resposta'));
        $('#faq_status').val(btn.data('status'));
        $('#faq_ordem').val(btn.data('ordem'));
        $('#faqModalLabel').text('Editar FAQ');
        $('#faqModal').modal('show');
    });

    // Submit form (criar/editar)
    $('#formFaq').on('submit', function (e) {
        e.preventDefault();
        let form = $(this);
        let url = editingId
            ? '/back-office/updateFaq/' + editingId
            : '/back-office/createFaq';

        $.ajax({
            url: url,
            method: 'POST',
            data: form.serialize(),
            success: function (response) {
                $('#faqModal').modal('hide');
                form[0].reset();
                editingId = null;
                tabelaFaqs.ajax.reload(null, false);
                toastr.success(response.message || 'Operação realizada com sucesso!');
            },
            error: function (xhr) {
                let msg = xhr.responseJSON?.message || 'Erro ao salvar FAQ.';
                toastr.error(msg);
            }
        });
    });

    // Excluir FAQ
    $('#tabela-faqs').on('click', '.btn-excluir', function () {
        let id = $(this).data('id');

        if (!confirm('Tem certeza que deseja excluir este FAQ?')) return;

        $.ajax({
            url: '/back-office/deleteFaq/' + id,
            method: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                tabelaFaqs.ajax.reload(null, false);
                toastr.success(response.message || 'FAQ excluído com sucesso!');
            },
            error: function (xhr) {
                let msg = xhr.responseJSON?.message || 'Erro ao excluir FAQ.';
                toastr.error(msg);
            }
        });
    });
});
