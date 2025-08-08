'use strict';

$(function () {

    // Parser robusto para datas BR e ISO
    function parseBrDateTime(str) {
        if (!str) return null;
        const s = String(str).trim();

        // ISO ou YYYY-MM-DD HH:MM:SS
        if (/^\d{4}-\d{2}-\d{2}/.test(s)) {
            return new Date(s.replace(' ', 'T'));
        }

        // BR: dd/mm/yyyy HH:MM:SS
        const [d, t = '00:00:00'] = s.split(' ');
        const [day, month, year] = d.split('/').map(Number);
        const [hour, minute, second = 0] = t.split(':').map(Number);
        return new Date(year, month - 1, day, hour, minute, second);
    }


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
        rowCallback: function (row, data) {
            const role = data.descricao || '';
            const updatedAt = parseBrDateTime(data.updated_at);

            let overdue = false;
            if (updatedAt && !isNaN(updatedAt)) {
                const diffHours = (new Date() - updatedAt) / 36e5;
                const diffDays = diffHours / 24;

                if (role === 'ANALISE DOCUMENTO' && diffHours > 48) overdue = true;
                else if (role === 'ANALISE OPERADORA' && diffDays > 10) overdue = true;
                else if (role === 'PENDENCIA' && diffHours > 48) overdue = true;
            }

            // pinta TODAS as células da linha (funciona mesmo com temas que sobrescrevem <tr>)
            if (overdue) {
                $('td', row).css({ color: '#dc3545', 'font-weight': '700' });
            } else {
                // reset pra não “vazar” estilo quando a tabela redesenha
                $('td', row).css({ color: '', 'font-weight': '' });
            }
        }
        ,
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'nome_contrato' },
            {
                data: 'descricao',
                render: function (data, type, full, meta) {
                    const role = full.descricao || '';

                    // calcula diferença a partir do updated_at (BR/ISO)
                    const updatedAt = parseBrDateTime(full.updated_at);
                    let diffHours = 0, diffDays = 0;
                    if (updatedAt && !isNaN(updatedAt)) {
                        const now = new Date();
                        diffHours = (now - updatedAt) / 36e5; // ms -> horas
                        diffDays = diffHours / 24;
                    }

                    // prazos
                    let overdue = false;
                    if (role === 'ANALISE DOCUMENTO' && diffHours > 48) overdue = true;
                    else if (role === 'ANALISE OPERADORA' && diffDays > 10) overdue = true;
                    else if (role === 'PENDENCIA' && diffHours > 48) overdue = true;

                    // PENDENCIA: só ícone de atenção + texto
                    if (role === 'PENDENCIA') {
                        const color = overdue ? 'text-danger' : 'text-warning';
                        const motivo = full.motivo_pendencia || 'Motivo não informado';
                        const motivoEsc = String(motivo)
                            .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                            .replace(/</g, '&lt;').replace(/>/g, '&gt;');

                        return (
                            "<span class='text-truncate d-flex align-items-center text-heading'>" +
                            "<i class='ri-error-warning-line " + color + " me-2' title='" + motivoEsc + "'></i>" +
                            role +
                            "</span>"
                        );
                    }

                    // Ícones padrão
                    const roleBadgeObj = {
                        'IMPLANTADO': '<i class="ri-user-line ri-22px text-primary me-2"></i>',
                        'VENDA': '<i class="ri-pie-chart-line ri-22px text-success me-2"></i>',
                        'ESTORNO': '<i class="ri-computer-line ri-22px text-danger me-2"></i>',
                        'DEVELOPER': '<i class="ri-vip-crown-line ri-22px text-warning me-2"></i>',
                        'DECLINADO': '<i class="ri-close-circle-line ri-22px text-danger me-2"></i>',
                        'ANALISE DOCUMENTO': '<i class="ri-file-search-line ri-22px me-2"></i>',
                        'ANALISE OPERADORA': '<i class="ri-building-line ri-22px me-2"></i>'
                    };

                    let icon = roleBadgeObj[role] || '<i class="ri-time-line ri-22px text-secondary me-2"></i>';

                    // Se estourou o prazo, força vermelho
                    if (overdue) {
                        icon = icon
                            .replace('ri-22px', 'ri-22px text-danger')
                            .replace('text-secondary', 'text-danger')
                            .replace('text-primary', 'text-danger')
                            .replace('text-success', 'text-danger')
                            .replace('text-warning', 'text-danger')
                            .replace('text-danger text-danger', 'text-danger'); // evita duplicar
                    }

                    return (
                        "<span class='text-truncate d-flex align-items-center text-heading'>" +
                        icon + role +
                        "</span>"
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
            { data: 'created_at' },
            {
                data: 'prazo',
                render: function (data, type, full, meta) {
                    const icon = '<i class="ri-time-fill ri-16px text-muted ms-1"></i>';
                    return (data ? (data + ' ' + icon) : ('N/A ' + icon));
                }
            },
            { data: 'updated_at' },
            {
                data: 'id',
                render: function (data, type, full, meta) {
                    let actions =
                        '<div class="d-flex align-items-center">' +
                        '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ri-more-2-line ri-22px"></i></button>' +
                        '<div class="dropdown-menu dropdown-menu-end m-0">' +
                        '<a href="/back-office/abrir-contrato/' + full['id'] + '" class="dropdown-item"><i class="ri-edit-box-line me-2"></i><span>Ver contrato</span></a>' +
                        '<button type="button" class="dropdown-item js-alterar-status" data-bs-toggle="modal" data-bs-target="#modalcomments" data-id="' + full['id'] + '">' +
                        '<i class="ri-arrow-left-right-fill me-2"></i><span>Alterar Status</span>' +
                        '</button>';

                    if (full['descricao'] === 'IMPLANTADO') {
                        actions +=
                            '<a href="/back-office/comprovante/' + full['id'] + '" class="dropdown-item"><i class="ri-download-line me-2"></i><span>Baixar comprovante</span></a>';
                    }

                    actions +=
                        '<a href="/back-office/deletar-contrato/' + full['id'] + '" class="dropdown-item"><i class="ri-delete-bin-line me-2"></i><span>Excluir</span></a>' +
                        '</div>' +
                        '</div>';
                    return actions;
                }
            }
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

        if ($(this).val() === '55') {
            $('#proof-group-data-pendencia').show();
            $('#data_pendencia').prop('required', true);
        } else {
            $('#proof-group-data-pendencia').hide();
            $('#data_pendencia').prop('required', false).val('');
        }
    });

});
