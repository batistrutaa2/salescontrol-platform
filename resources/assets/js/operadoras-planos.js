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
  let saudeDocumentos = { diretorios: [] };

  const $ = (id) => document.getElementById(id);
  const esc = (s) =>
    String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  function toast(msg, type = 'ok') {
    const el = document.createElement('div');
    el.className = `op-toast ${type}`;
    el.setAttribute('role', type === 'err' ? 'alert' : 'status');
    el.textContent = msg;
    document.body.appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 300); }, 2400);
  }

  async function api(url, method = 'GET', body = null) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 10000);
    const opts = { method, signal: controller.signal, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' } };
    if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
    try {
      const res = await fetch(url, opts);
      const payload = await res.json().catch(() => ({}));
      if (!res.ok) {
        const errors = payload.errors ? Object.values(payload.errors).flat() : [];
        return { success: false, message: errors[0] || payload.message || `A solicitação falhou (${res.status}).` };
      }
      return payload;
    } catch (error) {
      return {
        success: false,
        message: error.name === 'AbortError'
          ? 'O CRM demorou mais de 10 segundos para responder.'
          : 'Não foi possível conectar ao CRM. Tente novamente.',
      };
    } finally {
      clearTimeout(timeout);
    }
  }

  const badgeStatus = (s) =>
    s === 'Y' ? '<span class="op-badge ok">Ativa</span>' : '<span class="op-badge off">Inativa</span>';
  const badgePlano = (s) =>
    s === 'Y' ? '<span class="op-badge ok">Ativo</span>' : '<span class="op-badge off">Inativo</span>';

  // ---------- Carregar ----------
  async function load() {
    const [data, saude] = await Promise.all([
      api('/back-office/operadoras-planos/data'),
      api('/back-office/documentos/saude'),
    ]);
    if (saude.success) saudeDocumentos = saude;
    operadoras = data.success ? data.operadoras : [];
    if (selecionada) selecionada = operadoras.find((o) => o.id === selecionada.id) || null;
    renderMaster();
    renderDetail();
    renderSaude();
  }

  function renderSaude() {
    const el = $('op-doc-health');
    if (!el) return;
    const ultima = saudeDocumentos.sincronizado_em
      ? new Date(saudeDocumentos.sincronizado_em).toLocaleString('pt-BR')
      : 'ainda não sincronizado';
    el.classList.toggle('is-warning', !saudeDocumentos.sincronizado_em || saudeDocumentos.sincronizacao_erro);
    const filas = saudeDocumentos.filas || {};
    const filaTotal = Object.values(filas).filter(Number.isFinite).reduce((total, valor) => total + valor, 0);
    el.textContent = `${saudeDocumentos.pendentes || 0} documento(s) em processamento · ${filaTotal} job(s) na fila · pastas: ${ultima}`;
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
            <span class="op-item-meta">${o.planos.length} plano${o.planos.length === 1 ? '' : 's'} · ${o.diretorio_documentos ? `Pasta: ${esc(o.diretorio_documentos)}` : 'Pasta não vinculada'}</span>
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

      <section class="op-rules" aria-labelledby="op-rules-title">
        <div class="op-rules-heading">
          <div>
            <h6 id="op-rules-title">Regras comerciais</h6>
            <p>Estas regras pertencem a esta operadora nesta empresa. O nome da operadora não altera o comportamento.</p>
          </div>
          <span class="op-folder-badge is-ready"><i class="ri-shield-check-line" aria-hidden="true"></i> Por empresa</span>
        </div>
        <form class="op-rules-form" id="op-rules-form" autocomplete="off">
          <div class="op-rules-field">
            <label for="op-rules-coparticipacao">Formato de coparticipação</label>
            <select class="op-input" id="op-rules-coparticipacao">
              <option value="SIM_NAO" ${o.coparticipacao_formato === 'SIM_NAO' ? 'selected' : ''}>Sim / Não</option>
              <option value="PARCIAL_COMPLETA" ${o.coparticipacao_formato === 'PARCIAL_COMPLETA' ? 'selected' : ''}>Parcial / Completa</option>
            </select>
          </div>
          <label class="op-check op-rules-check" for="op-rules-angariacao">
            <input type="checkbox" id="op-rules-angariacao" ${o.angariacao_padrao ? 'checked' : ''}>
            <span>Ativar angariação por padrão nas novas vendas</span>
          </label>
          <div class="op-rules-field">
            <label for="op-rules-iof">IOF sobre a mensalidade (%)</label>
            <input class="op-input" type="number" id="op-rules-iof" value="${esc(o.iof_percentual || 0)}" min="0" max="100" step="0.01" inputmode="decimal" aria-describedby="op-rules-iof-help">
            <small id="op-rules-iof-help">Use 0 para não aplicar IOF.</small>
          </div>
          <div class="op-rules-field">
            <label for="op-rules-color">Cor da marca</label>
            <div class="op-color-field">
              <input type="color" id="op-rules-color-picker" value="${esc(o.cor_marca || '#334155')}" aria-label="Selecionar cor da marca">
              <input class="op-input" type="text" id="op-rules-color" value="${esc(o.cor_marca || '#334155')}" maxlength="7" pattern="#[0-9A-Fa-f]{6}" placeholder="#334155">
            </div>
          </div>
          <div class="op-rules-field op-rules-logo-field">
            <label for="op-rules-logo">Logo no estudo comercial</label>
            <input class="op-input" type="text" id="op-rules-logo" value="${esc(o.logo_path || '')}" maxlength="500" placeholder="assets/img/logos/operadora.png" aria-describedby="op-rules-logo-help">
            <small id="op-rules-logo-help">Caminho em assets/ ou URL HTTPS. Deixe vazio para exibir apenas o nome.</small>
          </div>
          <div class="op-rules-field">
            <label for="op-rules-app-ios">Aplicativo para iPhone</label>
            <input class="op-input" type="url" id="op-rules-app-ios" value="${esc(o.app_ios_url || '')}" maxlength="500" placeholder="https://apps.apple.com/..." inputmode="url" aria-describedby="op-rules-app-ios-help">
            <small id="op-rules-app-ios-help">Link oficial exibido nas boas-vindas.</small>
          </div>
          <div class="op-rules-field">
            <label for="op-rules-app-android">Aplicativo para Android</label>
            <input class="op-input" type="url" id="op-rules-app-android" value="${esc(o.app_android_url || '')}" maxlength="500" placeholder="https://play.google.com/..." inputmode="url" aria-describedby="op-rules-app-android-help">
            <small id="op-rules-app-android-help">Use somente uma URL HTTPS oficial.</small>
          </div>
          <button type="submit" class="op-btn op-btn-primary" id="op-rules-save">Salvar regras</button>
          <p class="op-rules-feedback" id="op-rules-feedback" role="status" aria-live="polite"></p>
        </form>
      </section>

      <section class="op-directory" aria-labelledby="op-directory-title">
        <div class="op-directory-icon" aria-hidden="true"><i class="ri-folder-shield-2-line"></i></div>
        <div class="op-directory-content">
          <div class="op-directory-heading">
            <div>
              <h6 id="op-directory-title">Pasta de documentos</h6>
              <p>Vincule a operadora à pasta que já existe em <code>Y:\\EmAnalise</code>.</p>
            </div>
            <span class="op-folder-badge ${o.diretorio_documentos ? 'is-ready' : 'is-missing'}">
              <i class="${o.diretorio_documentos ? 'ri-checkbox-circle-line' : 'ri-error-warning-line'}" aria-hidden="true"></i>
              ${o.diretorio_documentos ? 'Configurada' : 'Não configurada'}
            </span>
          </div>
          <form class="op-directory-form" id="op-directory-form" autocomplete="off">
            <div class="op-directory-field">
              <label for="op-directory-name">Nome exato da pasta</label>
              <div class="op-directory-input-wrap">
                <span aria-hidden="true">Y:\\EmAnalise\\</span>
                <input type="text" id="op-directory-name" list="op-directory-options" value="${esc(o.diretorio_documentos || '')}" maxlength="120" required aria-describedby="op-directory-help">
                <datalist id="op-directory-options">${(saudeDocumentos.diretorios || []).map(nome => `<option value="${esc(nome)}"></option>`).join('')}</datalist>
              </div>
              <small id="op-directory-help">Use exatamente o nome da pasta existente, respeitando espaços e letras maiúsculas/minúsculas.</small>
              <p class="op-directory-feedback" id="op-directory-feedback" role="status" aria-live="polite"></p>
            </div>
            <button type="submit" class="op-btn op-btn-primary" id="op-directory-save">Salvar vínculo</button>
          </form>
        </div>
      </section>

      <form class="op-plano-form" id="op-plano-form" autocomplete="off">
        <label class="visually-hidden" for="op-plano-nome">Nome do plano</label>
        <input type="text" class="op-input op-input-grow" id="op-plano-nome" placeholder="Nome do plano" maxlength="120" required>
        <label class="visually-hidden" for="op-plano-acom">Acomodação</label>
        <select class="op-input" id="op-plano-acom">
          <option value="ENFERMARIA">Enfermaria</option>
          <option value="APARTAMENTO">Apartamento</option>
        </select>
        <label class="visually-hidden" for="op-plano-status">Status do plano</label>
        <select class="op-input" id="op-plano-status">
          <option value="Y">Ativo</option>
          <option value="N">Inativo</option>
        </select>
        <button type="submit" class="op-btn op-btn-primary">Adicionar plano</button>
      </form>

      <h6 class="op-plano-listtitle">Planos</h6>
      ${planosHtml}`;

    $('op-plano-form').addEventListener('submit', salvarPlano);
    $('op-rules-form').addEventListener('submit', salvarRegrasComerciais);
    $('op-rules-color-picker').addEventListener('input', (event) => { $('op-rules-color').value = event.target.value.toUpperCase(); });
    $('op-rules-color').addEventListener('input', (event) => {
      if (/^#[0-9A-Fa-f]{6}$/.test(event.target.value)) $('op-rules-color-picker').value = event.target.value;
    });
    $('op-directory-form').addEventListener('submit', salvarDiretorioDocumentos);
  }

  // ---------- Ações ----------
  async function salvarOperadora(e) {
    e.preventDefault();
    const nome = $('op-nome').value.trim();
    if (!nome) return;
    const json = await api('/back-office/createOperation', 'POST', {
      nome,
      status: $('op-status').value,
      coparticipacao_formato: $('op-coparticipacao-formato').value,
      angariacao_padrao: $('op-angariacao-padrao').checked,
    });
    if (json.success) {
      toast('Operadora cadastrada.');
      $('op-nome').value = '';
      $('op-coparticipacao-formato').value = 'SIM_NAO';
      $('op-angariacao-padrao').checked = false;
      $('op-form').hidden = true;
      await load();
      const nova = operadoras.find((o) => o.nome === nome.toUpperCase());
      if (nova) { selecionada = nova; renderMaster(); renderDetail(); }
    } else {
      toast(json.message || 'Erro ao cadastrar operadora.', 'err');
    }
  }

  async function salvarRegrasComerciais(e) {
    e.preventDefault();
    if (!selecionada) return;

    const button = $('op-rules-save');
    const feedback = $('op-rules-feedback');
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    button.textContent = 'Salvando…';
    feedback.className = 'op-rules-feedback';
    feedback.textContent = 'Salvando regras desta empresa…';

    const json = await api(`/back-office/operadoras/${selecionada.id}/regras-comerciais`, 'PATCH', {
      coparticipacao_formato: $('op-rules-coparticipacao').value,
      angariacao_padrao: $('op-rules-angariacao').checked,
      iof_percentual: Number.parseFloat($('op-rules-iof').value || '0'),
      cor_marca: $('op-rules-color').value.trim(),
      logo_path: $('op-rules-logo').value.trim() || null,
      app_ios_url: $('op-rules-app-ios').value.trim() || null,
      app_android_url: $('op-rules-app-android').value.trim() || null,
    });

    if (json.success) {
      feedback.classList.add('is-success');
      feedback.textContent = 'Regras salvas para esta operadora.';
      toast('Regras comerciais atualizadas.');
      await load();
    } else {
      feedback.classList.add('is-error');
      feedback.textContent = json.message || 'Não foi possível salvar as regras.';
      toast(json.message || 'Não foi possível salvar as regras.', 'err');
    }

    if (document.body.contains(button)) {
      button.disabled = false;
      button.removeAttribute('aria-busy');
      button.textContent = 'Salvar regras';
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

  async function salvarDiretorioDocumentos(e) {
    e.preventDefault();
    if (!selecionada) return;
    const input = $('op-directory-name');
    const button = $('op-directory-save');
    const feedback = $('op-directory-feedback');
    const diretorio = input.value.trim();
    if (!diretorio) return input.focus();

    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    button.textContent = 'Salvando…';
    feedback.className = 'op-directory-feedback';
    feedback.textContent = 'Conferindo a pasta no catálogo local…';
    const json = await api(`/back-office/operadoras/${selecionada.id}/diretorio-documentos`, 'PATCH', { diretorio_documentos: diretorio });
    if (json.success) {
      feedback.classList.add('is-success');
      feedback.textContent = 'Pasta encontrada e vinculada com sucesso.';
      toast('Pasta validada e vinculada.');
      await load();
    } else {
      feedback.classList.add('is-error');
      feedback.textContent = json.message || 'Não foi possível validar a pasta.';
      toast(json.message || 'Não foi possível validar a pasta.', 'err');
      input.focus();
    }
    if (document.body.contains(button)) {
      button.disabled = false;
      button.removeAttribute('aria-busy');
      button.textContent = 'Salvar vínculo';
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
