/**
 * Operadoras e Planos — tela única (master-detail).
 * Esquerda: operadoras (cadastro + status). Direita: planos da operadora
 * selecionada (cadastro + status). Reaproveita createOperation/createPlan.
 */
(function () {
  'use strict';

  const root = document.getElementById('op-planos');
  if (!root) return;

  const csrf = root.dataset.csrf;
  let operadoras = [];
  let selecionada = null;

  const $ = (id) => document.getElementById(id);
  const esc = (s) =>
    String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  function toast(msg, type = 'ok') {
    const el = document.createElement('div');
    el.className = `op-toast ${type}`;
    el.textContent = msg;
    document.body.appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 300); }, 2400);
  }

  async function api(url, method = 'GET', body = null) {
    const opts = { method, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' } };
    if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
    const res = await fetch(url, opts);
    return res.json().catch(() => ({ success: false, message: 'Resposta inválida.' }));
  }

  const badgeStatus = (s) =>
    s === 'Y' ? '<span class="op-badge ok">Ativa</span>' : '<span class="op-badge off">Inativa</span>';
  const badgePlano = (s) =>
    s === 'Y' ? '<span class="op-badge ok">Ativo</span>' : '<span class="op-badge off">Inativo</span>';

  // ---------- Carregar ----------
  async function load() {
    const data = await api('/back-office/operadoras-planos/data');
    operadoras = data.success ? data.operadoras : [];
    if (selecionada) selecionada = operadoras.find((o) => o.id === selecionada.id) || null;
    renderMaster();
    renderDetail();
  }

  // ---------- Master (operadoras) ----------
  function renderMaster() {
    const list = $('op-list');
    if (!operadoras.length) {
      list.innerHTML = '<div class="op-empty">Nenhuma operadora. Clique em “Nova”.</div>';
      return;
    }
    list.innerHTML = operadoras
      .map(
        (o) => `<div class="op-item ${selecionada && selecionada.id === o.id ? 'is-active' : ''} ${o.status === 'N' ? 'is-off' : ''}" data-op="${o.id}">
          <button type="button" class="op-item-main" data-select="${o.id}">
            <span class="op-item-nome">${esc(o.nome)}</span>
            <span class="op-item-meta">${o.planos.length} plano${o.planos.length === 1 ? '' : 's'}</span>
          </button>
          <div class="op-item-side">
            ${badgeStatus(o.status)}
            <button type="button" class="op-toggle" data-op-toggle="${o.id}" title="${o.status === 'Y' ? 'Inativar' : 'Ativar'}">${o.status === 'Y' ? 'Inativar' : 'Ativar'}</button>
            ${o.can_delete ? `<button type="button" class="op-delete" data-op-delete="${o.id}" aria-label="Excluir operadora ${esc(o.nome)}">Excluir</button>` : ''}
          </div>
        </div>`
      )
      .join('');
  }

  // ---------- Detail (planos) ----------
  function renderDetail() {
    const detail = $('op-detail');
    if (!selecionada) {
      detail.innerHTML = `<div class="op-empty-detail">
          <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h7v7H3z"/><path d="M14 3h7v7h-7z"/><path d="M14 14h7v7h-7z"/><path d="M3 14h7v7H3z"/></svg>
          <p>Selecione uma operadora à esquerda para ver e cadastrar seus planos.</p>
        </div>`;
      return;
    }

    const o = selecionada;
    const planosHtml = o.planos.length
      ? `<div class="op-plano-list">${o.planos
          .map(
            (p) => `<div class="op-plano ${p.status === 'N' ? 'is-off' : ''}">
              <div class="op-plano-info">
                <span class="op-plano-nome">${esc(p.nome)}</span>
                <span class="op-plano-acom">${esc(p.acomodacao === 'APARTAMENTO' ? 'Apartamento' : 'Enfermaria')}</span>
              </div>
              <div class="op-plano-side">
                ${badgePlano(p.status)}
                <button type="button" class="op-toggle" data-plano-toggle="${p.id}">${p.status === 'Y' ? 'Inativar' : 'Ativar'}</button>
                ${p.can_delete ? `<button type="button" class="op-delete" data-plano-delete="${p.id}" aria-label="Excluir plano ${esc(p.nome)}">Excluir</button>` : ''}
              </div>
            </div>`
          )
          .join('')}</div>`
      : '<div class="op-empty">Nenhum plano nesta operadora ainda.</div>';

    detail.innerHTML = `
      <div class="op-detail-head">
        <div>
          <h5 class="op-detail-title">${esc(o.nome)}</h5>
          <span class="op-detail-sub">${o.planos.length} plano${o.planos.length === 1 ? '' : 's'} cadastrado${o.planos.length === 1 ? '' : 's'}</span>
        </div>
        ${badgeStatus(o.status)}
      </div>

      <form class="op-plano-form" id="op-plano-form" autocomplete="off">
        <input type="text" class="op-input op-input-grow" id="op-plano-nome" placeholder="Nome do plano" maxlength="120" required>
        <select class="op-input" id="op-plano-acom">
          <option value="ENFERMARIA">Enfermaria</option>
          <option value="APARTAMENTO">Apartamento</option>
        </select>
        <select class="op-input" id="op-plano-status">
          <option value="Y">Ativo</option>
          <option value="N">Inativo</option>
        </select>
        <button type="submit" class="op-btn op-btn-primary">Adicionar plano</button>
      </form>

      <h6 class="op-plano-listtitle">Planos</h6>
      ${planosHtml}`;

    $('op-plano-form').addEventListener('submit', salvarPlano);
  }

  // ---------- Ações ----------
  async function salvarOperadora(e) {
    e.preventDefault();
    const nome = $('op-nome').value.trim();
    if (!nome) return;
    const json = await api('/back-office/createOperation', 'POST', { nome, status: $('op-status').value });
    if (json.success) {
      toast('Operadora cadastrada.');
      $('op-nome').value = '';
      $('op-form').hidden = true;
      await load();
      const nova = operadoras.find((o) => o.nome === nome.toUpperCase());
      if (nova) { selecionada = nova; renderMaster(); renderDetail(); }
    } else {
      toast(json.message || 'Erro ao cadastrar operadora.', 'err');
    }
  }

  async function salvarPlano(e) {
    e.preventDefault();
    if (!selecionada) return;
    const nome = $('op-plano-nome').value.trim();
    if (!nome) return;
    const json = await api('/back-office/createPlan', 'POST', {
      operadora_id: selecionada.id,
      nome,
      status: $('op-plano-status').value,
      acomodacao: $('op-plano-acom').value,
    });
    if (json.success) {
      toast('Plano cadastrado.');
      await load();
    } else {
      toast(json.message || 'Erro ao cadastrar plano.', 'err');
    }
  }

  async function toggleOperadora(id) {
    const json = await api(`/back-office/operadoras/${id}/status`, 'PATCH');
    if (json.success) await load();
    else toast(json.message || 'Erro.', 'err');
  }

  async function togglePlano(id) {
    const json = await api(`/back-office/planos/${id}/status`, 'PATCH');
    if (json.success) await load();
    else toast(json.message || 'Erro.', 'err');
  }

  async function excluirOperadora(id) {
    const operadora = operadoras.find((item) => item.id === id);
    if (!operadora || !window.confirm(`Excluir a operadora “${operadora.nome}” e todos os planos dela? Esta ação não pode ser desfeita.`)) return;

    const json = await api(`/back-office/operadoras/${id}`, 'DELETE');
    if (!json.success) {
      toast(json.message || 'Erro ao excluir operadora.', 'err');
      return;
    }

    if (selecionada && selecionada.id === id) selecionada = null;
    toast(json.message || 'Operadora excluída.');
    await load();
  }

  async function excluirPlano(id) {
    if (!selecionada) return;
    const plano = selecionada.planos.find((item) => item.id === id);
    if (!plano || !window.confirm(`Excluir o plano “${plano.nome}”? Esta ação não pode ser desfeita.`)) return;

    const json = await api(`/back-office/planos/${id}`, 'DELETE');
    if (!json.success) {
      toast(json.message || 'Erro ao excluir plano.', 'err');
      return;
    }

    toast(json.message || 'Plano excluído.');
    await load();
  }

  // ---------- Eventos ----------
  $('op-add-toggle').addEventListener('click', () => {
    const f = $('op-form');
    f.hidden = !f.hidden;
    if (!f.hidden) $('op-nome').focus();
  });
  $('op-cancel').addEventListener('click', () => { $('op-form').hidden = true; });
  $('op-form').addEventListener('submit', salvarOperadora);

  root.addEventListener('click', (e) => {
    const sel = e.target.closest('[data-select]');
    if (sel) { selecionada = operadoras.find((o) => o.id === Number(sel.dataset.select)) || null; renderMaster(); renderDetail(); return; }
    const opTog = e.target.closest('[data-op-toggle]');
    if (opTog) { toggleOperadora(Number(opTog.dataset.opToggle)); return; }
    const plTog = e.target.closest('[data-plano-toggle]');
    if (plTog) { togglePlano(Number(plTog.dataset.planoToggle)); return; }
    const opDelete = e.target.closest('[data-op-delete]');
    if (opDelete) { excluirOperadora(Number(opDelete.dataset.opDelete)); return; }
    const planoDelete = e.target.closest('[data-plano-delete]');
    if (planoDelete) { excluirPlano(Number(planoDelete.dataset.planoDelete)); }
  });

  load();
})();
