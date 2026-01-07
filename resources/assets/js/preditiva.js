'use strict';

$(function () {
    let tabela = null;
    let filtrosAtivos = {};

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

    function animarAtualizacao(selector, novoValor) {
        const elemento = $(selector);
        const valorAtual = elemento.text();
        const novoValorStr = String(novoValor ?? '--');

        if (valorAtual !== novoValorStr) {
            elemento.text(novoValorStr);
            elemento.addClass('updated');
            setTimeout(() => {
                elemento.removeClass('updated');
            }, 500);
        }
    }

    function getTentativasClass(tentativas) {
        const num = parseInt(tentativas) || 0;
        if (num === 0) return 'pd-tentativas-0';
        if (num <= 2) return 'pd-tentativas-low';
        if (num <= 4) return 'pd-tentativas-medium';
        return 'pd-tentativas-high';
    }

    function updateSelectedCount() {
        const count = $('#tabela-fila-preditiva tbody input.select-lead:checked').length;
        $('#selected-count').text(count);
    }

    function carregarTabulacoes() {
        $.ajax({
            url: '/comercial/preditiva/tabulacoes',
            method: 'GET',
            success: function (response) {
                if (response.success && response.tabulacoes) {
                    const select = $('#filtroUltimaTabulacao');
                    select.find('option:not(:first)').remove();

                    response.tabulacoes.forEach(tabulacao => {
                        if (tabulacao && tabulacao.trim() !== '') {
                            select.append(`<option value="${tabulacao}">${tabulacao}</option>`);
                        }
                    });
                }
            },
            error: function (xhr) {
                console.error('Erro ao carregar tabulacoes:', xhr);
            }
        });
    }

    function atualizarPreditiva() {
        $.ajax({
            url: '/getPreditiva',
            method: 'GET',
            data: filtrosAtivos,
            success: function (response) {
                animarAtualizacao('#total-leads', response.total_leads_fila);
                animarAtualizacao('#tentativas-hoje', response.tentativas_hoje);
                animarAtualizacao('#conversoes-hoje', response.conversoes_hoje);
                animarAtualizacao('#recusas-hoje', response.recusas_hoje);

                if (tabela) {
                    tabela.clear().draw();
                    response.leads.forEach(lead => {
                        const tentativasClass = getTentativasClass(lead.tentativas);
                        const tabulacao = lead.ultima_tabulacao || 'N/A';

                        tabela.row.add([
                            lead.contato_id,
                            `<span style="font-weight: 600;">${lead.nome_cliente ?? '--'}</span>`,
                            lead.telefone1 ?? '--',
                            `<span class="pd-valor-plano">R$ ${parseFloat(lead.valor_plano_atual || 0).toFixed(2).replace('.', ',')}</span>`,
                            `<span class="pd-tentativas-badge ${tentativasClass}">${lead.tentativas}</span>`,
                            `<span class="pd-tabulacao-badge">${tabulacao}</span>`,
                            `
                                <div class="dropdown">
                                    <button class="pd-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="1"/>
                                            <circle cx="12" cy="5" r="1"/>
                                            <circle cx="12" cy="19" r="1"/>
                                        </svg>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item btn-transferir" href="javascript:void(0);" data-id="${lead.contato_id}">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                                    <circle cx="8.5" cy="7" r="4"/>
                                                    <polyline points="17 11 19 13 23 9"/>
                                                </svg>
                                                Transferir
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item btn-desativar" href="javascript:void(0);" data-id="${lead.contato_id}">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"/>
                                                    <line x1="10" y1="15" x2="10" y2="9"/>
                                                    <line x1="14" y1="15" x2="14" y2="9"/>
                                                </svg>
                                                Desativar
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item btn-remover" href="javascript:void(0);" data-id="${lead.contato_id}">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"/>
                                                    <line x1="8" y1="12" x2="16" y2="12"/>
                                                </svg>
                                                Remover da Fila
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger btn-excluir" href="javascript:void(0);" data-id="${lead.contato_id}">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 6h18"/>
                                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                                </svg>
                                                Excluir Permanentemente
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            `
                        ]);
                    });
                    tabela.draw();
                    updateSelectedCount();
                }
            },
            error: function () {
                console.warn('Erro ao buscar dados da fila preditiva.');
            }
        });
    }

    tabela = $('#tabela-fila-preditiva').DataTable({
        pageLength: 10,
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, 'Todos']
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
            searchPlaceholder: 'Pesquisar...',
            sLengthMenu: '_MENU_',
        },
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                searchable: false,
                className: 'dt-body-center',
                render: function (data) {
                    return `<input type="checkbox" class="form-check-input select-lead" value="${data}">`;
                }
            }
        ],
        drawCallback: function() {
            updateSelectedCount();
        }
    });

    // Event: Aplicar Filtros
    $('#btnAplicarFiltros').on('click', function () {
        filtrosAtivos = {
            nome_cliente: $('#filtroNomeCliente').val().trim(),
            ultima_tabulacao: $('#filtroUltimaTabulacao').val(),
            tentativas: $('#filtroTentativas').val()
        };

        atualizarPreditiva();
        toastr.info('Filtros aplicados!');
    });

    // Event: Limpar Filtros
    $('#btnLimparFiltros').on('click', function () {
        $('#filtroNomeCliente').val('');
        $('#filtroUltimaTabulacao').val('');
        $('#filtroTentativas').val('');

        filtrosAtivos = {};
        atualizarPreditiva();
        toastr.info('Filtros limpos!');
    });

    // Event: Transferir Lead
    $('#tabela-fila-preditiva tbody').on('click', '.btn-transferir', function () {
        const contatoId = $(this).data('id');
        abrirModalTransferencia(contatoId);
    });

    // Event: Desativar Lead
    $('#tabela-fila-preditiva tbody').on('click', '.btn-desativar', function () {
        const contatoId = $(this).data('id');

        if (!confirm('Deseja desativar este lead da preditiva? O lead ficara inativo mas podera ser reativado.')) {
            return;
        }

        $.ajax({
            url: `/comercial/preditiva/desativar/${contatoId}`,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    atualizarPreditiva();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Erro ao desativar lead.';
                toastr.error(message);
            }
        });
    });

    // Event: Excluir Lead Permanentemente
    $('#tabela-fila-preditiva tbody').on('click', '.btn-excluir', function () {
        const contatoId = $(this).data('id');

        if (!confirm('ATENCAO: Esta acao excluira o lead PERMANENTEMENTE e marcara o contato como inativo. Deseja continuar?')) {
            return;
        }

        $.ajax({
            url: `/comercial/preditiva/excluir/${contatoId}`,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    atualizarPreditiva();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Erro ao excluir lead.';
                toastr.error(message);
            }
        });
    });

    // Event: Remover da Fila
    $('#tabela-fila-preditiva tbody').on('click', '.btn-remover', function () {
        const contatoId = $(this).data('id');

        if (!confirm('Deseja remover este lead da fila preditiva? O contato permanecera ativo no sistema.')) {
            return;
        }

        $.ajax({
            url: `/comercial/preditiva/remover/${contatoId}`,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    atualizarPreditiva();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Erro ao remover lead.';
                toastr.error(message);
            }
        });
    });

    function abrirModalTransferencia(contatoId) {
        document.getElementById('idMailing').value = contatoId;
        $('#modalTransferirLead').modal('show');
    }

    // Checkbox selection with row highlight
    $('#tabela-fila-preditiva tbody').on('change', '.select-lead', function () {
        const row = $(this).closest('tr');
        row.toggleClass('selected', this.checked);
        updateSelectedCount();
    });

    // Reiniciar Tentativas (batch)
    $('#btnLimparLogsLeads').on('click', async function () {
        const selecionados = [];

        $('#tabela-fila-preditiva tbody input.select-lead:checked').each(function () {
            selecionados.push($(this).val());
        });

        if (selecionados.length === 0) {
            return toastr.warning('Nenhum lead selecionado.');
        }

        if (!confirm(`Deseja reiniciar as tentativas de ${selecionados.length} lead(s)? Os logs serao apagados e a contagem voltara a zero.`)) {
            return;
        }

        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Processando...').prop('disabled', true);

        // Processar em lotes para grandes quantidades
        const batchSize = 100;
        const batches = [];
        for (let i = 0; i < selecionados.length; i += batchSize) {
            batches.push(selecionados.slice(i, i + batchSize));
        }

        let totalProcessed = 0;

        for (const batch of batches) {
            try {
                await $.ajax({
                    url: '/comercial/preditiva/limpar-logs',
                    method: 'POST',
                    data: {
                        ids: batch,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    timeout: 60000
                });
                totalProcessed += batch.length;
            } catch (error) {
                console.error('Erro no lote:', error);
            }
        }

        btn.html(originalHtml).prop('disabled', false);
        toastr.success(`Tentativas reiniciadas para ${totalProcessed} lead(s)!`);
        atualizarPreditiva();
    });

    // Limpar Fila (batch)
    $('#btnLimparFila').on('click', async function () {
        const selecionados = [];

        $('#tabela-fila-preditiva tbody input.select-lead:checked').each(function () {
            selecionados.push($(this).val());
        });

        if (selecionados.length === 0) {
            return toastr.warning('Nenhum lead selecionado.');
        }

        if (!confirm(`ATENCAO: ${selecionados.length} lead(s) serao DESATIVADOS permanentemente e removidos da preditiva. Deseja continuar?`)) {
            return;
        }

        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Processando...').prop('disabled', true);

        // Processar em lotes
        const batchSize = 100;
        const batches = [];
        for (let i = 0; i < selecionados.length; i += batchSize) {
            batches.push(selecionados.slice(i, i + batchSize));
        }

        let totalSuccess = 0;
        let processedBatches = 0;

        for (const batch of batches) {
            try {
                await $.ajax({
                    url: '/comercial/descartar-multiplos-leads',
                    method: 'POST',
                    data: {
                        ids: batch,
                        clearPreditiva: true,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    timeout: 120000
                });
                totalSuccess += batch.length;
            } catch (error) {
                console.error('Erro no lote:', error);
            }

            processedBatches++;
            const percent = Math.round((processedBatches / batches.length) * 100);
            btn.html(`<span class="spinner-border spinner-border-sm me-2"></span>${percent}%`);
        }

        btn.html(originalHtml).prop('disabled', false);
        toastr.success(`${totalSuccess} lead(s) descartados com sucesso!`);
        atualizarPreditiva();
    });

    // Select All
    $('#select-all-leads').on('click', function () {
        const isChecked = $(this).is(':checked');
        $('#tabela-fila-preditiva tbody input.select-lead').prop('checked', isChecked).trigger('change');
    });

    // Inicializacao
    carregarTabulacoes();
    atualizarPreditiva();
    setInterval(atualizarPreditiva, 20000);
});
