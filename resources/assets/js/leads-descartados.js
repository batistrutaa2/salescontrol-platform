'use strict';

(function () {
    $(function () {
        $('#leadsDescartadosTable').DataTable({
            processing: true,
            serverSide: false, // como você retorna tudo de uma vez, usa client-side
            ajax: {
                url: '/mailing/get-leads-descartados',
                dataSrc: 'data'
            },
            columns: [
                { data: 'id' },
                { data: 'nome_cliente' },
                { data: 'cpf' },
                { data: 'telefone1' },
                {
                    data: 'valor_plano_atual',
                    render: function (data) {
                        return 'R$ ' + parseFloat(data).toFixed(2).replace('.', ',');
                    }
                },
                {
                    data: 'created_at',
                },
                {
                    data: 'id',
                    render: function (id) {
                        return `<button class="btn btn-sm btn-info js-ver-comentarios" data-id="${id}">
              <i class="ri-chat-3-line"></i>    
            </button>`;
                    },
                    orderable: false,
                    searchable: false
                }
            ],

            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
                searchPlaceholder: 'Pesquisar...',
                sLengthMenu: '_MENU_',
            }
        });
    });


    $(document).on('click', '.js-ver-comentarios', function () {
        const contatoId = $(this).data('id');
        const modal = new bootstrap.Modal(document.getElementById('modalComentarios'));

        $('#comentariosList').html('<li class="list-group-item">Carregando...</li>');
        modal.show();

        $.ajax({
            url: '/mailing/getComentariosLead/' + contatoId,
            method: 'GET',
            success: function (comentarios) {
                if (!comentarios.length) {
                    $('#comentariosList').html('<li class="list-group-item">Nenhum comentário encontrado.</li>');
                    return;
                }

                const items = comentarios.map(c => {
                    const autor = c.autor ?? 'Usuário';
                    const data = c.created_at;
                    return `<li class="list-group-item">
                    <strong>${autor}</strong> em <small>${data}</small>
                    <p class="mb-0">${c.anotacao}</p>
                    </li>`;
                });

                $('#comentariosList').html(items.join(''));
            },
            error: function () {
                $('#comentariosList').html('<li class="list-group-item text-danger">Erro ao carregar comentários.</li>');
            }
        });
    });



})();
