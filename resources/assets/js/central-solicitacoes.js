'use strict';

(function () {
    const cfg = window.spvConfig || {};
    const tipos = cfg.tipos || {};
    const naturezas = cfg.naturezas || {};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const COR_FALLBACK = '#7C3AED';
    const CAMPO_LABEL = {
        abertura: 'Abertura',
        etapa: 'Etapa',
        prazo: 'Prazo',
        responsavel: 'Responsável',
        titulo: 'Título',
        observacoes: 'Descrição',
        prioridade: 'Prioridade',
        retorno: 'Retorno à fila',
        atualizacao: 'Atualização',
    };

    // Estado local: fonte única para fila e kanban.
    let registros = [];
    let etapasPorTipo = cfg.etapasPorTipo || {};
    let filtroStatus = 'ABERTA';
    let kanbanTipo = Object.keys(tipos)[0] || 'CANCELAMENTO';

    // =========================================================================
    // Helpers
    // =========================================================================
    const escapar = (txt) => {
        const div = document.createElement('div');
        div.textContent = txt == null ? '' : String(txt);
        return div.innerHTML;
    };

    const formatarDoc = (valor) => {
        const v = String(valor || '').replace(/\D/g, '');
        if (v.length === 14) return v.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
        if (v.length === 11) return v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        return valor || '—';
    };

    const etapasDoTipo = (tipo) => etapasPorTipo[tipo] || [];
    const etapaInfo = (tipo, etapaId) => etapasDoTipo(tipo).find((e) => e.id === etapaId) || null;

    const SVG = {
        toastSuccess: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`,
        toastError: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
        toastWarning: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
        toastInfo: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`,
        toastClose: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`,
        check: `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`,
        relogio: `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`,
        grip: `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="6" r="1.2"/><circle cx="15" cy="6" r="1.2"/><circle cx="9" cy="12" r="1.2"/><circle cx="15" cy="12" r="1.2"/><circle cx="9" cy="18" r="1.2"/><circle cx="15" cy="18" r="1.2"/></svg>`,
        lixeira: `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>`,
        bandeira: `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>`,
    };

    const showModernToast = (type, title, message) => {
        if (typeof Swal === 'undefined') {
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

    const primeiroErro = (body, fallback) => {
        const errors = body?.errors;
        if (errors) {
            const chave = Object.keys(errors)[0];
            if (chave && errors[chave]?.length) return errors[chave][0];
        }
        return body?.message || fallback;
    };

    const api = async (url, options = {}) => {
        const resp = await fetch(url, {
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            ...options,
        });
        if (!resp.ok) {
            const body = await resp.json().catch(() => ({}));
            throw new Error(primeiroErro(body, `HTTP ${resp.status}`));
        }
        return resp.json().catch(() => ({}));
    };

    // =========================================================================
    // Badge de prazo
    // =========================================================================
    const prazoBadge = (item) => {
        if (item.adiado) {
            return `<span class="spv-prazo spv-prazo--adiado">${SVG.relogio} Retorna em ${escapar(item.data_retorno)}</span>`;
        }
        if (item.status !== 'ABERTA') {
            return item.status === 'CONCLUIDA'
                ? `<span class="spv-prazo spv-prazo--done">${SVG.check} Concluída${item.concluida_em ? ` em ${escapar(item.concluida_em)}` : ''}</span>`
                : '<span class="spv-prazo spv-prazo--done">Cancelada</span>';
        }
        if (item.dias_restantes == null) {
            return `<span class="spv-prazo spv-prazo--indefinido">${SVG.relogio} Sem prazo</span>`;
        }
        const dias = item.dias_restantes;
        if (dias < 0) {
            const abs = Math.abs(dias);
            return `<span class="spv-prazo spv-prazo--vencido">${SVG.relogio} Vencido há ${abs} dia${abs === 1 ? '' : 's'}</span>`;
        }
        if (dias === 0) {
            return `<span class="spv-prazo spv-prazo--alerta">${SVG.relogio} Vence hoje</span>`;
        }
        if (dias <= 7) {
            return `<span class="spv-prazo spv-prazo--alerta">${SVG.relogio} Vence em ${dias} dia${dias === 1 ? '' : 's'}</span>`;
        }
        return `<span class="spv-prazo spv-prazo--ok">${SVG.relogio} Vence em ${dias} dias</span>`;
    };

    const etapaBadge = (item) => {
        const cor = item.etapa_cor || COR_FALLBACK;
        return `<span class="spv-badge-etapa" style="--etapa-cor:${cor}">${escapar(item.etapa_nome || '—')}</span>`;
    };

    const prioridadeBadge = (item) => (item.prioridade
        ? `<span class="spv-badge-prioridade" title="Solicitação prioritária">${SVG.bandeira} Prioridade</span>`
        : '');

    // =========================================================================
    // Prioridade e exclusão
    // =========================================================================
    const alternarPrioridade = async (id) => {
        try {
            const body = await api(`/back-office/solicitacoes/${id}/prioridade`, { method: 'POST' });
            showModernToast('success', body.prioridade ? 'Marcada como prioridade' : 'Prioridade removida',
                body.prioridade ? 'A solicitação subiu para o topo da fila.' : 'A solicitação voltou à ordem normal da fila.');
            return body.prioridade;
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro', err.message || 'Não foi possível alterar a prioridade.');
            return null;
        } finally {
            carregarDados();
        }
    };

    const excluirSolicitacao = async (item) => {
        const result = await Swal.fire({
            title: 'Excluir solicitação?',
            text: `"${item.titulo || item.tipo_label}" de ${item.nome_contrato || 'cliente'} será excluída junto com o histórico. Essa ação não pode ser desfeita.`,
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonText: 'Excluir',
            cancelButtonText: 'Voltar',
            buttonsStyling: false,
            customClass: { confirmButton: 'pv-btn spv-btn-danger-confirm', cancelButton: 'pv-btn pv-btn-ghost' },
        });
        if (!result.isConfirmed) return false;

        try {
            await api(`/back-office/solicitacoes/${item.id}`, { method: 'DELETE' });
            showModernToast('success', 'Solicitação excluída', 'Ela saiu da fila do pós-venda.');
            carregarDados();
            return true;
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro ao excluir', err.message || 'Tente novamente.');
            return false;
        }
    };

    // =========================================================================
    // Carregar dados
    // =========================================================================
    const carregarDados = async () => {
        try {
            const body = await api('/back-office/solicitacoes/dados');
            registros = body.registros || [];
            etapasPorTipo = body.etapas || etapasPorTipo;
            renderKpis(body.kpis || {});
            renderFila();
            renderKanbanTipos();
            renderKanban();
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro', 'Não foi possível carregar as solicitações.');
        }
    };

    const renderKpis = (kpis) => {
        document.querySelectorAll('[data-kpi]').forEach((el) => {
            el.textContent = kpis[el.dataset.kpi] ?? 0;
        });
    };

    // =========================================================================
    // Filtros (aplicados no cliente sobre o último fetch)
    // =========================================================================
    const registrosFiltrados = () => {
        const tipo = document.getElementById('spvFiltroTipo')?.value || '';
        const responsavel = document.getElementById('spvFiltroResponsavel')?.value || '';
        const busca = (document.getElementById('spvFiltroBusca')?.value || '').trim().toLowerCase();

        return registros.filter((r) => {
            if (filtroStatus === 'ABERTA' && (r.status !== 'ABERTA' || r.adiado)) return false;
            if (filtroStatus === 'ADIADOS' && !r.adiado) return false;
            if (filtroStatus === 'ENCERRADAS' && r.status === 'ABERTA') return false;
            if (tipo && r.tipo !== tipo) return false;
            if (responsavel === '0' && r.responsavel_id) return false;
            if (responsavel && responsavel !== '0' && String(r.responsavel_id) !== responsavel) return false;
            if (!busca) return true;
            return [r.titulo, r.nome_contrato, r.cpf_cnpj, r.responsavel_nome, r.tipo_label]
                .some((v) => v && String(v).toLowerCase().includes(busca));
        });
    };

    ['spvFiltroTipo', 'spvFiltroResponsavel'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => { renderFila(); renderKanban(); });
    });
    document.getElementById('spvFiltroBusca')?.addEventListener('input', () => { renderFila(); renderKanban(); });

    document.querySelectorAll('.spv-chip').forEach((chip) => {
        chip.addEventListener('click', () => {
            filtroStatus = chip.dataset.status;
            document.querySelectorAll('.spv-chip').forEach((c) => c.classList.toggle('is-active', c === chip));
            renderFila();
            renderKanban();
        });
    });

    // =========================================================================
    // Toggle fila / kanban
    // =========================================================================
    const VIEW_KEY = 'spv-view';

    const trocarView = (view) => {
        document.querySelectorAll('.spv-view-btn').forEach((b) => b.classList.toggle('is-active', b.dataset.view === view));
        document.querySelectorAll('[data-view-pane]').forEach((p) => { p.hidden = p.dataset.viewPane !== view; });
        try { localStorage.setItem(VIEW_KEY, view); } catch { /* storage indisponível */ }
    };

    document.querySelectorAll('.spv-view-btn').forEach((btn) => {
        btn.addEventListener('click', () => trocarView(btn.dataset.view));
    });

    // =========================================================================
    // Fila por prazo
    // =========================================================================
    const renderFila = () => {
        const cont = document.getElementById('spvFila');
        const vazio = document.getElementById('spvFilaVazia');
        if (!cont) return;

        const linhas = registrosFiltrados();
        cont.innerHTML = linhas.map((r) => `
            <div class="spv-fila-row ${r.prioridade ? 'is-prioridade' : ''}" data-id="${r.id}">
                <div class="spv-fila-prazo">${prazoBadge(r)}</div>
                <div class="spv-fila-main">
                    <div class="spv-fila-titulo">
                        ${prioridadeBadge(r)}
                        <span class="spv-badge-tipo">${escapar(r.tipo_label)}</span>
                        <strong>${escapar(r.titulo || r.tipo_label)}</strong>
                        ${r.origem === 'VENDEDOR' ? `<span class="spv-badge-origem" title="Solicitação aberta pelo vendedor ${escapar(r.criado_por_nome || '')}">Vendedor</span>` : ''}
                    </div>
                    <div class="spv-fila-sub">${escapar(r.nome_contrato)} · ${escapar(formatarDoc(r.cpf_cnpj))}</div>
                </div>
                <div class="spv-fila-etapa">${etapaBadge(r)}</div>
                <div class="spv-fila-resp" title="Responsável">${escapar(r.responsavel_nome || 'Sem responsável')}</div>
                <div class="spv-fila-data" title="Quem abriu e quando">
                    <span class="spv-fila-autor">${escapar(r.criado_por_nome || '—')}</span>
                    <span>${escapar(r.criado_em)}</span>
                </div>
                <div class="spv-fila-acoes">
                    <button type="button" class="spv-acao-btn spv-acao-flag ${r.prioridade ? 'is-active' : ''}" data-acao="prioridade"
                            title="${r.prioridade ? 'Remover prioridade' : 'Marcar como prioridade'}"
                            aria-label="${r.prioridade ? 'Remover prioridade' : 'Marcar como prioridade'}" aria-pressed="${r.prioridade ? 'true' : 'false'}">${SVG.bandeira}</button>
                    <button type="button" class="spv-acao-btn spv-acao-del" data-acao="excluir"
                            title="Excluir solicitação" aria-label="Excluir solicitação">${SVG.lixeira}</button>
                </div>
            </div>
        `).join('');
        vazio.hidden = linhas.length > 0;

        cont.querySelectorAll('.spv-fila-row').forEach((row) => {
            const id = parseInt(row.dataset.id, 10);
            row.addEventListener('click', () => abrirDetalhe(id));
            row.querySelectorAll('.spv-acao-btn').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const item = registros.find((r) => r.id === id);
                    if (!item) return;
                    if (btn.dataset.acao === 'prioridade') alternarPrioridade(id);
                    else excluirSolicitacao(item);
                });
            });
        });
    };

    // =========================================================================
    // Kanban por tipo
    // =========================================================================
    const renderKanbanTipos = () => {
        const cont = document.getElementById('spvKanbanTipos');
        if (!cont) return;

        cont.innerHTML = Object.entries(tipos).map(([valor, label]) => {
            const count = registros.filter((r) => r.tipo === valor && r.status === 'ABERTA' && !r.adiado).length;
            return `<button type="button" class="spv-tipo-chip ${valor === kanbanTipo ? 'is-active' : ''}" data-tipo="${valor}" role="tab">
                ${escapar(label)}<span class="spv-tipo-count">${count}</span>
            </button>`;
        }).join('');

        cont.querySelectorAll('.spv-tipo-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                kanbanTipo = chip.dataset.tipo;
                cont.querySelectorAll('.spv-tipo-chip').forEach((c) => c.classList.toggle('is-active', c === chip));
                renderKanban();
            });
        });
    };

    const cardTemplate = (item) => {
        const el = document.createElement('div');
        el.className = 'spv-card';
        el.dataset.id = item.id;
        el.style.setProperty('--spv-card-status-color', item.etapa_cor || COR_FALLBACK);

        if (item.prioridade) el.classList.add('is-prioridade');

        el.innerHTML = `
            <div class="spv-card-header">
                <span class="spv-card-contrato">${escapar(item.titulo || item.tipo_label)}</span>
                ${prioridadeBadge(item)}
            </div>
            <div class="spv-card-linha">${escapar(item.nome_contrato)}</div>
            <div class="spv-card-prazo">${prazoBadge(item)}</div>
            <div class="spv-card-autor" title="Quem abriu a solicitação">
                Aberta por <strong>${escapar(item.criado_por_nome || '—')}</strong>${item.origem === 'VENDEDOR' ? ' <span class="spv-badge-origem">Vendedor</span>' : ''}
            </div>
            <div class="spv-card-footer">
                <span title="Responsável">${escapar(item.responsavel_nome || 'Sem responsável')}</span>
                <span class="spv-card-data" title="Aberta em">${escapar(item.criado_em)}</span>
            </div>
        `;
        el.addEventListener('click', () => abrirDetalhe(item.id));
        return el;
    };

    const renderKanban = () => {
        const board = document.getElementById('spvKanban');
        if (!board) return;

        const etapas = etapasDoTipo(kanbanTipo);
        const doTipo = registrosFiltrados().filter((r) => r.tipo === kanbanTipo);

        board.style.setProperty('--spv-cols', etapas.length);
        board.innerHTML = '';
        etapas.forEach((etapa) => {
            const col = document.createElement('div');
            col.className = 'spv-kanban-column';
            col.style.setProperty('--col-cor', etapa.cor || COR_FALLBACK);
            col.innerHTML = `
                <div class="spv-kanban-column-header">
                    <span class="spv-kanban-column-dot"></span>
                    <span class="spv-kanban-column-title">${escapar(etapa.nome)}</span>
                    <span class="spv-kanban-column-count">0</span>
                </div>
                <div class="spv-kanban-column-body" data-etapa="${etapa.id}"></div>
            `;
            board.appendChild(col);

            const body = col.querySelector('.spv-kanban-column-body');
            doTipo.filter((r) => r.etapa_id === etapa.id).forEach((item) => body.appendChild(cardTemplate(item)));
            col.querySelector('.spv-kanban-column-count').textContent = body.children.length;
        });

        initDragDrop();
    };

    const mover = async (id, etapaId, aoTerminar = null) => {
        try {
            await api(`/back-office/solicitacoes/${id}/mover`, { method: 'POST', body: JSON.stringify({ etapa_id: etapaId }) });
            showModernToast('success', 'Solicitação movida', 'Etapa atualizada.');
            if (aoTerminar) aoTerminar();
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro ao mover', err.message || 'Tente novamente.');
        } finally {
            carregarDados();
        }
    };

    const initDragDrop = () => {
        if (typeof Sortable === 'undefined') return;
        document.querySelectorAll('.spv-kanban-column-body').forEach((el) => {
            new Sortable(el, {
                group: 'spv-solicitacoes',
                animation: 160,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: (evt) => {
                    document.querySelectorAll('.spv-kanban-column-body').forEach((b) => b.classList.remove('drag-over'));
                    if (evt.from === evt.to) return;
                    mover(parseInt(evt.item.dataset.id, 10), parseInt(evt.to.dataset.etapa, 10));
                },
                onMove: (evt) => {
                    document.querySelectorAll('.spv-kanban-column-body').forEach((b) => b.classList.remove('drag-over'));
                    if (evt.to) evt.to.classList.add('drag-over');
                },
            });
        });
    };

    // =========================================================================
    // Modal de detalhe
    // =========================================================================
    let detalheAtualId = null;
    let detalheTipo = null;
    let etapaModalAtual = null;
    let detalheRegistro = null;

    const atualizarFlagDetalhe = (prioridade) => {
        const btn = document.getElementById('btnSpvPrioridadeDetalhe');
        if (!btn) return;
        btn.classList.toggle('is-active', !!prioridade);
        btn.setAttribute('aria-pressed', prioridade ? 'true' : 'false');
        const rotulo = prioridade ? 'Remover prioridade' : 'Marcar como prioridade';
        btn.title = rotulo;
        btn.setAttribute('aria-label', rotulo);
        btn.querySelector('.spv-flag-btn-label').textContent = prioridade ? 'Prioritária' : 'Prioridade';
    };

    const infoItem = (label, value) =>
        `<div class="spv-info-item"><span class="spv-info-label">${escapar(label)}</span><span class="spv-info-value">${escapar(value ?? '—')}</span></div>`;

    const renderStepper = (tipo, etapaId) => {
        const cont = document.getElementById('spvDetalheStepper');
        if (!cont) return;
        const fluxo = etapasDoTipo(tipo).filter((e) => e.natureza === 'EM_ANDAMENTO' || e.id === etapaId);
        const idx = fluxo.findIndex((e) => e.id === etapaId);
        cont.innerHTML = fluxo.map((e, i) => {
            const estado = i < idx ? 'done' : (i === idx ? 'active' : 'todo');
            const marker = estado === 'done' ? SVG.check : (i + 1);
            const cor = e.cor || COR_FALLBACK;
            return `<li class="spv-step is-${estado}" style="--s:${cor}">
                <span class="spv-step-marker">${marker}</span>
                <span class="spv-step-label">${escapar(e.nome)}</span>
            </li>`;
        }).join('');
    };

    const renderTimeline = (hist) => {
        const tl = document.getElementById('spvTimeline');
        if (!hist.length) {
            tl.innerHTML = '<p class="spv-empty">Sem alterações registradas.</p>';
            return;
        }
        tl.innerHTML = hist.map((h) => {
            const label = CAMPO_LABEL[h.campo_alterado] || h.campo_alterado;

            // Atualização de andamento: o texto livre é o conteúdo principal.
            if (h.campo_alterado === 'atualizacao') {
                return `
                <div class="spv-timeline-item spv-timeline-item--atualizacao">
                    <span class="spv-timeline-dot"></span>
                    <div class="spv-timeline-content">
                        <div class="spv-timeline-text"><span class="spv-timeline-campo">${escapar(label)}</span> ${escapar(h.observacao || '—')}</div>
                        <div class="spv-timeline-meta">${escapar(h.usuario_nome ?? 'Sistema')} · ${escapar(h.created_at)}</div>
                    </div>
                </div>`;
            }

            const texto = h.valor_anterior ? `${h.valor_anterior} → ${h.valor_novo}` : (h.valor_novo || '—');
            return `
            <div class="spv-timeline-item">
                <span class="spv-timeline-dot"></span>
                <div class="spv-timeline-content">
                    <div class="spv-timeline-text"><span class="spv-timeline-campo">${escapar(label)}</span> ${escapar(texto)}</div>
                    ${h.observacao ? `<div class="spv-timeline-obs">${escapar(h.observacao)}</div>` : ''}
                    <div class="spv-timeline-meta">${escapar(h.usuario_nome ?? 'Sistema')} · ${escapar(h.created_at)}</div>
                </div>
            </div>`;
        }).join('');
    };

    const aplicarEtapaNaModal = (tipo, etapaId) => {
        etapaModalAtual = etapaId;
        const etapa = etapaInfo(tipo, etapaId);
        const cor = etapa?.cor || COR_FALLBACK;
        const header = document.getElementById('spvDetalheHeader');
        header.style.setProperty('--spv-etapa-cor', cor);
        document.getElementById('spvDetalheEtapaPill').textContent = etapa?.nome || '—';
        renderStepper(tipo, etapaId);

        const sel = document.getElementById('spvDetalheEtapaSelect');
        sel.innerHTML = etapasDoTipo(tipo).map((e) =>
            `<option value="${e.id}">${escapar(e.nome)}${e.natureza !== 'EM_ANDAMENTO' ? ` (${naturezas[e.natureza] || e.natureza})` : ''}</option>`
        ).join('');
        sel.value = etapaId;
    };

    const ativarAba = (alvo) => {
        document.querySelectorAll('#modalDetalheSolicitacao .spv-tab').forEach((t) => t.classList.toggle('is-active', t.dataset.tab === alvo));
        document.querySelectorAll('#modalDetalheSolicitacao .spv-tabpane').forEach((p) => p.classList.toggle('is-active', p.id === alvo));
    };
    document.querySelectorAll('#modalDetalheSolicitacao .spv-tab').forEach((t) => {
        t.addEventListener('click', () => ativarAba(t.dataset.tab));
    });

    const abrirDetalhe = async (id) => {
        detalheAtualId = id;
        document.getElementById('spvDetalheId').value = id;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalheSolicitacao')).show();

        try {
            const data = await api(`/back-office/solicitacoes/${id}`);
            const r = data.registro;
            detalheTipo = r.tipo;
            detalheRegistro = r;
            document.getElementById('btnSpvAbrirRetorno').hidden = r.status !== 'ABERTA';

            aplicarEtapaNaModal(r.tipo, r.etapa_id);
            atualizarFlagDetalhe(r.prioridade);
            document.getElementById('spvDetalheTitulo').textContent = r.titulo || r.tipo_label;
            document.getElementById('spvDetalheSubtitulo').textContent = `${r.tipo_label} · ${r.nome_contrato || '—'}`;

            document.getElementById('spvDetalheInfo').innerHTML =
                infoItem('Contrato', r.nome_contrato)
                + infoItem('CPF/CNPJ', formatarDoc(r.cpf_cnpj))
                + infoItem('Proposta', r.numero_proposta)
                + infoItem('Plano', r.nome_plano ? `${r.nome_plano} · ${r.operadora || ''}` : r.operadora)
                + infoItem('Origem', r.origem === 'VENDEDOR' ? 'Vendedor' : 'Pós-venda')
                + infoItem('Aberta por', r.criado_por_nome)
                + infoItem('Aberta em', r.criado_em)
                + (r.adiado ? infoItem('Retorno à fila', r.data_retorno) : '')
                + (r.status !== 'ABERTA' ? infoItem('Encerrada em', r.concluida_em) : '');

            document.getElementById('spvDetalheTituloInput').value = r.titulo || '';
            document.getElementById('spvDetalhePrazo').value = r.data_limite_iso || '';
            document.getElementById('spvDetalheResponsavel').value = r.responsavel_id || '';
            document.getElementById('spvDetalheDescricao').value = r.descricao || '';
            document.getElementById('spvAtualizacaoTexto').value = '';

            ativarAba('spvTabGeral');
            renderTimeline(data.historico || []);
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro', 'Não foi possível carregar a solicitação.');
        }
    };

    const recarregarDetalhe = async () => {
        try {
            return await api(`/back-office/solicitacoes/${detalheAtualId}`);
        } catch {
            return {};
        }
    };

    const btnRegistrarAtualizacao = document.getElementById('btnSpvRegistrarAtualizacao');
    btnRegistrarAtualizacao?.addEventListener('click', async () => {
        const campo = document.getElementById('spvAtualizacaoTexto');
        const texto = campo.value.trim();
        if (!detalheAtualId) return;
        if (!texto) {
            showModernToast('warning', 'Escreva a atualização', 'Conte o que aconteceu no processo antes de registrar.');
            campo.focus();
            return;
        }
        btnRegistrarAtualizacao.disabled = true;
        try {
            await api(`/back-office/solicitacoes/${detalheAtualId}/atualizacoes`, {
                method: 'POST',
                body: JSON.stringify({ texto }),
            });
            campo.value = '';
            showModernToast('success', 'Atualização registrada', 'O vendedor dono do contrato foi avisado.');
            const detalhe = await recarregarDetalhe();
            if (detalhe.historico) renderTimeline(detalhe.historico);
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro ao registrar', err.message || 'Tente novamente.');
        } finally {
            btnRegistrarAtualizacao.disabled = false;
        }
    });

    const btnFlagDetalhe = document.getElementById('btnSpvPrioridadeDetalhe');
    btnFlagDetalhe?.addEventListener('click', async () => {
        if (!detalheAtualId) return;
        btnFlagDetalhe.disabled = true;
        try {
            const prioridade = await alternarPrioridade(detalheAtualId);
            if (prioridade !== null) {
                atualizarFlagDetalhe(prioridade);
                const detalhe = await recarregarDetalhe();
                if (detalhe.historico) renderTimeline(detalhe.historico);
            }
        } finally {
            btnFlagDetalhe.disabled = false;
        }
    });

    document.getElementById('btnSpvExcluirDetalhe')?.addEventListener('click', async () => {
        if (!detalheAtualId || !detalheRegistro) return;
        const excluida = await excluirSolicitacao(detalheRegistro);
        if (excluida) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalheSolicitacao')).hide();
        }
    });

    // Adiar tratativa: prazo e retorno são conceitos diferentes. O prazo segue
    // intacto; data_retorno apenas controla quando o card reaparece na fila.
    const modalRetornoEl = document.getElementById('modalProgramarRetorno');
    const inputRetorno = document.getElementById('spvDataRetorno');
    const previewRetorno = document.getElementById('spvRetornoPreview');
    let retornoProgramado = false;

    const dataLocalIso = (data) => {
        const ano = data.getFullYear();
        const mes = String(data.getMonth() + 1).padStart(2, '0');
        const dia = String(data.getDate()).padStart(2, '0');
        return `${ano}-${mes}-${dia}`;
    };

    const formatarDataLonga = (iso) => {
        if (!iso) return 'Escolha uma data futura.';
        const [ano, mes, dia] = iso.split('-').map(Number);
        return new Intl.DateTimeFormat('pt-BR', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })
            .format(new Date(ano, mes - 1, dia));
    };

    const atualizarPreviewRetorno = () => {
        previewRetorno.textContent = inputRetorno.value
            ? `O card voltará à fila em ${formatarDataLonga(inputRetorno.value)}.`
            : 'Escolha uma data futura.';
    };

    document.getElementById('btnSpvAbrirRetorno')?.addEventListener('click', () => {
        if (!detalheAtualId || !detalheRegistro || detalheRegistro.status !== 'ABERTA') return;
        retornoProgramado = false;
        const amanha = new Date();
        amanha.setDate(amanha.getDate() + 1);
        inputRetorno.min = dataLocalIso(amanha);
        inputRetorno.value = detalheRegistro.data_retorno_iso || '';
        document.getElementById('spvRetornoAtual').hidden = !detalheRegistro.adiado;
        document.getElementById('spvRetornoAtual').textContent = detalheRegistro.adiado
            ? `Este card está programado para voltar em ${detalheRegistro.data_retorno}.`
            : '';
        document.getElementById('btnSpvRetornarAgora').hidden = !detalheRegistro.adiado;
        atualizarPreviewRetorno();
        bootstrap.Modal.getInstance(document.getElementById('modalDetalheSolicitacao'))?.hide();
        bootstrap.Modal.getOrCreateInstance(modalRetornoEl).show();
    });

    inputRetorno?.addEventListener('change', atualizarPreviewRetorno);
    document.querySelectorAll('[data-retorno-dias]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const data = new Date();
            data.setDate(data.getDate() + parseInt(btn.dataset.retornoDias, 10));
            inputRetorno.value = dataLocalIso(data);
            atualizarPreviewRetorno();
        });
    });

    const salvarRetorno = async (dataRetorno) => {
        const btn = document.getElementById('btnSpvConfirmarRetorno');
        btn.disabled = true;
        try {
            const body = await api(`/back-office/solicitacoes/${detalheAtualId}/retorno`, {
                method: 'POST',
                body: JSON.stringify({ data_retorno: dataRetorno }),
            });
            retornoProgramado = true;
            bootstrap.Modal.getInstance(modalRetornoEl)?.hide();
            carregarDados();
            showModernToast(
                'success',
                dataRetorno ? 'Tratativa adiada' : 'Card devolvido à fila',
                dataRetorno ? `O card voltará automaticamente em ${body.data_retorno}.` : 'O card já está disponível nas tratativas abertas.',
            );
        } catch (err) {
            showModernToast('error', 'Não foi possível programar', err.message || 'Confira a data e tente novamente.');
        } finally {
            btn.disabled = false;
        }
    };

    document.getElementById('btnSpvConfirmarRetorno')?.addEventListener('click', () => {
        if (!inputRetorno.value) {
            showModernToast('warning', 'Escolha uma data', 'Informe quando o card deve voltar para a fila.');
            inputRetorno.focus();
            return;
        }
        salvarRetorno(inputRetorno.value);
    });
    document.getElementById('btnSpvRetornarAgora')?.addEventListener('click', () => salvarRetorno(null));
    modalRetornoEl?.addEventListener('hidden.bs.modal', () => {
        if (!retornoProgramado && detalheAtualId) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalheSolicitacao')).show();
        }
    });

    const btnMover = document.getElementById('btnSpvMoverEtapa');
    btnMover?.addEventListener('click', async () => {
        const novaEtapa = parseInt(document.getElementById('spvDetalheEtapaSelect').value, 10);
        if (!detalheAtualId || novaEtapa === etapaModalAtual) {
            showModernToast('info', 'Sem alteração', 'A solicitação já está nesta etapa.');
            return;
        }
        btnMover.disabled = true;
        try {
            await api(`/back-office/solicitacoes/${detalheAtualId}/mover`, {
                method: 'POST',
                body: JSON.stringify({ etapa_id: novaEtapa }),
            });
            aplicarEtapaNaModal(detalheTipo, novaEtapa);
            const detalhe = await recarregarDetalhe();
            if (detalhe.historico) renderTimeline(detalhe.historico);
            carregarDados();
            showModernToast('success', 'Solicitação movida', 'Etapa atualizada.');
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro ao mover', err.message || 'Tente novamente.');
        } finally {
            btnMover.disabled = false;
        }
    });

    document.getElementById('btnSpvSalvarDetalhe')?.addEventListener('click', async () => {
        if (!detalheAtualId) return;
        try {
            await api(`/back-office/solicitacoes/${detalheAtualId}`, {
                method: 'PATCH',
                body: JSON.stringify({
                    titulo: document.getElementById('spvDetalheTituloInput').value || null,
                    data_limite: document.getElementById('spvDetalhePrazo').value || null,
                    responsavel_id: document.getElementById('spvDetalheResponsavel').value
                        ? parseInt(document.getElementById('spvDetalheResponsavel').value, 10)
                        : null,
                    descricao: document.getElementById('spvDetalheDescricao').value || null,
                }),
            });
            showModernToast('success', 'Salvo', 'Alterações registradas.');
            const detalhe = await recarregarDetalhe();
            if (detalhe.historico) renderTimeline(detalhe.historico);
            carregarDados();
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro', err.message || 'Não foi possível salvar.');
        }
    });

    // =========================================================================
    // Nova solicitação
    // =========================================================================
    const modalNovoEl = document.getElementById('modalNovaSolicitacao');
    let contratoSelecionado = null;

    const limparContrato = () => {
        contratoSelecionado = null;
        document.getElementById('spvVendaId').value = '';
        document.getElementById('spvContratoResumo').hidden = true;
        const busca = document.getElementById('spvBuscaContrato');
        busca.value = '';
        busca.parentElement.hidden = false;
    };

    const resumoItem = (label, value) => value
        ? `<div class="spv-info-item"><span class="spv-info-label">${escapar(label)}</span><span class="spv-info-value">${escapar(value)}</span></div>`
        : '';

    const selecionarContrato = (c) => {
        contratoSelecionado = c;
        document.getElementById('spvVendaId').value = c.id;
        document.getElementById('spvResumoNome').textContent = c.nome_contrato || `Contrato #${c.id}`;
        document.getElementById('spvResumoDocs').textContent =
            [formatarDoc(c.cpf_cnpj), c.numero_proposta ? `Proposta ${c.numero_proposta}` : null]
                .filter(Boolean).join(' · ');

        // Visão rápida do que foi vendido.
        document.getElementById('spvResumoGrid').innerHTML =
            resumoItem('Status', c.status)
            + resumoItem('Operadora', c.operadora)
            + resumoItem('Plano', c.nome_plano)
            + resumoItem('Valor mensal', c.valor_contrato)
            + resumoItem('Vidas', c.vidas)
            + resumoItem('Vigência', c.data_vigencia)
            + resumoItem('Implantação', c.data_implantacao)
            + resumoItem('Vendedor', c.vendedor_nome)
            + resumoItem('Telefone', c.telefone)
            + resumoItem('Titulares', (c.titulares || []).join(', '));

        document.getElementById('spvContratoResumo').hidden = false;
        document.getElementById('spvBuscaContrato').parentElement.hidden = true;
    };

    document.getElementById('spvTrocarContrato')?.addEventListener('click', limparContrato);

    (function initBuscaContrato() {
        const input = document.getElementById('spvBuscaContrato');
        const box = document.getElementById('spvResultadosContrato');
        if (!input || !box) return;

        const fechar = () => { box.hidden = true; box.innerHTML = ''; };

        const render = (contratos) => {
            if (!contratos.length) {
                box.innerHTML = '<p class="spv-empty spv-empty-busca">Nenhum contrato encontrado.</p>';
                box.hidden = false;
                return;
            }
            box.innerHTML = contratos.map((c, i) => `
                <div class="spv-contrato-item" data-idx="${i}">
                    <strong>${escapar(c.nome_contrato || `Contrato #${c.id}`)}</strong>
                    <small>${escapar(formatarDoc(c.cpf_cnpj))}${c.operadora ? ` · ${escapar(c.operadora)}` : ''}${c.nome_plano ? ` · ${escapar(c.nome_plano)}` : ''}${c.valor_contrato ? ` · ${escapar(c.valor_contrato)}` : ''}</small>
                </div>
            `).join('');
            box.hidden = false;
            box.querySelectorAll('.spv-contrato-item').forEach((el) => {
                el.addEventListener('click', () => {
                    fechar();
                    selecionarContrato(contratos[parseInt(el.dataset.idx, 10)]);
                });
            });
        };

        let seq = 0;
        const buscar = () => {
            const q = input.value.trim();
            if (q.length < 2) { fechar(); return; }
            const atual = ++seq;
            fetch(`/back-office/solicitacoes/buscar-contratos?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } })
                .then((r) => r.json())
                .then((res) => { if (atual === seq) render(res.contratos || []); })
                .catch(() => showModernToast('error', 'Erro', 'Falha ao buscar contratos.'));
        };

        const debounce = (() => {
            let t;
            return () => { clearTimeout(t); t = setTimeout(buscar, 300); };
        })();

        input.addEventListener('input', debounce);
        input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); buscar(); } });
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.spv-busca-contrato')) fechar();
        });
    })();

    document.getElementById('btnNovaSolicitacao')?.addEventListener('click', () => {
        document.getElementById('formNovaSolicitacao').reset();
        limparContrato();
        bootstrap.Modal.getOrCreateInstance(modalNovoEl).show();
    });

    const btnConfirmar = document.getElementById('btnConfirmarNovaSolicitacao');
    btnConfirmar?.addEventListener('click', async () => {
        if (!contratoSelecionado) {
            showModernToast('warning', 'Contrato obrigatório', 'Busque e selecione o contrato do cliente.');
            return;
        }
        const formEl = document.getElementById('formNovaSolicitacao');
        if (!formEl.reportValidity()) return;

        const payload = {
            venda_id: contratoSelecionado.id,
            tipo: document.getElementById('spvNovoTipo').value,
            titulo: document.getElementById('spvNovoTitulo').value || null,
            data_limite: document.getElementById('spvNovoPrazo').value || null,
            responsavel_id: document.getElementById('spvNovoResponsavel').value
                ? parseInt(document.getElementById('spvNovoResponsavel').value, 10)
                : null,
            descricao: document.getElementById('spvNovaDescricao').value || null,
        };

        btnConfirmar.disabled = true;
        try {
            await api('/back-office/solicitacoes', { method: 'POST', body: JSON.stringify(payload) });
            showModernToast('success', 'Solicitação criada', 'A demanda entrou na fila do pós-venda.');
            bootstrap.Modal.getOrCreateInstance(modalNovoEl).hide();
            carregarDados();
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro ao criar', err.message || 'Tente novamente.');
        } finally {
            btnConfirmar.disabled = false;
        }
    });

    // =========================================================================
    // Gerenciar etapas
    // =========================================================================
    const modalEtapasEl = document.getElementById('modalGerenciarEtapas');
    let etapasSortable = null;

    const tipoEtapasAtual = () => document.getElementById('spvEtapasTipo')?.value || kanbanTipo;

    const renderListaEtapas = () => {
        const lista = document.getElementById('spvEtapasLista');
        if (!lista) return;
        const tipo = tipoEtapasAtual();

        lista.innerHTML = etapasDoTipo(tipo).map((e) => `
            <div class="spv-etapa-item" data-id="${e.id}">
                <span class="spv-etapa-grip" title="Arraste para reordenar">${SVG.grip}</span>
                <input type="color" class="spv-etapa-cor" value="${e.cor || COR_FALLBACK}" title="Cor da etapa">
                <input type="text" class="spv-etapa-nome pv-form-input" value="${escapar(e.nome)}" maxlength="60">
                <select class="spv-etapa-natureza pv-form-input">
                    ${Object.entries(naturezas).map(([v, l]) => `<option value="${v}" ${v === e.natureza ? 'selected' : ''}>${escapar(l)}</option>`).join('')}
                </select>
                <button type="button" class="spv-etapa-del" title="Excluir etapa">${SVG.lixeira}</button>
            </div>
        `).join('');

        lista.querySelectorAll('.spv-etapa-item').forEach((item) => {
            const id = parseInt(item.dataset.id, 10);

            const salvarCampo = async (payload) => {
                try {
                    await api(`/back-office/solicitacoes/etapas/${id}`, { method: 'PUT', body: JSON.stringify(payload) });
                    await carregarDados();
                    renderListaEtapas();
                } catch (err) {
                    showModernToast('error', 'Erro', err.message || 'Não foi possível salvar a etapa.');
                    renderListaEtapas();
                }
            };

            item.querySelector('.spv-etapa-nome').addEventListener('change', (e) => {
                const nome = e.target.value.trim();
                if (!nome) { renderListaEtapas(); return; }
                salvarCampo({ nome });
            });
            item.querySelector('.spv-etapa-cor').addEventListener('change', (e) => salvarCampo({ cor: e.target.value }));
            item.querySelector('.spv-etapa-natureza').addEventListener('change', (e) => salvarCampo({ natureza: e.target.value }));

            item.querySelector('.spv-etapa-del').addEventListener('click', async () => {
                const result = await Swal.fire({
                    title: 'Excluir etapa?',
                    text: 'Só é possível excluir etapas sem solicitações.',
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonText: 'Excluir',
                    cancelButtonText: 'Voltar',
                    buttonsStyling: false,
                    customClass: { confirmButton: 'pv-btn spv-btn-danger-confirm', cancelButton: 'pv-btn pv-btn-ghost' },
                });
                if (!result.isConfirmed) return;
                try {
                    await api(`/back-office/solicitacoes/etapas/${id}`, { method: 'DELETE' });
                    showModernToast('success', 'Etapa excluída', 'O fluxo foi atualizado.');
                    await carregarDados();
                    renderListaEtapas();
                } catch (err) {
                    showModernToast('error', 'Não foi possível excluir', err.message || 'Tente novamente.');
                }
            });
        });

        if (etapasSortable) etapasSortable.destroy();
        if (typeof Sortable !== 'undefined') {
            etapasSortable = new Sortable(lista, {
                animation: 160,
                handle: '.spv-etapa-grip',
                onEnd: async () => {
                    const ordem = Array.from(lista.querySelectorAll('.spv-etapa-item')).map((el) => parseInt(el.dataset.id, 10));
                    try {
                        await api('/back-office/solicitacoes/etapas/reordenar', {
                            method: 'POST',
                            body: JSON.stringify({ tipo: tipoEtapasAtual(), ordem }),
                        });
                        await carregarDados();
                    } catch (err) {
                        showModernToast('error', 'Erro ao reordenar', err.message || 'Tente novamente.');
                        renderListaEtapas();
                    }
                },
            });
        }
    };

    document.getElementById('spvEtapasTipo')?.addEventListener('change', renderListaEtapas);

    document.getElementById('btnGerenciarEtapas')?.addEventListener('click', () => {
        const sel = document.getElementById('spvEtapasTipo');
        if (sel) sel.value = kanbanTipo;
        renderListaEtapas();
        bootstrap.Modal.getOrCreateInstance(modalEtapasEl).show();
    });

    document.getElementById('btnSpvAddEtapa')?.addEventListener('click', async () => {
        const input = document.getElementById('spvNovaEtapaNome');
        const nome = input.value.trim();
        if (!nome) {
            showModernToast('warning', 'Nome obrigatório', 'Dê um nome para a nova etapa.');
            return;
        }
        try {
            await api('/back-office/solicitacoes/etapas', {
                method: 'POST',
                body: JSON.stringify({ tipo: tipoEtapasAtual(), nome }),
            });
            input.value = '';
            showModernToast('success', 'Etapa criada', 'Ela entrou no fim do fluxo.');
            await carregarDados();
            renderListaEtapas();
        } catch (err) {
            showModernToast('error', 'Erro ao criar etapa', err.message || 'Tente novamente.');
        }
    });

    // =========================================================================
    // Init
    // =========================================================================
    try {
        const salvo = localStorage.getItem(VIEW_KEY);
        if (salvo === 'kanban') trocarView('kanban');
    } catch { /* storage indisponível */ }

    carregarDados();
})();
