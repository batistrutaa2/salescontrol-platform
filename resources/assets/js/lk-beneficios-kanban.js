'use strict';

(function () {
    const cfg = window.lkbKanban || {};

    if (typeof Sortable === 'undefined') {
        console.error('SortableJS não carregado');
        return;
    }

    // =========================================================================
    // Helpers
    // =========================================================================
    const escapar = (txt) => {
        const div = document.createElement('div');
        div.textContent = txt == null ? '' : String(txt);
        return div.innerHTML;
    };

    const corDoStatus = (status) => {
        const cores = (cfg.statusCores || {})[status];
        return cores ? cores.start : '#7C3AED';
    };

    const formatarCpfCnpj = (valor) => {
        const v = String(valor || '').replace(/\D/g, '');
        if (v.length === 11) {
            return v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }
        if (v.length === 14) {
            return v.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
        }
        return valor || '—';
    };

    const SVG = {
        ver: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`,
        converter: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`,
        usuario: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
        relogio: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`,
        toastSuccess: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`,
        toastError: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
        toastWarning: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
        toastInfo: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`,
        toastClose: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`,
    };

    const showModernToast = (type, title, message) => {
        if (typeof Swal === 'undefined') {
            console.warn('SweetAlert2 não disponível, fallback alert.', { type, title, message });
            // eslint-disable-next-line no-alert
            alert(`${title}\n${message}`);
            return;
        }
        const iconKey = { success: SVG.toastSuccess, error: SVG.toastError, warning: SVG.toastWarning, info: SVG.toastInfo };
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            background: 'transparent',
            showClass: { popup: 'animate__animated animate__slideInRight animate__faster' },
            hideClass: { popup: 'animate__animated animate__slideOutRight animate__faster' },
            html: `
                <div class="custom-toast custom-toast-${type}">
                    <div class="toast-icon">${iconKey[type] || SVG.toastInfo}</div>
                    <div class="toast-content">
                        <div class="toast-title">${escapar(title)}</div>
                        <div class="toast-message">${escapar(message)}</div>
                    </div>
                    <div class="toast-close" onclick="Swal.close()">${SVG.toastClose}</div>
                </div>
            `,
            customClass: { popup: 'custom-toast-popup' },
        });
    };

    // =========================================================================
    // Render
    // =========================================================================
    const carregarDados = async () => {
        try {
            const resp = await fetch(cfg.dadosUrl, { headers: { Accept: 'application/json' } });
            if (!resp.ok) {
                throw new Error(`HTTP ${resp.status}`);
            }
            const body = await resp.json();
            renderizar(body.colunas || {});
        } catch (err) {
            console.error('Falha ao carregar kanban', err);
            showModernToast('error', 'Erro ao carregar', 'Não foi possível obter os leads.');
        }
    };

    const renderizar = (colunas) => {
        document.querySelectorAll('.lkb-kanban-column-body').forEach((container) => {
            const status = container.dataset.column;
            const leads = colunas[status] || [];
            container.innerHTML = '';
            leads.forEach((lead, idx) => {
                const card = montarCard(lead, status);
                card.style.animationDelay = `${idx * 40}ms`;
                container.appendChild(card);
            });
            const countEl = document.querySelector(`[data-count="${status}"]`);
            if (countEl) countEl.textContent = leads.length;
        });
    };

    const montarCard = (lead, status) => {
        const card = document.createElement('article');
        card.className = 'lkb-card';
        card.dataset.id = lead.id;
        card.style.setProperty('--lkb-card-status-color', corDoStatus(status));

        const showUrl = (cfg.showUrlTemplate || '').replace('__ID__', lead.id);
        const converterUrl = (cfg.converterUrlTemplate || '').replace('__ID__', lead.id);

        const produtoNome = lead.produto_nome || '—';
        const produtoClass = lead.produto_nome ? '' : 'is-empty';

        const origemClass = lead.origem === 'BASE_SAUDE' ? 'is-base-saude' : 'is-manual';
        const origemLabel = lead.origem === 'BASE_SAUDE' ? 'Base Saúde' : 'Manual';

        const dias = Number.isFinite(lead.dias_no_status) ? lead.dias_no_status : 0;
        let tempoClass = '';
        if (dias >= 7) tempoClass = 'urgente';
        else if (dias >= 3) tempoClass = 'atencao';

        card.innerHTML = `
            <header class="lkb-card-header">
                <span class="lkb-produto-badge ${produtoClass}" title="${escapar(produtoNome)}">${escapar(produtoNome)}</span>
                <div class="lkb-card-actions">
                    <a href="${showUrl}" class="lkb-btn-action" aria-label="Ver detalhes do lead" title="Ver detalhes">${SVG.ver}</a>
                    <a href="${converterUrl}" class="lkb-btn-action is-success" aria-label="Converter em contrato" title="Converter em contrato">${SVG.converter}</a>
                </div>
            </header>
            <div class="lkb-card-body">
                <h5 class="lkb-card-nome">${escapar(lead.nome)}</h5>
                <span class="lkb-card-cpf">${escapar(formatarCpfCnpj(lead.cpf_cnpj))}</span>
            </div>
            <div class="lkb-card-info">
                <span class="lkb-info-item">
                    ${SVG.usuario}
                    <span>${escapar(lead.corretor || 'Sem corretor')}</span>
                </span>
                <span class="lkb-origem-chip ${origemClass}">${origemLabel}</span>
            </div>
            <footer class="lkb-card-footer">
                <span class="lkb-tempo-status ${tempoClass}">
                    ${SVG.relogio}
                    <span>${dias}d no status</span>
                </span>
            </footer>
        `;
        return card;
    };

    // =========================================================================
    // Mover (persistência)
    // =========================================================================
    const mover = async (leadId, novoStatus, ordemIds) => {
        try {
            const resp = await fetch(cfg.moverUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': cfg.csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ lead_id: leadId, novo_status: novoStatus, ordem: ordemIds }),
            });
            if (!resp.ok) {
                const err = await resp.json().catch(() => ({}));
                throw new Error(err.error || `Falha ao mover (HTTP ${resp.status})`);
            }
            atualizarContadores();
            showModernToast('success', 'Lead movido', 'O status foi atualizado com sucesso.');
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro ao mover', err.message || 'Tente novamente.');
            carregarDados();
        }
    };

    const atualizarContadores = () => {
        document.querySelectorAll('.lkb-kanban-column-body').forEach((body) => {
            const status = body.dataset.column;
            const el = document.querySelector(`[data-count="${status}"]`);
            if (el) el.textContent = body.children.length;
        });
    };

    // =========================================================================
    // Drag & Drop
    // =========================================================================
    document.querySelectorAll('.lkb-kanban-column-body').forEach((el) => {
        new Sortable(el, {
            group: 'lkb-leads',
            animation: 160,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onStart: () => {
                document.querySelectorAll('.lkb-kanban-column-body').forEach((body) => body.classList.add('is-dragging-source'));
            },
            onEnd: (evt) => {
                document.querySelectorAll('.lkb-kanban-column-body').forEach((body) => body.classList.remove('is-dragging-source', 'drag-over'));
                if (evt.from === evt.to && evt.oldIndex === evt.newIndex) return;

                const leadId = parseInt(evt.item.dataset.id, 10);
                const novoStatus = evt.to.dataset.column;

                evt.item.style.setProperty('--lkb-card-status-color', corDoStatus(novoStatus));

                const ordemIds = Array.from(evt.to.children).map((c) => parseInt(c.dataset.id, 10));
                mover(leadId, novoStatus, ordemIds);
            },
            onMove: (evt) => {
                document.querySelectorAll('.lkb-kanban-column-body').forEach((body) => body.classList.remove('drag-over'));
                if (evt.to) evt.to.classList.add('drag-over');
            },
        });
    });

    carregarDados();
})();
