'use strict';

/**
 * Backoffice Kanban - Fila de Contratos
 * Gestão visual de contratos em pipeline
 */

$(function () {
    // =============================================================================
    // Helpers
    // =============================================================================
    function escapeHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value || 0);
    }

    function truncate(s, n) {
        s = String(s || '');
        return s.length <= n ? s : s.slice(0, n) + '…';
    }

    /**
     * Modern Toast Notification
     * @param {string} type - 'success', 'error', 'warning', 'info'
     * @param {string} title - Toast title
     * @param {string} message - Toast message
     */
    function showModernToast(type, title, message) {
        const icons = {
            success: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>`,
            error: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>`,
            warning: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>`,
            info: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="16" x2="12" y2="12"/>
                <line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>`
        };

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            background: 'transparent',
            showClass: {
                popup: 'animate__animated animate__slideInRight animate__faster'
            },
            hideClass: {
                popup: 'animate__animated animate__slideOutRight animate__faster'
            },
            html: `
                <div class="custom-toast custom-toast-${type}">
                    <div class="toast-icon">
                        ${icons[type] || icons.info}
                    </div>
                    <div class="toast-content">
                        <div class="toast-title">${escapeHtml(title)}</div>
                        <div class="toast-message">${escapeHtml(message)}</div>
                    </div>
                    <div class="toast-close" onclick="Swal.close()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </div>
                </div>
            `,
            customClass: {
                popup: 'custom-toast-popup'
            }
        });
    }

    // =============================================================================
    // Kanban Manager
    // =============================================================================
    const KanbanContratos = {
        data: null,
        sortableInstances: [],
        statusConfirmed: false,
        draggedItem: null,
        draggedFromColumn: null,

        async init() {
            await this.loadContratos();
            this.initFilters();
            this.initEventListeners();
        },

        async loadContratos() {
            try {
                $('#kanban-loading').show();
                $('#kanban-board').hide();

                const params = new URLSearchParams();
                const vendedor = $('#filter-vendedor').val();
                const mes = $('#filter-mes').val();
                const ano = $('#filter-ano').val();
                const busca = $('#filter-busca').val();

                if (vendedor) params.append('vendedor_id', vendedor);
                if (busca) params.append('busca', busca);

                // Calcular datas baseado em mês/ano
                if (mes && ano) {
                    const startDate = `${ano}-${String(mes).padStart(2, '0')}-01`;
                    const lastDay = new Date(ano, mes, 0).getDate();
                    const endDate = `${ano}-${String(mes).padStart(2, '0')}-${lastDay}`;
                    params.append('data_inicio', startDate);
                    params.append('data_fim', endDate);
                } else if (ano) {
                    params.append('data_inicio', `${ano}-01-01`);
                    params.append('data_fim', `${ano}-12-31`);
                }

                const url = `/back-office/pipeline-data${params.toString() ? '?' + params.toString() : ''}`;
                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    this.data = result;
                    this.renderBoard(result.pipeline);
                    this.populateVendedorFilter(result.vendedores);
                    this.initDragDrop();
                } else {
                    throw new Error(result.message || 'Erro ao carregar dados');
                }
            } catch (error) {
                console.error('Erro ao carregar contratos:', error);
                Swal.fire({
                    title: 'Erro',
                    text: 'Não foi possível carregar os contratos.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    customClass: { confirmButton: 'btn btn-primary' },
                    buttonsStyling: false
                });
            } finally {
                $('#kanban-loading').hide();
                $('#kanban-board').show();
            }
        },

        renderBoard(pipeline) {
            const board = $('#kanban-board');
            board.empty();

            // Destroy existing sortable instances
            this.sortableInstances.forEach(instance => instance.destroy());
            this.sortableInstances = [];

            pipeline.forEach(status => {
                const isExit = ['ESTORNO', 'DECLINADO'].includes(status.nome);
                const columnClass = isExit ? 'column-exit' : '';

                const columnHtml = `
                    <div class="kanban-column ${columnClass}" data-status-id="${status.id}">
                        <div class="kanban-column-header">
                            <div class="column-header-content">
                                <span class="status-indicator" style="background: ${status.cor}; box-shadow: 0 0 8px ${status.cor};"></span>
                                <span class="status-name" title="${escapeHtml(status.nome)}">${this.formatStatusName(status.nome)}</span>
                                <span class="status-count">${status.quantidade}</span>
                                ${status.atrasados > 0 ? `
                                    <span class="status-atrasados" title="${status.atrasados} contrato(s) atrasado(s)">
                                        <i class="ri-alarm-warning-line"></i>
                                        ${status.atrasados}
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                        <div class="kanban-column-body" data-status="${status.id}">
                            ${status.contratos && status.contratos.length > 0
                        ? status.contratos.map(c => this.renderCard(c, status.cor)).join('')
                        : `<div class="kanban-column-empty">
                                        <i class="ri-inbox-line"></i>
                                        <span>Nenhum contrato</span>
                                    </div>`
                    }
                        </div>
                    </div>
                `;

                board.append(columnHtml);
            });
        },

        formatStatusName(name) {
            const shortNames = {
                'ANALISE DE DOCUMENTOS': 'ANÁLISE DOCS',
                'ANALISE OPERADORA': 'ANÁLISE OPER.',
                'CONTR. GERADO - AGUARDANDO ASSINATURA': 'CONTR. GERADO',
                'AGUARD. ASSINATURA DA DS': 'AGUARD. DS',
                'BOLETO DISPONIVEL': 'BOLETO DISP.',
            };
            return shortNames[name] || name;
        },

        renderCard(contract, statusColor) {
            const atrasadoClass = contract.atrasado ? 'card-atrasado' : '';
            const diasNoStatus = Math.floor(contract.dias_no_status || 0);
            const tempoLabel = diasNoStatus === 1 ? '1 dia' : `${diasNoStatus} dias`;

            // Prazo restante (se houver)
            let prazoHtml = '';
            const prazoMaximo = Math.floor(contract.prazo_maximo || 0);
            if (prazoMaximo && !contract.atrasado) {
                const restante = prazoMaximo - diasNoStatus;
                if (restante > 0) {
                    prazoHtml = `<span class="tempo-status" style="color: var(--kb-warning);">
                        <i class="ri-timer-line"></i> ${restante}d restantes
                    </span>`;
                }
            } else if (contract.atrasado) {
                const atraso = diasNoStatus - prazoMaximo;
                prazoHtml = `<span class="tempo-status atrasado">
                    <i class="ri-alarm-warning-line"></i> ${atraso}d atrasado
                </span>`;
            }

            // Motivo pendência
            let motivoHtml = '';
            if (contract.motivo_pendencia) {
                motivoHtml = `
                    <div class="motivo-badge js-view-motivo" data-motivo="${escapeHtml(contract.motivo_pendencia)}" title="Clique para ver o motivo">
                        <i class="ri-error-warning-line"></i>
                        <span>${truncate(contract.motivo_pendencia, 60)}</span>
                    </div>
                `;
            }

            return `
                <div class="kanban-card ${atrasadoClass}"
                     data-id="${contract.id}"
                     data-venda-id="${contract.venda_id || contract.id}"
                     data-contato-id="${contract.contato_id || ''}"
                     style="--card-status-color: ${statusColor};">
                    ${contract.atrasado ? '<i class="ri-alarm-warning-fill atrasado-icon"></i>' : ''}

                    <div class="card-header-kb">
                        <span class="operadora-badge" title="${escapeHtml(contract.operadora || 'N/A')}">
                            ${escapeHtml(contract.operadora || 'N/A')}
                        </span>
                        <div class="card-dropdown">
                            <button type="button" class="btn-action js-toggle-dropdown">
                                <i class="ri-more-2-fill"></i>
                            </button>
                            <div class="dropdown-menu-card">
                                <a href="/back-office/abrir-contrato/${contract.id}" class="dropdown-item-card">
                                    <i class="ri-eye-line"></i>
                                    Ver Contrato
                                </a>
                                <button type="button" class="dropdown-item-card js-alterar-status" data-id="${contract.id}">
                                    <i class="ri-arrow-left-right-fill"></i>
                                    Alterar Status
                                </button>
                                <button type="button" class="dropdown-item-card js-ver-historico" data-id="${contract.id}">
                                    <i class="ri-history-line"></i>
                                    Histórico
                                </button>
                                <div class="dropdown-divider"></div>
                                <a href="/back-office/deletar-contrato/${contract.id}"
                                   class="dropdown-item-card text-danger js-delete-contract"
                                   data-id="${contract.id}"
                                   data-nome="${escapeHtml(contract.nome_contrato)}">
                                    <i class="ri-delete-bin-line"></i>
                                    Excluir
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body-kb">
                        <div class="contract-name" title="${escapeHtml(contract.nome_contrato)}">
                            ${escapeHtml(contract.nome_contrato || 'Sem nome')}
                        </div>
                        ${contract.numero_proposta ? `
                            <div class="contract-cpf">#${escapeHtml(contract.numero_proposta)}</div>
                        ` : ''}
                    </div>

                    ${motivoHtml}

                    <div class="card-info-kb">
                        <span class="info-item valor">
                            <i class="ri-money-dollar-circle-line"></i>
                            ${formatCurrency(contract.valor)}
                        </span>
                    </div>

                    <div class="card-footer-kb">
                        <span class="tempo-status ${contract.atrasado ? 'atrasado' : ''}">
                            <i class="ri-time-line"></i>
                            ${tempoLabel}
                        </span>
                        ${prazoHtml}
                        <span class="vendedor-badge" title="${escapeHtml(contract.vendedor || 'N/A')}">
                            <i class="ri-user-line"></i>
                            <span>${truncate(contract.vendedor || 'N/A', 15)}</span>
                        </span>
                    </div>
                </div>
            `;
        },

        populateVendedorFilter(vendedores) {
            const select = $('#filter-vendedor');
            const currentValue = select.val();

            select.find('option:not(:first)').remove();

            if (vendedores && vendedores.length) {
                vendedores.forEach(v => {
                    select.append(`<option value="${v.id}">${escapeHtml(v.name)}</option>`);
                });
            }

            if (currentValue) {
                select.val(currentValue);
            }
        },

        initDragDrop() {
            const self = this;

            document.querySelectorAll('.kanban-column-body').forEach(column => {
                const sortable = new Sortable(column, {
                    group: 'contratos',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    filter: '.kanban-column-empty',
                    onStart: function (evt) {
                        self.draggedItem = evt.item;
                        self.draggedFromColumn = evt.from;

                        // Remove empty state from all columns
                        document.querySelectorAll('.kanban-column-empty').forEach(el => {
                            el.style.display = 'none';
                        });
                    },
                    onEnd: function (evt) {
                        self.handleDrop(evt);

                        // Restore empty states where needed
                        document.querySelectorAll('.kanban-column-body').forEach(col => {
                            if (col.querySelectorAll('.kanban-card').length === 0) {
                                let emptyEl = col.querySelector('.kanban-column-empty');
                                if (!emptyEl) {
                                    emptyEl = document.createElement('div');
                                    emptyEl.className = 'kanban-column-empty';
                                    emptyEl.innerHTML = '<i class="ri-inbox-line"></i><span>Nenhum contrato</span>';
                                    col.appendChild(emptyEl);
                                }
                                emptyEl.style.display = 'flex';
                            }
                        });
                    }
                });

                self.sortableInstances.push(sortable);
            });
        },

        // IDs dos status que requerem modal (IMPLANTADO=18, PENDENCIA=55)
        statusComModal: [18, 55],

        handleDrop(evt) {
            const cardId = evt.item.dataset.id;
            const newStatusId = parseInt(evt.to.dataset.status);
            const oldStatusId = parseInt(evt.from.dataset.status);

            if (newStatusId === oldStatusId) {
                return;
            }

            // Store event info for potential revert
            this.lastDropEvent = evt;
            this.statusConfirmed = false;

            // Verificar se o status de destino requer modal
            if (this.statusComModal.includes(newStatusId)) {
                // Abrir modal para status que requerem dados adicionais
                $('#idSale').val(cardId);
                $('#label').val(newStatusId).trigger('change');
                $('#modalcomments').modal('show');

                // Listen for modal close
                const self = this;
                $('#modalcomments').one('hidden.bs.modal', function () {
                    if (!self.statusConfirmed) {
                        // Revert: move card back to original column
                        if (self.lastDropEvent) {
                            self.lastDropEvent.from.appendChild(self.lastDropEvent.item);
                            self.updateColumnCounts();
                        }
                    }
                    self.statusConfirmed = false;
                    self.lastDropEvent = null;
                });
            } else {
                // Mudança rápida de status (sem modal)
                this.quickStatusChange(cardId, newStatusId, evt);
            }
        },

        async quickStatusChange(vendaId, tabulacaoId, evt) {
            const self = this;

            try {
                // Mostrar loading no card
                const card = evt.item;
                card.style.opacity = '0.6';
                card.style.pointerEvents = 'none';

                const response = await fetch('/back-office/quick-status-change', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        venda_id: vendaId,
                        tabulacao_id: tabulacaoId,
                    }),
                });

                const result = await response.json();

                if (result.success) {
                    // Sucesso - atualizar contadores
                    self.statusConfirmed = true;
                    self.updateColumnCounts();

                    // Toast de sucesso moderno
                    showModernToast('success', 'Status Atualizado!', result.message || 'O contrato foi movido com sucesso.');
                } else {
                    // Erro - reverter card
                    if (evt.from) {
                        evt.from.appendChild(evt.item);
                        self.updateColumnCounts();
                    }

                    showModernToast('error', 'Erro', result.message || 'Não foi possível atualizar o status.');
                }
            } catch (error) {
                console.error('Erro ao atualizar status:', error);

                // Reverter card
                if (evt.from) {
                    evt.from.appendChild(evt.item);
                    self.updateColumnCounts();
                }

                showModernToast('error', 'Erro de Conexão', 'Verifique sua internet e tente novamente.');
            } finally {
                // Restaurar card
                const card = evt.item;
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
                self.lastDropEvent = null;
            }
        },

        updateColumnCounts() {
            document.querySelectorAll('.kanban-column').forEach(column => {
                const count = column.querySelectorAll('.kanban-card').length;
                const countEl = column.querySelector('.status-count');
                if (countEl) {
                    countEl.textContent = count;
                }
            });
        },

        confirmStatusChange() {
            this.statusConfirmed = true;
        },

        initFilters() {
            const self = this;
            let debounceTimer;

            // Debounced search
            $('#filter-busca').on('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    self.loadContratos();
                }, 500);
            });

            // Immediate filters
            $('#filter-vendedor, #filter-mes, #filter-ano').on('change', function () {
                self.loadContratos();
            });

            // Clear filters
            $('#btn-clear-filters').on('click', function () {
                $('#filter-vendedor').val('');
                $('#filter-mes').val('');
                $('#filter-ano').val('');
                $('#filter-busca').val('');
                self.loadContratos();
            });
        },

        initEventListeners() {
            const self = this;

            // Dropdown toggle
            $(document).on('click', '.js-toggle-dropdown', function (e) {
                e.stopPropagation();
                const dropdown = $(this).siblings('.dropdown-menu-card');

                // Close other dropdowns
                $('.dropdown-menu-card.show').not(dropdown).removeClass('show');

                dropdown.toggleClass('show');
            });

            // Close dropdown when clicking outside
            $(document).on('click', function () {
                $('.dropdown-menu-card.show').removeClass('show');
            });

            // Prevent dropdown close when clicking inside
            $(document).on('click', '.dropdown-menu-card', function (e) {
                e.stopPropagation();
            });

            // Alterar status button
            $(document).on('click', '.js-alterar-status', function () {
                const id = $(this).data('id');
                $('#idSale').val(id);
                $('.dropdown-menu-card.show').removeClass('show');
                $('#modalcomments').modal('show');
            });

            // View motivo
            $(document).on('click', '.js-view-motivo', function (e) {
                e.stopPropagation();
                const motivo = $(this).data('motivo') || 'Motivo não informado';
                Swal.fire({
                    html: `
                        <div class="kb-modal-pendencia">
                            <div class="kb-modal-pendencia-header">
                                <div class="kb-modal-pendencia-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                        <line x1="12" y1="9" x2="12" y2="13"/>
                                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                                    </svg>
                                </div>
                                <div class="kb-modal-pendencia-title-group">
                                    <h4 class="kb-modal-pendencia-title">Motivo da Pendência</h4>
                                    <span class="kb-modal-pendencia-subtitle">Detalhes do impedimento do contrato</span>
                                </div>
                            </div>
                            <div class="kb-modal-pendencia-body">
                                <div class="kb-modal-pendencia-content">
                                    ${escapeHtml(motivo)}
                                </div>
                            </div>
                            <div class="kb-modal-pendencia-footer">
                                <button type="button" class="kb-btn kb-btn-ghost" onclick="Swal.close()">
                                    Fechar
                                </button>
                            </div>
                        </div>
                    `,
                    showConfirmButton: false,
                    showCloseButton: false,
                    width: 480,
                    padding: 0,
                    background: 'transparent',
                    customClass: {
                        popup: 'kb-modal-pendencia-popup'
                    }
                });
            });

            // Ver histórico
            $(document).on('click', '.js-ver-historico', function () {
                const id = $(this).data('id');
                $('.dropdown-menu-card.show').removeClass('show');
                self.loadHistorico(id);
            });

            // Delete contract
            $(document).on('click', '.js-delete-contract', function (e) {
                e.preventDefault();
                const id = $(this).data('id');
                const nome = $(this).data('nome');
                const href = $(this).attr('href');

                $('.dropdown-menu-card.show').removeClass('show');

                Swal.fire({
                    title: 'Excluir Contrato?',
                    html: `<p>Tem certeza que deseja excluir o contrato:</p><p class="fw-bold text-danger">"${escapeHtml(nome)}"</p><p class="text-muted small">Esta ação não pode ser desfeita.</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="ri-delete-bin-line me-1"></i> Excluir',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false,
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = href;
                    }
                });
            });

            // Gerar recebível
            $(document).on('click', '.js-gerar-recebivel', function () {
                const vendaId = $(this).data('id');
                const nomeContrato = $(this).data('nome') || 'este contrato';

                $('.dropdown-menu-card.show').removeClass('show');

                self.gerarRecebivel(vendaId, nomeContrato);
            });

            // Form submit - mark as confirmed
            $('#transferLead').on('submit', function () {
                self.confirmStatusChange();
            });
        },

        async loadHistorico(vendaId) {
            try {
                Swal.fire({
                    title: 'Carregando...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const response = await fetch(`/back-office/historico/${vendaId}`);
                const result = await response.json();

                Swal.close();

                if (result.success) {
                    this.renderHistorico(result);
                    $('#modalHistorico').modal('show');
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                Swal.fire({
                    title: 'Erro',
                    text: 'Não foi possível carregar o histórico.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    customClass: { confirmButton: 'btn btn-primary' },
                    buttonsStyling: false
                });
            }
        },

        renderHistorico(data) {
            const { venda, historico } = data;

            let html = `
                <div class="p-4">
                    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${escapeHtml(venda.nome_contrato)}</h6>
                            <small class="text-muted">
                                ${venda.numero_proposta ? `Proposta: ${escapeHtml(venda.numero_proposta)} | ` : ''}
                                ${escapeHtml(venda.operadora || 'N/A')}
                            </small>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-primary">${formatCurrency(venda.valor_contrato)}</div>
                            <small class="text-muted">Criado em ${venda.data_criacao}</small>
                        </div>
                    </div>
            `;

            if (historico && historico.length > 0) {
                html += '<div class="timeline-historico">';
                historico.forEach((h, index) => {
                    html += `
                        <div class="timeline-item ${index === 0 ? 'active' : ''}">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <span class="badge bg-label-primary">${escapeHtml(h.status_novo)}</span>
                                    <small class="text-muted">${h.data}</small>
                                </div>
                                <div class="small text-muted mb-1">
                                    De: <span class="text-secondary">${escapeHtml(h.status_anterior)}</span>
                                </div>
                                <div class="small">
                                    <i class="ri-user-line me-1"></i>
                                    ${escapeHtml(h.usuario)}
                                    ${h.tempo_formatado ? `<span class="text-muted ms-2">(${h.tempo_formatado})</span>` : ''}
                                </div>
                                ${h.motivo_pendencia ? `
                                    <div class="alert alert-warning py-2 px-3 mt-2 mb-0 small">
                                        <i class="ri-error-warning-line me-1"></i>
                                        ${escapeHtml(h.motivo_pendencia)}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            } else {
                html += `
                    <div class="text-center py-4 text-muted">
                        <i class="ri-history-line ri-3x mb-2"></i>
                        <p>Nenhum histórico encontrado</p>
                    </div>
                `;
            }

            html += '</div>';

            // Add timeline styles inline
            html += `
                <style>
                    .timeline-historico { position: relative; padding-left: 1.5rem; }
                    .timeline-item { position: relative; padding-bottom: 1.5rem; padding-left: 1.5rem; border-left: 2px solid #e9ecef; }
                    .timeline-item:last-child { border-left-color: transparent; padding-bottom: 0; }
                    .timeline-item.active .timeline-marker { background: #696cff; box-shadow: 0 0 0 4px rgba(105, 108, 255, 0.2); }
                    .timeline-marker { position: absolute; left: -0.5rem; top: 0; width: 1rem; height: 1rem; border-radius: 50%; background: #d9dee3; border: 2px solid #fff; }
                    .timeline-content { background: #f8f9fa; border-radius: 8px; padding: 0.75rem 1rem; }
                    .dark-style .timeline-content { background: #32334a; }
                    .dark-style .timeline-item { border-left-color: #3b3c54; }
                </style>
            `;

            $('#historico-content').html(html);
        },

        async gerarRecebivel(vendaId, nomeContrato) {
            try {
                Swal.fire({
                    title: 'Verificando...',
                    text: 'Consultando informações do contrato',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const checkResponse = await fetch(`/back-office/verificar-recebiveis/${vendaId}`);
                const checkResult = await checkResponse.json();

                Swal.close();

                if (checkResult.possui_recebiveis) {
                    const valorFormatado = formatCurrency(checkResult.valor_total);

                    const confirmResult = await Swal.fire({
                        title: '<i class="ri-error-warning-line text-warning"></i> Atenção!',
                        html: `
                            <div class="text-start">
                                <p class="mb-3">O contrato <strong>"${escapeHtml(nomeContrato)}"</strong> já possui recebíveis cadastrados:</p>
                                <div class="alert alert-warning d-flex align-items-center mb-3">
                                    <i class="ri-information-line me-2 fs-4"></i>
                                    <div>
                                        <strong>${checkResult.quantidade} parcela(s)</strong> no valor total de <strong>${valorFormatado}</strong>
                                    </div>
                                </div>
                                <p class="text-primary fw-bold mb-2">
                                    <i class="ri-refresh-line me-1"></i>
                                    Os valores serão ATUALIZADOS conforme as regras de comissionamento atuais.
                                </p>
                            </div>
                        `,
                        icon: null,
                        showCancelButton: true,
                        confirmButtonText: '<i class="ri-refresh-line me-1"></i> Atualizar Recebíveis',
                        cancelButtonText: 'Cancelar',
                        customClass: {
                            confirmButton: 'btn btn-warning me-2',
                            cancelButton: 'btn btn-secondary'
                        },
                        buttonsStyling: false,
                        reverseButtons: true
                    });

                    if (confirmResult.isConfirmed) {
                        await this.executarGeracaoRecebiveis(vendaId, nomeContrato);
                    }
                } else {
                    const confirmResult = await Swal.fire({
                        title: 'Gerar Recebíveis',
                        html: `
                            <div class="text-start">
                                <p>Deseja gerar os recebíveis para o contrato:</p>
                                <p class="fw-bold text-primary">"${escapeHtml(nomeContrato)}"</p>
                                <p class="text-muted small mt-3">Os recebíveis serão gerados com base nas regras de comissionamento configuradas.</p>
                            </div>
                        `,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: '<i class="ri-check-line me-1"></i> Gerar Recebíveis',
                        cancelButtonText: 'Cancelar',
                        customClass: {
                            confirmButton: 'btn btn-success me-2',
                            cancelButton: 'btn btn-secondary'
                        },
                        buttonsStyling: false,
                        reverseButtons: true
                    });

                    if (confirmResult.isConfirmed) {
                        await this.executarGeracaoRecebiveis(vendaId, nomeContrato);
                    }
                }
            } catch (error) {
                Swal.fire({
                    title: 'Erro',
                    text: 'Não foi possível verificar os recebíveis do contrato.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    customClass: { confirmButton: 'btn btn-primary' },
                    buttonsStyling: false
                });
            }
        },

        async executarGeracaoRecebiveis(vendaId, nomeContrato) {
            try {
                Swal.fire({
                    title: 'Processando...',
                    html: `Gerando recebíveis para <strong>"${escapeHtml(nomeContrato)}"</strong>`,
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const response = await fetch(`/back-office/gerar-recebivel/${vendaId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Content-Type': 'application/json'
                    }
                });

                const result = await response.json();

                let iconHtml = '<i class="ri-checkbox-circle-line text-success"></i>';
                let alertClass = 'alert-success';

                if (result.atualizados > 0 && result.criados === 0) {
                    iconHtml = '<i class="ri-refresh-line text-warning"></i>';
                    alertClass = 'alert-warning';
                }

                Swal.fire({
                    title: `${iconHtml} Sucesso!`,
                    html: `
                        <div class="text-start">
                            <p>${escapeHtml(result.message)}</p>
                            <div class="alert ${alertClass} mt-3">
                                ${result.criados > 0 ? `<div><i class="ri-add-line me-1"></i> <strong>${result.criados}</strong> parcela(s) criada(s)</div>` : ''}
                                ${result.atualizados > 0 ? `<div><i class="ri-refresh-line me-1"></i> <strong>${result.atualizados}</strong> parcela(s) atualizada(s)</div>` : ''}
                            </div>
                        </div>
                    `,
                    icon: null,
                    confirmButtonText: 'OK',
                    customClass: { confirmButton: 'btn btn-success' },
                    buttonsStyling: false
                });
            } catch (error) {
                Swal.fire({
                    title: 'Erro',
                    text: 'Falha ao gerar recebíveis.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    customClass: { confirmButton: 'btn btn-primary' },
                    buttonsStyling: false
                });
            }
        }
    };

    // =============================================================================
    // Modal Status Change Logic (Existing)
    // =============================================================================
    $(document).on('change', '#label', function () {
        const val = $(this).val();

        // IMPLANTADO (ID 18)
        if (val === '18') {
            $('#proof-group').show();
            $('#comprovante').prop('required', true);
            $('#proof-group-data-implantacao').show();
            $('#proof-group-numero_proposta').show();
            $('#data_implantacao').prop('required', true);
            $('#numero_proposta').prop('required', true);
            $('#proof-group-acesso-empresa').slideDown(300);
        } else {
            $('#proof-group').hide();
            $('#comprovante').prop('required', false).val('');
            $('#proof-group-data-implantacao').hide();
            $('#proof-group-numero_proposta').hide();
            $('#data_implantacao').prop('required', false).val('');
            $('#numero_proposta').prop('required', false).val('');
            $('#proof-group-acesso-empresa').slideUp(300);
            $('#acesso_email, #acesso_senha, #acesso_cpf').val('');
        }

        // PENDÊNCIA, ESTORNO, DECLINADO (IDs 55, 17, 53)
        if (val === '55' || val === '17' || val === '53') {
            $('#proof-group-data-pendencia').show();
            $('#data_pendencia').prop('required', true);
        } else {
            $('#proof-group-data-pendencia').hide();
            $('#data_pendencia').prop('required', false).val('');
        }

        // BOLETO DISPONÍVEL (ID 58)
        if (val === '58') {
            $('#proof-group-boleto-disponivel').show();
            $('#boleto_disponivel').prop('required', true);
        } else {
            $('#proof-group-boleto-disponivel').hide();
            $('#boleto_disponivel').prop('required', false).val('');
        }
    });

    // Password toggle
    $(document).on('click', '#toggle-acesso-senha', function () {
        const $input = $('#acesso_senha');
        const $iconEye = $('#icon-eye');
        const $iconEyeOff = $('#icon-eye-off');

        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $iconEye.addClass('d-none');
            $iconEyeOff.removeClass('d-none');
        } else {
            $input.attr('type', 'password');
            $iconEye.removeClass('d-none');
            $iconEyeOff.addClass('d-none');
        }
    });

    // CPF Mask
    function initAcessoCpfMask() {
        const cpfInput = document.querySelector('.acesso-mask-cpf');
        if (cpfInput && typeof Cleave !== 'undefined') {
            new Cleave(cpfInput, {
                delimiters: ['.', '.', '-'],
                blocks: [3, 3, 3, 2],
                numericOnly: true
            });
        }
    }

    $('#modalcomments').on('shown.bs.modal', function () {
        initAcessoCpfMask();
    });

    $('#modalcomments').on('hidden.bs.modal', function () {
        $('#acesso_email, #acesso_senha, #acesso_cpf').val('');
        $('#proof-group-acesso-empresa').hide();
        $('#acesso_senha').attr('type', 'password');
        $('#icon-eye').removeClass('d-none');
        $('#icon-eye-off').addClass('d-none');

        // Reset form
        $('#label').val('').trigger('change');
    });

    // =============================================================================
    // Initialize Kanban
    // =============================================================================
    KanbanContratos.init();
});
