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

    // Derives one of 6 stable color tones for the seller avatar from the seller name.
    // Same name → same tone across the board, so each rep keeps a consistent identity.
    function sellerToneFromName(name) {
        const s = String(name || '');
        let hash = 0;
        for (let i = 0; i < s.length; i++) {
            hash = (hash * 31 + s.charCodeAt(i)) >>> 0;
        }
        return (hash % 6) + 1;
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
                const custodia = $('#filter-custodia').val();
                const backoffice = $('#filter-backoffice').val();

                if (vendedor) params.append('vendedor_id', vendedor);
                if (busca) params.append('busca', busca);
                if (custodia) params.append('custodia', custodia);
                if (backoffice) params.append('backoffice_id', backoffice);

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
                    this.populateBackofficeFilter(result.backoffices);
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
                                <span class="status-value">${formatCurrency(status.valor_total)}</span>
                                <span class="status-count" title="${status.quantidade} contrato(s)">${status.quantidade}</span>
                            </div>
                        </div>
                        <div class="kanban-column-body" data-status="${status.id}">
                            ${status.contratos && status.contratos.length > 0
                        ? status.contratos.map(c => this.renderCard(c, status.cor, status.nome)).join('')
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

        renderCard(contract, statusColor, statusName) {
            const diasNaFila = Math.floor(contract.dias_na_fila || 0);
            const tempoLabel = diasNaFila === 0
                ? 'hoje'
                : (diasNaFila === 1 ? '1d na fila' : `${diasNaFila}d na fila`);

            const vendedorNome = (contract.vendedor || 'Sem vendedor').trim();
            const vendedorIniciais = vendedorNome
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2)
                .map(p => p.charAt(0).toUpperCase())
                .join('') || '?';
            const vendedorTone = sellerToneFromName(vendedorNome);

            // Motivo pendência - só exibir se o status for PENDENCIA
            let motivoHtml = '';
            if (contract.motivo_pendencia && statusName === 'PENDENCIA') {
                motivoHtml = `
                    <div class="motivo-badge js-view-motivo" data-motivo="${escapeHtml(contract.motivo_pendencia)}" title="Clique para ver o motivo">
                        <i class="ri-error-warning-line"></i>
                        <span>${truncate(contract.motivo_pendencia, 60)}</span>
                    </div>
                `;
            }

            return `
                <div class="kanban-card"
                     data-id="${contract.id}"
                     data-venda-id="${contract.venda_id || contract.id}"
                     data-contato-id="${contract.contato_id || ''}"
                     style="--card-status-color: ${statusColor};">
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

                    <div class="card-seller-row">
                        <div class="seller-identity" title="${escapeHtml(vendedorNome)}">
                            <span class="seller-avatar" data-tone="${vendedorTone}">${escapeHtml(vendedorIniciais)}</span>
                            <span class="seller-meta">
                                <span class="seller-name">${escapeHtml(truncate(vendedorNome, 22))}</span>
                                <span class="seller-label">Vendedor</span>
                            </span>
                        </div>
                        <span class="dias-fila-pill" title="Dias na fila desde a venda (${escapeHtml(contract.data_venda || '')})">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="3"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <span class="dias-fila-value">${tempoLabel}</span>
                        </span>
                    </div>

                    <div class="card-backoffice-strip ${contract.backoffice_nome ? 'has-owner' : 'no-owner'}">
                        <i class="ri-shield-user-line"></i>
                        ${contract.backoffice_nome
                            ? `<span class="bo-name">${escapeHtml(contract.backoffice_nome)}</span>`
                            : `<span class="bo-name bo-livre">Livre</span>`
                        }
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

        populateBackofficeFilter(backoffices) {
            const select = $('#filter-backoffice');
            if (!select.length) return; // Não existe para backoffice users

            const currentValue = select.val();

            // Manter "Todos" e "Sem responsável" (primeiras 2 options)
            select.find('option:not(:nth-child(-n+2))').remove();

            if (backoffices && backoffices.length) {
                backoffices.forEach(b => {
                    select.append(`<option value="${b.id}">${escapeHtml(b.name)}</option>`);
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

                    // Card saiu de PENDENCIA: a badge de motivo só faz sentido nesse status.
                    // Quick change nunca tem PENDENCIA como destino (cai no modal), então remoção é incondicional.
                    const motivoBadge = evt.item.querySelector('.motivo-badge');
                    if (motivoBadge) motivoBadge.remove();

                    // Invalidar aba de demandas para refletir novo status
                    self.demandasLoaded = false;

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

        getFilterParams() {
            const params = new URLSearchParams();
            const vendedor = $('#filter-vendedor').val();
            const mes = $('#filter-mes').val();
            const ano = $('#filter-ano').val();
            const busca = $('#filter-busca').val();
            const custodia = $('#filter-custodia').val();
            const backoffice = $('#filter-backoffice').val();

            if (vendedor) params.append('vendedor_id', vendedor);
            if (busca) params.append('busca', busca);
            if (custodia) params.append('custodia', custodia);
            if (backoffice) params.append('backoffice_id', backoffice);

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
            return params;
        },

        async loadDemandasList() {
            try {
                $('#demandas-loading').show();
                $('#demandas-backlog').hide();

                const params = this.getFilterParams();
                const url = `/back-office/demandas-pendentes-kanban${params.toString() ? '?' + params.toString() : ''}`;
                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    this.demandasLoaded = true;
                    this.renderDemandasList(result.demandas);
                    const pendentes = result.demandas.filter(d => d.status === 'PENDENTE').length;
                    this.updateDemandasBadge(pendentes);
                }
            } catch (error) {
                console.error('Erro ao carregar demandas:', error);
            } finally {
                $('#demandas-loading').hide();
                $('#demandas-backlog').show();
            }
        },

        updateDemandasBadge(count) {
            const badge = $('#tab-demandas-count');
            if (count > 0) {
                badge.text(count).show();
            } else {
                badge.hide();
            }
        },

        renderDemandasList(demandas) {
            const container = $('#demandas-backlog');

            if (!demandas || demandas.length === 0) {
                container.html(`
                    <div class="demandas-empty-state">
                        <div class="empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 11l3 3L22 4"/>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                            </svg>
                        </div>
                        <h5>Nenhuma demanda pendente</h5>
                        <p>Todos os contratos estão em dia!</p>
                    </div>
                `);
                return;
            }

            // Agrupar por contrato
            const grouped = {};
            demandas.forEach(d => {
                if (!grouped[d.venda_id]) {
                    grouped[d.venda_id] = {
                        venda_id: d.venda_id,
                        contrato_nome: d.contrato_nome,
                        contrato_proposta: d.contrato_proposta,
                        contrato_operadora: d.contrato_operadora,
                        backoffice_nome: d.backoffice_nome,
                        status_atual: d.status_atual,
                        demandas: [],
                        pendentes: 0,
                    };
                }
                grouped[d.venda_id].demandas.push(d);
                if (d.status === 'PENDENTE') grouped[d.venda_id].pendentes++;
            });

            const tipoLabels = {
                'ACESSO_EMPRESA': 'Acesso Empresa',
                'LOGIN_APPS': 'Login Apps',
                'TROCA_EMAIL': 'Alterar E-mail',
                'CANCELAMENTO_QUALICORP': 'Cancel. Qualicorp',
                'CANCELAMENTO_LIMITAR': 'Cancel. Limitar',
                'BOAS_VINDAS': 'Boas-vindas',
                'ENVIO_BOLETO': 'Envio Boleto',
                'REEMBOLSO': 'Reembolso',
                'INCLUSAO_BENEFICIARIO': 'Incluir Benef.',
                'EXCLUSAO_BENEFICIARIO': 'Excluir Benef.',
                'CANCELAMENTO': 'Cancelamento',
                'CARTA_PERMANENCIA': 'Carta Perm.',
                'PORTABILIDADE': 'Portabilidade',
                'OUTRO': 'Outro',
            };
            const tipoClasses = {
                'ACESSO_EMPRESA': 'tipo-primary',
                'LOGIN_APPS': 'tipo-primary',
                'TROCA_EMAIL': 'tipo-primary',
                'CANCELAMENTO_QUALICORP': 'tipo-danger',
                'CANCELAMENTO_LIMITAR': 'tipo-danger',
                'BOAS_VINDAS': 'tipo-info',
                'ENVIO_BOLETO': 'tipo-warning',
                'REEMBOLSO': 'tipo-warning',
                'INCLUSAO_BENEFICIARIO': 'tipo-info',
                'EXCLUSAO_BENEFICIARIO': 'tipo-warning',
                'CANCELAMENTO': 'tipo-danger',
                'CARTA_PERMANENCIA': 'tipo-warning',
                'PORTABILIDADE': 'tipo-info',
                'OUTRO': 'tipo-muted',
            };

            let html = '';
            Object.values(grouped).forEach(contract => {
                const boHtml = contract.backoffice_nome
                    ? `<span class="bl-responsavel has-owner"><i class="ri-shield-user-line"></i> ${escapeHtml(contract.backoffice_nome)}</span>`
                    : `<span class="bl-responsavel no-owner"><i class="ri-shield-user-line"></i> Sem responsável</span>`;

                const statusHtml = contract.status_atual
                    ? `<span class="bl-status-tag">${escapeHtml(contract.status_atual)}</span>`
                    : '';

                const concluidas = contract.demandas.length - contract.pendentes;

                let demandasHtml = '';
                contract.demandas.forEach(d => {
                    const isConcluida = d.status === 'CONCLUIDA';
                    const tipoLabel = tipoLabels[d.tipo] || d.tipo;
                    const tipoClass = tipoClasses[d.tipo] || 'tipo-muted';

                    if (isConcluida) {
                        demandasHtml += `
                            <div class="bl-demanda-row concluida">
                                <span class="demanda-tipo-tag tipo-success">${escapeHtml(tipoLabel)}</span>
                                <span class="bl-demanda-titulo">${escapeHtml(d.titulo)}</span>
                                <span class="bl-demanda-status-done">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    Concluída
                                </span>
                                <span class="bl-demanda-criador">
                                    <i class="ri-user-line"></i> ${escapeHtml(d.criador)}
                                </span>
                            </div>
                        `;
                    } else {
                        let urgenciaClass = '';
                        if (d.dias_pendente > 7) urgenciaClass = 'urgente';
                        else if (d.dias_pendente > 3) urgenciaClass = 'atencao';
                        const diasLabel = d.dias_pendente === 0 ? 'Hoje' : d.dias_pendente === 1 ? '1 dia' : `${d.dias_pendente}d`;

                        demandasHtml += `
                            <div class="bl-demanda-row ${urgenciaClass}">
                                <span class="demanda-tipo-tag ${tipoClass}">${escapeHtml(tipoLabel)}</span>
                                <span class="bl-demanda-titulo">${escapeHtml(d.titulo)}</span>
                                <span class="bl-demanda-dias ${urgenciaClass}">
                                    <i class="ri-time-line"></i> ${diasLabel}
                                </span>
                                <span class="bl-demanda-criador">
                                    <i class="ri-user-line"></i> ${escapeHtml(d.criador)}
                                </span>
                                <button type="button" class="demanda-btn-concluir js-concluir-demanda" data-id="${d.id}" title="Concluir">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                </button>
                            </div>
                        `;
                    }
                });

                html += `
                    <div class="bl-contract-group">
                        <div class="bl-contract-header">
                            <div class="bl-contract-info">
                                <a href="/back-office/abrir-contrato/${contract.venda_id}" class="bl-contract-name">
                                    ${escapeHtml(contract.contrato_nome || 'Sem nome')}
                                </a>
                                <div class="bl-contract-meta">
                                    ${contract.contrato_proposta ? `<span class="bl-proposta">#${escapeHtml(contract.contrato_proposta)}</span>` : ''}
                                    ${contract.contrato_operadora ? `<span class="bl-operadora">${escapeHtml(contract.contrato_operadora)}</span>` : ''}
                                    ${statusHtml}
                                </div>
                            </div>
                            <div class="bl-contract-right">
                                ${boHtml}
                                <span class="bl-demanda-count" title="${contract.pendentes} pendente${contract.pendentes > 1 ? 's' : ''}${concluidas > 0 ? `, ${concluidas} concluída${concluidas > 1 ? 's' : ''}` : ''}">${contract.pendentes}</span>
                            </div>
                        </div>
                        <div class="bl-demandas-list">
                            ${demandasHtml}
                        </div>
                    </div>
                `;
            });

            container.html(html);
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
            $('#filter-vendedor, #filter-mes, #filter-ano, #filter-custodia, #filter-backoffice').on('change', function () {
                self.loadContratos();
            });

            // Clear filters
            $('#btn-clear-filters').on('click', function () {
                $('#filter-vendedor').val('');
                $('#filter-mes').val('');
                $('#filter-ano').val('');
                $('#filter-busca').val('');
                $('#filter-custodia').val('meus');
                $('#filter-backoffice').val('');
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

            // Concluir demanda
            $(document).on('click', '.js-concluir-demanda', async function (e) {
                e.stopPropagation();
                const btn = $(this);
                const demandaId = btn.data('id');

                btn.prop('disabled', true).addClass('loading');

                try {
                    const response = await fetch(`/back-office/demandas-contrato/${demandaId}/toggle`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                        },
                    });
                    const result = await response.json();

                    if (result.success) {
                        showModernToast('success', 'Demanda Concluída!', result.message || 'Demanda finalizada com sucesso.');
                        // Reload kanban (updates badge counts) and demandas list
                        self.loadContratos();
                        if (self.demandasLoaded) {
                            self.loadDemandasList();
                        }
                    } else {
                        showModernToast('error', 'Erro', result.message || 'Não foi possível concluir a demanda.');
                        btn.prop('disabled', false).removeClass('loading');
                    }
                } catch (error) {
                    showModernToast('error', 'Erro', 'Falha na conexão.');
                    btn.prop('disabled', false).removeClass('loading');
                }
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
    // Painel de Estornos — propostas devolvidas ao vendedor, do estorno mais
    // recente para o mais antigo: o que acabou de voltar ainda dá para captar.
    // O tempo parado continua visível no card porque é ele que mede o risco.
    // =============================================================================
    const PainelEstornos = {
        estornos: [],
        termo: '',
        carregando: false,

        init() {
            if (!document.getElementById('modalEstornos')) return;
            this.bind();
            this.carregar();
        },

        bind() {
            const self = this;

            $('#btn-abrir-estornos').on('click', () => {
                $('#modalEstornos').modal('show');
                self.carregar();
            });

            $('#modalEstornos').on('shown.bs.modal', () => {
                $('#estornos-busca').trigger('focus');
            });

            $('#modalEstornos').on('hidden.bs.modal', () => {
                self.termo = '';
                $('#estornos-busca').val('');
                self.render();
            });

            $('#estornos-busca').on('input', function () {
                self.termo = this.value.trim().toLowerCase();
                self.render();
            });

            // Esc limpa a busca antes de fechar o modal
            $('#estornos-busca').on('keydown', function (e) {
                if (e.key === 'Escape' && this.value) {
                    e.stopPropagation();
                    this.value = '';
                    self.termo = '';
                    self.render();
                }
            });

            $(document).on('click', '.js-estorno-limpar-busca', () => {
                $('#estornos-busca').val('').trigger('input').trigger('focus');
            });

            // O card vira o formulário de confirmação — nada de modal sobre modal.
            $(document).on('click', '.js-estorno-retomar', function () {
                const card = $(this).closest('.est-card');
                card.addClass('is-confirming');
                card.find('.est-justificativa').trigger('focus');
            });

            $(document).on('click', '.js-estorno-cancelar', function () {
                $(this).closest('.est-card').removeClass('is-confirming');
            });

            $(document).on('click', '.js-estorno-confirmar', function () {
                const card = $(this).closest('.est-card');
                self.retomar(card, card.find('.est-justificativa').val());
            });
        },

        async carregar() {
            if (this.carregando) return;
            this.carregando = true;

            try {
                const response = await fetch('/back-office/estornos', {
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();

                if (!result.success) throw new Error(result.message || 'Falha ao carregar estornos.');

                this.estornos = result.estornos || [];
                this.atualizarContador();
                this.render();
            } catch (error) {
                console.error('Erro ao carregar estornos:', error);
                $('#estornos-lista').html(`
                    <div class="est-empty">
                        <div class="est-empty-title">Não foi possível carregar os estornos</div>
                        <p class="est-empty-text">Recarregue a página e tente de novo.</p>
                    </div>
                `);
            } finally {
                this.carregando = false;
            }
        },

        atualizarContador() {
            const total = this.estornos.length;
            const badge = $('#estornos-count');

            badge.text(total);
            $('#btn-abrir-estornos').toggleClass('has-estornos', total > 0);
            badge.toggleClass('d-none', total === 0);
        },

        filtrados() {
            if (!this.termo) return this.estornos;

            return this.estornos.filter(e => {
                const alvo = [e.nome_contrato, e.cpf_cnpj, e.numero_proposta, e.vendedor, e.operadora]
                    .join(' ')
                    .toLowerCase();
                return alvo.includes(this.termo);
            });
        },

        // Faixas de urgência: o que passa de um mês parado virou venda morta.
        nivelUrgencia(dias) {
            if (dias >= 30) return 3;
            if (dias >= 15) return 2;
            if (dias >= 7) return 1;
            return 0;
        },

        render() {
            const lista = this.filtrados();
            const container = $('#estornos-lista');
            const total = this.estornos.length;

            // Resumo: quantidade e, se houver, quantas já passaram de 30 dias.
            const criticos = this.estornos.filter(e => e.dias_parado >= 30).length;
            $('#estornos-resumo').html(
                total === 0
                    ? 'Nenhuma proposta estornada'
                    : `${total} ${total === 1 ? 'proposta estornada' : 'propostas estornadas'}` +
                      (criticos > 0 ? ` <span class="est-resumo-critico">${criticos} parada${criticos === 1 ? '' : 's'} há mais de 30 dias</span>` : '')
            );

            if (total === 0) {
                container.html(`
                    <div class="est-empty">
                        <div class="est-empty-title">Nada estornado por aqui</div>
                        <p class="est-empty-text">Quando uma proposta for devolvida ao vendedor, ela aparece nesta lista até voltar para a fila.</p>
                    </div>
                `);
                return;
            }

            if (lista.length === 0) {
                container.html(`
                    <div class="est-empty">
                        <div class="est-empty-title">Nenhum resultado para “${escapeHtml(this.termo)}”</div>
                        <p class="est-empty-text">Busque por cliente, CPF/CNPJ, nº da proposta, vendedor ou operadora.</p>
                        <button type="button" class="est-btn est-btn-ghost js-estorno-limpar-busca">Limpar busca</button>
                    </div>
                `);
                return;
            }

            container.html(lista.map((e, i) => this.renderCard(e, i)).join(''));
        },

        renderCard(e, index) {
            const nivel = this.nivelUrgencia(e.dias_parado);
            const diasLabel = e.dias_parado === 0 ? 'hoje' : (e.dias_parado === 1 ? 'dia parado' : 'dias parados');

            const meta = [
                e.numero_proposta ? `<span class="est-meta-item est-meta-num">#${escapeHtml(e.numero_proposta)}</span>` : '',
                e.cpf_cnpj ? `<span class="est-meta-item est-meta-num">${escapeHtml(e.cpf_cnpj)}</span>` : '',
                e.operadora ? `<span class="est-meta-item">${escapeHtml(e.operadora)}</span>` : '',
                `<span class="est-meta-item est-meta-num">${formatCurrency(e.valor)}</span>`,
            ].filter(Boolean).join('');

            const motivo = e.motivo
                ? `<p class="est-motivo">${escapeHtml(e.motivo)}</p>`
                : '<p class="est-motivo est-motivo-vazio">Estorno registrado sem motivo.</p>';

            const rodapeInfo = [
                e.vendedor ? `com <strong>${escapeHtml(e.vendedor)}</strong>` : 'sem vendedor',
                e.estornado_em ? `desde ${escapeHtml(e.estornado_em)}` : '',
            ].filter(Boolean).join(' · ');

            const acao = e.pode_retomar
                ? `<button type="button" class="est-btn est-btn-retomar js-estorno-retomar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 14 4 9 9 4"/>
                            <path d="M20 20v-7a4 4 0 0 0-4-4H4"/>
                        </svg>
                        Trazer para a fila
                   </button>`
                : `<span class="est-bloqueado" title="Só ${escapeHtml(e.backoffice_nome || 'o responsável')} ou um administrador pode retomar">
                        Sob responsabilidade de ${escapeHtml(e.backoffice_nome || 'outro backoffice')}
                   </span>`;

            return `
                <article class="est-card" data-id="${e.id}" data-nivel="${nivel}" style="--est-delay: ${Math.min(index, 8) * 45}ms">
                    <div class="est-card-head">
                        <div class="est-cliente">
                            <h6 class="est-nome">${escapeHtml(e.nome_contrato || 'Sem nome')}</h6>
                            <div class="est-meta">${meta}</div>
                        </div>
                        <div class="est-tempo" title="Parado com o vendedor desde ${escapeHtml(e.estornado_em || 'data não registrada')}">
                            <span class="est-tempo-num">${e.dias_parado === 0 ? '' : e.dias_parado}</span>
                            <span class="est-tempo-label">${diasLabel}</span>
                        </div>
                    </div>

                    ${motivo}

                    <div class="est-card-foot">
                        <span class="est-rodape-info">${rodapeInfo}</span>
                        <div class="est-acao">${acao}</div>
                    </div>

                    <div class="est-confirm">
                        <div class="est-confirm-inner">
                            <label class="est-confirm-label" for="est-just-${e.id}">Justificativa (opcional)</label>
                            <textarea id="est-just-${e.id}" class="est-justificativa" rows="2" maxlength="500"
                                      placeholder="Ex.: alinhado por telefone, documento já recebido."></textarea>
                            <div class="est-confirm-actions">
                                <span class="est-confirm-hint">O vendedor é avisado e a proposta sai de “Meus Estornos”.</span>
                                <div class="est-confirm-buttons">
                                    <button type="button" class="est-btn est-btn-ghost js-estorno-cancelar">Cancelar</button>
                                    <button type="button" class="est-btn est-btn-confirmar js-estorno-confirmar">Confirmar retomada</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            `;
        },

        async retomar(card, observacao) {
            const vendaId = card.data('id');
            card.addClass('is-loading');
            card.find('button').prop('disabled', true);

            try {
                const response = await fetch('/back-office/retomar-estorno', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        venda_id: vendaId,
                        observacao: observacao || null,
                    }),
                });

                const result = await response.json();

                if (result.success) {
                    // A ficha sai da lista literalmente indo embora.
                    card.addClass('is-leaving');
                    setTimeout(() => {
                        this.estornos = this.estornos.filter(e => e.id !== vendaId);
                        this.atualizarContador();
                        this.render();
                    }, 320);

                    showModernToast('success', 'Proposta na fila', result.message || 'O contrato voltou para a fila do backoffice.');
                    KanbanContratos.loadContratos();
                } else {
                    card.removeClass('is-loading is-confirming').find('button').prop('disabled', false);
                    showModernToast('error', 'Não foi possível retomar', result.message || 'Tente novamente.');
                }
            } catch (error) {
                console.error('Erro ao retomar estorno:', error);
                card.removeClass('is-loading is-confirming').find('button').prop('disabled', false);
                showModernToast('error', 'Erro de conexão', 'Verifique sua internet e tente novamente.');
            }
        },
    };

    // =============================================================================
    // Modal Status Change Logic (Existing)
    // =============================================================================
    $(document).on('change', '#label', function () {
        const val = $(this).val();

        // IMPLANTADO (ID 18) — modal expande para 2 colunas (implantação + demandas)
        if (val === '18') {
            $('#proof-group').show();
            $('#comprovante').prop('required', true);
            $('#proof-group-data-implantacao').show();
            $('#proof-group-numero_proposta').show();
            $('#data_implantacao').prop('required', true);
            $('#numero_proposta').prop('required', true);
            $('#proof-group-acesso-empresa').show();
            $('#modalcommentsDialog').addClass('kb-dialog-wide');
            $('#implantado-grid').slideDown(300);
            atualizarContadorDemandas();
        } else {
            $('#proof-group').hide();
            $('#comprovante').prop('required', false).val('');
            $('#proof-group-data-implantacao').hide();
            $('#proof-group-numero_proposta').hide();
            $('#data_implantacao').prop('required', false).val('');
            $('#numero_proposta').prop('required', false).val('');
            $('#proof-group-acesso-empresa').hide();
            $('#acesso_email, #acesso_senha, #acesso_cpf').val('');
            $('#implantado-grid').slideUp(300);
            $('#modalcommentsDialog').removeClass('kb-dialog-wide');
        }

        // PENDÊNCIA, ESTORNO, DECLINADO (IDs 55, 17, 53)
        if (val === '55' || val === '17' || val === '53') {
            $('#proof-group-data-pendencia').show();
            $('#data_pendencia').prop('required', true);

            if (val === '17') {
                $('#label-motivo-pendencia').text('Motivo do Estorno');
                $('#data_pendencia').attr('placeholder', 'Descreva o motivo do estorno (mínimo 10 caracteres). O vendedor receberá esta mensagem.');
                $('#data_pendencia').attr('minlength', '10');
                $('#hint-motivo-estorno').removeClass('d-none');
                $('#proof-group-observacao-estorno').show();
            } else if (val === '55') {
                $('#label-motivo-pendencia').text('Motivo da Pendência');
                $('#data_pendencia').attr('placeholder', 'Descreva o motivo da pendência...');
                $('#data_pendencia').removeAttr('minlength');
                $('#hint-motivo-estorno').addClass('d-none');
                $('#proof-group-observacao-estorno').hide();
                $('#observacao_estorno').val('');
            } else {
                $('#label-motivo-pendencia').text('Motivo do Declínio');
                $('#data_pendencia').attr('placeholder', 'Descreva o motivo...');
                $('#data_pendencia').removeAttr('minlength');
                $('#hint-motivo-estorno').addClass('d-none');
                $('#proof-group-observacao-estorno').hide();
                $('#observacao_estorno').val('');
            }
        } else {
            $('#proof-group-data-pendencia').hide();
            $('#data_pendencia').prop('required', false).val('');
            $('#data_pendencia').removeAttr('minlength');
            $('#hint-motivo-estorno').addClass('d-none');
            $('#proof-group-observacao-estorno').hide();
            $('#observacao_estorno').val('');
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

    // ----- Demandas de pós-venda (checklist do modal de implantação) -----
    function atualizarContadorDemandas() {
        const $boxes = $('#kb-demandas-checklist input[type="checkbox"]');
        const marcadas = $boxes.filter(':checked').length;
        $('#kb-demandas-counter').text(marcadas);

        const $toggle = $('#kb-demandas-toggle-all');
        const todas = marcadas === $boxes.length && $boxes.length > 0;
        $toggle.text(todas ? 'Desmarcar todas' : 'Marcar todas');
    }

    $(document).on('change', '#kb-demandas-checklist input[type="checkbox"]', atualizarContadorDemandas);

    $(document).on('click', '#kb-demandas-toggle-all', function () {
        const $boxes = $('#kb-demandas-checklist input[type="checkbox"]');
        const marcarTodas = $boxes.filter(':checked').length < $boxes.length;
        $boxes.prop('checked', marcarTodas);
        atualizarContadorDemandas();
    });

    $('#modalcomments').on('shown.bs.modal', function () {
        initAcessoCpfMask();
    });

    $('#modalcomments').on('hidden.bs.modal', function () {
        $('#acesso_email, #acesso_senha, #acesso_cpf').val('');
        $('#proof-group-acesso-empresa').hide();
        $('#implantado-grid').hide();
        $('#modalcommentsDialog').removeClass('kb-dialog-wide');
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
    PainelEstornos.init();
});
