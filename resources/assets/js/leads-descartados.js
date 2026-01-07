'use strict';

let table;

(function () {
    // Toastr configuration
    if (typeof toastr !== 'undefined') {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: '5000',
            showMethod: 'fadeIn',
            hideMethod: 'fadeOut'
        };
    }

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
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'dt-center',
                    render: function (data, type, full) {
                        return '<input type="checkbox" class="form-check-input lead-checkbox" data-id="' + full.id + '">';
                    }
                },
                { data: 'id' },
                {
                    data: 'nome_cliente',
                    render: function(data) {
                        return '<span style="font-weight: 600;">' + (data || '-') + '</span>';
                    }
                },
                { data: 'cpf' },
                { data: 'telefone1' },
                {
                    data: 'valor_plano_atual',
                    render: function (data) {
                        const valor = parseFloat(data);
                        if (isNaN(valor) || valor === null || valor === undefined) {
                            return '<span class="ld-valor-plano">R$ 0,00</span>';
                        }
                        return '<span class="ld-valor-plano">R$ ' + valor.toFixed(2).replace('.', ',') + '</span>';
                    }
                },
                {
                    data: 'categoria',
                    render: function(data) {
                        if (!data) return '<span class="ld-text-muted">N/D</span>';
                        return '<span class="ld-categoria-badge">' + data + '</span>';
                    }
                },
                {
                    data: 'nome_base',
                    render: function (data) {
                        if (!data) return '<span class="ld-text-muted">N/D</span>';
                        return '<span class="ld-base-badge">' + data + '</span>';
                    }
                },
                {
                    data: 'created_at',
                    render: function (data) {
                        if (!data) return '<span class="ld-text-muted">N/D</span>';
                        return data;
                    }
                },
                {
                    data: 'updated_at',
                    render: function (data) {
                        if (!data) return '<span class="ld-text-muted">N/D</span>';
                        return data;
                    }
                },
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    render: function (id) {
                        return `
                            <div class="dropdown">
                                <button class="ld-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="1"/>
                                        <circle cx="12" cy="5" r="1"/>
                                        <circle cx="12" cy="19" r="1"/>
                                    </svg>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item js-ver-comentarios" data-id="${id}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                            </svg>
                                            Ver Comentarios
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item js-enviar-preditiva" data-id="${id}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="22" y1="2" x2="11" y2="13"/>
                                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                                            </svg>
                                            Enviar para Preditiva
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button class="dropdown-item text-danger js-excluir-item" data-id="${id}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 6h18"/>
                                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                            </svg>
                                            Excluir Lead
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        `;
                    }
                }
            ],
            order: [[9, 'desc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
                searchPlaceholder: 'Pesquisar...',
                sLengthMenu: '_MENU_',
            },
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'TODOS']],
            drawCallback: function() {
                if (table) {
                    updateFilteredMetrics();
                    // Reset checkboxes on redraw
                    $('#selectAll').prop('checked', false);
                    updateSelectedCount();
                }
            }
        });
    });

    // Select All checkbox handler
    $(document).on('change', '#selectAll', function () {
        const isChecked = $(this).prop('checked');
        $('.lead-checkbox').prop('checked', isChecked);
        updateSelectedCount();
    });

    // Individual checkbox handler
    $(document).on('change', '.lead-checkbox', function () {
        updateSelectedCount();
        const totalCheckboxes = $('.lead-checkbox').length;
        const checkedCheckboxes = $('.lead-checkbox:checked').length;
        $('#selectAll').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
    });

    // Update selected count and button state
    function updateSelectedCount() {
        const count = $('.lead-checkbox:checked').length;
        $('#selected-count').text(count);
        $('#enviarSelecionadosBtn').prop('disabled', count === 0);
    }

    // Single lead send to preditiva
    $(document).on('click', '.js-enviar-preditiva', function () {
        const leadId = $(this).data('id');

        if (!confirm('Enviar este lead para a fila preditiva?')) return;

        $.ajax({
            url: '/mailing/send-descartado-preditiva',
            method: 'POST',
            data: {
                id: leadId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    table.ajax.reload(null, false);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Erro ao enviar lead.';
                toastr.error(errorMsg);
            }
        });
    });

    // Bulk send to preditiva with batch processing
    $('#enviarSelecionadosBtn').on('click', async function () {
        const selectedIds = [];
        $('.lead-checkbox:checked').each(function () {
            selectedIds.push($(this).data('id'));
        });

        if (selectedIds.length === 0) {
            toastr.warning('Nenhum lead selecionado');
            return;
        }

        if (!confirm(`Enviar ${selectedIds.length} lead(s) para a fila preditiva?\n\nIsso pode levar alguns minutos para grandes quantidades.`)) return;

        const btn = $(this);
        const originalHtml = btn.html();
        const batchSize = 100; // Enviar 100 por vez para evitar timeout
        const batches = [];

        // Dividir em lotes
        for (let i = 0; i < selectedIds.length; i += batchSize) {
            batches.push(selectedIds.slice(i, i + batchSize));
        }

        let totalSuccess = 0;
        let totalSkipped = 0;
        let totalErrors = 0;
        let processedBatches = 0;

        // Mostrar progresso
        const updateProgress = () => {
            const percent = Math.round((processedBatches / batches.length) * 100);
            btn.html(`<span class="spinner-border spinner-border-sm me-2"></span>${percent}% (${processedBatches}/${batches.length} lotes)`);
        };

        btn.prop('disabled', true);
        updateProgress();

        // Processar lotes sequencialmente
        for (const batch of batches) {
            try {
                const response = await $.ajax({
                    url: '/mailing/send-multiple-descartados-preditiva',
                    method: 'POST',
                    data: {
                        ids: batch,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    timeout: 120000 // 2 minutos por lote
                });

                if (response.stats) {
                    totalSuccess += response.stats.success || 0;
                    totalSkipped += response.stats.skipped || 0;
                    totalErrors += response.stats.errors || 0;
                }
            } catch (error) {
                totalErrors += batch.length;
                console.error('Erro no lote:', error);
            }

            processedBatches++;
            updateProgress();
        }

        // Resultado final
        btn.html(originalHtml);
        btn.prop('disabled', false);

        let message = `${totalSuccess} lead(s) enviado(s) com sucesso.`;
        if (totalSkipped > 0) message += ` ${totalSkipped} ja estavam na fila.`;
        if (totalErrors > 0) message += ` ${totalErrors} com falha.`;

        if (totalSuccess > 0) {
            toastr.success(message, 'Processamento Concluido', { timeOut: 10000 });
        } else if (totalErrors > 0) {
            toastr.error(message, 'Erro no Processamento');
        } else {
            toastr.info(message);
        }

        $('.lead-checkbox, #selectAll').prop('checked', false);
        updateSelectedCount();
        table.ajax.reload();
    });

    // View comments
    $(document).on('click', '.js-ver-comentarios', function () {
        const contatoId = $(this).data('id');
        const modal = new bootstrap.Modal(document.getElementById('modalComentarios'));

        $('#comentariosContainer').html('<div class="ld-comment-item"><span class="ld-comment-author">Carregando...</span></div>');
        modal.show();

        $.ajax({
            url: '/mailing/getComentariosLead/' + contatoId,
            method: 'GET',
            success: function (comentarios) {
                if (!comentarios.length) {
                    $('#comentariosContainer').html('<div class="ld-comment-item"><span class="ld-text-muted">Nenhum comentario encontrado.</span></div>');
                    return;
                }

                const items = comentarios.map(c => {
                    const autor = c.autor ?? 'Usuario';
                    const dateObj = moment(c.created_at);
                    const data = dateObj.isValid() ? dateObj.format('DD/MM/YYYY HH:mm') : 'Data invalida';
                    return `<div class="ld-comment-item">
                        <span class="ld-comment-author">${autor}</span>
                        <span class="ld-comment-date">${data}</span>
                        <p class="ld-comment-text">${c.anotacao}</p>
                    </div>`;
                });

                $('#comentariosContainer').html(items.join(''));
            },
            error: function () {
                $('#comentariosContainer').html('<div class="ld-comment-item"><span class="text-danger">Erro ao carregar comentarios.</span></div>');
            }
        });
    });

    // Delete lead
    $(document).on('click', '.js-excluir-item', function () {
        const contatoId = $(this).data('id');

        if (!confirm('Deseja realmente excluir este lead permanentemente?')) return;

        $.ajax({
            url: '/mailing/excluir-lead-descartado/' + contatoId,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                toastr.success('Lead excluido com sucesso!');
                table.ajax.reload(null, false);
            },
            error: function () {
                toastr.error('Erro ao excluir o lead.');
            }
        });
    });

    // Flatpickr initialization
    if (document.querySelector('#filtro_periodo')) {
        flatpickr('#filtro_periodo', {
            mode: 'range',
            dateFormat: 'd/m/Y',
            locale: 'pt'
        });
    }

    // Metrics functions
    function updateMetrics() {
        if (!table || !table.rows) return;
        const allData = table.rows().data().toArray();
        const totalDescartados = allData.length;

        const currentMonth = moment().month();
        const currentYear = moment().year();
        const totalMesAtual = allData.filter(row => {
            if (!row.updated_at) return false;
            const rowDate = moment(row.updated_at, 'DD/MM/YYYY HH:mm:ss');
            return rowDate.month() === currentMonth && rowDate.year() === currentYear;
        }).length;

        const basesUnicas = new Set();
        allData.forEach(row => {
            if (row.nome_base) basesUnicas.add(row.nome_base);
        });

        $('#total-descartados').text(totalDescartados);
        $('#total-mes-atual').text(totalMesAtual);
        $('#total-bases').text(basesUnicas.size);
    }

    function updateFilteredMetrics() {
        if (!table || !table.rows) return;
        const filteredData = table.rows({ search: 'applied' }).data().toArray();
        $('#total-filtrado').text(filteredData.length);
    }

    function populateBaseFilter() {
        if (!table || !table.rows) return;
        const allData = table.rows().data().toArray();
        const basesUnicas = new Set();

        allData.forEach(row => {
            if (row.nome_base) basesUnicas.add(row.nome_base);
        });

        const baseSelect = $('#filtro_base');
        baseSelect.find('option:not(:first)').remove();

        Array.from(basesUnicas).sort().forEach(base => {
            baseSelect.append(`<option value="${base}">${base}</option>`);
        });
    }

    // Custom DataTable filter
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!settings.nTable || !$(settings.nTable).is('#leadsDescartadosTable')) return true;

        const mes = $('#filtro_mes').val();
        const ano = $('#filtro_ano').val();
        const base = $('#filtro_base').val();
        const periodoRange = $('#filtro_periodo').val();

        // Column indices adjusted for checkbox column
        const dataCriacao = data[8]; // created_at column
        const nomeBase = data[7];    // nome_base column

        if (base && nomeBase !== base && !nomeBase.includes('N/D')) return false;

        if ((mes || ano) && dataCriacao && !dataCriacao.includes('N/D')) {
            const dataParts = dataCriacao.split('/');
            if (dataParts.length >= 3) {
                const rowMes = parseInt(dataParts[1]);
                const rowAno = parseInt(dataParts[2].substring(0, 4));
                if (mes && rowMes !== parseInt(mes)) return false;
                if (ano && rowAno !== parseInt(ano)) return false;
            }
        }

        if (periodoRange && dataCriacao && !dataCriacao.includes('N/D')) {
            const dates = periodoRange.split(' to ');
            if (dates.length === 2) {
                const startDate = moment(dates[0], 'DD/MM/YYYY');
                const endDate = moment(dates[1], 'DD/MM/YYYY');
                const rowDate = moment(dataCriacao, 'DD/MM/YYYY');
                if (!rowDate.isBetween(startDate, endDate, 'day', '[]')) return false;
            }
        }

        return true;
    });

    // Filter event listeners
    $('#filtro_mes, #filtro_ano, #filtro_base').on('change', function() {
        if (table) table.draw();
    });

    $('#filtro_periodo').on('change', function() {
        if (table) table.draw();
    });

    // Clear filters
    $('#limpar_filtros').on('click', function() {
        $('#filtro_mes, #filtro_ano, #filtro_base, #filtro_periodo').val('');
        if (document.querySelector('#filtro_periodo')._flatpickr) {
            document.querySelector('#filtro_periodo')._flatpickr.clear();
        }
        if (table) table.search('').draw();
    });
})();
