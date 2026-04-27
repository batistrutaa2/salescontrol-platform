'use strict';

(function () {
    const cfg = window.lkbKanban || {};
    if (typeof Sortable === 'undefined') {
        console.error('SortableJS não carregado');
        return;
    }

    const carregarDados = async () => {
        const resp = await fetch(cfg.dadosUrl, { headers: { Accept: 'application/json' } });
        if (!resp.ok) {
            console.error('Falha ao carregar kanban', resp.status);
            return;
        }
        const body = await resp.json();
        renderizar(body.colunas || {});
    };

    const renderizar = (colunas) => {
        Object.entries(colunas).forEach(([status, leads]) => {
            const container = document.querySelector(`.lkb-kanban-column-body[data-column="${status}"]`);
            const countEl = document.querySelector(`[data-count="${status}"]`);
            if (!container) return;
            container.innerHTML = '';
            (leads || []).forEach((lead) => container.appendChild(montarCard(lead)));
            if (countEl) countEl.textContent = (leads || []).length;
        });
    };

    const montarCard = (lead) => {
        const card = document.createElement('div');
        card.className = 'lkb-card';
        card.dataset.id = lead.id;
        const showUrl = (cfg.showUrlTemplate || '').replace('__ID__', lead.id);
        const converterUrl = (cfg.converterUrlTemplate || '').replace('__ID__', lead.id);
        const origemClass = lead.origem === 'BASE_SAUDE' ? 'base-saude' : 'manual';
        const origemLabel = lead.origem === 'BASE_SAUDE' ? 'Base Saúde' : 'Manual';
        card.innerHTML = `
            <div class="lkb-card-nome">${escapar(lead.nome)}</div>
            <div class="lkb-card-produto">${escapar(lead.produto_nome || '—')}</div>
            <div class="lkb-card-meta">
                <span>${escapar(lead.corretor || '—')}</span>
                <span class="lkb-card-badge-origem ${origemClass}">${origemLabel}</span>
            </div>
            <div class="lkb-card-meta">
                <span>CPF/CNPJ: ${escapar(lead.cpf_cnpj)}</span>
                <span>${lead.dias_no_status}d no status</span>
            </div>
            <div class="lkb-card-actions">
                <a href="${showUrl}" class="btn btn-sm btn-outline-primary">Detalhes</a>
                <a href="${converterUrl}" class="btn btn-sm btn-success">Converter</a>
            </div>
        `;
        return card;
    };

    const escapar = (txt) => {
        const div = document.createElement('div');
        div.textContent = txt == null ? '' : String(txt);
        return div.innerHTML;
    };

    const mover = async (leadId, novoStatus, ordemIds) => {
        const resp = await fetch(cfg.moverUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
                Accept: 'application/json',
            },
            body: JSON.stringify({
                lead_id: leadId,
                novo_status: novoStatus,
                ordem: ordemIds,
            }),
        });
        if (!resp.ok) {
            const err = await resp.json().catch(() => ({}));
            alert(err.error || 'Falha ao mover lead.');
            carregarDados();
            return;
        }
        atualizarContadores();
    };

    const atualizarContadores = () => {
        document.querySelectorAll('.lkb-kanban-column-body').forEach((body) => {
            const status = body.dataset.column;
            const el = document.querySelector(`[data-count="${status}"]`);
            if (el) el.textContent = body.children.length;
        });
    };

    document.querySelectorAll('.lkb-kanban-column-body').forEach((el) => {
        new Sortable(el, {
            group: 'lkb-leads',
            animation: 150,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: (evt) => {
                if (evt.from === evt.to && evt.oldIndex === evt.newIndex) return;
                const leadId = parseInt(evt.item.dataset.id, 10);
                const novoStatus = evt.to.dataset.column;
                const ordemIds = Array.from(evt.to.children).map((c) => parseInt(c.dataset.id, 10));
                mover(leadId, novoStatus, ordemIds);
            },
        });
    });

    carregarDados();
})();
