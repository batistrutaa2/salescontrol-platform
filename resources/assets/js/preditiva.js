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
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json',
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

    // ===== REGRAS DE PRIORIZACAO =====
    let camposDisponiveis = [];
    let regraEditando = null;

    function carregarCamposDisponiveis() {
        $.ajax({
            url: '/comercial/preditiva/regras/campos',
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    camposDisponiveis = response.campos;
                    preencherSelectCampos();
                }
            },
            error: function (xhr) {
                console.error('Erro ao carregar campos:', xhr);
            }
        });
    }

    function preencherSelectCampos() {
        const select = $('#regraCampo');
        select.find('option:not(:first)').remove();

        camposDisponiveis.forEach(campo => {
            select.append(`<option value="${campo.campo}" data-tipo="${campo.tipo}">${campo.label}</option>`);
        });
    }

    function atualizarOperadores(campoSelecionado) {
        const selectOperador = $('#regraOperador');
        selectOperador.find('option:not(:first)').remove();

        if (!campoSelecionado) {
            selectOperador.prop('disabled', true);
            return;
        }

        const campo = camposDisponiveis.find(c => c.campo === campoSelecionado);
        if (!campo) return;

        selectOperador.prop('disabled', false);
        campo.operadores.forEach(op => {
            let label = op;
            if (op === '=') label = 'Igual a (=)';
            else if (op === '!=') label = 'Diferente de (!=)';
            else if (op === '>') label = 'Maior que (>)';
            else if (op === '>=') label = 'Maior ou igual (>=)';
            else if (op === '<') label = 'Menor que (<)';
            else if (op === '<=') label = 'Menor ou igual (<=)';
            else if (op === 'LIKE') label = 'Contem (LIKE)';
            else if (op === 'IN') label = 'Esta em (IN)';

            selectOperador.append(`<option value="${op}">${label}</option>`);
        });

        // Se o campo tiver valores fixos (enum), popular sugestoes
        if (campo.valores) {
            mostrarSugestoes(campo.valores);
        } else {
            // Buscar valores unicos para o campo
            carregarValoresUnicos(campoSelecionado);
        }
    }

    function carregarValoresUnicos(campo) {
        $.ajax({
            url: `/comercial/preditiva/regras/valores/${campo}`,
            method: 'GET',
            success: function (response) {
                if (response.success && response.valores) {
                    mostrarSugestoes(response.valores.slice(0, 10));
                }
            },
            error: function () {
                $('#valorSuggestions').empty();
            }
        });
    }

    function mostrarSugestoes(valores) {
        const container = $('#valorSuggestions');
        container.empty();

        valores.forEach(valor => {
            container.append(`<span class="pd-valor-suggestion" data-valor="${valor}">${valor}</span>`);
        });
    }

    function carregarRegras() {
        $.ajax({
            url: '/comercial/preditiva/regras',
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    renderizarRegras(response.regras);
                }
            },
            error: function (xhr) {
                console.error('Erro ao carregar regras:', xhr);
            }
        });
    }

    function renderizarRegras(regras) {
        const container = $('#regras-lista');
        const emptyState = $('#regras-empty');

        // Remove itens anteriores exceto o empty state
        container.find('.pd-rule-item').remove();

        if (!regras || regras.length === 0) {
            emptyState.show();
            return;
        }

        emptyState.hide();

        regras.forEach(regra => {
            const statusClass = regra.ativo === 'Y' ? 'active' : 'inactive';
            const toggleIcon = regra.ativo === 'Y' ?
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>' :
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>';

            const campoConfig = camposDisponiveis.find(c => c.campo === regra.campo);
            const campoLabel = campoConfig ? campoConfig.label : regra.campo;

            const conditionText = `${campoLabel} ${regra.operador} "${regra.valor}"`;

            const html = `
                <div class="pd-rule-item" data-id="${regra.id}" draggable="true">
                    <div class="pd-rule-drag">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/>
                            <circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/>
                        </svg>
                    </div>
                    <div class="pd-rule-status ${statusClass}"></div>
                    <div class="pd-rule-info">
                        <div class="pd-rule-name">${regra.nome}</div>
                        <div class="pd-rule-condition">${conditionText}</div>
                    </div>
                    <div class="pd-rule-peso">
                        Peso: <span>${regra.peso}</span>
                    </div>
                    <div class="pd-rule-actions">
                        <button class="pd-rule-btn btn-toggle" title="${regra.ativo === 'Y' ? 'Desativar' : 'Ativar'}" data-id="${regra.id}">
                            ${toggleIcon}
                        </button>
                        <button class="pd-rule-btn btn-edit" title="Editar" data-id="${regra.id}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </button>
                        <button class="pd-rule-btn btn-delete" title="Excluir" data-id="${regra.id}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `;

            container.append(html);
        });

        // Configurar drag-drop
        setupDragDrop();
    }

    function setupDragDrop() {
        const container = document.getElementById('regras-lista');
        const items = container.querySelectorAll('.pd-rule-item');

        items.forEach(item => {
            item.addEventListener('dragstart', handleDragStart);
            item.addEventListener('dragend', handleDragEnd);
            item.addEventListener('dragover', handleDragOver);
            item.addEventListener('drop', handleDrop);
        });
    }

    let draggedItem = null;

    function handleDragStart(e) {
        draggedItem = this;
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    }

    function handleDragEnd() {
        this.classList.remove('dragging');
        draggedItem = null;

        // Enviar nova ordem para o servidor
        const ordens = [];
        document.querySelectorAll('.pd-rule-item').forEach(item => {
            ordens.push(parseInt(item.dataset.id));
        });

        $.ajax({
            url: '/comercial/preditiva/regras/reordenar',
            method: 'POST',
            data: {
                ordens: ordens,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    toastr.info('Ordem das regras atualizada');
                }
            },
            error: function () {
                toastr.error('Erro ao reordenar regras');
                carregarRegras();
            }
        });
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const afterElement = getDragAfterElement(this.parentElement, e.clientY);
        const container = document.getElementById('regras-lista');

        if (afterElement == null) {
            container.appendChild(draggedItem);
        } else {
            container.insertBefore(draggedItem, afterElement);
        }
    }

    function handleDrop(e) {
        e.preventDefault();
    }

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.pd-rule-item:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    function abrirModalRegra(regra = null) {
        regraEditando = regra;

        $('#formRegra')[0].reset();
        $('#regraId').val('');
        $('#regraOperador').prop('disabled', true).find('option:not(:first)').remove();
        $('#valorSuggestions').empty();
        $('#pesoValue').text('50');
        $('#regraPeso').val(50);

        if (regra) {
            $('#modalRegraTitle').html(`
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2" style="vertical-align: -4px;">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Editar Regra
            `);
            $('#btnSalvarRegraText').text('Atualizar Regra');

            $('#regraId').val(regra.id);
            $('#regraNome').val(regra.nome);
            $('#regraDescricao').val(regra.descricao || '');
            $('#regraCampo').val(regra.campo).trigger('change');

            setTimeout(() => {
                $('#regraOperador').val(regra.operador);
                $('#regraValor').val(regra.valor);
                $('#regraPeso').val(regra.peso);
                $('#pesoValue').text(regra.peso);
            }, 100);
        } else {
            $('#modalRegraTitle').html(`
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2" style="vertical-align: -4px;">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                    <polyline points="2 17 12 22 22 17"/>
                    <polyline points="2 12 12 17 22 12"/>
                </svg>
                Nova Regra de Priorizacao
            `);
            $('#btnSalvarRegraText').text('Salvar Regra');
        }

        $('#modalRegra').modal('show');
    }

    function salvarRegra() {
        const id = $('#regraId').val();
        const data = {
            nome: $('#regraNome').val().trim(),
            descricao: $('#regraDescricao').val().trim(),
            campo: $('#regraCampo').val(),
            operador: $('#regraOperador').val(),
            valor: $('#regraValor').val().trim(),
            peso: parseInt($('#regraPeso').val()),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        if (!data.nome || !data.campo || !data.operador || !data.valor) {
            toastr.warning('Preencha todos os campos obrigatorios.');
            return;
        }

        const url = id ? `/comercial/preditiva/regras/${id}` : '/comercial/preditiva/regras';
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: data,
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#modalRegra').modal('hide');
                    carregarRegras();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Erro ao salvar regra.';
                toastr.error(message);
            }
        });
    }

    function toggleRegra(id) {
        $.ajax({
            url: `/comercial/preditiva/regras/${id}/toggle`,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    carregarRegras();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Erro ao alterar status.';
                toastr.error(message);
            }
        });
    }

    function excluirRegra(id) {
        if (!confirm('Deseja excluir esta regra de priorizacao?')) {
            return;
        }

        $.ajax({
            url: `/comercial/preditiva/regras/${id}`,
            method: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    carregarRegras();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Erro ao excluir regra.';
                toastr.error(message);
            }
        });
    }

    // Event: Nova Regra
    $('#btnNovaRegra').on('click', function () {
        abrirModalRegra();
    });

    // Event: Campo selecionado - atualizar operadores
    $('#regraCampo').on('change', function () {
        atualizarOperadores($(this).val());
    });

    // Event: Peso slider
    $('#regraPeso').on('input', function () {
        $('#pesoValue').text($(this).val());
    });

    // Event: Clique em sugestao de valor
    $(document).on('click', '.pd-valor-suggestion', function () {
        const valor = $(this).data('valor');
        $('#regraValor').val(valor);
    });

    // Event: Submit formulario de regra
    $('#formRegra').on('submit', function (e) {
        e.preventDefault();
        salvarRegra();
    });

    // Event: Toggle regra
    $(document).on('click', '.pd-rule-btn.btn-toggle', function () {
        const id = $(this).data('id');
        toggleRegra(id);
    });

    // Event: Editar regra
    $(document).on('click', '.pd-rule-btn.btn-edit', function () {
        const id = $(this).data('id');

        $.ajax({
            url: '/comercial/preditiva/regras',
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    const regra = response.regras.find(r => r.id == id);
                    if (regra) {
                        abrirModalRegra(regra);
                    }
                }
            }
        });
    });

    // Event: Excluir regra
    $(document).on('click', '.pd-rule-btn.btn-delete', function () {
        const id = $(this).data('id');
        excluirRegra(id);
    });

    // Inicializar regras
    carregarCamposDisponiveis();
    carregarRegras();
});

