'use strict';

$(function () {
    const table = $('.datatables-ajax').DataTable({
        processing: true,
        serverSide: false,
        searching: true,
        ordering: false,
        ajax: {
            url: '/back-office/lista-vendas-filtro',
            data: function (d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
            },
            dataSrc: 'data',
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'nome_contrato' },
            {
                targets: 4,
                render: function (data, type, full, meta) {
                    var $role = full['descricao'];
                    var roleBadgeObj = {
                        IMPLANTADO: '<i class="ri-user-line ri-22px text-primary me-2"></i>',
                        VENDA: '<i class="ri-pie-chart-line ri-22px text-success me-2"></i>',
                        ESTORNO: '<i class="ri-computer-line ri-22px text-danger me-2"></i>',
                        DEVELOPER: '<i class="ri-vip-crown-line ri-22px text-warning me-2"></i>',
                        DECLINADO: '<i class="ri-close-circle-line ri-22px text-danger me-2"></i>'
                    };
                    return (
                        "<span class='text-truncate d-flex align-items-center text-heading'>" +
                        roleBadgeObj[$role] +
                        $role +
                        '</span>'
                    );
                }
            },
            {
                data: 'valor_contrato',
                render: function (data) {
                    return new Intl.NumberFormat('pt-BR', {
                        style: 'currency',
                        currency: 'BRL',
                    }).format(data);
                },
            },
            {
                data: 'created_at',
            },
            {
                data: 'id',
                render: function (data, type, full, meta) {
                    let actions =
                        '<div class="d-flex align-items-center">' +
                        '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ri-more-2-line ri-22px"></i></button>' +
                        '<div class="dropdown-menu dropdown-menu-end m-0">' +
                        '<a href="/back-office/abrir-contrato/' +
                        full['id'] +
                        '" class="dropdown-item"><i class="ri-edit-box-line me-2"></i><span>Ver contrato</span></a>' +
                        '<button type="button" class="dropdown-item js-alterar-status" data-bs-toggle="modal" data-bs-target="#modalcomments" data-id="' +
                        full['id'] +
                        '">' +
                        '<i class="ri-arrow-left-right-fill"></i><span> Alterar Status</span></button>';

                    if (full['descricao'] === 'IMPLANTADO') {
                        actions +=
                            '<a href="/back-office/comprovante/' +
                            full['id'] +
                            '" class="dropdown-item"><i class="ri-download-line me-2"></i><span>Baixar comprovante</span></a>';
                    }

                    actions +=
                        '<a href="/back-office/deletar-contrato/' +
                        full['id'] +
                        '" class="dropdown-item"><i class="ri-delete-bin-line me-2"></i><span>Excluir</span></a>' +
                        '</div>' +
                        '</div>';
                    return actions;
                }
            },

        ],
    });

    // Filtro
    $('#filter_button').on('click', function () {
        table.ajax.reload();
    });

    // Limpar filtro
    $('#clear_filter').on('click', function () {
        $('#start_date').val('');
        $('#end_date').val('');
        table.ajax.reload();
    });

    // Modal status
    $(document).on('click', '.open-status-modal', function () {
        const id = $(this).data('id');
        $('#idSale').val(id);
        $('#modalcomments').modal('show');
    });

    // Modal status
    $(document).on('click', '.js-alterar-status', function () {
        const id = $(this).data('id');
        $('#idSale').val(id);
    });

    // Mostrar/ocultar comprovante conforme status
    $(document).on('change', '#label', function () {
        if ($(this).val() === '18') {
            $('#proof-group').show();
            $('#comprovante').prop('required', true);

            $('#proof-group-data-implantacao').show();
            $('#data_implantacao').prop('required', true);
        } else {
            $('#proof-group').hide();
            $('#comprovante').prop('required', false).val('');

            $('#proof-group-data-implantacao').hide();
            $('#data_implantacao').prop('required', false).val('');
        }
    });



});
