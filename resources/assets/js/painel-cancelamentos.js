'use strict';

(function () {
    const cfg = window.pcxConfig || {};
    const colunas = cfg.colunas || [];
    const desistido = cfg.desistido || { id: 'DESISTIDO', label: 'Desistido', gradiente: { start: '#EF4444', end: '#F87171' } };
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const ETAPA_SOLICITADO = 'SOLICITADO';

    // Estado local: todos os registros do último fetch (kanban e tabela leem daqui).
    let registros = [];
    let filtroEtapaTabela = null;

    // =========================================================================
    // Helpers
    // =========================================================================
    const escapar = (txt) => {
        const div = document.createElement('div');
        div.textContent = txt == null ? '' : String(txt);
        return div.innerHTML;
    };

    const etapaInfo = (etapa) => {
        if (etapa === desistido.id) return desistido;
        return colunas.find((c) => c.id === etapa) || { id: etapa, label: etapa, gradiente: { start: '#94A3B8', end: '#CBD5E1' } };
    };

    const formatarDoc = (valor) => {
        const v = String(valor || '').replace(/\D/g, '');
        if (v.length === 14) return v.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
        if (v.length === 11) return v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        return valor || '—';
    };

    const SVG = {
        toastSuccess: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`,
        toastError: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
        toastWarning: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
        toastInfo: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`,
        toastClose: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`,
        check: `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`,
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

    // Primeira mensagem de erro de validação do Laravel (payload 422).
    const primeiroErro = (body, fallback) => {
        const errors = body?.errors;
        if (errors) {
            const chave = Object.keys(errors)[0];
            if (chave && errors[chave]?.length) return errors[chave][0];
        }
        return body?.message || fallback;
    };

    // =========================================================================
    // Carregar dados (fonte única: kanban + tabela + esteira)
    // =========================================================================
    const carregarDados = async () => {
        try {
            const resp = await fetch('/back-office/cancelamentos/dados', { headers: { Accept: 'application/json' } });
            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            const body = await resp.json();
            registros = body.registros || [];
            renderEsteira(body.kpis || {});
            renderKanban();
            renderTabela();
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro', 'Não foi possível carregar os cancelamentos.');
        }
    };

    // =========================================================================
    // Esteira (KPIs por etapa)
    // =========================================================================
    const renderEsteira = (kpis) => {
        document.querySelectorAll('[data-kpi]').forEach((el) => {
            el.textContent = kpis[el.dataset.kpi] ?? 0;
        });
    };

    // Clique num segmento: kanban → destaca a coluna; tabela → filtra pela etapa.
    document.querySelectorAll('.pcx-esteira-seg').forEach((seg) => {
        seg.addEventListener('click', () => {
            const etapa = seg.dataset.etapa;
            if (viewAtual() === 'kanban') {
                if (etapa === desistido.id) {
                    trocarView('tabela');
                    filtroEtapaTabela = etapa;
                    marcarSegAtivo(etapa);
                    renderTabela();
                    return;
                }
                const col = document.querySelector(`.pcx-kanban-column[data-status="${etapa}"]`);
                if (col) {
                    col.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                    col.classList.remove('is-flash');
                    void col.offsetWidth; // reinicia a animação
                    col.classList.add('is-flash');
                }
            } else {
                filtroEtapaTabela = filtroEtapaTabela === etapa ? null : etapa;
                marcarSegAtivo(filtroEtapaTabela);
                renderTabela();
            }
        });
    });

    const marcarSegAtivo = (etapa) => {
        document.querySelectorAll('.pcx-esteira-seg').forEach((s) => {
            s.classList.toggle('is-active', s.dataset.etapa === etapa);
        });
    };

    // =========================================================================
    // Toggle kanban / tabela
    // =========================================================================
    const VIEW_KEY = 'pcx-view';
    const viewAtual = () => document.querySelector('.pcx-view-btn.is-active')?.dataset.view || 'kanban';

    const trocarView = (view) => {
        document.querySelectorAll('.pcx-view-btn').forEach((b) => b.classList.toggle('is-active', b.dataset.view === view));
        document.querySelectorAll('[data-view-pane]').forEach((p) => { p.hidden = p.dataset.viewPane !== view; });
        if (view === 'kanban') {
            filtroEtapaTabela = null;
            marcarSegAtivo(null);
        }
        try { localStorage.setItem(VIEW_KEY, view); } catch { /* storage indisponível */ }
    };

    document.querySelectorAll('.pcx-view-btn').forEach((btn) => {
        btn.addEventListener('click', () => trocarView(btn.dataset.view));
    });

    // =========================================================================
    // Kanban
    // =========================================================================
    const cardTemplate = (item) => {
        const grad = etapaInfo(item.etapa).gradiente;
        const el = document.createElement('div');
        el.className = 'pcx-card';
        el.dataset.id = item.id;
        el.style.setProperty('--pcx-card-status-color', grad.start);

        const escopo = item.escopo === 'BENEFICIARIO'
            ? `Beneficiário: ${item.beneficiario_nome || '—'}`
            : 'Apólice inteira';
        const podeSolicitar = item.etapa === 'AGUARDANDO_IMPLANTACAO' && item.implantado;

        el.innerHTML = `
            <div class="pcx-card-header">
                <span class="pcx-card-contrato">${escapar(item.nome_contrato)}</span>
                <span class="pcx-card-via">${escapar(item.via_label)}</span>
            </div>
            <div class="pcx-card-linha">${escapar(escopo)}</div>
            <div class="pcx-card-linha pcx-card-operadora">
                ${escapar(item.operadora_anterior)}${item.operadora_nova ? ` → ${escapar(item.operadora_nova)}` : ''}
            </div>
            ${podeSolicitar ? `<div class="pcx-card-alerta">${SVG.check} Implantado — pode solicitar</div>` : ''}
            ${item.protocolo ? `<div class="pcx-card-protocolo">Protocolo: ${escapar(item.protocolo)}</div>` : ''}
            <div class="pcx-card-footer">
                <span title="Responsável">${escapar(item.responsavel_nome || 'Sem responsável')}</span>
                <span class="pcx-card-data">${escapar(item.criado_em)}</span>
            </div>
        `;
        el.addEventListener('click', () => abrirDetalhe(item.id));
        return el;
    };

    const renderKanban = () => {
        document.querySelectorAll('.pcx-kanban-column-body').forEach((body) => {
            const etapa = body.dataset.column;
            body.innerHTML = '';
            registros
                .filter((r) => r.etapa === etapa)
                .forEach((item) => body.appendChild(cardTemplate(item)));
            const count = document.querySelector(`[data-count="${etapa}"]`);
            if (count) count.textContent = body.children.length;
        });
    };

    // =========================================================================
    // Tabela
    // =========================================================================
    const badgeEtapa = (etapa) => {
        const info = etapaInfo(etapa);
        return `<span class="pcx-badge-etapa" style="--seg-start:${info.gradiente.start};--seg-end:${info.gradiente.end}">${escapar(info.label)}</span>`;
    };

    const renderTabela = () => {
        const tbody = document.getElementById('pcxTabelaBody');
        const vazio = document.getElementById('pcxTabelaVazia');
        if (!tbody) return;

        const texto = (document.getElementById('pcxFiltroTexto')?.value || '').trim().toLowerCase();
        const mostrarDesistidos = document.getElementById('pcxMostrarDesistidos')?.checked
            || filtroEtapaTabela === desistido.id;

        const linhas = registros.filter((r) => {
            if (!mostrarDesistidos && r.etapa === desistido.id) return false;
            if (filtroEtapaTabela && r.etapa !== filtroEtapaTabela) return false;
            if (!texto) return true;
            return [r.nome_contrato, r.cpf_cnpj, r.operadora_anterior, r.operadora_nova, r.responsavel_nome, r.beneficiario_nome, r.protocolo]
                .some((v) => v && String(v).toLowerCase().includes(texto));
        });

        tbody.innerHTML = linhas.map((r) => `
            <tr data-id="${r.id}">
                <td class="pcx-td-contrato">${escapar(r.nome_contrato)}</td>
                <td class="pcx-td-mono">${escapar(formatarDoc(r.cpf_cnpj))}</td>
                <td>${escapar(r.escopo === 'BENEFICIARIO' ? (r.beneficiario_nome || 'Beneficiário') : 'Apólice inteira')}</td>
                <td>${escapar(r.operadora_anterior)}</td>
                <td>${escapar(r.via_label)}</td>
                <td>${badgeEtapa(r.etapa)}</td>
                <td class="pcx-td-mono">${escapar(r.protocolo || '—')}</td>
                <td>${escapar(r.responsavel_nome || '—')}</td>
                <td class="pcx-td-mono">${escapar(r.criado_em)}</td>
            </tr>
        `).join('');
        vazio.hidden = linhas.length > 0;

        tbody.querySelectorAll('tr').forEach((tr) => {
            tr.addEventListener('click', () => abrirDetalhe(parseInt(tr.dataset.id, 10)));
        });
    };

    document.getElementById('pcxFiltroTexto')?.addEventListener('input', renderTabela);
    document.getElementById('pcxMostrarDesistidos')?.addEventListener('change', renderTabela);

    // =========================================================================
    // Mover etapa
    // =========================================================================
    // Ao entrar em SOLICITADO, oferece registrar o protocolo (opcional — pode pular).
    const pedirProtocoloSeSolicitado = async (novaEtapa) => {
        if (novaEtapa !== ETAPA_SOLICITADO) return null;
        const result = await Swal.fire({
            title: 'Cancelamento solicitado',
            text: 'Se a operadora já forneceu protocolo, registre agora (dá para preencher depois no detalhe).',
            input: 'text',
            inputPlaceholder: 'Protocolo da operadora (opcional)',
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonText: 'Registrar',
            cancelButtonText: 'Pular',
            buttonsStyling: false,
            customClass: { confirmButton: 'pv-btn pv-btn-primary', cancelButton: 'pv-btn pv-btn-ghost' },
        });
        return result.isConfirmed ? (result.value || null) : null;
    };

    const enviarMover = async (id, etapa, protocolo = null) => {
        const resp = await fetch(`/back-office/cancelamentos/${id}/mover`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            body: JSON.stringify({ etapa, protocolo }),
        });
        if (!resp.ok) {
            const err = await resp.json().catch(() => ({}));
            throw new Error(primeiroErro(err, `Falha ao mover (HTTP ${resp.status})`));
        }
    };

    const mover = async (id, novaEtapa) => {
        try {
            const protocolo = await pedirProtocoloSeSolicitado(novaEtapa);
            await enviarMover(id, novaEtapa, protocolo);
            showModernToast('success', 'Cancelamento movido', `Etapa: ${etapaInfo(novaEtapa).label}.`);
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro ao mover', err.message || 'Tente novamente.');
        } finally {
            carregarDados(); // fonte única: reposiciona o card e atualiza esteira/tabela
        }
    };

    const initDragDrop = () => {
        if (typeof Sortable === 'undefined') {
            console.error('SortableJS não carregado');
            return;
        }
        document.querySelectorAll('.pcx-kanban-column-body').forEach((el) => {
            new Sortable(el, {
                group: 'pcx-cancelamentos',
                animation: 160,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: (evt) => {
                    document.querySelectorAll('.pcx-kanban-column-body').forEach((b) => b.classList.remove('drag-over'));
                    if (evt.from === evt.to) return;
                    const id = parseInt(evt.item.dataset.id, 10);
                    const novaEtapa = evt.to.dataset.column;
                    evt.item.style.setProperty('--pcx-card-status-color', etapaInfo(novaEtapa).gradiente.start);
                    mover(id, novaEtapa);
                },
                onMove: (evt) => {
                    document.querySelectorAll('.pcx-kanban-column-body').forEach((b) => b.classList.remove('drag-over'));
                    if (evt.to) evt.to.classList.add('drag-over');
                },
            });
        });
    };

    // =========================================================================
    // Modal de detalhe
    // =========================================================================
    let detalheAtualId = null;
    let etapaModalAtual = null;

    const infoItem = (label, value) =>
        `<div class="pcx-info-item"><span class="pcx-info-label">${escapar(label)}</span><span class="pcx-info-value">${escapar(value ?? '—')}</span></div>`;

    const renderStepper = (etapaAtual) => {
        const cont = document.getElementById('pcxDetalheStepper');
        if (!cont) return;
        if (etapaAtual === desistido.id) {
            cont.innerHTML = `<li class="pcx-step is-active" style="--s:${desistido.gradiente.start};--e:${desistido.gradiente.end}">
                <span class="pcx-step-marker">✕</span>
                <span class="pcx-step-label">${escapar(desistido.label)}</span>
            </li>`;
            return;
        }
        const idx = colunas.findIndex((c) => c.id === etapaAtual);
        cont.innerHTML = colunas.map((c, i) => {
            const estado = i < idx ? 'done' : (i === idx ? 'active' : 'todo');
            const marker = estado === 'done' ? SVG.check : (i + 1);
            return `<li class="pcx-step is-${estado}" style="--s:${c.gradiente.start};--e:${c.gradiente.end}">
                <span class="pcx-step-marker">${marker}</span>
                <span class="pcx-step-label">${escapar(c.curto)}</span>
            </li>`;
        }).join('');
    };

    const renderTimeline = (hist) => {
        const tl = document.getElementById('pcxTimeline');
        if (!hist.length) {
            tl.innerHTML = '<p class="pcx-empty">Sem alterações registradas.</p>';
            return;
        }
        tl.innerHTML = hist.map((h) => `
            <div class="pcx-timeline-item">
                <span class="pcx-timeline-dot"></span>
                <div class="pcx-timeline-content">
                    <div class="pcx-timeline-text">
                        ${escapar(h.valor_anterior ? `${h.valor_anterior} → ${h.valor_novo}` : h.valor_novo)}
                    </div>
                    ${h.observacao ? `<div class="pcx-timeline-obs">${escapar(h.observacao)}</div>` : ''}
                    <div class="pcx-timeline-meta">${escapar(h.usuario_nome ?? 'Sistema')} · ${escapar(h.created_at)}</div>
                </div>
            </div>
        `).join('');
    };

    const aplicarEtapaNaModal = (etapa) => {
        etapaModalAtual = etapa;
        const grad = etapaInfo(etapa).gradiente;
        const header = document.getElementById('pcxDetalheHeader');
        header.style.setProperty('--pcx-etapa-start', grad.start);
        header.style.setProperty('--pcx-etapa-end', grad.end);
        document.getElementById('pcxDetalheEtapaPill').textContent = etapaInfo(etapa).label;
        renderStepper(etapa);
        const sel = document.getElementById('pcxDetalheEtapaSelect');
        if (sel && etapa !== desistido.id) sel.value = etapa;
    };

    const ativarAba = (alvo) => {
        document.querySelectorAll('#modalDetalheCancelamento .pcx-tab').forEach((t) => t.classList.toggle('is-active', t.dataset.tab === alvo));
        document.querySelectorAll('#modalDetalheCancelamento .pcx-tabpane').forEach((p) => p.classList.toggle('is-active', p.id === alvo));
    };
    document.querySelectorAll('#modalDetalheCancelamento .pcx-tab').forEach((t) => {
        t.addEventListener('click', () => ativarAba(t.dataset.tab));
    });

    const abrirDetalhe = async (id) => {
        detalheAtualId = id;
        document.getElementById('pcxDetalheId').value = id;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalheCancelamento')).show();

        try {
            const resp = await fetch(`/back-office/cancelamentos/${id}`, { headers: { Accept: 'application/json' } });
            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            const data = await resp.json();
            const r = data.registro;

            aplicarEtapaNaModal(r.etapa);
            document.getElementById('pcxDetalheTitulo').textContent = r.nome_contrato || 'Cancelamento';
            document.getElementById('pcxDetalheSubtitulo').textContent =
                r.escopo === 'BENEFICIARIO' ? `Beneficiário: ${r.beneficiario_nome || '—'}` : 'Apólice inteira';

            document.getElementById('pcxDetalheInfo').innerHTML =
                infoItem('CPF/CNPJ', formatarDoc(r.cpf_cnpj))
                + infoItem('Proposta', r.numero_proposta)
                + infoItem('Plano novo', r.nome_plano ? `${r.nome_plano} · ${r.operadora_nova || ''}` : r.operadora_nova)
                + infoItem('Operadora anterior', r.operadora_anterior)
                + infoItem('Via', r.via_label)
                + infoItem('Implantação', r.data_implantacao || (r.implantado ? 'Implantado' : 'Aguardando'))
                + infoItem('Aberto por', r.criado_por_nome)
                + infoItem('Aberto em', r.criado_em);

            document.getElementById('pcxDetalheProtocolo').value = r.protocolo || '';
            document.getElementById('pcxDetalheResponsavel').value = r.responsavel_id || '';
            document.getElementById('pcxDetalheSolicitadoEm').value = r.solicitado_em || '—';
            document.getElementById('pcxDetalheObservacoes').value = r.observacoes || '';

            const btnDesistir = document.getElementById('btnPcxDesistir');
            if (btnDesistir) btnDesistir.hidden = r.etapa === desistido.id || r.etapa === 'CONCLUIDO';

            ativarAba('pcxTabGeral');
            renderTimeline(data.historico || []);
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro', 'Não foi possível carregar o cancelamento.');
        }
    };

    const recarregarDetalhe = async () => {
        try {
            const resp = await fetch(`/back-office/cancelamentos/${detalheAtualId}`, { headers: { Accept: 'application/json' } });
            if (!resp.ok) return {};
            return await resp.json();
        } catch {
            return {};
        }
    };

    // Mover pela modal
    const btnMover = document.getElementById('btnPcxMoverEtapa');
    btnMover?.addEventListener('click', async () => {
        const novaEtapa = document.getElementById('pcxDetalheEtapaSelect').value;
        if (!detalheAtualId || novaEtapa === etapaModalAtual) {
            showModernToast('info', 'Sem alteração', 'O cancelamento já está nesta etapa.');
            return;
        }
        btnMover.disabled = true;
        try {
            const protocolo = await pedirProtocoloSeSolicitado(novaEtapa);
            await enviarMover(detalheAtualId, novaEtapa, protocolo);
            aplicarEtapaNaModal(novaEtapa);
            const detalhe = await recarregarDetalhe();
            if (detalhe.historico) renderTimeline(detalhe.historico);
            if (detalhe.registro) {
                document.getElementById('pcxDetalheProtocolo').value = detalhe.registro.protocolo || '';
                document.getElementById('pcxDetalheSolicitadoEm').value = detalhe.registro.solicitado_em || '—';
            }
            carregarDados();
            showModernToast('success', 'Cancelamento movido', `Etapa: ${etapaInfo(novaEtapa).label}.`);
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro ao mover', err.message || 'Tente novamente.');
        } finally {
            btnMover.disabled = false;
        }
    });

    // Salvar protocolo/observações + responsável
    document.getElementById('btnPcxSalvarDetalhe')?.addEventListener('click', async () => {
        if (!detalheAtualId) return;
        try {
            const respUpdate = await fetch(`/back-office/cancelamentos/${detalheAtualId}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({
                    protocolo: document.getElementById('pcxDetalheProtocolo').value || null,
                    observacoes: document.getElementById('pcxDetalheObservacoes').value || null,
                }),
            });
            if (!respUpdate.ok) throw new Error(`HTTP ${respUpdate.status}`);

            const responsavelId = document.getElementById('pcxDetalheResponsavel').value;
            const respAtribuir = await fetch(`/back-office/cancelamentos/${detalheAtualId}/atribuir`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({ responsavel_id: responsavelId ? parseInt(responsavelId, 10) : null }),
            });
            if (!respAtribuir.ok) throw new Error(`HTTP ${respAtribuir.status}`);

            showModernToast('success', 'Salvo', 'Alterações registradas.');
            const detalhe = await recarregarDetalhe();
            if (detalhe.historico) renderTimeline(detalhe.historico);
            carregarDados();
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro', 'Não foi possível salvar.');
        }
    });

    // Desistir (com motivo)
    document.getElementById('btnPcxDesistir')?.addEventListener('click', async () => {
        if (!detalheAtualId) return;
        const result = await Swal.fire({
            title: 'Desistir do cancelamento?',
            text: 'O registro sai do fluxo e fica marcado como desistido. Informe o motivo.',
            input: 'text',
            inputPlaceholder: 'Motivo da desistência',
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonText: 'Desistir',
            cancelButtonText: 'Voltar',
            buttonsStyling: false,
            customClass: { confirmButton: 'pv-btn pcx-btn-desistir-confirm', cancelButton: 'pv-btn pv-btn-ghost' },
        });
        if (!result.isConfirmed) return;
        try {
            const resp = await fetch(`/back-office/cancelamentos/${detalheAtualId}/desistir`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({ motivo: result.value || null }),
            });
            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalheCancelamento')).hide();
            showModernToast('success', 'Desistido', 'Cancelamento marcado como desistido.');
            carregarDados();
        } catch (err) {
            console.error(err);
            showModernToast('error', 'Erro', 'Não foi possível registrar a desistência.');
        }
    });

    // =========================================================================
    // Novo cancelamento
    // =========================================================================
    const modalNovoEl = document.getElementById('modalNovoCancelamento');
    let contratoSelecionado = null;

    const limparContrato = () => {
        contratoSelecionado = null;
        document.getElementById('pcxVendaId').value = '';
        document.getElementById('pcxContratoResumo').hidden = true;
        const busca = document.getElementById('pcxBuscaContrato');
        busca.value = '';
        busca.parentElement.hidden = false;
    };

    const selecionarContrato = (c) => {
        contratoSelecionado = c;
        document.getElementById('pcxVendaId').value = c.id;
        document.getElementById('pcxResumoNome').textContent = c.nome_contrato || `Contrato #${c.id}`;
        document.getElementById('pcxResumoDocs').textContent =
            [formatarDoc(c.cpf_cnpj), c.numero_proposta ? `Proposta ${c.numero_proposta}` : null, c.operadora]
                .filter(Boolean).join(' · ');
        document.getElementById('pcxResumoImplantado').hidden = !c.implantado;
        document.getElementById('pcxResumoAguardando').hidden = !!c.implantado;
        document.getElementById('pcxContratoResumo').hidden = false;
        document.getElementById('pcxBuscaContrato').parentElement.hidden = true;
    };

    document.getElementById('pcxTrocarContrato')?.addEventListener('click', limparContrato);

    // Busca inline com debounce
    (function initBuscaContrato() {
        const input = document.getElementById('pcxBuscaContrato');
        const box = document.getElementById('pcxResultadosContrato');
        if (!input || !box) return;

        const fechar = () => { box.hidden = true; box.innerHTML = ''; };

        const render = (contratos) => {
            if (!contratos.length) {
                box.innerHTML = '<p class="pcx-empty pcx-empty-busca">Nenhum contrato encontrado.</p>';
                box.hidden = false;
                return;
            }
            box.innerHTML = contratos.map((c, i) => `
                <div class="pcx-contrato-item" data-idx="${i}">
                    <strong>${escapar(c.nome_contrato || `Contrato #${c.id}`)}</strong>
                    <small>${escapar(formatarDoc(c.cpf_cnpj))}${c.operadora ? ` · ${escapar(c.operadora)}` : ''}${c.implantado ? ' · Implantado' : ''}</small>
                </div>
            `).join('');
            box.hidden = false;
            box.querySelectorAll('.pcx-contrato-item').forEach((el) => {
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
            fetch(`/back-office/cancelamentos/buscar-contratos?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } })
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
            if (!e.target.closest('.pcx-busca-contrato')) fechar();
        });
    })();

    // Escopo: campo do beneficiário só aparece quando específico
    document.querySelectorAll('#modalNovoCancelamento input[name="escopo"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            const especifico = radio.value === 'BENEFICIARIO' && radio.checked;
            const campo = document.getElementById('pcxCampoBeneficiario');
            campo.hidden = !especifico;
            document.getElementById('pcxBeneficiarioNome').required = especifico;
        });
    });

    document.getElementById('btnNovoCancelamento')?.addEventListener('click', () => {
        document.getElementById('formNovoCancelamento').reset();
        limparContrato();
        document.getElementById('pcxCampoBeneficiario').hidden = true;
        bootstrap.Modal.getOrCreateInstance(modalNovoEl).show();
    });

    const btnConfirmar = document.getElementById('btnConfirmarNovoCancelamento');
    btnConfirmar?.addEventListener('click', async () => {
        if (!contratoSelecionado) {
            showModernToast('warning', 'Contrato obrigatório', 'Busque e selecione o contrato da venda.');
            return;
        }
        const formEl = document.getElementById('formNovoCancelamento');
        if (!formEl.reportValidity()) return;

        const escopo = formEl.querySelector('input[name="escopo"]:checked')?.value || 'APOLICE';
        const payload = {
            venda_id: contratoSelecionado.id,
            escopo,
            beneficiario_nome: escopo === 'BENEFICIARIO' ? document.getElementById('pcxBeneficiarioNome').value : null,
            via: document.getElementById('pcxVia').value,
            operadora_anterior: document.getElementById('pcxOperadoraAnterior').value,
            responsavel_id: document.getElementById('pcxResponsavel').value || null,
            observacoes: document.getElementById('pcxObservacoes').value || null,
        };

        btnConfirmar.disabled = true;
        try {
            const resp = await fetch('/back-office/cancelamentos', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify(payload),
            });
            if (!resp.ok) {
                const err = await resp.json().catch(() => ({}));
                throw new Error(primeiroErro(err, `HTTP ${resp.status}`));
            }
            showModernToast('success', 'Cancelamento criado', 'O processo entrou na esteira.');
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
    // Init
    // =========================================================================
    try {
        const salvo = localStorage.getItem(VIEW_KEY);
        if (salvo === 'tabela') trocarView('tabela');
    } catch { /* storage indisponível */ }

    initDragDrop();
    carregarDados();
})();
