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
    // Filter Tabs
    // ============================================
    $('.filter-tab').on('click', function () {
        const status = $(this).data('status');

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
    // Filter by Implantation Month/Year
    // ============================================
    $('#btnAplicarFiltro').on('click', function () {
        const mesImplantacao = $('#filtroMesImplantacao').val();
        const currentUrl = new URL(window.location.href);

        if (mesImplantacao) {
            currentUrl.searchParams.set('mes_implantacao', mesImplantacao);
        } else {
            currentUrl.searchParams.delete('mes_implantacao');
        }

        window.location.href = currentUrl.toString();
    });

    // Allow pressing Enter on the select to apply filter
    $('#filtroMesImplantacao').on('keypress', function (e) {
        if (e.which === 13) {
            $('#btnAplicarFiltro').click();
        }
    });

    // ============================================
    // Load Parcelas Function
    // ============================================
    function carregarParcelas(vendaId) {
        const tbody = $('#parcelasTable tbody');

        // Show loading state
        tbody.html(`
            <tr class="loading-row">
                <td colspan="6" class="text-center py-4">
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

        $.get(`/financeiro/recebiveis/${vendaId}/parcelas`, function (data) {
            tbody.empty();

            if (data.length === 0) {
                tbody.html(`
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <span style="color: var(--rcb-text-muted)">Nenhuma parcela encontrada</span>
                        </td>
                    </tr>
                `);
                return;
            }

            data.forEach((parcela, index) => {
                const statusBadge = getStatusBadge(parcela.status);
                const actionBtns = getActionButtons(parcela);
                const dataRecebimento = parcela.data_recebimento
                    ? parcela.data_recebimento
                    : '<span style="color: var(--rcb-text-muted)">—</span>';

                const valorFormatado = parseFloat(parcela.valor).toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                });

                tbody.append(`
                    <tr style="animation: fadeInUp 0.3s ease forwards; animation-delay: ${index * 50}ms; opacity: 0;">
                        <td>
                            <span style="font-family: 'IBM Plex Mono', monospace; font-weight: 600; color: var(--rcb-emerald);">
                                ${parcela.parcela}
                            </span>
                        </td>
                        <td>
                            <span style="font-family: 'IBM Plex Mono', monospace; font-weight: 600;">
                                ${valorFormatado}
                            </span>
                        </td>
                        <td>${parcela.data_prevista}</td>
                        <td>${dataRecebimento}</td>
                        <td>${statusBadge}</td>
                        <td>${actionBtns}</td>
                    </tr>
                `);
            });

            $('#parcelasModal').modal('show');
        }).fail(function () {
            tbody.html(`
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <span style="color: var(--rcb-danger)">Erro ao carregar parcelas</span>
                    </td>
                </tr>
            `);
            showToast('error', 'Erro ao carregar parcelas');
        });
    }

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
        if (parcela.status === 'PAGO') {
            return `
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
                    Editar
                </button>`;
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
                </div>`;
        }
        return '<span style="color: var(--rcb-text-muted)">—</span>';
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
                }).done(function () {
                    Swal.fire({
                        title: 'Pagamento Registrado!',
                        text: 'A parcela foi marcada como paga com sucesso.',
                        icon: 'success',
                        iconColor: '#10B981',
                        confirmButtonText: 'OK',
                        customClass: {
                            popup: 'swal-recebiveis',
                            confirmButton: 'btn btn-success'
                        },
                        buttonsStyling: false
                    }).then(() => {
                        $('#parcelasModal').modal('hide');
                        location.reload();
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
                        Swal.fire({
                            title: 'Parcela Atualizada!',
                            text: response.message || 'Dados da parcela salvos com sucesso.',
                            icon: 'success',
                            iconColor: '#10B981',
                            confirmButtonText: 'OK',
                            customClass: {
                                popup: 'swal-recebiveis',
                                confirmButton: 'btn btn-success'
                            },
                            buttonsStyling: false
                        }).then(() => {
                            location.reload();
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

// Add CSS for loading spinner animation
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