// ============================================================
// Configuracoes da Preditiva
// ============================================================
(function () {
    'use strict';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // Inicializa Select2 com tags (permite digitar tabulacoes novas)
    function initSelect2TabHard() {
        $('#inputNovaTabHard').select2({
            dropdownParent: $('#modalConfiguracoesPreditiva'),
            placeholder: 'Selecione ou digite uma tabulacao...',
            allowClear: true,
            tags: true,          // permite criar opcao nova digitando
            createTag: function (params) {
                const term = $.trim(params.term).toUpperCase();
                if (!term) return null;
                return { id: term, text: term, newTag: true };
            },
            language: {
                noResults: function () { return 'Nenhuma tabulacao encontrada'; },
                inputTooShort: function () { return ''; },
                searching: function () { return 'Buscando...'; },
            },
        });
    }

    // Carrega tabulacoes existentes no log_preditiva e popula o Select2,
    // excluindo as que ja sao HARD (ja estao na lista)
    function carregarOpcoesTabHard() {
        const jaHard = [];
        $('#listaTabsHard .badge-tab-hard').each(function () {
            jaHard.push($(this).data('tab'));
        });

        $.get('/comercial/preditiva/tabulacoes', function (res) {
            if (!res.success) return;

            const select = $('#inputNovaTabHard');
            select.find('option:not([value=""])').remove();

            res.tabulacoes.forEach(function (tab) {
                if (!jaHard.includes(tab)) {
                    select.append(new Option(tab, tab, false, false));
                }
            });

            select.trigger('change.select2');
        });
    }

    // Abre o modal e (re)carrega as opcoes
    $('#btnAbrirConfiguracoes').on('click', function () {
        $('#modalConfiguracoesPreditiva').modal('show');
    });

    $('#modalConfiguracoesPreditiva').on('shown.bs.modal', function () {
        if (!$('#inputNovaTabHard').hasClass('select2-hidden-accessible')) {
            initSelect2TabHard();
        }
        carregarOpcoesTabHard();
    });

    // Salvar parametros gerais
    $('#btnSalvarConfiguracoes').on('click', function () {
        const dados = {
            cooldown_horas:        parseInt($('#cfgCooldownHoras').val()) || 0,
            exclusao_usuario_dias: $('#cfgExclusaoDias').val() ? parseInt($('#cfgExclusaoDias').val()) : null,
            limite_descartes_soft: parseInt($('#cfgLimiteSoft').val()) || 5,
            limite_descartes_hard: parseInt($('#cfgLimiteHard').val()) || 1,
        };

        $.ajax({
            url: '/comercial/preditiva/configuracoes',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: dados,
            success: function (res) {
                if (res.success) {
                    toastr.success('Configuracoes salvas com sucesso.');
                } else {
                    toastr.error(res.message || 'Erro ao salvar.');
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Erro ao salvar configuracoes.');
            }
        });
    });

    // Adicionar tabulacao HARD
    function adicionarTabHard() {
        const tabulacao = ($('#inputNovaTabHard').val() || '').toUpperCase().trim();
        if (!tabulacao) { toastr.warning('Selecione ou digite uma tabulacao.'); return; }

        $.ajax({
            url: '/comercial/preditiva/tabulacoes-hard',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: { tabulacao },
            success: function (res) {
                if (res.success) {
                    renderBadgeTabHard(res.id, res.tabulacao);
                    // Remove a opcao do dropdown (ja e HARD) e limpa a selecao
                    $('#inputNovaTabHard option[value="' + res.tabulacao + '"]').remove();
                    $('#inputNovaTabHard').val(null).trigger('change');
                } else {
                    toastr.warning(res.message);
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Erro ao adicionar tabulacao.');
            }
        });
    }

    function renderBadgeTabHard(id, tabulacao) {
        if ($('#listaTabsHard').find('[data-id="' + id + '"]').length) return;
        const badge = $(
            '<span class="badge-tab-hard" data-id="' + id + '" data-tab="' + tabulacao + '">' +
            tabulacao +
            '<button type="button" class="btn-remove-tab-hard" data-id="' + id + '">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">' +
            '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>' +
            '</svg></button></span>'
        );
        $('#listaTabsHard').append(badge);
    }

    $('#btnAdicionarTabHard').on('click', adicionarTabHard);

    // Remover tabulacao HARD
    // Badges do servidor (gerados pelo @foreach) nao tem data-id numerico — usamos o nome para buscar o id
    $(document).on('click', '.btn-remove-tab-hard', function () {
        const btn    = $(this);
        const badge  = btn.closest('.badge-tab-hard');
        const id     = btn.data('id');

        if (id) {
            removerTabHardPorId(id, badge);
            return;
        }

        // Badge do servidor: faz upsert para obter o id e entao remove
        const tabName = badge.data('tab');
        $.ajax({
            url: '/comercial/preditiva/tabulacoes-hard',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: { tabulacao: tabName },
            success: function (r) {
                if (r.success) removerTabHardPorId(r.id, badge);
            }
        });
    });

    function removerTabHardPorId(id, badge) {
        $.ajax({
            url: '/comercial/preditiva/tabulacoes-hard/' + id,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) {
                if (res.success) badge.remove();
                else toastr.error(res.message);
            },
            error: function () { toastr.error('Erro ao remover tabulacao.'); }
        });
    }
})();
