'use strict';

/**
 * Workspace de Demandas de Pós-Venda.
 * Um card por contrato implantado com demandas pendentes; o backoffice dá check
 * nas tarefas, adiciona demandas avulsas e conclui tudo de uma vez.
 *
 * Reaproveita os endpoints `*DemandaContrato` já existentes (toggle/editar/criar/remover).
 */
(function () {
    const cfg = window.posVendaDemandas || {};
    const grid = document.getElementById('pvd-grid');
    const loading = document.getElementById('pvd-loading');
    const empty = document.getElementById('pvd-empty');

    let templates = [];
    let debounceTimer = null;

    // -------------------------------------------------- helpers

    // Toast padrão do projeto (componente .custom-toast — estilos em pos-venda.scss)
    const TOAST_ICONS = {
        success: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        error: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        warning: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        info: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
    };
    const TOAST_TITLES = { success: 'Sucesso!', error: 'Erro', warning: 'Atenção', info: 'Informação' };

    function toast(type, message) {
        if (!window.Swal) return;
        const t = TOAST_ICONS[type] ? type : 'info';
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            background: 'transparent',
            showClass: { popup: 'animate__animated animate__slideInRight animate__faster' },
            hideClass: { popup: 'animate__animated animate__slideOutRight animate__faster' },
            customClass: { popup: 'custom-toast-popup' },
            html: `
                <div class="custom-toast custom-toast-${t}">
                    <div class="toast-icon">${TOAST_ICONS[t]}</div>
                    <div class="toast-content">
                        <div class="toast-title">${TOAST_TITLES[t]}</div>
                        <div class="toast-message">${esc(message || '')}</div>
                    </div>
                    <div class="toast-close" onclick="Swal.close()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </div>
                </div>`,
        });
    }

    function esc(str) {
        return String(str ?? '').replace(/[&<>"']/g, s => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[s]));
    }

    function tipoLabel(tipo) {
        return (cfg.tipoLabels && cfg.tipoLabels[tipo]) || tipo;
    }

    function jsonHeaders() {
        return {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': cfg.csrf,
            'Accept': 'application/json'
        };
    }

    function getFilters() {
        const params = new URLSearchParams();
        const busca = document.getElementById('filtro-busca')?.value.trim();
        const operadora = document.getElementById('filtro-operadora')?.value;
        const backoffice = document.getElementById('filtro-backoffice')?.value;
        if (busca) params.set('busca', busca);
        if (operadora) params.set('operadora', operadora);
        if (backoffice) params.set('backoffice_id', backoffice);
        return params.toString();
    }

    // -------------------------------------------------- data

    function loadData() {
        loading.style.display = 'block';
        empty.style.display = 'none';
        grid.innerHTML = '';

        const qs = getFilters();
        fetch(`${cfg.urls.data}${qs ? '?' + qs : ''}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                loading.style.display = 'none';
                if (!data.success) {
                    toast('error', data.message || 'Erro ao carregar.');
                    return;
                }
                updateKpis(data.kpis);
                if (!data.contratos || data.contratos.length === 0) {
                    empty.style.display = 'flex';
                    return;
                }
                data.contratos.forEach((c, i) => grid.appendChild(renderCard(c, i)));
            })
            .catch(() => {
                loading.style.display = 'none';
                toast('error', 'Falha de conexão.');
            });
    }

    function updateKpis(kpis) {
        if (!kpis) return;
        document.getElementById('kpi-contratos').textContent = kpis.contratos_pendentes ?? 0;
        document.getElementById('kpi-demandas').textContent = kpis.demandas_pendentes ?? 0;
        document.getElementById('kpi-hoje').textContent = kpis.demandas_concluidas_hoje ?? 0;
    }

    // -------------------------------------------------- render

    function renderCard(c, index) {
        const card = document.createElement('div');
        card.className = 'pvd-card';
        card.dataset.vendaId = c.venda_id;
        card.style.animationDelay = `${index * 50}ms`;

        const meta = [c.numero_proposta ? `Proposta ${esc(c.numero_proposta)}` : null, esc(c.operadora || ''),
            c.data_implantacao ? `Implantado ${esc(c.data_implantacao)}` : null]
            .filter(Boolean).join(' &middot; ');

        const respChip = c.backoffice_nome
            ? `<span class="pvd-chip">${esc(c.backoffice_nome)}</span>`
            : `<span class="pvd-chip pvd-chip-muted">Sem responsável</span>`;

        const demandasHtml = (c.demandas || []).map(d => renderDemanda(d)).join('');

        card.innerHTML = `
            <div class="pvd-card-head">
                <div class="pvd-card-title">
                    <h6>${esc(c.nome_contrato || 'Contrato #' + c.venda_id)}</h6>
                    <span class="pvd-card-meta">${meta}</span>
                </div>
                ${respChip}
            </div>
            <div class="pvd-progress">
                <div class="pvd-progress-bar"><span style="width:${c.progresso}%"></span></div>
                <span class="pvd-progress-text">${c.concluidas}/${c.total}</span>
            </div>
            <div class="pvd-demandas">${demandasHtml}</div>
            <div class="pvd-card-actions">
                <div class="pvd-card-actions-left">
                    <button type="button" class="pvd-btn pvd-btn-wpp" data-action="boas-vindas" data-venda-id="${c.venda_id}" title="Enviar boas-vindas por WhatsApp">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347M12.05 21.785h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884"/></svg>
                        Boas-vindas
                    </button>
                    <button type="button" class="pvd-btn pvd-btn-ghost" data-action="add" data-venda-id="${c.venda_id}">
                        + Adicionar demanda
                    </button>
                </div>
                <button type="button" class="pvd-btn pvd-btn-primary" data-action="concluir" data-venda-id="${c.venda_id}" data-pendentes="${c.pendentes}">
                    Concluir todas
                </button>
            </div>
        `;
        return card;
    }

    function renderDemanda(d) {
        const concluida = d.status === 'CONCLUIDA';
        const check = concluida
            ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"></path></svg>'
            : '';
        const concluidaInfo = concluida && d.concluida_por
            ? `<span class="pvd-d-info">Concluída por ${esc(d.concluida_por.name)}</span>` : '';
        return `
            <div class="pvd-demanda ${concluida ? 'is-done' : ''}" data-demanda-id="${d.id}">
                <button type="button" class="pvd-check ${concluida ? 'checked' : ''}" data-action="toggle" data-demanda-id="${d.id}" title="${concluida ? 'Reabrir' : 'Concluir'}">${check}</button>
                <div class="pvd-d-body">
                    <div class="pvd-d-top">
                        <span class="pvd-d-tipo">${esc(tipoLabel(d.tipo))}</span>
                        <span class="pvd-d-titulo">${esc(d.titulo)}</span>
                    </div>
                    ${d.descricao ? `<span class="pvd-d-desc">${esc(d.descricao)}</span>` : ''}
                    ${concluidaInfo}
                </div>
                <div class="pvd-d-actions">
                    <button type="button" class="pvd-icon-btn" data-action="edit" data-demanda-id="${d.id}" data-tipo="${esc(d.tipo)}" data-titulo="${esc(d.titulo)}" data-descricao="${esc(d.descricao || '')}" title="Editar">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    <button type="button" class="pvd-icon-btn pvd-icon-danger" data-action="delete" data-demanda-id="${d.id}" title="Remover">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                </div>
            </div>
        `;
    }

    // -------------------------------------------------- actions (event delegation)

    grid.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const action = btn.dataset.action;

        if (action === 'toggle') return toggleDemanda(btn.dataset.demandaId);
        if (action === 'delete') return deleteDemanda(btn.dataset.demandaId);
        if (action === 'edit') return openModal('edit', btn.dataset);
        if (action === 'add') return openModal('add', { vendaId: btn.dataset.vendaId });
        if (action === 'concluir') return concluirTodas(btn.dataset.vendaId, parseInt(btn.dataset.pendentes, 10));
        if (action === 'boas-vindas') {
            // Aberto pelo módulo boas-vindas.js (modais do partial).
            if (typeof window.abrirBoasVindas === 'function') window.abrirBoasVindas(btn.dataset.vendaId);
            return;
        }
    });

    // Recarrega a lista após um envio de boas-vindas bem-sucedido.
    document.addEventListener('boasVindasEnviada', loadData);

    function toggleDemanda(id) {
        fetch(`${cfg.urls.demandaBase}/${id}/toggle`, { method: 'PATCH', headers: jsonHeaders() })
            .then(r => r.json())
            .then(data => {
                if (data.success) { toast('success', data.message); loadData(); }
                else toast('error', data.message || 'Erro.');
            })
            .catch(() => toast('error', 'Falha de conexão.'));
    }

    function deleteDemanda(id) {
        Swal.fire({
            title: 'Remover demanda?',
            text: 'Essa ação não pode ser desfeita.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Remover',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#9CA3AF'
        }).then(res => {
            if (!res.isConfirmed) return;
            fetch(`${cfg.urls.demandaBase}/${id}`, { method: 'DELETE', headers: jsonHeaders() })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { toast('success', data.message); loadData(); }
                    else toast('error', data.message || 'Erro.');
                })
                .catch(() => toast('error', 'Falha de conexão.'));
        });
    }

    function concluirTodas(vendaId, pendentes) {
        Swal.fire({
            title: 'Concluir pós-venda?',
            text: `${pendentes} demanda(s) pendente(s) serão concluídas e o contrato sairá desta tela.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Concluir todas',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#7C3AED',
            cancelButtonColor: '#9CA3AF'
        }).then(res => {
            if (!res.isConfirmed) return;
            fetch(`${cfg.urls.concluirTodas}/${vendaId}/concluir-todas`, { method: 'POST', headers: jsonHeaders() })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { toast('success', data.message); loadData(); }
                    else toast('error', data.message || 'Erro.');
                })
                .catch(() => toast('error', 'Falha de conexão.'));
        });
    }

    // -------------------------------------------------- modal

    const modal = document.getElementById('pvd-modal');
    const form = document.getElementById('pvd-form');
    const tipoSelect = document.getElementById('pvd-tipo');
    const tituloInput = document.getElementById('pvd-titulo');
    const descInput = document.getElementById('pvd-descricao');

    function fillTipoOptions() {
        const labels = cfg.tipoLabels || {};
        tipoSelect.innerHTML = Object.keys(labels)
            .map(t => `<option value="${esc(t)}">${esc(labels[t])}</option>`).join('');
    }

    function openModal(mode, data) {
        document.getElementById('pvd-modal-title').textContent = mode === 'edit' ? 'Editar demanda' : 'Adicionar demanda';
        fillTipoOptions();
        if (mode === 'edit') {
            document.getElementById('pvd-demanda-id').value = data.demandaId;
            document.getElementById('pvd-venda-id').value = '';
            tipoSelect.value = data.tipo;
            tituloInput.value = data.titulo || '';
            descInput.value = data.descricao || '';
        } else {
            document.getElementById('pvd-demanda-id').value = '';
            document.getElementById('pvd-venda-id').value = data.vendaId;
            // Pré-preenche o título com a label do tipo selecionado a partir dos templates.
            tipoSelect.value = templates[0]?.tipo || Object.keys(cfg.tipoLabels || {})[0] || '';
            tituloInput.value = templateTitulo(tipoSelect.value);
            descInput.value = '';
        }
        modal.style.display = 'flex';
    }

    function closeModal() { modal.style.display = 'none'; }

    function templateTitulo(tipo) {
        const t = templates.find(x => x.tipo === tipo);
        return t ? t.titulo : tipoLabel(tipo);
    }

    // Ao trocar o tipo num cadastro novo, sugere o título do template (sem sobrescrever edição manual).
    tipoSelect.addEventListener('change', function () {
        if (!document.getElementById('pvd-demanda-id').value) {
            tituloInput.value = templateTitulo(this.value);
        }
    });

    document.getElementById('pvd-modal-close').addEventListener('click', closeModal);
    document.getElementById('pvd-cancel').addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const demandaId = document.getElementById('pvd-demanda-id').value;
        const vendaId = document.getElementById('pvd-venda-id').value;
        const payload = {
            tipo: tipoSelect.value,
            titulo: tituloInput.value.trim(),
            descricao: descInput.value.trim() || null
        };
        if (!demandaId) payload.venda_id = parseInt(vendaId, 10);

        const url = demandaId ? `${cfg.urls.demandaBase}/${demandaId}` : cfg.urls.storeDemanda;
        const method = demandaId ? 'PUT' : 'POST';

        fetch(url, { method, headers: jsonHeaders(), body: JSON.stringify(payload) })
            .then(r => r.json())
            .then(data => {
                if (data.success) { toast('success', data.message); closeModal(); loadData(); }
                else toast('error', data.message || 'Erro ao salvar.');
            })
            .catch(() => toast('error', 'Falha de conexão.'));
    });

    // -------------------------------------------------- filters

    ['filtro-busca'].forEach(id => {
        const el = document.getElementById(id);
        el && el.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(loadData, 350);
        });
    });
    ['filtro-operadora', 'filtro-backoffice'].forEach(id => {
        const el = document.getElementById(id);
        el && el.addEventListener('change', loadData);
    });

    // -------------------------------------------------- abrir chamado avulso

    const chamadoModal = document.getElementById('pvd-chamado-modal');
    if (chamadoModal) {
        const chBusca = document.getElementById('pvd-chamado-busca');
        const chResults = document.getElementById('pvd-chamado-results');
        const chBuscaGroup = document.getElementById('pvd-chamado-busca-group');
        const chSelected = document.getElementById('pvd-chamado-selected');
        const chVendaId = document.getElementById('pvd-chamado-venda-id');
        const chTipo = document.getElementById('pvd-chamado-tipo');
        const chTitulo = document.getElementById('pvd-chamado-titulo');
        const chDesc = document.getElementById('pvd-chamado-descricao');
        const chSave = document.getElementById('pvd-chamado-save');
        const chForm = document.getElementById('pvd-chamado-form');
        let chamadoDebounce = null;

        function openChamado() {
            chBusca.value = '';
            chResults.innerHTML = '';
            chBuscaGroup.style.display = '';
            chSelected.style.display = 'none';
            chVendaId.value = '';
            chSave.disabled = true;
            chamadoModal.style.display = 'flex';
            buscarContratos(''); // lista inicial (mais recentes)
            setTimeout(() => chBusca.focus(), 50);
        }
        function closeChamado() { chamadoModal.style.display = 'none'; }

        function buscarContratos(termo) {
            chResults.innerHTML = '<div class="pvd-chamado-hint">Buscando...</div>';
            fetch(`${cfg.urls.buscarContratos}?termo=${encodeURIComponent(termo)}`, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    if (!data.success || !data.contratos || data.contratos.length === 0) {
                        chResults.innerHTML = '<div class="pvd-chamado-hint">Nenhum contrato implantado encontrado.</div>';
                        return;
                    }
                    chResults.innerHTML = data.contratos.map(c => `
                        <button type="button" class="pvd-chamado-result"
                            data-venda-id="${c.venda_id}"
                            data-nome="${esc(c.nome_contrato || 'Contrato #' + c.venda_id)}"
                            data-meta="${esc([c.numero_proposta ? 'Proposta ' + c.numero_proposta : '', c.operadora || '', c.data_implantacao ? 'Implantado ' + c.data_implantacao : ''].filter(Boolean).join(' · '))}">
                            <span class="pvd-chamado-result-nome">${esc(c.nome_contrato || 'Contrato #' + c.venda_id)}</span>
                            <span class="pvd-chamado-result-meta">${esc([c.numero_proposta ? 'Proposta ' + c.numero_proposta : '', c.operadora || '', c.cpf_cnpj || ''].filter(Boolean).join(' · '))}</span>
                        </button>
                    `).join('');
                })
                .catch(() => { chResults.innerHTML = '<div class="pvd-chamado-hint">Falha ao buscar.</div>'; });
        }

        function selecionarContrato(vendaId, nome, meta) {
            chVendaId.value = vendaId;
            document.getElementById('pvd-chamado-sel-nome').textContent = nome;
            document.getElementById('pvd-chamado-sel-meta').textContent = meta;
            chBuscaGroup.style.display = 'none';
            chSelected.style.display = '';
            // Preenche tipos a partir dos templates/labels.
            const labels = cfg.tipoLabels || {};
            chTipo.innerHTML = Object.keys(labels).map(t => `<option value="${esc(t)}">${esc(labels[t])}</option>`).join('');
            chTipo.value = templates[0]?.tipo || Object.keys(labels)[0] || '';
            chTitulo.value = templateTitulo(chTipo.value);
            chDesc.value = '';
            chSave.disabled = false;
        }

        document.getElementById('pvd-abrir-chamado')?.addEventListener('click', openChamado);
        document.getElementById('pvd-chamado-close').addEventListener('click', closeChamado);
        document.getElementById('pvd-chamado-cancel').addEventListener('click', closeChamado);
        chamadoModal.addEventListener('click', e => { if (e.target === chamadoModal) closeChamado(); });

        chBusca.addEventListener('input', function () {
            clearTimeout(chamadoDebounce);
            const termo = this.value.trim();
            chamadoDebounce = setTimeout(() => buscarContratos(termo), 350);
        });

        chResults.addEventListener('click', function (e) {
            const btn = e.target.closest('.pvd-chamado-result');
            if (!btn) return;
            selecionarContrato(btn.dataset.vendaId, btn.dataset.nome, btn.dataset.meta);
        });

        document.getElementById('pvd-chamado-trocar').addEventListener('click', function () {
            chSelected.style.display = 'none';
            chBuscaGroup.style.display = '';
            chVendaId.value = '';
            chSave.disabled = true;
            chBusca.focus();
        });

        chTipo.addEventListener('change', function () {
            chTitulo.value = templateTitulo(this.value);
        });

        chForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const vendaId = parseInt(chVendaId.value, 10);
            if (!vendaId) { toast('warning', 'Selecione um contrato.'); return; }
            const titulo = chTitulo.value.trim();
            if (!titulo) { toast('warning', 'Informe o título do chamado.'); return; }

            chSave.disabled = true;
            fetch(cfg.urls.storeDemanda, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ venda_id: vendaId, tipo: chTipo.value, titulo, descricao: chDesc.value.trim() || null })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { toast('success', 'Chamado aberto com sucesso.'); closeChamado(); loadData(); }
                    else { toast('error', data.message || 'Erro ao abrir chamado.'); chSave.disabled = false; }
                })
                .catch(() => { toast('error', 'Falha de conexão.'); chSave.disabled = false; });
        });
    }

    // -------------------------------------------------- init

    function loadTemplates() {
        fetch(cfg.urls.templates, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => { if (data.success) templates = data.templates || []; })
            .catch(() => {});
    }

    loadTemplates();
    loadData();
})();
