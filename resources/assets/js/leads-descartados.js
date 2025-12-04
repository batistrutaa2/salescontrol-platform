'use strict';

let table;

(function () {
    $(function () {
        table = $('#leadsDescartadosTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '/mailing/get-leads-descartados',
                dataSrc: 'data',
                complete: function() {
                    updateMetrics();
                    populateBaseFilter();
                }
            },
            columns: [
                { data: 'id' },
                { data: 'nome_cliente' },
                { data: 'cpf' },
                { data: 'telefone1' },
                {
                    data: 'valor_plano_atual',
                    render: function (data) {
                        // Corrigir R$ NaN validando se o valor é um número válido
                        const valor = parseFloat(data);
                        if (isNaN(valor) || valor === null || valor === undefined) {
                            return 'R$ 0,00';
                        }
                        return 'R$ ' + valor.toFixed(2).replace('.', ',');
                    }
                },
                { data: 'categoria' },
                {
                    data: 'nome_base',
                    render: function (data) {
                        return data ? data : '<span class="text-muted">N/D</span>';
                    }
                },
                {
                    data: 'created_at',
                    render: function (data, type) {
                        if (!data) return '<span class="text-muted">N/D</span>';
                        // Data já vem formatada do backend, apenas exibir
                        return data;
                    }
                },
                {
                    data: 'updated_at',
                    render: function (data, type) {
                        if (!data) return '<span class="text-muted">N/D</span>';
                        // Data já vem formatada do backend, apenas exibir
                        return data;
                    }
                },
                {
                    data: 'id',
                    render: function (id) {
                        const csrfToken = $('meta[name="csrf-token"]').attr('content');
                        return `
                            <div style="align-items: center; display: flex; gap: 5px;">
                                <button class="btn btn-sm btn-info js-ver-comentarios" data-id="${id}">
                                    <i class="ri-chat-3-line"></i>
                                </button>
                                <button class="btn btn-sm btn-danger js-excluir-item" data-id="${id}" data-token="${csrfToken}">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        `;
                    },
                    orderable: false,
                    searchable: false
                }
            ],
            order: [[7, 'desc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
                searchPlaceholder: 'Pesquisar...',
                sLengthMenu: '_MENU_',
            },
            drawCallback: function() {
                if (table) {
                    updateFilteredMetrics();
                }
            }
        });
    });

    // Inicializar flatpickr para período personalizado
    if (document.querySelector('#filtro_periodo')) {
        flatpickr('#filtro_periodo', {
            mode: 'range',
            dateFormat: 'd/m/Y',
            locale: 'pt'
        });
    }

    // Função para atualizar métricas gerais
    function updateMetrics() {
        if (!table || !table.rows) return;
        const allData = table.rows().data().toArray();
        const totalDescartados = allData.length;

        // Contar leads do mês atual
        const currentMonth = moment().month();
        const currentYear = moment().year();
        const totalMesAtual = allData.filter(row => {
            if (!row.created_at) return false;
            const rowDate = moment(row.created_at);
            return rowDate.month() === currentMonth && rowDate.year() === currentYear;
        }).length;

        // Contar bases únicas
        const basesUnicas = new Set();
        allData.forEach(row => {
            if (row.nome_base) {
                basesUnicas.add(row.nome_base);
            }
        });

        $('#total-descartados').text(totalDescartados);
        $('#total-mes-atual').text(totalMesAtual);
        $('#total-bases').text(basesUnicas.size);
    }

    // Função para atualizar métricas de filtros
    function updateFilteredMetrics() {
        if (!table || !table.rows) return;
        const filteredData = table.rows({ search: 'applied' }).data().toArray();
        $('#total-filtrado').text(filteredData.length);
    }

    // Função para popular filtro de bases
    function populateBaseFilter() {
        if (!table || !table.rows) return;
        const allData = table.rows().data().toArray();
        const basesUnicas = new Set();

        allData.forEach(row => {
            if (row.nome_base) {
                basesUnicas.add(row.nome_base);
            }
        });

        const baseSelect = $('#filtro_base');
        baseSelect.find('option:not(:first)').remove();

        Array.from(basesUnicas).sort().forEach(base => {
            baseSelect.append(`<option value="${base}">${base}</option>`);
        });
    }

    // Função customizada de filtro do DataTable
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        // Verificar se é a tabela correta
        if (!settings.nTable || !$(settings.nTable).is('#leadsDescartadosTable')) {
            return true;
        }

        const mes = $('#filtro_mes').val();
        const ano = $('#filtro_ano').val();
        const base = $('#filtro_base').val();
        const periodoRange = $('#filtro_periodo').val();

        const dataCriacao = data[6]; // índice da coluna created_at
        const nomeBase = data[5]; // índice da coluna nome_base

        // Filtro por base
        if (base && nomeBase !== base && !nomeBase.includes('N/D')) {
            return false;
        }

        // Filtro por mês/ano
        if ((mes || ano) && dataCriacao && !dataCriacao.includes('N/D')) {
            const dataParts = dataCriacao.split('/');
            if (dataParts.length === 3) {
                const rowMes = parseInt(dataParts[1]);
                const rowAno = parseInt(dataParts[2]);

                if (mes && rowMes !== parseInt(mes)) {
                    return false;
                }
                if (ano && rowAno !== parseInt(ano)) {
                    return false;
                }
            }
        }

        // Filtro por período personalizado
        if (periodoRange && dataCriacao && !dataCriacao.includes('N/D')) {
            const dates = periodoRange.split(' to ');
            if (dates.length === 2) {
                const startDate = moment(dates[0], 'DD/MM/YYYY');
                const endDate = moment(dates[1], 'DD/MM/YYYY');
                const rowDate = moment(dataCriacao, 'DD/MM/YYYY');

                if (!rowDate.isBetween(startDate, endDate, 'day', '[]')) {
                    return false;
                }
            }
        }

        return true;
    });

    // Event listeners para os filtros
    $('#filtro_mes, #filtro_ano, #filtro_base').on('change', function() {
        if (table) table.draw();
    });

    $('#filtro_periodo').on('change', function() {
        if (table) table.draw();
    });

    // Limpar filtros
    $('#limpar_filtros').on('click', function() {
        $('#filtro_mes').val('');
        $('#filtro_ano').val('');
        $('#filtro_base').val('');
        $('#filtro_periodo').val('');
        if (document.querySelector('#filtro_periodo')._flatpickr) {
            document.querySelector('#filtro_periodo')._flatpickr.clear();
        }
        if (table) {
            table.search('').draw();
        }
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
                    const dateObj = moment(c.created_at);
                    const data = dateObj.isValid() ? dateObj.format('DD/MM/YYYY HH:mm') : 'Data inválida';
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

    $(document).on('click', '.js-excluir-item', function () {
        const contatoId = $(this).data('id');
        const token = $(this).data('token');

        if (!confirm('Deseja realmente excluir este lead?')) return;

        $.ajax({
            url: '/mailing/excluir-lead-descartado/' + contatoId,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token
            },
            success: function () {
                toastr.success('Lead excluído com sucesso!');
                $('#leadsDescartadosTable').DataTable().ajax.reload();
            },
            error: function () {
                toastr.error('Erro ao excluir o lead.');
            }
        });
    });
})();
