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
                    <button type="button" class="toast-close" aria-label="Fechar notificação" onclick="Swal.close()">${SVG.toastClose}</button>
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
        // Sem venda atrelada, a empresa contratante é o título do card. O beneficiário
        // só aparece em processos antigos que ainda tenham esse dado.
        const titulo = item.nome_empresa || item.nome_contrato || '—';
        const temBeneficiario = item.beneficiario_nome && item.beneficiario_nome !== '—';
        el.innerHTML = `
            <div class="lim-card-header">
                <span class="lim-card-contrato">${escapar(titulo)}</span>
                ${temBeneficiario ? `<span class="lim-card-tipo">${escapar(item.beneficiario_tipo)}</span>` : ''}
            </div>
            ${temBeneficiario ? `<div class="lim-card-beneficiario">${escapar(item.beneficiario_nome)}</div>` : ''}
            <div class="lim-card-meta">
                ${item.protocolo ? `<span class="lim-card-protocolo">Protocolo: ${escapar(item.protocolo)}</span>` : ''}
            </div>
            <div class="lim-card-footer">
                <span title="Responsável do cliente">${escapar(item.responsavel_nome || '—')}</span>
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
    // Ícones do diálogo de conclusão (mesma linguagem visual do design system).
    const CONCLUSAO_SVG = {
        scale: `<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M5 7h14M7 7l-3 6a3 3 0 0 0 6 0L7 7zM17 7l-3 6a3 3 0 0 0 6 0l-3-6zM7 21h10"/></svg>`,
        doc: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>`,
        check: `<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`,
    };

    // Concluir exige o PDF da decisão. Diálogo no design system do sistema (SweetAlert2
    // estilizado + componente .lim-upload-slot), que flutua acima de modais bootstrap.
    // O campo indica claramente quando o documento foi importado e será salvo.
    // Resolve com o File; rejeita com 'cancelado' se desistir.
    const pedirPdfConclusao = async () => {
        let arquivo = null;

        const html = `
            <div class="lim-conclusao">
                <div class="lim-conclusao-head">
                    <span class="lim-conclusao-badge">${CONCLUSAO_SVG.scale}</span>
                    <div class="lim-conclusao-headtext">
                        <h3 class="lim-conclusao-title">Concluir processo</h3>
                        <p class="lim-conclusao-sub">Anexe o PDF da decisão para registrar a liminar concedida.</p>
                    </div>
                </div>
                <div class="lim-upload-slot lim-conclusao-slot" data-slot>
                    <input type="file" id="conclusaoInput" accept="application/pdf" hidden>
                    <label for="conclusaoInput" class="lim-slot-click">
                        <span class="lim-slot-icon">${CONCLUSAO_SVG.doc}</span>
                        <span class="lim-slot-body">
                            <span class="lim-slot-head">
                                <span class="lim-slot-title">Decisão da liminar</span>
                                <span class="lim-slot-badge is-req">Obrigatório</span>
                            </span>
                            <span class="lim-slot-hint">Arraste o PDF ou <u>selecione</u> · PDF até 10MB</span>
                            <span class="lim-slot-file"><span class="lim-slot-filename"></span><span class="lim-slot-size"></span></span>
                            <span class="lim-conclusao-status">${CONCLUSAO_SVG.check}<span>Documento importado — será salvo ao concluir</span></span>
                        </span>
                        <span class="lim-slot-check">${CONCLUSAO_SVG.check}</span>
                    </label>
                    <button type="button" class="lim-slot-remove" aria-label="Remover documento">${SVG.toastClose}</button>
                </div>
            </div>`;

        const result = await Swal.fire({
            html,
            width: 480,
            showCancelButton: true,
            reverseButtons: true,
            buttonsStyling: false,
            focusConfirm: false,
            confirmButtonText: `${CONCLUSAO_SVG.check}<span>Concluir processo</span>`,
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'lim-conclusao-popup',
                htmlContainer: 'lim-conclusao-htmlcontainer',
                actions: 'lim-conclusao-actions',
                confirmButton: 'pv-btn pv-btn-success',
                cancelButton: 'pv-btn pv-btn-ghost',
                validationMessage: 'lim-conclusao-validation',
            },
            showClass: { popup: 'animate__animated animate__fadeInUp animate__faster' },
            hideClass: { popup: 'animate__animated animate__fadeOutDown animate__faster' },
            didOpen: () => {
                const slot = document.querySelector('.lim-conclusao-slot');
                const input = slot.querySelector('#conclusaoInput');

                const aplicar = (f) => {
                    arquivo = f || null;
                    if (!f) { slot.classList.remove('is-filled'); return; }
                    slot.querySelector('.lim-slot-filename').textContent = f.name;
                    slot.querySelector('.lim-slot-size').textContent = formatarBytes(f.size);
                    slot.classList.add('is-filled');
                    Swal.resetValidationMessage();
                };

                input.addEventListener('change', () => aplicar(input.files[0]));
                slot.querySelector('.lim-slot-remove').addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    input.value = '';
                    aplicar(null);
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
                        aplicar(e.dataTransfer.files[0]);
                    }
                });
            },
            preConfirm: () => {
                if (!arquivo) { Swal.showValidationMessage('Anexe o PDF da decisão.'); return false; }
                const ehPdf = arquivo.type === 'application/pdf' || /\.pdf$/i.test(arquivo.name);
                if (!ehPdf) { Swal.showValidationMessage('O arquivo deve ser PDF.'); return false; }
                if (arquivo.size > 10 * 1024 * 1024) { Swal.showValidationMessage('O arquivo excede 10MB.'); return false; }
                return true;
            },
        });

        if (!result.isConfirmed || !arquivo) throw new Error('cancelado');
        return arquivo;
    };

    // Envia a mudança de fase (multipart — carrega o PDF quando concluindo).
    const enviarMover = async (id, novaFase, file = null) => {
        const form = new FormData();
        form.append('fase', novaFase);
        if (file) form.append('documento_conclusao', file);
        form.append('_token', csrf);
        const resp = await fetch(`/back-office/liminar/${id}/mover`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            body: form,
        });
        if (!resp.ok) {
            const err = await resp.json().catch(() => ({}));
            throw new Error(err.message || `Falha ao mover (HTTP ${resp.status})`);
        }
        return resp;
    };

    // Resolve o PDF quando a fase destino for a conclusão; null caso contrário.
    const arquivoConclusaoSeNecessario = async (novaFase) =>
        novaFase === FASE_CONCLUIDA ? await pedirPdfConclusao() : null;

    const mover = async (id, novaFase) => {
        try {
            const file = await arquivoConclusaoSeNecessario(novaFase);
            await enviarMover(id, novaFase, file);
            atualizarContadores();
            showModernToast('success', 'Processo movido', `Fase: ${labelDaFase(novaFase)}.`);
        } catch (err) {
            if (err.message !== 'cancelado') {
                console.error(err);
                showModernToast('error', 'Erro ao mover', err.message || 'Tente novamente.');
            }
            carregarDados(); // reverte a posição do card no board
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
        PROCURACAO_ENVIADA: 'Enviada',
        PROCURACAO_ASSINADA: 'Assinada',
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
        // Sem venda atrelada: o processo gira em torno da empresa contratante e da procuração.
        // Beneficiário/venda só aparecem em processos antigos que ainda os tenham.
        const tipo = l.beneficiario_tipo === 'TITULAR' ? 'Titular' : 'Dependente';
        const grupoBeneficiario = beneficiario?.nome
            ? infoGroup('Beneficiário', infoItem('Nome', beneficiario.nome) + infoItem('Tipo', tipo))
            : '';

        document.getElementById('tabGeral').innerHTML =
            grupoBeneficiario +
            infoGroup('Empresa contratante',
                infoItem('Razão social', l.nome_empresa)
                + infoItem('CNPJ', formatarCnpj(l.cnpj))
                + infoItem('Protocolo de cancelamento', l.protocolo_cancelamento)) +
            infoGroup('Procuração',
                infoItem('E-mail de envio', l.email_procuracao)
                + infoItem('Responsável', l.nome_responsavel_procuracao));
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
            document.getElementById('detalheModalTitulo').textContent = l.nome_empresa || l.nome_contrato || 'Processo de Liminar';
            document.getElementById('detalheModalSubtitulo').textContent =
                l.protocolo_cancelamento ? `Protocolo: ${l.protocolo_cancelamento}` : (data.beneficiario?.nome ?? '—');
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
            // Concluir exige o PDF da decisão (pode cancelar no prompt).
            const file = await arquivoConclusaoSeNecessario(novaFase);
            await enviarMover(liminarAtualId, novaFase, file);
            aplicarFaseNaModal(novaFase);
            const detalhe = await recarregarDetalhe();
            renderHistorico(detalhe.historico || []);
            renderDocumentos(detalhe.documentos || []); // mostra o PDF de conclusão anexado
            carregarDados();        // atualiza o board atrás da modal
            showModernToast('success', 'Processo movido', `Fase: ${labelDaFase(novaFase)}.`);
        } catch (err) {
            if (err.message !== 'cancelado') {
                console.error(err);
                showModernToast('error', 'Erro ao mover', err.message || 'Tente novamente.');
            }
        } finally {
            btnMoverFase.disabled = false;
        }
    });

    // Recarrega o detalhe do processo aberto (para refletir a movimentação).
    const recarregarDetalhe = async () => {
        try {
            const resp = await fetch(`/back-office/liminar/${liminarAtualId}`, { headers: { Accept: 'application/json' } });
            if (!resp.ok) return {};
            return await resp.json();
        } catch {
            return {};
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

        // Excluir processo (backoffice/admin) — confirmação antes.
        document.getElementById('btnExcluirProcesso')?.addEventListener('click', async () => {
            const r = await Swal.fire({
                title: 'Excluir este processo?',
                text: 'O processo, seus documentos e o histórico serão removidos. Esta ação não pode ser desfeita.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Excluir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#EF4444',
            });
            if (!r.isConfirmed) return;
            try {
                const resp = await fetch(`/back-office/liminar/${liminarAtualId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                });
                if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalhes')).hide();
                showModernToast('success', 'Excluído', 'Processo removido.');
                carregarDados();
            } catch (err) {
                console.error(err);
                showModernToast('error', 'Erro', 'Não foi possível excluir o processo.');
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
        const modalNovoEl = document.getElementById('modalNovoProcesso');
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

        const resetNovo = () => {
            document.getElementById('formProcuracao').reset();
            slots.forEach(limparSlot);
        };

        document.getElementById('btnNovoProcesso')?.addEventListener('click', () => {
            resetNovo();
            bootstrap.Modal.getOrCreateInstance(modalNovoEl).show();
        });

        // Abertura direta dos dados da procuração — sem venda/titular atrelados.
        btnConfirmar.addEventListener('click', async () => {
            const formEl = document.getElementById('formProcuracao');
            if (!formEl.reportValidity()) return;

            // Datas vão em d/m/Y (formato do Flatpickr) — o backend valida e converte.
            const form = new FormData(formEl);
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
    // Busca de liminares concluídas (todos os papéis) — empresa ou CNPJ/CPF.
    // Resultado abre o processo completo (com o documento de conclusão anexado).
    // =========================================================================
    (function initBuscaConcluidas() {
        const input = document.getElementById('buscaConcluida');
        const box = document.getElementById('resultadosConcluidas');
        if (!input || !box) return;

        const fecharResultados = () => { box.hidden = true; box.innerHTML = ''; };

        const renderConcluidas = (liminares) => {
            if (!liminares.length) {
                box.innerHTML = '<p class="lim-empty" style="margin:0;padding:.5rem .75rem">Nenhuma liminar concluída encontrada.</p>';
                box.hidden = false;
                return;
            }
            box.innerHTML = liminares.map((l) => `
                <div class="lim-contrato-item" data-id="${l.id}">
                    <strong>${escapar(l.nome_empresa || `Processo #${l.id}`)}</strong>
                    <small>${escapar(formatarCnpj(l.cnpj))}${l.protocolo ? ` · Protocolo ${escapar(l.protocolo)}` : ''}</small>
                </div>
            `).join('');
            box.hidden = false;
            box.querySelectorAll('.lim-contrato-item').forEach((el) => {
                el.addEventListener('click', () => {
                    fecharResultados();
                    input.value = '';
                    abrirDetalhes(parseInt(el.dataset.id, 10));
                });
            });
        };

        let seq = 0;
        const buscar = () => {
            const q = input.value.trim();
            if (q.length < 2) { fecharResultados(); return; }
            const atual = ++seq;
            fetch(`/back-office/liminar-buscar-concluidas?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } })
                .then((r) => r.json())
                .then((res) => { if (atual === seq) renderConcluidas(res.liminares || []); })
                .catch(() => showModernToast('error', 'Erro', 'Falha ao buscar liminares concluídas.'));
        };

        const debounceBusca = (() => {
            let t;
            return () => { clearTimeout(t); t = setTimeout(buscar, 300); };
        })();

        input.addEventListener('input', debounceBusca);
        input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); buscar(); } });
        // Fecha o popover ao clicar fora.
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.lim-search-concluidas')) fecharResultados();
        });
    })();

    // =========================================================================
    // Init
    // =========================================================================
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.flatpickr-date', { dateFormat: 'd/m/Y', locale: 'pt', allowInput: true });
    }

    initDragDrop();
    carregarDados();
})();
