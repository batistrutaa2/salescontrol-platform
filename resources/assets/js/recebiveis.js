/**
 * Recebiveis Page JavaScript
 * Premium Financial Dashboard
 */
'use strict';

$(function () {
    // ============================================
    // Variables
    // ============================================
    let currentVendaId = null;
    let currentFilterTab = 'todos'; // Guarda o tab ativo atual

    // Pagination variables
    let allParcelas = [];
    let currentPage = 1;
    const itemsPerPage = 10;

    // Selection variables
    let selectedParcelas = new Set();

    // ============================================
    // DataTable Initialization
    // ============================================
    const table = $('#recebiveisTable').DataTable({
        pageLength: 10,
        responsive: true,
        order: [[4, 'desc']],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
        },
        dom: '<"dataTables_header"lf>rt<"dataTables_footer"ip>',
        drawCallback: function () {
            // Re-apply animations after table redraw
            animateTableRows();
        }
    });

    // ============================================
    // Filter Tabs (Status)
    // ============================================
    $('.filter-tab').on('click', function () {
        const status = $(this).data('status');

        // Guarda o tab ativo atual
        currentFilterTab = status;

        // Update active state
        $('.filter-tab').removeClass('active');
        $(this).addClass('active');

        // Apply filter
        if (status === 'todos') {
            table.column(7).search('').draw();
        } else {
            table.column(7).search(status).draw();
        }

        // Update count badge
        updateContractsCount();
    });

    // ============================================
    // Year tabs are now links, no JS needed
    // ============================================

    // ============================================
    // Load Parcelas Function
    // ============================================
    function carregarParcelas(vendaId) {
        const tbody = $('#parcelasTable tbody');

        // Show loading state
        tbody.html(`
            <tr class="loading-row">
                <td colspan="8" class="text-center py-4">
                    <div class="loading-spinner">
                        <svg class="spinner" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/>
                        </svg>
                        <span>Carregando parcelas...</span>
                    </div>
                </td>
            </tr>
        `);

        // Hide pagination while loading
        $('#parcelasPagination').hide();

        $.get(`/financeiro/recebiveis/${vendaId}/parcelas`, function (data) {
            // Store all parcelas and reset to first page
            allParcelas = data;
            currentPage = 1;

            // Limpar seleção ao carregar novas parcelas
            selectedParcelas.clear();
            updateSelectionUI();

            if (data.length === 0) {
                tbody.html(`
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <span style="color: var(--rcb-text-muted)">Nenhuma parcela encontrada</span>
                        </td>
                    </tr>
                `);
                $('#parcelasPagination').hide();
                $('#parcelasModal').modal('show');
                return;
            }

            // Render first page
            renderParcelas();
            updatePaginationControls();

            // Show pagination if more than one page
            if (data.length > itemsPerPage) {
                $('#parcelasPagination').show();
            } else {
                $('#parcelasPagination').hide();
            }

            $('#parcelasModal').modal('show');
        }).fail(function () {
            tbody.html(`
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <span style="color: var(--rcb-danger)">Erro ao carregar parcelas</span>
                    </td>
                </tr>
            `);
            $('#parcelasPagination').hide();
            showToast('error', 'Erro ao carregar parcelas');
        });
    }

    // ============================================
    // Pagination Functions
    // ============================================
    function renderParcelas() {
        const tbody = $('#parcelasTable tbody');
        tbody.empty();

        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, allParcelas.length);
        const parcelasToShow = allParcelas.slice(startIndex, endIndex);

        parcelasToShow.forEach((parcela, index) => {
            const statusBadge = getStatusBadge(parcela.status);
            const actionBtns = getActionButtons(parcela);
            const dataRecebimento = parcela.data_recebimento
                ? parcela.data_recebimento
                : '<span style="color: var(--rcb-text-muted)">—</span>';

            const valorFormatado = parseFloat(parcela.valor).toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });

            // Custo Atual (apenas para parcelas vitalícias 4+)
            const custoAtualFormatado = parcela.custo_atual
                ? parseFloat(parcela.custo_atual).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
                : null;

            // Badge de tipo (Parcela ou Vitalicio)
            const tipoBadge = getTipoBadge(parcela.parcela, parcela.tipo);

            // Checkbox de seleção
            const isChecked = selectedParcelas.has(parcela.id) ? 'checked' : '';

            tbody.append(`
                <tr style="animation: fadeInUp 0.3s ease forwards; animation-delay: ${index * 30}ms; opacity: 0;" data-parcela-id="${parcela.id}">
                    <td class="col-checkbox">
                        <label class="custom-checkbox">
                            <input type="checkbox" class="parcela-checkbox" data-id="${parcela.id}" ${isChecked}>
                            <span class="checkmark"></span>
                        </label>
                    </td>
                    <td>
                        <div class="parcela-info">
                            <span style="font-family: 'IBM Plex Mono', monospace; font-weight: 600; color: var(--rcb-emerald);">
                                ${parcela.parcela}
                            </span>
                            ${tipoBadge}
                        </div>
                    </td>
                    <td>
                        <span style="font-family: 'IBM Plex Mono', monospace; font-weight: 600;">
                            ${valorFormatado}
                        </span>
                    </td>
                    <td>
                        ${custoAtualFormatado
                            ? `<span style="font-family: 'IBM Plex Mono', monospace; font-weight: 600;">${custoAtualFormatado}</span>`
                            : '<span style="color: var(--rcb-text-muted)">—</span>'}
                    </td>
                    <td>${parcela.data_prevista}</td>
                    <td>${dataRecebimento}</td>
                    <td>${statusBadge}</td>
                    <td>${actionBtns}</td>
                </tr>
            `);
        });

        // Atualizar estado do checkbox "selecionar todos"
        updateSelectAllCheckbox();
    }

    /**
     * Retorna o badge indicando o tipo da parcela (Parcela ou Vitalicio)
     */
    function getTipoBadge(numeroParcela, tipo) {
        if (numeroParcela >= 4 || tipo === 'vitalicio') {
            return `<span class="tipo-badge tipo-vitalicio" title="Parcela vitalicia (recorrente)">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                </svg>
                Vitalicio
            </span>`;
        }
        return `<span class="tipo-badge tipo-parcela" title="Parcela de pagamento (1-3)">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="4" width="20" height="16" rx="2"/>
                <path d="M7 15h0M2 9h20"/>
            </svg>
            Parcela
        </span>`;
    }

    function updatePaginationControls() {
        const totalPages = Math.ceil(allParcelas.length / itemsPerPage);
        const startIndex = (currentPage - 1) * itemsPerPage + 1;
        const endIndex = Math.min(currentPage * itemsPerPage, allParcelas.length);

        // Update info text
        $('#paginationInfo').text(`Mostrando ${startIndex}-${endIndex} de ${allParcelas.length} parcelas`);

        // Update prev/next buttons
        $('#btnPrevPage').prop('disabled', currentPage === 1);
        $('#btnNextPage').prop('disabled', currentPage === totalPages);

        // Generate page numbers
        const pagesContainer = $('#paginationPages');
        pagesContainer.empty();

        // Calculate which pages to show
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);

        // Adjust if we're near the beginning or end
        if (currentPage <= 3) {
            endPage = Math.min(5, totalPages);
        }
        if (currentPage >= totalPages - 2) {
            startPage = Math.max(1, totalPages - 4);
        }

        // First page + ellipsis
        if (startPage > 1) {
            pagesContainer.append(`<button type="button" class="pagination-page" data-page="1">1</button>`);
            if (startPage > 2) {
                pagesContainer.append(`<span class="pagination-ellipsis">...</span>`);
            }
        }

        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === currentPage ? 'active' : '';
            pagesContainer.append(`<button type="button" class="pagination-page ${activeClass}" data-page="${i}">${i}</button>`);
        }

        // Last page + ellipsis
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                pagesContainer.append(`<span class="pagination-ellipsis">...</span>`);
            }
            pagesContainer.append(`<button type="button" class="pagination-page" data-page="${totalPages}">${totalPages}</button>`);
        }
    }

    function goToPage(page) {
        const totalPages = Math.ceil(allParcelas.length / itemsPerPage);
        if (page < 1 || page > totalPages) return;

        currentPage = page;
        renderParcelas();
        updatePaginationControls();

        // Scroll to top of table
        $('.parcelas-table-wrapper').scrollTop(0);
    }

    // Pagination event handlers
    $(document).on('click', '#btnPrevPage', function () {
        goToPage(currentPage - 1);
    });

    $(document).on('click', '#btnNextPage', function () {
        goToPage(currentPage + 1);
    });

    $(document).on('click', '.pagination-page', function () {
        const page = parseInt($(this).data('page'));
        goToPage(page);
    });

    function getStatusBadge(status) {
        switch (status) {
            case 'PAGO':
                return `<span class="badge bg-success">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Pago
                </span>`;
            case 'CANCELADO':
                return '<span class="badge bg-secondary">Cancelado</span>';
            default:
                return `<span class="badge bg-warning">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    Pendente
                </span>`;
        }
    }

    function getActionButtons(parcela) {
        const btnExcluir = `
            <button class="btn btn-sm btn-outline-danger excluir-parcela"
                    data-id="${parcela.id}"
                    data-parcela="${parcela.parcela}"
                    title="Excluir parcela">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
            </button>`;

        if (parcela.status === 'PAGO') {
            return `
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-primary editar-data-recebimento"
                            data-id="${parcela.id}"
                            data-parcela="${parcela.parcela}"
                            data-data="${parcela.data_recebimento || ''}"
                            data-valor="${parcela.valor || ''}"
                            title="Editar parcela">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    ${btnExcluir}
                </div>`;
        } else if (parcela.status !== 'CANCELADO') {
            return `
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-success pagar-parcela" data-id="${parcela.id}" title="Marcar como pago">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </button>
                    <button class="btn btn-sm btn-outline-primary editar-data-recebimento"
                            data-id="${parcela.id}"
                            data-parcela="${parcela.parcela}"
                            data-data=""
                            data-valor="${parcela.valor || ''}"
                            title="Definir data e valor">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </button>
                    ${btnExcluir}
                </div>`;
        }
        return btnExcluir;
    }

    // ============================================
    // Reactive Update Functions
    // ============================================


    /**
     * Atualiza a linha do contrato na tabela via AJAX
     */
    function atualizarLinhaContrato(vendaId) {
        $.ajax({
            url: `/financeiro/recebiveis/contrato/${vendaId}/resumo`,
            method: 'GET'
        }).done(function (response) {
            if (response.success) {
                const row = $(`tr[data-venda-id="${vendaId}"]`);

                if (response.removido) {
                    // Contrato foi removido (sem mais recebíveis)
                    // Remove a linha do DataTable
                    table.row(row).remove().draw(false);
                    return;
                }

                const dados = response.dados;

                // Atualiza os valores na linha
                row.find('.valor-total').text(formatCurrency(dados.valor_total));
                row.find('.valor-pago').text(formatCurrency(dados.valor_pago));
                row.find('.valor-pendente').text(formatCurrency(dados.valor_pendente));

                // Atualiza o badge de status
                const statusBadge = row.find('.status-badge');
                statusBadge.removeClass('status-success status-warning status-danger');
                statusBadge.addClass(dados.status_class);
                statusBadge.text(dados.status);

                // Atualiza o valor do status na coluna oculta (para filtro)
                row.find('td').eq(7).text(dados.status);

                // Adiciona animação de highlight
                row.addClass('highlight-update');
                setTimeout(() => {
                    row.removeClass('highlight-update');
                }, 1000);

                // Reaplicar o filtro de tab ativo
                reaplicarFiltroTab();
            }
        }).fail(function () {
            console.error('Erro ao atualizar linha do contrato');
        });
    }

    /**
     * Reaplicar o filtro de tab ativo após atualizações
     */
    function reaplicarFiltroTab() {
        if (currentFilterTab === 'todos') {
            table.column(7).search('').draw(false);
        } else {
            table.column(7).search(currentFilterTab).draw(false);
        }
        updateContractsCount();
    }

    // ============================================
    // Event Handlers
    // ============================================

    // View Parcelas
    $(document).on('click', '.view-parcelas', function () {
        currentVendaId = $(this).data('id');
        carregarParcelas(currentVendaId);
    });

    // Pay Parcela
    $(document).on('click', '.pagar-parcela', function () {
        const parcelaId = $(this).data('id');

        Swal.fire({
            title: 'Confirmar Pagamento',
            html: `
                <div style="text-align: left; padding: 1rem 0;">
                    <p style="margin-bottom: 0.5rem;">A parcela sera marcada como <strong>paga</strong> com a data de hoje.</p>
                    <p style="color: var(--rcb-text-muted); font-size: 0.875rem; margin: 0;">Voce pode editar a data posteriormente se necessario.</p>
                </div>
            `,
            icon: 'question',
            iconColor: '#10B981',
            showCancelButton: true,
            confirmButtonText: 'Confirmar Pagamento',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'swal-recebiveis',
                confirmButton: 'btn btn-success me-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then(result => {
            if (result.isConfirmed) {
                showLoadingSwal('Processando pagamento...');

                $.post(`/financeiro/recebiveis/parcelas/${parcelaId}/pagar`, {
                    _token: $('meta[name="csrf-token"]').attr('content')
                }).done(function (response) {
                    let mensagem = 'A parcela foi marcada como paga com sucesso.';
                    if (response.nova_parcela_gerada) {
                        mensagem += ' Uma nova parcela vitalicia foi gerada.';
                    }

                    Swal.fire({
                        title: 'Pagamento Registrado!',
                        text: mensagem,
                        icon: 'success',
                        iconColor: '#10B981',
                        confirmButtonText: 'OK',
                        customClass: {
                            popup: 'swal-recebiveis',
                            confirmButton: 'btn btn-success'
                        },
                        buttonsStyling: false
                    }).then(() => {
                        // Atualiza de forma reativa sem recarregar a página
                        atualizarLinhaContrato(currentVendaId);
                        carregarParcelas(currentVendaId);
                    });
                }).fail(function () {
                    Swal.fire({
                        title: 'Erro!',
                        text: 'Nao foi possivel processar o pagamento.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        customClass: {
                            popup: 'swal-recebiveis',
                            confirmButton: 'btn btn-danger'
                        },
                        buttonsStyling: false
                    });
                });
            }
        });
    });

    // Helper: Format number to Brazilian currency (1234.56 -> 1.234,56)
    function formatarMoeda(valor) {
        if (!valor && valor !== 0) return '';
        return parseFloat(valor).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Helper: Parse Brazilian currency to number (1.234,56 -> 1234.56)
    function parseMoeda(valorFormatado) {
        if (!valorFormatado) return null;
        // Remove dots (thousand separator) and replace comma with dot (decimal separator)
        const valorLimpo = valorFormatado.replace(/\./g, '').replace(',', '.');
        const numero = parseFloat(valorLimpo);
        return isNaN(numero) ? null : numero;
    }

    // Helper: Apply currency mask on input
    function aplicarMascaraMoeda(input) {
        input.addEventListener('input', function (e) {
            let valor = e.target.value;

            // Remove tudo exceto numeros
            valor = valor.replace(/\D/g, '');

            // Converte para centavos
            if (valor.length === 0) {
                e.target.value = '';
                return;
            }

            // Converte para decimal (divide por 100)
            const valorDecimal = parseInt(valor, 10) / 100;

            // Formata como moeda brasileira
            e.target.value = valorDecimal.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        });
    }

    // Edit Receipt Date and Value
    $(document).on('click', '.editar-data-recebimento', function () {
        const parcelaId = $(this).data('id');
        const numeroParcela = $(this).data('parcela');
        const dataAtual = $(this).data('data') || '';
        const valorAtual = $(this).data('valor') || '';

        let dataFormatada = '';
        if (dataAtual) {
            const partes = dataAtual.split('/');
            if (partes.length === 3) {
                dataFormatada = `${partes[2]}-${partes[1]}-${partes[0]}`;
            }
        }

        // Format valor for display (Brazilian format: 1.234,56)
        let valorFormatado = '';
        if (valorAtual) {
            valorFormatado = formatarMoeda(valorAtual);
        }

        const hoje = new Date().toISOString().split('T')[0];

        // Hide Bootstrap modal before showing SweetAlert2 to avoid aria-hidden conflict
        $('#parcelasModal').modal('hide');

        // Wait for modal to fully hide before showing SweetAlert
        setTimeout(() => {
            Swal.fire({
                title: `Parcela ${numeroParcela}`,
                html: `
                    <div style="text-align: left; padding: 1rem 0;">
                        <p style="margin-bottom: 1rem; color: var(--rcb-text-secondary);">
                            ${dataAtual ? 'Edite os dados da parcela:' : 'Informe os dados do pagamento recebido:'}
                        </p>
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.875rem;">
                                Valor da Parcela
                            </label>
                            <div style="position: relative;">
                                <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--rcb-text-muted); font-weight: 600;">R$</span>
                                <input type="text" id="swal-valor-parcela"
                                       inputmode="numeric"
                                       style="width: 100%; padding: 0.625rem 1rem 0.625rem 40px; border: 1px solid var(--rcb-card-border); border-radius: 8px; font-size: 0.9375rem; font-family: 'IBM Plex Mono', monospace;"
                                       value="${valorFormatado}" placeholder="0,00">
                            </div>
                            <p style="color: var(--rcb-text-muted); font-size: 0.75rem; margin: 0.5rem 0 0 0;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 2px;">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="16" x2="12" y2="12"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                </svg>
                                Altere apenas se o valor recebido for diferente do previsto (ex: saida de dependente).
                            </p>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.875rem;">
                                Data de Recebimento
                            </label>
                            <input type="date" id="swal-data-recebimento"
                                   style="width: 100%; padding: 0.625rem 1rem; border: 1px solid var(--rcb-card-border); border-radius: 8px; font-size: 0.9375rem;"
                                   value="${dataFormatada}" max="${hoje}">
                        </div>
                        <p style="color: var(--rcb-text-muted); font-size: 0.8125rem; margin: 0;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="16" x2="12" y2="12"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                            </svg>
                            ${dataAtual ? 'A parcela continuara marcada como paga.' : 'A parcela sera marcada como paga na data informada.'}
                        </p>
                    </div>
                `,
                icon: null,
                showCancelButton: true,
                confirmButtonText: 'Salvar',
                cancelButtonText: 'Cancelar',
                customClass: {
                    popup: 'swal-recebiveis',
                    confirmButton: 'btn btn-success me-2',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false,
                didOpen: () => {
                    // Apply currency mask after modal opens
                    const inputValor = document.getElementById('swal-valor-parcela');
                    aplicarMascaraMoeda(inputValor);
                },
                preConfirm: () => {
                    const data = document.getElementById('swal-data-recebimento').value;
                    const valorTexto = document.getElementById('swal-valor-parcela').value;
                    const valor = parseMoeda(valorTexto);

                    if (!data) {
                        Swal.showValidationMessage('Por favor, informe a data de recebimento.');
                        return false;
                    }

                    if (valorTexto && valor === null) {
                        Swal.showValidationMessage('Valor invalido.');
                        return false;
                    }

                    if (valor !== null && valor < 0) {
                        Swal.showValidationMessage('O valor nao pode ser negativo.');
                        return false;
                    }

                    return { data_recebimento: data, valor: valor };
                }
            }).then(result => {
                if (result.isConfirmed && result.value) {
                    showLoadingSwal('Salvando...');

                    const requestData = {
                        data_recebimento: result.value.data_recebimento
                    };

                    // Only send valor if it was provided
                    if (result.value.valor !== null) {
                        requestData.valor = result.value.valor;
                    }

                    $.ajax({
                        url: `/financeiro/recebiveis/parcelas/${parcelaId}/data-recebimento`,
                        method: 'PUT',
                        data: requestData,
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                    }).done(function (response) {
                        let mensagem = response.message || 'Dados da parcela salvos com sucesso.';
                        if (response.nova_parcela_gerada) {
                            mensagem += ' Uma nova parcela vitalicia foi gerada.';
                        }

                        Swal.fire({
                            title: 'Parcela Atualizada!',
                            text: mensagem,
                            icon: 'success',
                            iconColor: '#10B981',
                            confirmButtonText: 'OK',
                            customClass: {
                                popup: 'swal-recebiveis',
                                confirmButton: 'btn btn-success'
                            },
                            buttonsStyling: false
                        }).then(() => {
                            // Atualiza de forma reativa sem recarregar a página
                                atualizarLinhaContrato(currentVendaId);
                            carregarParcelas(currentVendaId);
                            // Reabre o modal de parcelas
                            $('#parcelasModal').modal('show');
                        });
                    }).fail(function (xhr) {
                        const errorMsg = xhr.responseJSON?.message || 'Erro ao atualizar parcela.';
                        Swal.fire({
                            title: 'Erro!',
                            text: errorMsg,
                            icon: 'error',
                            confirmButtonText: 'OK',
                            customClass: {
                                popup: 'swal-recebiveis',
                                confirmButton: 'btn btn-danger'
                            },
                            buttonsStyling: false
                        });
                    });
                } else if (result.isDismissed) {
                    // User cancelled - reopen the parcelas modal
                    $('#parcelasModal').modal('show');
                }
            });
        }, 300);
    });

    // Excluir Parcela Individual
    $(document).on('click', '.excluir-parcela', function () {
        const parcelaId = $(this).data('id');
        const numeroParcela = $(this).data('parcela');

        Swal.fire({
            title: 'Excluir Parcela',
            html: `
                <div style="text-align: left; padding: 1rem 0;">
                    <p style="margin-bottom: 0.75rem;">Voce tem certeza que deseja excluir a <strong>parcela #${numeroParcela}</strong>?</p>
                    <div style="background: rgba(239, 68, 68, 0.1); padding: 0.75rem 1rem; border-radius: 8px; margin-top: 1rem;">
                        <p style="color: var(--rcb-danger); font-size: 0.875rem; margin: 0; display: flex; align-items: flex-start; gap: 0.5rem;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            Esta acao nao pode ser desfeita.
                        </p>
                    </div>
                </div>
            `,
            icon: 'warning',
            iconColor: '#EF4444',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'swal-recebiveis',
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then(result => {
            if (result.isConfirmed) {
                showLoadingSwal('Excluindo parcela...');

                $.ajax({
                    url: `/financeiro/recebiveis/parcelas/${parcelaId}`,
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                }).done(function (response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Parcela Excluida!',
                            text: response.message,
                            icon: 'success',
                            iconColor: '#10B981',
                            confirmButtonText: 'OK',
                            customClass: {
                                popup: 'swal-recebiveis',
                                confirmButton: 'btn btn-success'
                            },
                            buttonsStyling: false
                        }).then(() => {
                            // Atualiza de forma reativa
                                atualizarLinhaContrato(currentVendaId);
                            carregarParcelas(currentVendaId);
                        });
                    }
                }).fail(function (xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Erro ao excluir parcela.';
                    Swal.fire({
                        title: 'Erro!',
                        text: errorMsg,
                        icon: 'error',
                        customClass: {
                            popup: 'swal-recebiveis',
                            confirmButton: 'btn btn-danger'
                        },
                        buttonsStyling: false
                    });
                });
            }
        });
    });

    // Excluir Todos os Recebiveis
    $(document).on('click', '#btnExcluirTodos', function () {
        if (!currentVendaId) {
            showToast('error', 'ID do contrato nao encontrado.');
            return;
        }

        Swal.fire({
            title: 'Excluir Todos os Recebiveis',
            html: `
                <div style="text-align: left; padding: 1rem 0;">
                    <p style="margin-bottom: 0.75rem;">Voce tem certeza que deseja excluir <strong>TODAS</strong> as parcelas deste contrato?</p>
                    <div style="background: rgba(239, 68, 68, 0.1); padding: 0.75rem 1rem; border-radius: 8px; margin-top: 1rem;">
                        <p style="color: var(--rcb-danger); font-size: 0.875rem; margin: 0; display: flex; align-items: flex-start; gap: 0.5rem;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            Esta acao ira remover permanentemente todos os recebiveis. Nao pode ser desfeita.
                        </p>
                    </div>
                </div>
            `,
            icon: 'warning',
            iconColor: '#EF4444',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir todos',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'swal-recebiveis',
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then(result => {
            if (result.isConfirmed) {
                showLoadingSwal('Excluindo recebiveis...');

                const vendaIdToRemove = currentVendaId;

                $.ajax({
                    url: `/financeiro/recebiveis/${vendaIdToRemove}/todos`,
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                }).done(function (response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Recebiveis Excluidos!',
                            text: response.message,
                            icon: 'success',
                            iconColor: '#10B981',
                            confirmButtonText: 'OK',
                            customClass: {
                                popup: 'swal-recebiveis',
                                confirmButton: 'btn btn-success'
                            },
                            buttonsStyling: false
                        }).then(() => {
                            // Fecha o modal
                            $('#parcelasModal').modal('hide');

                            // Atualiza KPIs
    
                            // Remove a linha do contrato da tabela
                            const row = $(`tr[data-venda-id="${vendaIdToRemove}"]`);
                            table.row(row).remove().draw(false);

                            // Atualiza o contador
                            updateContractsCount();

                            // Limpa o currentVendaId
                            currentVendaId = null;
                        });
                    }
                }).fail(function (xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Erro ao excluir recebiveis.';
                    Swal.fire({
                        title: 'Erro!',
                        text: errorMsg,
                        icon: 'error',
                        customClass: {
                            popup: 'swal-recebiveis',
                            confirmButton: 'btn btn-danger'
                        },
                        buttonsStyling: false
                    });
                });
            }
        });
    });

    // ============================================
    // Selection Handlers (Checkboxes)
    // ============================================

    // Checkbox individual
    $(document).on('change', '.parcela-checkbox', function () {
        const parcelaId = parseInt($(this).data('id'));

        if ($(this).is(':checked')) {
            selectedParcelas.add(parcelaId);
        } else {
            selectedParcelas.delete(parcelaId);
        }

        updateSelectionUI();
        updateSelectAllCheckbox();
    });

    // Checkbox "Selecionar Todos"
    $(document).on('change', '#selectAllParcelas', function () {
        const isChecked = $(this).is(':checked');

        if (isChecked) {
            // Selecionar todas as parcelas (não apenas as da página atual)
            allParcelas.forEach(p => selectedParcelas.add(p.id));
        } else {
            // Desmarcar todas
            selectedParcelas.clear();
        }

        // Atualizar checkboxes visíveis
        $('.parcela-checkbox').prop('checked', isChecked);
        updateSelectionUI();
    });

    // Atualizar estado do checkbox "Selecionar Todos"
    function updateSelectAllCheckbox() {
        const totalParcelas = allParcelas.length;
        const totalSelecionadas = selectedParcelas.size;

        if (totalParcelas === 0) {
            $('#selectAllParcelas').prop('checked', false).prop('indeterminate', false);
        } else if (totalSelecionadas === totalParcelas) {
            $('#selectAllParcelas').prop('checked', true).prop('indeterminate', false);
        } else if (totalSelecionadas > 0) {
            $('#selectAllParcelas').prop('checked', false).prop('indeterminate', true);
        } else {
            $('#selectAllParcelas').prop('checked', false).prop('indeterminate', false);
        }
    }

    // Atualizar UI de seleção (mostrar/esconder botão, contador)
    function updateSelectionUI() {
        const count = selectedParcelas.size;
        $('#countSelecionados').text(count);
        $('#countEditarSelecionados').text(count);
        $('#countDarBaixa').text(count);

        if (count > 0) {
            $('#btnExcluirSelecionados').show();
            $('#btnEditarSelecionados').show();
            $('#btnDarBaixaSelecionados').show();
        } else {
            $('#btnExcluirSelecionados').hide();
            $('#btnEditarSelecionados').hide();
            $('#btnDarBaixaSelecionados').hide();
        }
    }

    // Excluir Parcelas Selecionadas
    $(document).on('click', '#btnExcluirSelecionados', function () {
        const count = selectedParcelas.size;

        if (count === 0) {
            showToast('warning', 'Nenhuma parcela selecionada.');
            return;
        }

        Swal.fire({
            title: 'Excluir Parcelas Selecionadas',
            html: `
                <div style="text-align: left; padding: 1rem 0;">
                    <p style="margin-bottom: 0.75rem;">Voce tem certeza que deseja excluir <strong>${count} parcela(s)</strong> selecionada(s)?</p>
                    <div style="background: rgba(239, 68, 68, 0.1); padding: 0.75rem 1rem; border-radius: 8px; margin-top: 1rem;">
                        <p style="color: var(--rcb-danger); font-size: 0.875rem; margin: 0; display: flex; align-items: flex-start; gap: 0.5rem;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            Esta acao nao pode ser desfeita.
                        </p>
                    </div>
                </div>
            `,
            icon: 'warning',
            iconColor: '#EF4444',
            showCancelButton: true,
            confirmButtonText: `Sim, excluir ${count} parcela(s)`,
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'swal-recebiveis',
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then(result => {
            if (result.isConfirmed) {
                showLoadingSwal('Excluindo parcelas...');

                const parcelaIds = Array.from(selectedParcelas);

                $.ajax({
                    url: '/financeiro/recebiveis/parcelas/excluir-multiplas',
                    method: 'POST',
                    data: {
                        parcela_ids: parcelaIds
                    },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                }).done(function (response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Parcelas Excluidas!',
                            text: response.message,
                            icon: 'success',
                            iconColor: '#10B981',
                            confirmButtonText: 'OK',
                            customClass: {
                                popup: 'swal-recebiveis',
                                confirmButton: 'btn btn-success'
                            },
                            buttonsStyling: false
                        }).then(() => {
                            // Limpar seleção
                            selectedParcelas.clear();
                            updateSelectionUI();

                            // Atualizar dados
                                atualizarLinhaContrato(currentVendaId);
                            carregarParcelas(currentVendaId);
                        });
                    }
                }).fail(function (xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Erro ao excluir parcelas.';
                    Swal.fire({
                        title: 'Erro!',
                        text: errorMsg,
                        icon: 'error',
                        customClass: {
                            popup: 'swal-recebiveis',
                            confirmButton: 'btn btn-danger'
                        },
                        buttonsStyling: false
                    });
                });
            }
        });
    });

    // Dar Baixa em Parcelas Selecionadas (Pendente → Pago)
    $(document).on('click', '#btnDarBaixaSelecionados', function () {
        const count = selectedParcelas.size;

        if (count === 0) {
            showToast('warning', 'Nenhuma parcela selecionada.');
            return;
        }

        // Filtrar apenas parcelas pendentes
        const parcelasPendentes = allParcelas.filter(p => selectedParcelas.has(p.id) && p.status === 'PENDENTE');
        const countPendentes = parcelasPendentes.length;

        if (countPendentes === 0) {
            Swal.fire({
                title: 'Nenhuma parcela pendente',
                text: 'As parcelas selecionadas já estão pagas ou canceladas.',
                icon: 'info',
                iconColor: '#06B6D4',
                confirmButtonText: 'OK',
                customClass: {
                    popup: 'swal-recebiveis',
                    confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
            });
            return;
        }

        const valorTotal = parcelasPendentes.reduce((sum, p) => sum + parseFloat(p.valor || 0), 0);

        Swal.fire({
            title: 'Dar Baixa em Parcelas',
            html: `
                <div style="text-align: left; padding: 1rem 0;">
                    <p style="margin-bottom: 0.75rem;">Confirmar pagamento de <strong>${countPendentes} parcela(s)</strong> pendente(s)?</p>
                    <div style="background: rgba(16, 185, 129, 0.1); padding: 0.75rem 1rem; border-radius: 8px; margin-top: 0.75rem;">
                        <p style="color: var(--rcb-success, #10B981); font-size: 0.875rem; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;">
                                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                            </svg>
                            Valor total: <strong style="font-family: 'JetBrains Mono', monospace;">R$ ${valorTotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
                        </p>
                    </div>
                    <div style="background: rgba(6, 182, 212, 0.1); padding: 0.75rem 1rem; border-radius: 8px; margin-top: 0.5rem;">
                        <p style="color: var(--rcb-info, #06B6D4); font-size: 0.8125rem; margin: 0; display: flex; align-items: flex-start; gap: 0.5rem;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="16" x2="12" y2="12"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                            </svg>
                            A data de recebimento sera igual a data de vencimento de cada parcela.
                        </p>
                    </div>
                </div>
            `,
            icon: 'question',
            iconColor: '#10B981',
            showCancelButton: true,
            confirmButtonText: `Confirmar Baixa (${countPendentes})`,
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'swal-recebiveis',
                confirmButton: 'btn btn-success me-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then(result => {
            if (result.isConfirmed) {
                showLoadingSwal('Processando baixa das parcelas...');

                const parcelaIds = parcelasPendentes.map(p => p.id);

                $.ajax({
                    url: '/financeiro/recebiveis/parcelas/dar-baixa-multiplas',
                    method: 'POST',
                    data: {
                        parcela_ids: parcelaIds
                    },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                }).done(function (response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Baixa Realizada!',
                            text: response.message,
                            icon: 'success',
                            iconColor: '#10B981',
                            confirmButtonText: 'OK',
                            customClass: {
                                popup: 'swal-recebiveis',
                                confirmButton: 'btn btn-success'
                            },
                            buttonsStyling: false
                        }).then(() => {
                            selectedParcelas.clear();
                            updateSelectionUI();
                                atualizarLinhaContrato(currentVendaId);
                            carregarParcelas(currentVendaId);
                        });
                    }
                }).fail(function (xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Erro ao dar baixa nas parcelas.';
                    Swal.fire({
                        title: 'Erro!',
                        text: errorMsg,
                        icon: 'error',
                        customClass: {
                            popup: 'swal-recebiveis',
                            confirmButton: 'btn btn-danger'
                        },
                        buttonsStyling: false
                    });
                });
            }
        });
    });

    // Editar Parcelas Selecionadas em Massa
    $(document).on('click', '#btnEditarSelecionados', function () {
        const count = selectedParcelas.size;

        if (count === 0) {
            showToast('warning', 'Nenhuma parcela selecionada.');
            return;
        }

        const hoje = new Date().toISOString().split('T')[0];

        // Hide Bootstrap modal before showing SweetAlert2 to avoid aria-hidden conflict
        $('#parcelasModal').modal('hide');

        // Wait for modal to fully hide before showing SweetAlert
        setTimeout(() => {
            Swal.fire({
                title: 'Editar Parcelas em Massa',
                html: `
                    <div style="text-align: left; padding: 1rem 0;">
                        <div style="background: linear-gradient(135deg, rgba(124, 58, 237, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%); padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid rgba(124, 58, 237, 0.2);">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #7C3AED 0%, #8B5CF6 100%); display: flex; align-items: center; justify-content: center;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p style="margin: 0; font-weight: 700; font-size: 1rem; color: var(--rcb-text-primary);">${count} parcela(s) selecionada(s)</p>
                                    <p style="margin: 0; font-size: 0.8125rem; color: var(--rcb-text-muted);">As alteracoes serao aplicadas a todas</p>
                                </div>
                            </div>
                        </div>

                        <div style="margin-bottom: 1.25rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.875rem; color: var(--rcb-text-primary);">
                                Valor Vitalicio
                            </label>
                            <div style="position: relative;">
                                <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--rcb-text-muted); font-weight: 600; font-size: 0.875rem;">R$</span>
                                <input type="text" id="swal-valor-vitalicio"
                                       inputmode="numeric"
                                       autocomplete="off"
                                       style="width: 100%; padding: 0.75rem 1rem 0.75rem 40px; border: 1px solid var(--rcb-card-border); border-radius: 10px; font-size: 1rem; font-family: 'JetBrains Mono', 'IBM Plex Mono', monospace; background: var(--rcb-card-bg); color: var(--rcb-text-primary); transition: all 0.2s ease;"
                                       placeholder="0,00">
                            </div>
                            <p style="color: var(--rcb-text-muted); font-size: 0.75rem; margin: 0.5rem 0 0 0;">
                                Este valor sera replicado para todas as parcelas selecionadas
                            </p>
                        </div>

                        <div style="margin-bottom: 1.25rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.875rem; color: var(--rcb-text-primary);">
                                Data de Vencimento (1ª parcela)
                            </label>
                            <input type="date" id="swal-data-vencimento"
                                   style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--rcb-card-border); border-radius: 10px; font-size: 1rem; background: var(--rcb-card-bg); color: var(--rcb-text-primary); transition: all 0.2s ease;"
                                   value="${hoje}">
                            <p style="color: var(--rcb-text-muted); font-size: 0.75rem; margin: 0.5rem 0 0 0;">
                                As demais parcelas terao datas incrementadas mes a mes
                            </p>
                        </div>

                        <div style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.1) 0%, rgba(34, 211, 238, 0.05) 100%); padding: 0.875rem 1rem; border-radius: 10px; border: 1px solid rgba(6, 182, 212, 0.2);">
                            <div style="display: flex; align-items: flex-start; gap: 0.625rem;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#06B6D4" stroke-width="2" style="flex-shrink: 0; margin-top: 1px;">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="16" x2="12" y2="12"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                </svg>
                                <div style="font-size: 0.8125rem; color: var(--rcb-text-secondary); line-height: 1.5;">
                                    <strong style="color: var(--rcb-info);">Exemplo:</strong> Data 01/01/2026<br>
                                    Parcela 1: 01/01 → Parcela 2: 01/02 → Parcela 3: 01/03...
                                </div>
                            </div>
                        </div>
                    </div>
                `,
                icon: null,
                showCancelButton: true,
                confirmButtonText: 'Salvar Alteracoes',
                cancelButtonText: 'Cancelar',
                customClass: {
                    popup: 'swal-recebiveis swal-wide',
                    confirmButton: 'btn btn-primary me-2',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false,
                didOpen: () => {
                    // Apply currency mask after modal opens
                    const inputValor = document.getElementById('swal-valor-vitalicio');
                    if (inputValor) {
                        aplicarMascaraMoeda(inputValor);
                        // Focus on the input
                        inputValor.focus();
                    }
                },
                preConfirm: () => {
                    const valorTexto = document.getElementById('swal-valor-vitalicio').value;
                    const dataVencimento = document.getElementById('swal-data-vencimento').value;
                    const valor = parseMoeda(valorTexto);

                    if (!valorTexto || valorTexto.trim() === '') {
                        Swal.showValidationMessage('Por favor, informe o valor vitalicio.');
                        return false;
                    }

                    if (valor === null || valor <= 0) {
                        Swal.showValidationMessage('Informe um valor valido maior que zero.');
                        return false;
                    }

                    if (!dataVencimento) {
                        Swal.showValidationMessage('Por favor, informe a data de vencimento.');
                        return false;
                    }

                    return { valor: valor, dataPagamento: dataVencimento };
                }
            }).then(result => {
                if (result.isConfirmed && result.value) {
                    showLoadingSwal('Atualizando parcelas...');

                    const parcelaIds = Array.from(selectedParcelas);

                    $.ajax({
                        url: '/financeiro/recebiveis/parcelas/editar-multiplas',
                        method: 'PUT',
                        data: {
                            parcela_ids: parcelaIds,
                            valor: result.value.valor,
                            data_pagamento: result.value.dataPagamento
                        },
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                    }).done(function (response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Parcelas Atualizadas!',
                                text: response.message,
                                icon: 'success',
                                iconColor: '#10B981',
                                confirmButtonText: 'OK',
                                customClass: {
                                    popup: 'swal-recebiveis',
                                    confirmButton: 'btn btn-success'
                                },
                                buttonsStyling: false
                            }).then(() => {
                                // Limpar selecao
                                selectedParcelas.clear();
                                updateSelectionUI();

                                // Atualizar dados
                                        atualizarLinhaContrato(currentVendaId);
                                carregarParcelas(currentVendaId);

                                // Reabrir modal de parcelas
                                $('#parcelasModal').modal('show');
                            });
                        }
                    }).fail(function (xhr) {
                        const errorMsg = xhr.responseJSON?.message || 'Erro ao editar parcelas.';
                        Swal.fire({
                            title: 'Erro!',
                            text: errorMsg,
                            icon: 'error',
                            customClass: {
                                popup: 'swal-recebiveis',
                                confirmButton: 'btn btn-danger'
                            },
                            buttonsStyling: false
                        }).then(() => {
                            // Reabrir modal de parcelas
                            $('#parcelasModal').modal('show');
                        });
                    });
                } else if (result.isDismissed) {
                    // User cancelled - reopen the parcelas modal
                    $('#parcelasModal').modal('show');
                }
            });
        }, 300);
    });

    // Generate Manual Receivables
    $(document).on('click', '#btnGerarManual', function () {
        if (!currentVendaId) {
            showToast('error', 'ID do contrato nao encontrado.');
            return;
        }

        const hoje = new Date().toISOString().split('T')[0];

        // Hide Bootstrap modal before showing SweetAlert2 to avoid aria-hidden conflict
        $('#parcelasModal').modal('hide');

        // Wait for modal to fully hide before showing SweetAlert
        setTimeout(() => {
            Swal.fire({
                title: 'Gerar Parcelas Manualmente',
                html: `
                    <div style="text-align: left; padding: 1rem 0;">
                        <div style="background: linear-gradient(135deg, rgba(124, 58, 237, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%); padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid rgba(124, 58, 237, 0.2);">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #7C3AED 0%, #8B5CF6 100%); display: flex; align-items: center; justify-content: center;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="12" y1="8" x2="12" y2="16"/>
                                        <line x1="8" y1="12" x2="16" y2="12"/>
                                    </svg>
                                </div>
                                <div>
                                    <p style="margin: 0; font-weight: 700; font-size: 1rem; color: var(--rcb-text-primary);">Geracao Manual</p>
                                    <p style="margin: 0; font-size: 0.8125rem; color: var(--rcb-text-muted);">As parcelas serao criadas a partir do ultimo numero existente</p>
                                </div>
                            </div>
                        </div>

                        <div style="margin-bottom: 1.25rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.875rem; color: var(--rcb-text-primary);">
                                Quantidade de Parcelas
                            </label>
                            <input type="number" id="swalQuantidade" min="1" max="120" value="1"
                                   inputmode="numeric"
                                   autocomplete="off"
                                   style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--rcb-card-border); border-radius: 10px; font-size: 1rem; font-family: 'JetBrains Mono', 'IBM Plex Mono', monospace; background: var(--rcb-card-bg); color: var(--rcb-text-primary); transition: all 0.2s ease;" />
                        </div>

                        <div style="margin-bottom: 1.25rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.875rem; color: var(--rcb-text-primary);">
                                Data Inicial
                            </label>
                            <input type="date" id="swalDataInicial" value="${hoje}"
                                   style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--rcb-card-border); border-radius: 10px; font-size: 1rem; background: var(--rcb-card-bg); color: var(--rcb-text-primary); transition: all 0.2s ease;" />
                            <p style="color: var(--rcb-text-muted); font-size: 0.75rem; margin: 0.5rem 0 0 0;">
                                As demais parcelas terao datas incrementadas mes a mes
                            </p>
                        </div>

                        <div style="margin-bottom: 1.25rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.875rem; color: var(--rcb-text-primary);">
                                Valor por Parcela <span style="font-weight: 400; color: var(--rcb-text-muted);">(opcional - usa valor da regra)</span>
                            </label>
                            <div style="position: relative;">
                                <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--rcb-text-muted); font-weight: 600; font-size: 0.875rem;">R$</span>
                                <input type="text" id="swalValor"
                                       inputmode="numeric"
                                       autocomplete="off"
                                       placeholder="Automatico"
                                       style="width: 100%; padding: 0.75rem 1rem 0.75rem 40px; border: 1px solid var(--rcb-card-border); border-radius: 10px; font-size: 1rem; font-family: 'JetBrains Mono', 'IBM Plex Mono', monospace; background: var(--rcb-card-bg); color: var(--rcb-text-primary); transition: all 0.2s ease;" />
                            </div>
                            <p style="color: var(--rcb-text-muted); font-size: 0.75rem; margin: 0.5rem 0 0 0;">
                                Se nao informado, sera utilizado o percentual vitalicio da regra de comissionamento
                            </p>
                        </div>

                        <div style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.1) 0%, rgba(34, 211, 238, 0.05) 100%); padding: 0.875rem 1rem; border-radius: 10px; border: 1px solid rgba(6, 182, 212, 0.2);">
                            <div style="display: flex; align-items: flex-start; gap: 0.625rem;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#06B6D4" stroke-width="2" style="flex-shrink: 0; margin-top: 1px;">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="16" x2="12" y2="12"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                </svg>
                                <div style="font-size: 0.8125rem; color: var(--rcb-text-secondary); line-height: 1.5;">
                                    <strong style="color: var(--rcb-info);">Exemplo:</strong> Data 01/01/2026, 3 parcelas<br>
                                    Parcela 1: 01/01 → Parcela 2: 01/02 → Parcela 3: 01/03
                                </div>
                            </div>
                        </div>
                    </div>
                `,
                icon: null,
                showCancelButton: true,
                confirmButtonText: 'Gerar Parcelas',
                cancelButtonText: 'Cancelar',
                customClass: {
                    popup: 'swal-recebiveis swal-wide',
                    confirmButton: 'btn btn-primary me-2',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false,
                didOpen: () => {
                    // Apply currency mask after modal opens
                    const valorInput = document.getElementById('swalValor');
                    if (valorInput) {
                        aplicarMascaraMoeda(valorInput);
                    }
                    // Focus on the first input
                    const qtdInput = document.getElementById('swalQuantidade');
                    if (qtdInput) {
                        qtdInput.focus();
                        qtdInput.select();
                    }
                },
                preConfirm: () => {
                    const quantidade = parseInt(document.getElementById('swalQuantidade').value);
                    const dataInicial = document.getElementById('swalDataInicial').value;
                    const valorTexto = document.getElementById('swalValor').value;

                    if (!quantidade || quantidade < 1) {
                        Swal.showValidationMessage('Informe a quantidade de parcelas.');
                        return false;
                    }

                    if (!dataInicial) {
                        Swal.showValidationMessage('Informe a data inicial.');
                        return false;
                    }

                    let valor = null;
                    if (valorTexto && valorTexto.trim() !== '') {
                        valor = parseMoeda(valorTexto);
                        if (valor === null || valor <= 0) {
                            Swal.showValidationMessage('Informe um valor valido maior que zero.');
                            return false;
                        }
                    }

                    return { quantidade, dataInicial, valor };
                }
            }).then(result => {
                if (result.isConfirmed) {
                    const { quantidade, dataInicial, valor } = result.value;

                    showLoadingSwal('Gerando parcelas...');

                    const payload = {
                        quantidade_parcelas: quantidade,
                        data_inicial: dataInicial
                    };

                    if (valor) {
                        payload.valor = valor;
                    }

                    $.ajax({
                        url: `/financeiro/recebiveis/${currentVendaId}/gerar-manual`,
                        method: 'POST',
                        data: payload,
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                    }).done(function (response) {
                        if (response.success) {
                            let detalhesHtml = '';

                            if (response.parcelas_criadas && response.parcelas_criadas.length > 0) {
                                detalhesHtml = `
                                    <div style="text-align: left; margin-top: 1.5rem;">
                                        <h6 style="font-weight: 600; margin-bottom: 0.75rem; color: var(--rcb-success); display: flex; align-items: center; gap: 0.5rem;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <line x1="12" y1="8" x2="12" y2="16"/>
                                                <line x1="8" y1="12" x2="16" y2="12"/>
                                            </svg>
                                            Parcelas Criadas
                                        </h6>
                                        <div style="overflow-x: auto; border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 10px; background: rgba(16, 185, 129, 0.05); max-height: 250px; overflow-y: auto;">
                                            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                                                <thead style="position: sticky; top: 0; background: rgba(16, 185, 129, 0.1);">
                                                    <tr>
                                                        <th style="padding: 0.625rem 0.75rem; text-align: left; font-weight: 600;">Parcela</th>
                                                        <th style="padding: 0.625rem 0.75rem; text-align: right; font-weight: 600;">Valor</th>
                                                        <th style="padding: 0.625rem 0.75rem; text-align: left; font-weight: 600;">Vencimento</th>
                                                    </tr>
                                                </thead>
                                                <tbody>`;

                                response.parcelas_criadas.forEach(p => {
                                    detalhesHtml += `
                                        <tr style="border-top: 1px solid rgba(16, 185, 129, 0.15);">
                                            <td style="padding: 0.5rem 0.75rem; font-weight: 600;">${p.parcela}</td>
                                            <td style="padding: 0.5rem 0.75rem; text-align: right; font-family: 'JetBrains Mono', 'IBM Plex Mono', monospace;">${formatCurrency(p.valor)}</td>
                                            <td style="padding: 0.5rem 0.75rem;">${p.data_prevista}</td>
                                        </tr>`;
                                });

                                detalhesHtml += '</tbody></table></div></div>';

                                // Summary
                                const totalValor = response.parcelas_criadas.reduce((sum, p) => sum + parseFloat(p.valor), 0);
                                detalhesHtml += `
                                    <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(6, 182, 212, 0.1)); padding: 1rem; border-radius: 10px; margin-top: 1rem; text-align: left;">
                                        <div style="display: flex; justify-content: space-between;">
                                            <span style="font-weight: 500;">Total gerado:</span>
                                            <span style="font-family: 'JetBrains Mono', 'IBM Plex Mono', monospace; font-weight: 600; color: var(--rcb-success);">${formatCurrency(totalValor)}</span>
                                        </div>
                                    </div>`;
                            }

                            Swal.fire({
                                title: 'Parcelas Geradas!',
                                html: response.message + detalhesHtml,
                                icon: 'success',
                                iconColor: '#10B981',
                                width: 550,
                                customClass: {
                                    popup: 'swal-recebiveis',
                                    confirmButton: 'btn btn-success'
                                },
                                buttonsStyling: false
                            }).then(() => {
                                        atualizarLinhaContrato(currentVendaId);
                                carregarParcelas(currentVendaId);
                                // Reabrir modal de parcelas
                                $('#parcelasModal').modal('show');
                            });
                        } else {
                            Swal.fire({
                                title: 'Atencao!',
                                text: response.message,
                                icon: 'warning',
                                iconColor: '#F59E0B',
                                customClass: {
                                    popup: 'swal-recebiveis',
                                    confirmButton: 'btn btn-warning'
                                },
                                buttonsStyling: false
                            }).then(() => {
                                $('#parcelasModal').modal('show');
                            });
                        }
                    }).fail(function (xhr) {
                        const errorMsg = xhr.responseJSON?.message || 'Erro ao gerar parcelas.';
                        Swal.fire({
                            title: 'Erro!',
                            text: errorMsg,
                            icon: 'error',
                            customClass: {
                                popup: 'swal-recebiveis',
                                confirmButton: 'btn btn-danger'
                            },
                            buttonsStyling: false
                        }).then(() => {
                            $('#parcelasModal').modal('show');
                        });
                    });
                } else if (result.isDismissed) {
                    // User cancelled - reopen the parcelas modal
                    $('#parcelasModal').modal('show');
                }
            });
        }, 300);
    });

    // Recalculate Values
    $(document).on('click', '#btnRecalcular', function () {
        if (!currentVendaId) {
            showToast('error', 'ID do contrato nao encontrado.');
            return;
        }

        Swal.fire({
            title: 'Recalcular Valores',
            html: `
                <div style="text-align: left; padding: 1rem 0;">
                    <p style="margin-bottom: 0.75rem;">Os valores das parcelas serao recalculados com base na <strong>regra de comissionamento atual</strong>.</p>
                    <div style="background: var(--rcb-warning-light); padding: 0.75rem 1rem; border-radius: 8px; margin-top: 1rem;">
                        <p style="color: var(--rcb-warning); font-size: 0.875rem; margin: 0; display: flex; align-items: flex-start; gap: 0.5rem;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            Status, datas de vencimento e recebimento serao mantidos.
                        </p>
                    </div>
                </div>
            `,
            icon: 'warning',
            iconColor: '#F59E0B',
            showCancelButton: true,
            confirmButtonText: 'Recalcular',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'swal-recebiveis',
                confirmButton: 'btn btn-warning me-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then(result => {
            if (result.isConfirmed) {
                showLoadingSwal('Recalculando valores...');

                $.ajax({
                    url: `/financeiro/recebiveis/${currentVendaId}/recalcular`,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                }).done(function (response) {
                    if (response.success) {
                        const detalhes = buildRecalculationDetails(response);

                        Swal.fire({
                            title: 'Recebiveis Atualizados!',
                            html: response.message + detalhes,
                            icon: 'success',
                            iconColor: '#10B981',
                            width: 650,
                            customClass: {
                                popup: 'swal-recebiveis',
                                confirmButton: 'btn btn-success'
                            },
                            buttonsStyling: false
                        }).then(() => {
                            carregarParcelas(currentVendaId);
                        });
                    } else {
                        Swal.fire({
                            title: 'Atencao!',
                            text: response.message,
                            icon: 'warning',
                            iconColor: '#F59E0B',
                            customClass: {
                                popup: 'swal-recebiveis',
                                confirmButton: 'btn btn-warning'
                            },
                            buttonsStyling: false
                        });
                    }
                }).fail(function (xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Erro ao recalcular valores.';
                    Swal.fire({
                        title: 'Erro!',
                        text: errorMsg,
                        icon: 'error',
                        customClass: {
                            popup: 'swal-recebiveis',
                            confirmButton: 'btn btn-danger'
                        },
                        buttonsStyling: false
                    });
                });
            }
        });
    });

    // ============================================
    // Helper Functions
    // ============================================

    function showLoadingSwal(message) {
        Swal.fire({
            title: message,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
            customClass: {
                popup: 'swal-recebiveis'
            }
        });
    }

    function showToast(type, message) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });

        Toast.fire({
            icon: type,
            title: message
        });
    }

    function formatCurrency(value) {
        return parseFloat(value).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    }

    function buildRecalculationDetails(response) {
        const temAlteracoes = response.alteracoes && response.alteracoes.length > 0;
        const temNovasParcelas = response.novas_parcelas && response.novas_parcelas.length > 0;

        if (!temAlteracoes && !temNovasParcelas) {
            return '';
        }

        let detalhes = '';

        if (temAlteracoes) {
            detalhes += `
                <div style="text-align: left; margin-top: 1.5rem;">
                    <h6 style="font-weight: 600; margin-bottom: 0.75rem; color: var(--rcb-text-primary);">
                        Parcelas Atualizadas
                    </h6>
                    <div style="overflow-x: auto; border: 1px solid var(--rcb-card-border); border-radius: 8px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                            <thead style="background: var(--rcb-slate-50);">
                                <tr>
                                    <th style="padding: 0.625rem 0.75rem; text-align: left; font-weight: 600;">Parcela</th>
                                    <th style="padding: 0.625rem 0.75rem; text-align: right; font-weight: 600;">Anterior</th>
                                    <th style="padding: 0.625rem 0.75rem; text-align: right; font-weight: 600;">Novo</th>
                                    <th style="padding: 0.625rem 0.75rem; text-align: right; font-weight: 600;">Diferenca</th>
                                </tr>
                            </thead>
                            <tbody>`;

            response.alteracoes.forEach(alt => {
                const corDif = alt.diferenca >= 0 ? 'var(--rcb-success)' : 'var(--rcb-danger)';
                const sinalDif = alt.diferenca >= 0 ? '+' : '';
                detalhes += `
                    <tr style="border-top: 1px solid var(--rcb-card-border);">
                        <td style="padding: 0.625rem 0.75rem; font-weight: 600;">${alt.parcela}</td>
                        <td style="padding: 0.625rem 0.75rem; text-align: right; font-family: 'IBM Plex Mono', monospace;">${formatCurrency(alt.valor_antigo)}</td>
                        <td style="padding: 0.625rem 0.75rem; text-align: right; font-family: 'IBM Plex Mono', monospace;">${formatCurrency(alt.valor_novo)}</td>
                        <td style="padding: 0.625rem 0.75rem; text-align: right; font-family: 'IBM Plex Mono', monospace; color: ${corDif}; font-weight: 600;">${sinalDif}${formatCurrency(alt.diferenca)}</td>
                    </tr>`;
            });

            detalhes += '</tbody></table></div></div>';
        }

        if (temNovasParcelas) {
            detalhes += `
                <div style="text-align: left; margin-top: 1.5rem;">
                    <h6 style="font-weight: 600; margin-bottom: 0.75rem; color: var(--rcb-success); display: flex; align-items: center; gap: 0.5rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="16"/>
                            <line x1="8" y1="12" x2="16" y2="12"/>
                        </svg>
                        Novas Parcelas Criadas
                    </h6>
                    <div style="overflow-x: auto; border: 1px solid var(--rcb-success); border-radius: 8px; background: var(--rcb-success-light);">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                            <thead>
                                <tr>
                                    <th style="padding: 0.625rem 0.75rem; text-align: left; font-weight: 600;">Parcela</th>
                                    <th style="padding: 0.625rem 0.75rem; text-align: right; font-weight: 600;">Valor</th>
                                    <th style="padding: 0.625rem 0.75rem; text-align: left; font-weight: 600;">Vencimento</th>
                                </tr>
                            </thead>
                            <tbody>`;

            response.novas_parcelas.forEach(nova => {
                detalhes += `
                    <tr style="border-top: 1px solid rgba(16, 185, 129, 0.2);">
                        <td style="padding: 0.625rem 0.75rem; font-weight: 600;">${nova.parcela}</td>
                        <td style="padding: 0.625rem 0.75rem; text-align: right; font-family: 'IBM Plex Mono', monospace;">${formatCurrency(nova.valor_novo)}</td>
                        <td style="padding: 0.625rem 0.75rem;">${nova.data_prevista}</td>
                    </tr>`;
            });

            detalhes += '</tbody></table></div></div>';
        }

        if (response.resumo) {
            detalhes += `
                <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(6, 182, 212, 0.1)); padding: 1rem; border-radius: 8px; margin-top: 1.5rem; text-align: left;">`;

            if (temAlteracoes && response.resumo.diferenca_atualizacoes !== undefined) {
                const corDif = response.resumo.diferenca_atualizacoes >= 0 ? 'var(--rcb-success)' : 'var(--rcb-danger)';
                const sinalDif = response.resumo.diferenca_atualizacoes >= 0 ? '+' : '';
                detalhes += `
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="font-weight: 500;">Diferenca nas atualizacoes:</span>
                        <span style="font-family: 'IBM Plex Mono', monospace; font-weight: 600; color: ${corDif};">${sinalDif}${formatCurrency(response.resumo.diferenca_atualizacoes)}</span>
                    </div>`;
            }

            if (temNovasParcelas && response.resumo.total_novas_parcelas !== undefined) {
                detalhes += `
                    <div style="display: flex; justify-content: space-between;">
                        <span style="font-weight: 500;">Total novas parcelas:</span>
                        <span style="font-family: 'IBM Plex Mono', monospace; font-weight: 600; color: var(--rcb-success);">+${formatCurrency(response.resumo.total_novas_parcelas)}</span>
                    </div>`;
            }

            detalhes += '</div>';
        }

        return detalhes;
    }

    function updateContractsCount() {
        const visibleRows = table.rows({ search: 'applied' }).count();
        $('.contracts-count').text(`${visibleRows} registros`);
    }

    function animateTableRows() {
        const rows = document.querySelectorAll('.contract-row');
        rows.forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(10px)';

            setTimeout(() => {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, index * 30);
        });
    }

    // ============================================
    // Initialize
    // ============================================
    animateTableRows();
});

// Add CSS for loading spinner animation and reactive updates
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .loading-spinner {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        color: var(--rcb-text-muted);
    }

    .loading-spinner .spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes highlightPulse {
        0% { background-color: rgba(16, 185, 129, 0.2); }
        50% { background-color: rgba(16, 185, 129, 0.1); }
        100% { background-color: transparent; }
    }

    .highlight-update {
        animation: highlightPulse 1s ease-out forwards;
    }

    tr.highlight-update td {
        animation: highlightPulse 1s ease-out forwards;
    }

    .swal-recebiveis {
        font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
        border-radius: 16px !important;
    }

    .swal-recebiveis .swal2-title {
        font-weight: 700 !important;
    }

    .swal-recebiveis .swal2-html-container {
        font-size: 0.9375rem !important;
    }
`;
document.head.appendChild(style);
