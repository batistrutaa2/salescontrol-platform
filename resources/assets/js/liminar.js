'use strict';

(function () {
    const cfg = window.limConfig || {};
    const isAdvogada = !!cfg.isAdvogada;
    const colunas = cfg.colunas || [];
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // =========================================================================
    // Helpers
    // =========================================================================
    const escapar = (txt) => {
        const div = document.createElement('div');
        div.textContent = txt == null ? '' : String(txt);
        return div.innerHTML;
    };

    const formatarBytes = (bytes) => {
        if (!bytes) return '';
        const u = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return `${(bytes / Math.pow(1024, i)).toFixed(i ? 1 : 0)} ${u[i]}`;
    };

    const gradienteDaFase = (fase) => {
        const col = colunas.find((c) => c.id === fase);
        return col ? col.gradiente : { start: '#94A3B8', end: '#CBD5E1' };
    };

    const labelDaFase = (fase) => {
        const col = colunas.find((c) => c.id === fase);
        return col ? col.label : fase;
    };

    const SVG = {
        toastSuccess: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`,
        toastError: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
        toastWarning: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
        toastInfo: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`,
        toastClose: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`,
        download: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>`,
        remove: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>`,
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

    // =========================================================================
    // Kanban — render
    // =========================================================================
    const cardTemplate = (item) => {
        const grad = gradienteDaFase(item.fase);
        const el = document.createElement('div');
        el.className = 'lim-card';
        el.dataset.id = item.id;
        el.style.setProperty('--lim-card-status-color', grad.start);
        el.innerHTML = `
            <div class="lim-card-header">
                <span class="lim-card-contrato">${escapar(item.nome_contrato || '—')}</span>
                <span class="lim-card-tipo">${escapar(item.beneficiario_tipo)}</span>
            </div>
            <div class="lim-card-beneficiario">${escapar(item.beneficiario_nome)}</div>
            ${item.nome_empresa ? `<div class="lim-card-empresa">${escapar(item.nome_empresa)}</div>` : ''}
            <div class="lim-card-meta">
                ${item.protocolo ? `<span class="lim-card-protocolo">Protocolo: ${escapar(item.protocolo)}</span>` : ''}
            </div>
            <div class="lim-card-footer">
                <span title="Corretor">${escapar(item.corretor_nome)}</span>
                <span class="lim-card-data">${escapar(item.data_criacao)}</span>
            </div>
        `;
        el.addEventListener('click', () => abrirDetalhes(item.id));
        return el;
    };

    const renderizar = (dados) => {
        document.querySelectorAll('.lim-kanban-column-body').forEach((body) => {
            const fase = body.dataset.column;
            body.innerHTML = '';
            (dados[fase] || []).forEach((item) => body.appendChild(cardTemplate(item)));
        });
        atualizarContadores();
    };

    const atualizarContadores = () => {
        document.querySelectorAll('.lim-kanban-column-body').forEach((body) => {
            const fase = body.dataset.column;
            const el = document.querySelector(`[data-count="${fase}"]`);
            if (el) el.textContent = body.children.length;
        });
        atualizarDashboard();
    };

    // Termômetro: nº por fase, total e "em aberto" (tudo menos a fase concluída).
    const FASE_CONCLUIDA = 'LIMINAR_CONCEDIDA';
    const atualizarDashboard = () => {
        let total = 0;
        let concluidos = 0;
        const porFase = {};
        document.querySelectorAll('.lim-kanban-column-body').forEach((body) => {
            const fase = body.dataset.column;
            const n = body.children.length;
            porFase[fase] = n;
            total += n;
            if (fase === FASE_CONCLUIDA) concluidos += n;
        });
        const emAberto = total - concluidos;

        const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        setText('dashEmAberto', emAberto);
        setText('dashTotal', total);

        // Largura proporcional de cada segmento + contagem na legenda.
        document.querySelectorAll('.lim-dash-seg').forEach((seg) => {
            const fase = seg.dataset.seg;
            const n = porFase[fase] || 0;
            seg.style.flexGrow = n;
            seg.style.opacity = n ? '1' : '0';
        });
        document.querySelectorAll('[data-legcount]').forEach((el) => {
            el.textContent = porFase[el.dataset.legcount] || 0;
        });
        const bar = document.getElementById('dashBar');
        if (bar) bar.classList.toggle('is-empty', total === 0);
    };

    const carregarDados = async () => {
        try {
            const resp = await fetch('/back-office/liminar/dados', { headers: { Accept: 'application/json' } });
            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            const body = await resp.json();
            renderizar(body.colunas || {});
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro', 'Não foi possível carregar os processos.');
        }
    };

    // =========================================================================
    // Kanban — mover
    // =========================================================================
    const mover = async (id, novaFase) => {
        try {
            const resp = await fetch(`/back-office/liminar/${id}/mover`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({ fase: novaFase }),
            });
            if (!resp.ok) {
                const err = await resp.json().catch(() => ({}));
                throw new Error(err.message || `Falha ao mover (HTTP ${resp.status})`);
            }
            atualizarContadores();
            showModernToast('success', 'Processo movido', `Fase: ${labelDaFase(novaFase)}.`);
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro ao mover', err.message || 'Tente novamente.');
            carregarDados();
        }
    };

    const initDragDrop = () => {
        if (typeof Sortable === 'undefined') {
            console.error('SortableJS não carregado');
            return;
        }
        document.querySelectorAll('.lim-kanban-column-body').forEach((el) => {
            new Sortable(el, {
                group: 'lim-liminares',
                animation: 160,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: (evt) => {
                    document.querySelectorAll('.lim-kanban-column-body').forEach((b) => b.classList.remove('drag-over'));
                    if (evt.from === evt.to) return;
                    const id = parseInt(evt.item.dataset.id, 10);
                    const novaFase = evt.to.dataset.column;
                    evt.item.style.setProperty('--lim-card-status-color', gradienteDaFase(novaFase).start);
                    mover(id, novaFase);
                },
                onMove: (evt) => {
                    document.querySelectorAll('.lim-kanban-column-body').forEach((b) => b.classList.remove('drag-over'));
                    if (evt.to) evt.to.classList.add('drag-over');
                },
            });
        });
    };

    // =========================================================================
    // Modal de detalhes
    // =========================================================================
    let liminarAtualId = null;
    let faseModalAtual = null;

    const FASE_SHORT = {
        CANCELAMENTO_ABERTO: 'Aberto',
        AGUARDANDO_ASSINATURA: 'Assinatura',
        AGUARDANDO_RETORNO_OPERADORA: 'Retorno operadora',
        LIMINAR_CONCEDIDA: 'Concedida',
    };

    const stepCheck = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`;

    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val;
    };

    const formatarCnpj = (valor) => {
        const v = String(valor || '').replace(/\D/g, '');
        if (v.length === 14) return v.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
        if (v.length === 11) return v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        return valor || '—';
    };

    // --- montagem das abas ---
    const infoItem = (label, value) =>
        `<div class="lim-info-item"><span class="lim-info-label">${escapar(label)}</span><span class="lim-info-value">${escapar(value ?? '—')}</span></div>`;
    const infoGroup = (titulo, itens) =>
        `<div class="lim-info-group"><div class="lim-group-title">${escapar(titulo)}</div><div class="lim-info-grid">${itens}</div></div>`;
    const dateCard = (label, value) =>
        `<div class="lim-date-card"><span class="lim-date-label">${escapar(label)}</span><span class="lim-date-value">${escapar(value ?? '—')}</span></div>`;
    const dateGroup = (titulo, cards) =>
        `<div class="lim-date-group"><div class="lim-group-title">${escapar(titulo)}</div><div class="lim-date-cards">${cards}</div></div>`;

    const renderStepper = (faseAtual) => {
        const cont = document.getElementById('detalheStepper');
        if (!cont) return;
        const idx = colunas.findIndex((c) => c.id === faseAtual);
        cont.innerHTML = colunas.map((c, i) => {
            const estado = i < idx ? 'done' : (i === idx ? 'active' : 'todo');
            const vars = `--s:${c.gradiente.start};--e:${c.gradiente.end}`;
            const marker = estado === 'done' ? stepCheck : (i + 1);
            return `<li class="lim-step is-${estado}" style="${vars}">
                <span class="lim-step-marker">${marker}</span>
                <span class="lim-step-label">${escapar(FASE_SHORT[c.id] || c.label)}</span>
            </li>`;
        }).join('');
    };

    const montarVisaoGeral = (l, beneficiario) => {
        const tipo = l.beneficiario_tipo === 'TITULAR' ? 'Titular' : 'Dependente';
        document.getElementById('tabGeral').innerHTML =
            infoGroup('Beneficiário',
                infoItem('Nome', beneficiario?.nome) + infoItem('Tipo', tipo)) +
            infoGroup('Contrato',
                infoItem('Contrato', l.nome_contrato)
                + infoItem('Vendedor', l.venda?.user?.name)
                + infoItem('Responsável', l.responsavel?.name)) +
            infoGroup('Empresa contratante',
                infoItem('Razão social', l.nome_empresa)
                + infoItem('CNPJ', formatarCnpj(l.cnpj))
                + infoItem('Protocolo de cancelamento', l.protocolo_cancelamento)) +
            infoGroup('Procuração',
                infoItem('E-mail de envio', l.email_procuracao));
    };

    const montarDatas = (l) => {
        document.getElementById('tabDatas').innerHTML =
            dateGroup('Plano anterior',
                dateCard('Contratação', l.data_contratacao) + dateCard('Fim do plano', l.data_fim_plano)) +
            dateGroup('Cancelamento',
                dateCard('Solicitação', l.data_solicitacao_cancelamento) + dateCard('Último pgto. do boleto', l.data_ultimo_pagamento_boleto)) +
            dateGroup('Cobertura do comprovante',
                dateCard('Início', l.cobertura_comprovante_inicio) + dateCard('Fim', l.cobertura_comprovante_fim)) +
            dateGroup('Boletos inelegíveis',
                dateCard('1º vencimento', l.data_vencimento_boleto_1) + dateCard('2º vencimento', l.data_vencimento_boleto_2));
    };

    const ativarAba = (alvo) => {
        document.querySelectorAll('#modalDetalhes .lim-tab').forEach((t) => t.classList.toggle('is-active', t.dataset.tab === alvo));
        document.querySelectorAll('#modalDetalhes .lim-tabpane').forEach((p) => p.classList.toggle('is-active', p.id === alvo));
    };
    document.querySelectorAll('#modalDetalhes .lim-tab').forEach((t) => {
        t.addEventListener('click', () => ativarAba(t.dataset.tab));
    });

    const renderDocumentos = (docs) => {
        const lista = document.getElementById('listaDocumentos');
        const badge = document.getElementById('tabDocsCount');
        if (badge) badge.textContent = docs.length;
        if (!docs.length) {
            lista.innerHTML = '<p class="lim-empty">Nenhum documento anexado.</p>';
            return;
        }
        lista.innerHTML = docs.map((d) => `
            <div class="lim-doc-item">
                <div class="lim-doc-info">
                    <span class="lim-doc-tipo">${escapar(d.tipo_documento_label)}</span>
                    <span class="lim-doc-nome">${escapar(d.nome_original)}</span>
                </div>
                <div class="lim-doc-actions">
                    <button class="doc-btn btn-download" data-id="${d.id}" title="Baixar">${SVG.download}</button>
                    ${isAdvogada ? '' : `<button class="doc-btn btn-remove" data-id="${d.id}" title="Remover">${SVG.remove}</button>`}
                </div>
            </div>
        `).join('');

        lista.querySelectorAll('.btn-download').forEach((btn) => {
            btn.addEventListener('click', () => {
                window.location.href = `/back-office/liminar/${liminarAtualId}/documentos/${btn.dataset.id}/download`;
            });
        });
        if (!isAdvogada) {
            lista.querySelectorAll('.btn-remove').forEach((btn) => {
                btn.addEventListener('click', () => removerDocumento(btn.dataset.id));
            });
        }
    };

    const renderHistorico = (hist) => {
        const tl = document.getElementById('timelineHistorico');
        if (!hist.length) {
            tl.innerHTML = '<p class="lim-empty">Sem alterações registradas.</p>';
            return;
        }
        tl.innerHTML = hist.map((h) => `
            <div class="lim-timeline-item">
                <span class="timeline-dot"></span>
                <div class="lim-timeline-content">
                    <div class="lim-timeline-text">
                        ${escapar(h.valor_anterior ? `${h.valor_anterior} → ${h.valor_novo}` : h.valor_novo)}
                    </div>
                    ${h.observacao ? `<div class="lim-timeline-obs">${escapar(h.observacao)}</div>` : ''}
                    <div class="lim-timeline-meta">${escapar(h.usuario_nome ?? 'Sistema')} · ${escapar(h.created_at)}</div>
                </div>
            </div>
        `).join('');
    };

    const abrirDetalhes = async (id) => {
        liminarAtualId = id;
        const hidden = document.getElementById('detalheId');
        if (hidden) hidden.value = id;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalhes'));
        modal.show();

        try {
            const resp = await fetch(`/back-office/liminar/${id}`, { headers: { Accept: 'application/json' } });
            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            const data = await resp.json();
            const l = data.liminar;

            // Cabeçalho status-aware: gradiente e fase espelham a coluna do kanban.
            const header = document.getElementById('detalheHeader');
            const grad = gradienteDaFase(l.fase);
            header.style.setProperty('--lim-fase-start', grad.start);
            header.style.setProperty('--lim-fase-end', grad.end);
            document.getElementById('detalheFasePill').textContent = data.fase_label;
            document.getElementById('detalheModalTitulo').textContent = l.nome_contrato || 'Processo de Liminar';
            document.getElementById('detalheModalSubtitulo').textContent = data.beneficiario?.nome ?? '—';
            renderStepper(l.fase);

            faseModalAtual = l.fase;
            const faseSelect = document.getElementById('detalheFaseSelect');
            if (faseSelect) faseSelect.value = l.fase;

            ativarAba('tabGeral');
            montarVisaoGeral(l, data.beneficiario);
            montarDatas(l);

            if (!isAdvogada) {
                setVal('detalheHonorarios', l.status_honorarios || 'PENDENTE');
                setVal('detalheRecebimento', l.status_recebimento || 'PENDENTE');
                setVal('detalheValorRecebimento', l.valor_recebimento ?? '');
                setVal('detalheObservacoes', l.observacoes ?? '');
            }

            renderDocumentos(data.documentos || []);
            renderHistorico(data.historico || []);
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro', 'Não foi possível carregar o processo.');
        }
    };

    // Atualiza o cabeçalho/stepper da modal após mover sem refazer o fetch.
    const aplicarFaseNaModal = (novaFase) => {
        faseModalAtual = novaFase;
        const grad = gradienteDaFase(novaFase);
        const header = document.getElementById('detalheHeader');
        header.style.setProperty('--lim-fase-start', grad.start);
        header.style.setProperty('--lim-fase-end', grad.end);
        document.getElementById('detalheFasePill').textContent = labelDaFase(novaFase);
        renderStepper(novaFase);
        const faseSelect = document.getElementById('detalheFaseSelect');
        if (faseSelect) faseSelect.value = novaFase;
    };

    // Move o processo pela própria modal (disponível para backoffice e advogada).
    const btnMoverFase = document.getElementById('btnMoverFase');
    btnMoverFase?.addEventListener('click', async () => {
        const novaFase = document.getElementById('detalheFaseSelect').value;
        if (!liminarAtualId || novaFase === faseModalAtual) {
            showModernToast('info', 'Sem alteração', 'O processo já está nesta fase.');
            return;
        }
        btnMoverFase.disabled = true;
        try {
            const resp = await fetch(`/back-office/liminar/${liminarAtualId}/mover`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({ fase: novaFase }),
            });
            if (!resp.ok) {
                const err = await resp.json().catch(() => ({}));
                throw new Error(err.message || `HTTP ${resp.status}`);
            }
            aplicarFaseNaModal(novaFase);
            renderHistorico(await recarregarHistorico());
            carregarDados(); // atualiza o board atrás da modal
            showModernToast('success', 'Processo movido', `Fase: ${labelDaFase(novaFase)}.`);
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro ao mover', err.message || 'Tente novamente.');
        } finally {
            btnMoverFase.disabled = false;
        }
    });

    // Recarrega só o histórico do processo aberto (para refletir a movimentação).
    const recarregarHistorico = async () => {
        try {
            const resp = await fetch(`/back-office/liminar/${liminarAtualId}`, { headers: { Accept: 'application/json' } });
            if (!resp.ok) return [];
            const data = await resp.json();
            return data.historico || [];
        } catch {
            return [];
        }
    };

    // =========================================================================
    // Backoffice — salvar detalhes / documentos
    // =========================================================================
    const removerDocumento = async (docId) => {
        const r = await Swal.fire({
            title: 'Remover documento?', text: 'Essa ação não pode ser desfeita.', icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Remover', cancelButtonText: 'Cancelar',
        });
        if (!r.isConfirmed) return;
        try {
            const resp = await fetch(`/back-office/liminar/${liminarAtualId}/documentos/${docId}`, {
                method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            });
            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            showModernToast('success', 'Removido', 'Documento excluído.');
            abrirDetalhes(liminarAtualId);
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro', 'Falha ao remover o documento.');
        }
    };

    if (!isAdvogada) {
        document.getElementById('btnSalvarDetalhes')?.addEventListener('click', async () => {
            const payload = {
                status_honorarios: document.getElementById('detalheHonorarios').value,
                status_recebimento: document.getElementById('detalheRecebimento').value,
                valor_recebimento: document.getElementById('detalheValorRecebimento').value || null,
                observacoes: document.getElementById('detalheObservacoes').value || null,
            };
            try {
                const resp = await fetch(`/back-office/liminar/${liminarAtualId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                    body: JSON.stringify(payload),
                });
                if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
                showModernToast('success', 'Salvo', 'Alterações registradas.');
                carregarDados();
            } catch (err) {
                console.error(err);
                showModernToast('error', 'Erro', 'Não foi possível salvar.');
            }
        });

        const dropZone = document.getElementById('fileDropZone');
        const inputArquivo = document.getElementById('inputArquivo');
        const enviarDocumento = async (file) => {
            const tipo = document.getElementById('uploadTipoDoc').value;
            if (!tipo) {
                showModernToast('warning', 'Atenção', 'Selecione o tipo do documento.');
                return;
            }
            const form = new FormData();
            form.append('tipo_documento', tipo);
            form.append('arquivo', file);
            form.append('_token', csrf);
            try {
                const resp = await fetch(`/back-office/liminar/${liminarAtualId}/documentos`, { method: 'POST', body: form });
                if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
                showModernToast('success', 'Documento enviado', 'Anexo registrado com sucesso.');
                abrirDetalhes(liminarAtualId);
            } catch (err) {
                console.error(err);
                showModernToast('error', 'Erro', 'Falha ao enviar o documento.');
            }
        };
        inputArquivo?.addEventListener('change', (e) => { if (e.target.files[0]) enviarDocumento(e.target.files[0]); });
        if (dropZone) {
            ['dragover', 'dragenter'].forEach((ev) => dropZone.addEventListener(ev, (e) => { e.preventDefault(); dropZone.classList.add('dragover'); }));
            ['dragleave', 'drop'].forEach((ev) => dropZone.addEventListener(ev, (e) => { e.preventDefault(); dropZone.classList.remove('dragover'); }));
            dropZone.addEventListener('drop', (e) => { if (e.dataTransfer.files[0]) enviarDocumento(e.dataTransfer.files[0]); });
        }
    }

    // =========================================================================
    // Novo Processo (somente backoffice)
    // =========================================================================
    if (!isAdvogada) {
        let vendaSelecionada = null;
        let beneficiarioSelecionado = null;

        const modalNovoEl = document.getElementById('modalNovoProcesso');
        const subtitulo = document.getElementById('novoSubtitulo');
        const btnVoltar = document.getElementById('btnVoltarPasso');
        const btnConfirmar = document.getElementById('btnConfirmarNovo');

        // --- Slots de upload (estado anexado/pendente) ---
        const slots = modalNovoEl.querySelectorAll('[data-slot]');

        const preencherSlot = (slot) => {
            const input = slot.querySelector('input[type=file]');
            const file = input.files[0];
            if (!file) { limparSlot(slot); return; }
            slot.classList.add('is-filled');
            slot.querySelector('.lim-slot-filename').textContent = file.name;
            slot.querySelector('.lim-slot-size').textContent = formatarBytes(file.size);
        };

        const limparSlot = (slot) => {
            const input = slot.querySelector('input[type=file]');
            input.value = '';
            slot.classList.remove('is-filled', 'is-drag');
            slot.querySelector('.lim-slot-filename').textContent = '';
            slot.querySelector('.lim-slot-size').textContent = '';
        };

        slots.forEach((slot) => {
            const input = slot.querySelector('input[type=file]');
            input.addEventListener('change', () => preencherSlot(slot));
            slot.querySelector('.lim-slot-remove').addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                limparSlot(slot);
            });
            ['dragover', 'dragenter'].forEach((ev) => slot.addEventListener(ev, (e) => { e.preventDefault(); slot.classList.add('is-drag'); }));
            ['dragleave', 'dragend'].forEach((ev) => slot.addEventListener(ev, () => slot.classList.remove('is-drag')));
            slot.addEventListener('drop', (e) => {
                e.preventDefault();
                slot.classList.remove('is-drag');
                if (e.dataTransfer.files.length) {
                    const dt = new DataTransfer();
                    dt.items.add(e.dataTransfer.files[0]);
                    input.files = dt.files;
                    preencherSlot(slot);
                }
            });
        });

        const mostrarPasso = (n) => {
            ['passo1', 'passo2', 'passo3'].forEach((p, i) => {
                document.getElementById(p).style.display = (i + 1 === n) ? '' : 'none';
            });
            btnVoltar.style.display = n > 1 ? '' : 'none';
            btnConfirmar.style.display = n === 3 ? '' : 'none';
            subtitulo.textContent = ['Selecione o contrato', 'Selecione o beneficiário', 'Preencha os dados da procuração'][n - 1];
            btnVoltar.dataset.passo = n;
        };

        const resetNovo = () => {
            vendaSelecionada = null;
            beneficiarioSelecionado = null;
            document.getElementById('buscaContrato').value = '';
            document.getElementById('resultadosContratos').innerHTML = '';
            document.getElementById('listaBeneficiarios').innerHTML = '';
            document.getElementById('formProcuracao').reset();
            slots.forEach(limparSlot);
            mostrarPasso(1);
        };

        // Passo 2: beneficiários
        const selecionarContrato = (contrato) => {
            vendaSelecionada = contrato;
            document.getElementById('infoContratoSelecionado').innerHTML = `<strong>${escapar(contrato.nome_contrato)}</strong>`;
            fetch(`/back-office/pos-venda/liminar/beneficiarios/${contrato.id}`, { headers: { Accept: 'application/json' } })
                .then((r) => r.json())
                .then((res) => {
                    const lista = document.getElementById('listaBeneficiarios');
                    lista.innerHTML = (res.beneficiarios || []).map((b) => `
                        <label class="lim-beneficiario-item ${b.tem_liminar_ativa ? 'is-disabled' : ''}">
                            <input type="radio" name="beneficiario" value="${b.id}" data-tipo="${b.tipo}" ${b.tem_liminar_ativa ? 'disabled' : ''}>
                            <span class="lim-ben-nome">${escapar(b.nome)}</span>
                            <span class="lim-ben-tipo">${escapar(b.tipo_label)}</span>
                            ${b.tem_liminar_ativa ? '<span class="lim-ben-badge">Liminar Ativa</span>' : ''}
                        </label>
                    `).join('');
                    lista.querySelectorAll('input[name="beneficiario"]').forEach((inp) => {
                        inp.addEventListener('change', () => {
                            beneficiarioSelecionado = { id: parseInt(inp.value, 10), tipo: inp.dataset.tipo };
                            mostrarPasso(3);
                        });
                    });
                    mostrarPasso(2);
                })
                .catch(() => showModernToast('error', 'Erro', 'Falha ao carregar beneficiários.'));
        };

        // Passo 1: buscar contratos (autocomplete por nome ou CPF/CNPJ)
        const boxResultados = document.getElementById('resultadosContratos');

        const renderResultados = (contratos) => {
            if (!contratos.length) {
                boxResultados.innerHTML = '<p class="text-muted small mb-0">Nenhum contrato encontrado.</p>';
                return;
            }
            boxResultados.innerHTML = contratos.map((c) => `
                <div class="lim-contrato-item" data-id="${c.id}" data-nome="${escapar(c.nome_contrato || '')}">
                    <strong>${escapar(c.nome_contrato || `Contrato #${c.id}`)}</strong>
                    <small>${escapar(c.cpf_cnpj || '')}${c.numero_proposta ? ` · Proposta ${escapar(c.numero_proposta)}` : ''}</small>
                </div>
            `).join('');
            boxResultados.querySelectorAll('.lim-contrato-item').forEach((el) => {
                el.addEventListener('click', () => selecionarContrato({ id: parseInt(el.dataset.id, 10), nome_contrato: el.dataset.nome }));
            });
        };

        let buscaSeq = 0;
        const buscarContratos = () => {
            const q = document.getElementById('buscaContrato').value.trim();
            if (q.length < 2) {
                boxResultados.innerHTML = '<p class="text-muted small mb-0">Digite ao menos 2 caracteres…</p>';
                return;
            }
            const seq = ++buscaSeq;
            fetch(`/back-office/liminar-buscar-contratos?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } })
                .then((r) => r.json())
                .then((res) => {
                    if (seq !== buscaSeq) return; // ignora respostas fora de ordem
                    renderResultados(res.contratos || []);
                })
                .catch(() => showModernToast('error', 'Erro', 'Falha ao buscar contratos.'));
        };

        const debounce = (fn, ms) => {
            let t;
            return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
        };
        const buscarDebounced = debounce(buscarContratos, 300);

        document.getElementById('btnNovoProcesso')?.addEventListener('click', () => {
            resetNovo();
            bootstrap.Modal.getOrCreateInstance(modalNovoEl).show();
        });
        btnVoltar.addEventListener('click', () => mostrarPasso(parseInt(btnVoltar.dataset.passo, 10) - 1));
        document.getElementById('btnBuscarContrato').addEventListener('click', buscarContratos);
        document.getElementById('buscaContrato').addEventListener('input', buscarDebounced);
        document.getElementById('buscaContrato').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); buscarContratos(); } });

        // Passo 3: enviar
        btnConfirmar.addEventListener('click', async () => {
            if (!vendaSelecionada || !beneficiarioSelecionado) return;
            const formEl = document.getElementById('formProcuracao');
            if (!formEl.reportValidity()) return;

            // Datas vão em d/m/Y (formato do Flatpickr) — o backend valida e converte.
            const form = new FormData(formEl);
            form.append('venda_id', vendaSelecionada.id);
            form.append('beneficiario_tipo', beneficiarioSelecionado.tipo);
            form.append('beneficiario_id', beneficiarioSelecionado.id);
            form.append('_token', csrf);

            btnConfirmar.disabled = true;
            try {
                const resp = await fetch('/back-office/liminar', { method: 'POST', headers: { Accept: 'application/json' }, body: form });
                if (resp.status === 422) {
                    showModernToast('warning', 'Dados incompletos', 'Verifique os campos obrigatórios.');
                    return;
                }
                if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
                showModernToast('success', 'Processo aberto', 'Cancelamento via liminar registrado.');
                bootstrap.Modal.getOrCreateInstance(modalNovoEl).hide();
                carregarDados();
            } catch (err) {
                console.error(err);
                showModernToast('error', 'Erro', 'Não foi possível abrir o processo.');
            } finally {
                btnConfirmar.disabled = false;
            }
        });
    }

    // =========================================================================
    // Init
    // =========================================================================
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.flatpickr-date', { dateFormat: 'd/m/Y', locale: 'pt', allowInput: true });
    }

    initDragDrop();
    carregarDados();
})();
