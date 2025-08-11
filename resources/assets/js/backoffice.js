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
        const [day, month, year] = (d || '').split('/').map(Number);
        const [hour, minute, second = 0] = (t || '').split(':').map(Number);
        return new Date(year, (month || 1) - 1, day || 1, hour || 0, minute || 0, second || 0);
    }

    let table;

    // Filtro customizado do DataTables (Status + Mês/Ano em cima de created_at)
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!table || settings.nTable !== table.table().node()) return true;

        const row = table.row(dataIndex).data() || {};

        const statusSel = ($('#status_filter').val() || '').toString().trim().toUpperCase();
        const mesSel = parseInt($('#periodo_mes').val(), 10);
        const anoSel = parseInt($('#periodo_ano').val(), 10);

        // Filtra por status (campo: descricao)
        if (statusSel && (String(row.descricao || '').toUpperCase() !== statusSel)) {
            return false;
        }

        // Filtra por mês/ano do created_at
        if (mesSel || anoSel) {
            const dt = parseBrDateTime(row.created_at);
            if (!(dt instanceof Date) || isNaN(dt)) return false;

            if (mesSel && (dt.getMonth() + 1) !== mesSel) return false;
            if (anoSel && dt.getFullYear() !== anoSel) return false;
        }

        return true;
    });

    // DataTable
    table = $('.datatables-ajax').DataTable({
        processing: true,
        serverSide: false,
        searching: true,
        ordering: false,
        ajax: {
            url: '/back-office/lista-vendas-filtro',
            dataSrc: 'data'
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

            if (overdue) {
                $('td', row).css({ color: '#dc3545', 'font-weight': '700' });
            } else {
                $('td', row).css({ color: '', 'font-weight': '' });
            }
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'nome_contrato' },
            {
                data: 'descricao',
                render: function (data, type, full) {
                    const role = full.descricao || '';
                    const updatedAt = parseBrDateTime(full.updated_at);
                    let diffHours = 0, diffDays = 0;
                    if (updatedAt && !isNaN(updatedAt)) {
                        const now = new Date();
                        diffHours = (now - updatedAt) / 36e5;
                        diffDays = diffHours / 24;
                    }

                    let overdue = false;
                    if (role === 'ANALISE DOCUMENTO' && diffHours > 48) overdue = true;
                    else if (role === 'ANALISE OPERADORA' && diffDays > 10) overdue = true;
                    else if (role === 'PENDENCIA' && diffHours > 48) overdue = true;

                    if (role === 'PENDENCIA' || role === 'DECLINADO' || role === 'ESTORNO') {
                        const color = overdue ? 'text-danger' : 'text-warning';
                        const motivo = full.motivo_pendencia || 'Motivo não informado';
                        const motivoEsc = String(motivo)
                            .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                            .replace(/</g, '&lt;').replace(/>/g, '&gt;');

                        return "<span class='text-truncate d-flex align-items-center text-heading'>" +
                            "<i class='ri-error-warning-line " + color + " me-2' title='" + motivoEsc + "'></i>" +
                            role + "</span>";
                    }

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

                    if (overdue) {
                        icon = icon
                            .replace('ri-22px', 'ri-22px text-danger')
                            .replace('text-secondary', 'text-danger')
                            .replace('text-primary', 'text-danger')
                            .replace('text-success', 'text-danger')
                            .replace('text-warning', 'text-danger')
                            .replace('text-danger text-danger', 'text-danger');
                    }

                    return "<span class='text-truncate d-flex align-items-center text-heading'>" + icon + role + "</span>";
                }
            },
            {
                data: 'valor_contrato',
                render: function (data) {
                    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(data);
                }
            },
            { data: 'created_at' },
            {
                data: 'prazo',
                render: function (data) {
                    const icon = '<i class="ri-time-fill ri-16px text-muted ms-1"></i>';
                    return (data ? (data + ' ' + icon) : ('N/A ' + icon));
                }
            },
            { data: 'updated_at' },
            {
                data: 'id',
                render: function (data, type, full) {
                    let actions =
                        '<div class="d-flex align-items-center">' +
                        '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ri-more-2-line ri-22px"></i></button>' +
                        '<div class="dropdown-menu dropdown-menu-end m-0">' +
                        '<a href="/back-office/abrir-contrato/' + full['id'] + '" class="dropdown-item"><i class="ri-edit-box-line me-2"></i><span>Ver contrato</span></a>' +
                        '<button type="button" class="dropdown-item js-alterar-status" data-bs-toggle="modal" data-bs-target="#modalcomments" data-id="' + full['id'] + '">' +
                        '<i class="ri-arrow-left-right-fill me-2"></i><span>Alterar Status</span>' +
                        '</button>';

                    if (full['descricao'] === 'IMPLANTADO') {
                        actions += '<a href="/back-office/comprovante/' + full['id'] + '" class="dropdown-item"><i class="ri-download-line me-2"></i><span>Baixar comprovante</span></a>';
                    }

                    actions +=
                        '<a href="/back-office/deletar-contrato/' + full['id'] + '" class="dropdown-item"><i class="ri-delete-bin-line me-2"></i><span>Excluir</span></a>' +
                        '</div>' +
                        '</div>';
                    return actions;
                }
            }
        ]
    });

    // Ações dos filtros (sem start/end date)
    $('#btn_aplicar_filtro').on('click', function (e) {
        e.preventDefault();
        table.draw(); // aplica ext.search
    });

    $('#btn_limpar_filtro').on('click', function () {
        $('#status_filter').val('');
        $('#periodo_mes').val('');
        $('#periodo_ano').val('');
        table.draw();
    });

    // Também dispara no change para UX melhor
    $('#status_filter, #periodo_mes, #periodo_ano').on('change', function () {
        table.draw();
    });

    // Modal status (mantido)
    $(document).on('click', '.open-status-modal', function () {
        const id = $(this).data('id');
        $('#idSale').val(id);
        $('#modalcomments').modal('show');
    });

    $(document).on('click', '.js-alterar-status', function () {
        const id = $(this).data('id');
        $('#idSale').val(id);
    });

    // Mostrar/ocultar comprovante conforme status (mantido)
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
        if ($(this).val() === '55' || $(this).val() === '17' || $(this).val() === '53') {
            $('#proof-group-data-pendencia').show();
            $('#data_pendencia').prop('required', true);
        } else {
            $('#proof-group-data-pendencia').hide();
            $('#data_pendencia').prop('required', false).val('');
        }
    });
});
