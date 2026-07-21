/**
 * Tela do contrato dividida em abas: Contrato · Portabilidade · Cancelamento.
 *
 * - Troca de abas (.pv-tab / .pv-pane).
 * - Move a seção editável de portabilidade (já existente no Blade) para a aba
 *   Portabilidade, preservando os modais/JS de edição.
 * - Renderiza os processos de cancelamento por titular (modalidade + fase),
 *   consumindo backoffice.processos.dados e gravando via backoffice.processos.cancelamento.
 */
(function () {
  'use strict';

  const root = document.querySelector('.pv-screen');
  if (!root) return;

  const vendaId = root.dataset.vendaId;
  const csrf = root.dataset.csrf;
  let canEdit = false;

  const esc = (s) =>
    String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  function toast(msg, type = 'ok') {
    const el = document.createElement('div');
    el.className = `pv-toast ${type}`;
    el.textContent = msg;
    document.body.appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    setTimeout(() => {
      el.classList.remove('show');
      setTimeout(() => el.remove(), 300);
    }, 2600);
  }

  async function api(url, method = 'GET', body = null) {
    const opts = {
      method,
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
    };
    if (body) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }
    const res = await fetch(url, opts);
    const json = await res.json().catch(() => ({ success: false, message: 'Resposta inválida.' }));
    return json;
  }

  // ---------- Abas ----------
  root.addEventListener('click', (e) => {
    const tab = e.target.closest('.pv-tab');
    if (!tab) return;
    root.querySelectorAll('.pv-tab').forEach((t) => t.classList.remove('active'));
    tab.classList.add('active');
    const key = tab.dataset.pane;
    root.querySelectorAll('.pv-pane').forEach((p) => p.classList.toggle('active', p.dataset.pane === key));
  });

  // ---------- Move a portabilidade para a aba dela ----------
  function moverPortabilidade() {
    const host = root.querySelector('.pv-portab-host');
    if (!host) return;

    const badge = document.getElementById('portabilidade-badge');
    const toggle = badge ? badge.closest('.pme-inline-toggle') : null;
    const lista = document.getElementById('portabilidades-container');

    if (toggle || lista) {
      const sec = document.createElement('div');
      sec.className = 'pv-portab-section';
      if (toggle) sec.appendChild(toggle);
      if (lista) sec.appendChild(lista);
      host.appendChild(sec);
    } else {
      host.innerHTML = '<div class="pv-empty">Nenhuma informação de portabilidade.</div>';
    }

    // A seção que sobrou na aba Contrato passa a ser só "Angariação".
    root.querySelectorAll('.pv-pane[data-pane="contrato"] .section-title').forEach((el) => {
      if (el.textContent.trim().toLowerCase().startsWith('angaria')) el.textContent = 'Angariação';
    });
  }

  // ---------- Cancelamento ----------
  let modalidades = {};
  let fases = [];

  async function carregarCancelamentos() {
    const host = root.querySelector('.pv-cancel-host');
    const data = await api(`/back-office/processos/${vendaId}`);
    if (!data.success) {
      host.innerHTML = `<div class="pv-empty">${esc(data.message || 'Erro ao carregar.')}</div>`;
      return;
    }
    canEdit = !!data.can_edit;
    modalidades = data.modalidades || {};
    fases = data.fases || [];
    renderCancelamentos(host, data.cancelamentos || []);
    atualizarContador(data.cancelamentos || []);
    renderCliente(data);
  }

  function setContador(nome, n, cor) {
    const el = root.querySelector(`.pv-tab-count[data-count="${nome}"]`);
    if (!el) return;
    if (n > 0) {
      el.textContent = n;
      el.className = `pv-tab-count has-value${cor ? ' ' + cor : ''}`;
    } else {
      el.className = 'pv-tab-count';
      el.textContent = '';
    }
  }

  // Cor semântica do status do contrato (verde = ativo, vermelho = perdido, âmbar = pendente).
  function statusClasse(status) {
    const s = String(status || '').toUpperCase();
    if (s.includes('IMPLANTADO') || s.includes('REGULARIZADO')) return 'st-ok';
    if (s.includes('ESTORNO') || s.includes('DECLINIO') || s.includes('CANCEL')) return 'st-perdido';
    if (s.includes('PENDENCIA')) return 'st-atencao';
    return 'st-andamento';
  }

  function campo(label, valor, extraClass = '') {
    if (valor === null || valor === undefined || valor === '') return '';
    return `<div class="pv-cli-field"><span class="pv-cli-label">${esc(label)}</span><span class="pv-cli-value ${extraClass}">${esc(valor)}</span></div>`;
  }

  function contratoCard(c) {
    return `<a class="pv-cli-card is-link" href="/back-office/abrir-contrato/${c.id}" target="_blank" title="Abrir contrato">
        <div class="pv-cli-head">
          <div class="pv-cli-id">
            <span class="pv-cli-op">${esc(c.operadora || 'Contrato')}</span>
            ${c.nome_plano ? `<span class="pv-cli-plano">${esc(c.nome_plano)}</span>` : ''}
          </div>
          <span class="pv-status ${statusClasse(c.status)}">${esc(c.status)}</span>
        </div>
        <div class="pv-cli-grid">
          ${campo('Valor do contrato', c.valor_contrato, 'mono destaque')}
          ${campo('Vidas', c.vidas)}
          ${campo('Vigência', c.data_vigencia)}
          ${campo('Implantado em', c.data_implantacao)}
        </div>
      </a>`;
  }

  function acessoCard(a, i) {
    const sid = `pv-cli-secret-${i}`;
    return `<div class="pv-cli-card">
        <div class="pv-cli-head">
          <div class="pv-cli-id">
            <span class="pv-cli-op">${esc(a.nome || 'Acesso')}</span>
            ${a.operadora ? `<span class="pv-chip">${esc(a.operadora)}</span>` : ''}
            ${a.tipo ? `<span class="pv-chip origem">${esc(a.tipo)}</span>` : ''}
          </div>
        </div>
        <div class="pv-cred">
          <div class="pv-cred-field">
            <span class="pv-cli-label">Login</span>
            <div class="pv-cred-valor">
              <span class="pv-cli-value mono">${esc(a.login || '—')}</span>
              ${a.login ? `<button type="button" class="pv-mini-btn" data-copy="${esc(a.login)}" title="Copiar login">copiar</button>` : ''}
            </div>
          </div>
          <div class="pv-cred-field">
            <span class="pv-cli-label">Senha</span>
            <div class="pv-cred-valor">
              <span class="pv-cli-value mono pv-secret" id="${sid}" data-secret="${esc(a.senha || '')}">••••••••</span>
              <button type="button" class="pv-mini-btn" data-reveal="${sid}">mostrar</button>
              ${a.senha ? `<button type="button" class="pv-mini-btn" data-copy="${esc(a.senha)}" title="Copiar senha">copiar</button>` : ''}
            </div>
          </div>
          <div class="pv-cred-field">
            <span class="pv-cli-label">Observação</span>
            <span class="pv-cli-value pv-obs-value ${a.observacao ? '' : 'is-empty'}">${esc(a.observacao || '—')}</span>
          </div>
        </div>
      </div>`;
  }

  function renderCliente(data) {
    const host = root.querySelector('.pv-cliente-host');
    if (!host) return;
    const contratos = data.contratos_anteriores || [];
    const acessos = data.acessos || [];
    setContador('cliente', contratos.length, contratos.length ? 'all-done' : '');

    const contratosHtml = contratos.length
      ? `<div class="pv-cli-grid-cards">${contratos.map(contratoCard).join('')}</div>`
      : '<div class="pv-empty">Nenhum outro contrato com este CNPJ — cliente novo.</div>';

    const acessosHtml = acessos.length
      ? `<div class="pv-cli-grid-cards">${acessos.map(acessoCard).join('')}</div>`
      : '<div class="pv-empty">Nenhum acesso encontrado para este CNPJ. <a href="/back-office/credenciais" target="_blank">Cadastrar acesso</a>.</div>';

    host.innerHTML = `
      <div class="pv-cliente-section">
        <div class="pv-cliente-header">
          <h6 class="pv-cliente-title">Histórico de contratos</h6>
          <span class="pv-cliente-count">${contratos.length}</span>
          <span class="pv-cliente-hint">outros contratos deste CNPJ</span>
        </div>
        ${contratosHtml}
      </div>
      <div class="pv-cliente-section">
        <div class="pv-cliente-header">
          <h6 class="pv-cliente-title">Acessos do cliente</h6>
          <span class="pv-cliente-count">${acessos.length}</span>
          <span class="pv-cliente-hint">senhas de portais ligadas a este CNPJ</span>
        </div>
        ${acessosHtml}
      </div>`;
  }

  function atualizarContador(lista) {
    const el = root.querySelector('.pv-tab-count[data-count="cancelamento"]');
    if (!el) return;
    const pend = lista.filter((c) => c.status !== 'CONCLUIDA').length;
    if (pend > 0) {
      el.textContent = pend;
      el.classList.add('has-value');
      el.classList.remove('all-done');
    } else if (lista.length > 0) {
      el.textContent = '✓';
      el.classList.add('has-value', 'all-done');
    } else {
      el.classList.remove('has-value', 'all-done');
      el.textContent = '';
    }
  }

  function stepperHtml(faseAtual) {
    const idxAtual = fases.findIndex((f) => f.value === faseAtual);
    return `<div class="pv-stepper">${fases
      .map((f, i) => {
        const cls = i < idxAtual ? 'done' : i === idxAtual ? 'current' : '';
        return `<div class="pv-step ${cls}"><span class="pv-step-dot">${i < idxAtual ? '✓' : i + 1}</span><span class="pv-step-label">${esc(f.label)}</span></div>`;
      })
      .join('<div class="pv-step-line"></div>')}</div>`;
  }

  function cancelCard(c) {
    const done = c.status === 'CONCLUIDA';
    const modalOpts =
      '<option value="">Modalidade…</option>' +
      Object.keys(modalidades)
        .map((k) => `<option value="${esc(k)}" ${c.modalidade === k ? 'selected' : ''}>${esc(modalidades[k])}</option>`)
        .join('');
    const faseOpts = fases
      .map((f) => `<option value="${esc(f.value)}" ${c.fase === f.value ? 'selected' : ''}>${esc(f.label)}</option>`)
      .join('');

    const controls = canEdit
      ? `<div class="pv-cancel-controls">
          <select class="pv-input pv-cancel-modalidade">${modalOpts}</select>
          <select class="pv-input pv-cancel-fase">${faseOpts}</select>
          <input type="text" class="pv-input pv-cancel-protocolo" placeholder="Protocolo" value="${esc(c.protocolo || '')}" maxlength="120">
          <button type="button" class="pv-btn pv-btn-primary pv-cancel-save">Salvar</button>
        </div>`
      : `<div class="pv-cancel-readonly">
          <span class="pv-chip">${esc(c.modalidade_label || 'Modalidade não definida')}</span>
          <span class="pv-chip">Fase: ${esc(c.fase_label)}</span>
          ${c.protocolo ? `<span class="pv-chip">Protocolo: ${esc(c.protocolo)}</span>` : ''}
        </div>`;

    return `<div class="pv-cancel-card ${done ? 'is-done' : ''}" data-id="${c.id}">
        <div class="pv-cancel-head">
          <div>
            <div class="pv-cancel-title">${esc(c.titular ? c.titular.nome : c.titulo)}</div>
            <div class="pv-cancel-sub">
              ${c.operadora_anterior ? `<span class="pv-chip warn">${esc(c.operadora_anterior)}</span>` : ''}
              <span class="pv-chip origem">${esc(c.origem || '')}</span>
              ${done ? '<span class="pv-chip ok">Concluído</span>' : ''}
            </div>
          </div>
        </div>
        ${stepperHtml(c.fase)}
        ${controls}
      </div>`;
  }

  function renderCancelamentos(host, lista) {
    if (!lista.length) {
      host.innerHTML = `<div class="pv-empty">
          <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
          <div>Nenhum cancelamento sinalizado para este contrato.</div>
          <small>O vendedor marca o cancelamento por titular no cadastro da proposta.</small>
        </div>`;
      return;
    }
    host.innerHTML = `<div class="pv-cancel-list">${lista.map(cancelCard).join('')}</div>`;
  }

  root.addEventListener('click', async (e) => {
    const copy = e.target.closest('[data-copy]');
    if (copy) {
      try {
        await navigator.clipboard.writeText(copy.dataset.copy);
        const original = copy.textContent;
        copy.textContent = 'copiado ✓';
        copy.classList.add('is-copied');
        setTimeout(() => { copy.textContent = original; copy.classList.remove('is-copied'); }, 1600);
      } catch {
        toast('Não foi possível copiar.', 'err');
      }
      return;
    }
    const reveal = e.target.closest('[data-reveal]');
    if (reveal) {
      const secret = document.getElementById(reveal.dataset.reveal);
      const shown = secret.dataset.shown === '1';
      secret.textContent = shown ? '••••••••' : (secret.dataset.secret || '(vazio)');
      secret.dataset.shown = shown ? '0' : '1';
      reveal.textContent = shown ? 'mostrar' : 'ocultar';
      return;
    }
    const btn = e.target.closest('.pv-cancel-save');
    if (!btn) return;
    const card = btn.closest('.pv-cancel-card');
    const id = card.dataset.id;
    btn.disabled = true;
    const body = {
      modalidade: card.querySelector('.pv-cancel-modalidade').value || null,
      fase: card.querySelector('.pv-cancel-fase').value,
      protocolo: card.querySelector('.pv-cancel-protocolo').value.trim() || null,
    };
    const json = await api(`/back-office/processos/cancelamento/${id}`, 'PATCH', body);
    if (json.success) {
      toast('Cancelamento atualizado.');
      await carregarCancelamentos();
    } else {
      toast(json.message || 'Erro ao salvar.', 'err');
      btn.disabled = false;
    }
  });

  // ---------- Fase da portabilidade (seletor nos cards da aba Portabilidade) ----------
  const FASE_PORT_CLASSE = {
    REUNINDO_DOCUMENTOS: 'st-atencao',
    ENVIADA_ANALISE: 'st-andamento',
    CONCLUIDA: 'st-ok',
    NEGADA: 'st-perdido',
  };

  root.addEventListener('change', async (e) => {
    const sel = e.target.closest('.pv-port-fase');
    if (!sel) return;

    const opcao = sel.options[sel.selectedIndex];
    const anterior = sel.dataset.atual || sel.querySelector('option[selected]')?.value || 'REUNINDO_DOCUMENTOS';

    if (opcao.dataset.final === '1' && !confirm(`Marcar esta portabilidade como "${opcao.textContent.trim()}"? Isso encerra o processo.`)) {
      sel.value = anterior;
      return;
    }

    sel.disabled = true;
    const json = await api(`/back-office/processos/portabilidade/${sel.dataset.portId}/fase`, 'PATCH', { fase: sel.value });
    sel.disabled = false;

    if (json.success) {
      sel.dataset.atual = json.fase.value;
      const badge = document.querySelector(`[data-port-badge="${sel.dataset.portId}"]`);
      if (badge) {
        badge.textContent = json.fase.label;
        badge.className = `pv-status ${FASE_PORT_CLASSE[json.fase.value] || 'st-atencao'}`;
        badge.setAttribute('data-port-badge', sel.dataset.portId);
      }
      toast('Fase da portabilidade atualizada.');
    } else {
      sel.value = anterior;
      toast(json.message || 'Erro ao atualizar fase.', 'err');
    }
  });

  // ---------- Init ----------
  moverPortabilidade();
  carregarCancelamentos();
})();
