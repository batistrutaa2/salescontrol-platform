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
    renderEmailsCriados(data);
    renderSwitcher(data);
  }

  // ---------- Switcher de contratos do cliente (mesmo CNPJ/CPF) ----------
  function formatarDoc(doc) {
    const d = String(doc || '').replace(/\D/g, '');
    if (d.length === 14) return d.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    if (d.length === 11) return d.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    return doc || '';
  }

  function renderSwitcher(data) {
    const hostEl = root.querySelector('.pv-switcher-host');
    if (!hostEl) return;

    const anteriores = data.contratos_anteriores || [];
    if (!anteriores.length) { hostEl.innerHTML = ''; return; } // cliente com um só contrato

    const atualId = Number(vendaId);
    const todos = [
      { id: atualId, operadora: data.venda.operadora, status: data.venda.status, data_vigencia: data.venda.data_vigencia, atual: true },
      ...anteriores.map((c) => ({ id: c.id, operadora: c.operadora, status: c.status, data_vigencia: c.data_vigencia, atual: false })),
    ];

    const rotulo = (c) =>
      `${c.atual ? '● ' : ''}${c.operadora || 'Contrato'}${c.data_vigencia ? ' · ' + c.data_vigencia : ''} · ${c.status}${c.atual ? ' (atual)' : ''}`;

    const opcoes = todos
      .map((c) => `<option value="${c.id}" ${c.atual ? 'selected' : ''}>${esc(rotulo(c))}</option>`)
      .join('');

    hostEl.innerHTML = `
      <div class="pv-switcher">
        <div class="pv-switcher-info">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M3 9V7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2"/></svg>
          <span>Este cliente tem <strong>${todos.length}</strong> contratos${data.venda.cpf_cnpj ? ` · ${esc(formatarDoc(data.venda.cpf_cnpj))}` : ''}</span>
        </div>
        <label class="pv-switcher-select">
          <span>Ir para</span>
          <select id="pv-switcher">${opcoes}</select>
        </label>
      </div>`;

    root.querySelector('#pv-switcher').addEventListener('change', (e) => {
      const alvo = Number(e.target.value);
      if (alvo && alvo !== atualId) window.location.href = `/back-office/abrir-contrato/${alvo}`;
    });
  }

  // ---------- Aba E-mails criados (contas criadas para o cliente) ----------
  function emailCriadoCard(e, i) {
    const sid = `pv-email-secret-${i}`;
    const acoes = canEdit
      ? `<div class="pv-email-acoes">
          <button type="button" class="pv-mini-btn" data-email-editar="${e.id}">editar</button>
          <button type="button" class="pv-mini-btn pv-mini-danger" data-email-excluir="${e.id}">excluir</button>
        </div>`
      : '';

    return `<div class="pv-cli-card" data-email-card="${e.id}">
        <div class="pv-cli-head">
          <div class="pv-cli-id">
            <span class="pv-cli-op mono">${esc(e.email)}</span>
            ${e.titular ? `<span class="pv-chip titular">${esc(e.titular)}</span>` : ''}
          </div>
          ${acoes}
        </div>
        <div class="pv-cred">
          <div class="pv-cred-field">
            <span class="pv-cli-label">E-mail</span>
            <div class="pv-cred-valor">
              <span class="pv-cli-value mono">${esc(e.email)}</span>
              <button type="button" class="pv-mini-btn" data-copy="${esc(e.email)}" title="Copiar e-mail">copiar</button>
            </div>
          </div>
          <div class="pv-cred-field">
            <span class="pv-cli-label">Senha</span>
            <div class="pv-cred-valor">
              <span class="pv-cli-value mono pv-secret" id="${sid}" data-secret="${esc(e.senha)}">••••••••</span>
              <button type="button" class="pv-mini-btn" data-reveal="${sid}">mostrar</button>
              <button type="button" class="pv-mini-btn" data-copy="${esc(e.senha)}" title="Copiar senha">copiar</button>
            </div>
          </div>
          <div class="pv-cred-field">
            <span class="pv-cli-label">Observação</span>
            <span class="pv-cli-value pv-obs-value ${e.observacao ? '' : 'is-empty'}">${esc(e.observacao || '—')}</span>
          </div>
        </div>
        <div class="pv-acesso-meta">Criado por ${esc(e.criado_por || '—')}${e.criado_em ? ` em ${esc(e.criado_em)}` : ''}</div>
      </div>`;
  }

  function renderEmailsCriados(data) {
    const host = root.querySelector('.pv-emails-host');
    if (!host) return;
    const emails = data.emails_criados || [];
    setContador('emails', emails.length, 'all-done');

    const form = canEdit
      ? `<form class="pv-email-form" id="pv-email-form" data-editando="">
          <input type="email" class="pv-input pv-input-grow" id="pv-email-endereco" placeholder="endereco@provedor.com" required maxlength="255">
          <input type="text" class="pv-input" id="pv-email-senha" placeholder="Senha" required maxlength="255">
          <select class="pv-input" id="pv-email-titular">
            <option value="">Sem titular específico</option>
            ${(data.titulares || []).map((t) => `<option value="${t.id}">${esc(t.nome)}</option>`).join('')}
          </select>
          <input type="text" class="pv-input pv-input-grow" id="pv-email-obs" placeholder="Observação (opcional)" maxlength="500">
          <button type="submit" class="pv-btn pv-btn-primary" id="pv-email-salvar">Salvar e-mail</button>
          <button type="button" class="pv-btn pv-btn-ghost" id="pv-email-cancelar" style="display:none;">Cancelar edição</button>
        </form>`
      : '';

    const lista = emails.length
      ? `<div class="pv-cli-grid-cards">${emails.map(emailCriadoCard).join('')}</div>`
      : `<div class="pv-empty">Nenhum e-mail criado para este cliente ainda.${canEdit ? '<small>Use o formulário acima para registrar o primeiro.</small>' : ''}</div>`;

    host.innerHTML = `${form}${lista}`;
    host.dataset.emails = JSON.stringify(emails);
  }

  function preencherFormEmail(e) {
    const form = root.querySelector('#pv-email-form');
    if (!form) return;
    form.dataset.editando = e ? e.id : '';
    root.querySelector('#pv-email-endereco').value = e ? e.email : '';
    root.querySelector('#pv-email-senha').value = e ? e.senha : '';
    root.querySelector('#pv-email-titular').value = e && e.titular_id ? e.titular_id : '';
    root.querySelector('#pv-email-obs').value = e ? (e.observacao || '') : '';
    root.querySelector('#pv-email-cancelar').style.display = e ? 'inline-flex' : 'none';
    root.querySelector('#pv-email-salvar').textContent = e ? 'Atualizar e-mail' : 'Salvar e-mail';
    if (e) form.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  async function salvarEmailCriado(ev) {
    ev.preventDefault();
    const form = ev.target;
    const editando = form.dataset.editando;
    const body = {
      email: root.querySelector('#pv-email-endereco').value.trim(),
      senha: root.querySelector('#pv-email-senha').value,
      titular_id: root.querySelector('#pv-email-titular').value || null,
      observacao: root.querySelector('#pv-email-obs').value.trim() || null,
    };
    const json = editando
      ? await api(`/back-office/processos/emails/${editando}`, 'PATCH', body)
      : await api(`/back-office/processos/${vendaId}/emails`, 'POST', body);

    if (json.success) {
      toast(json.message || 'E-mail salvo.');
      await carregarCancelamentos();
    } else {
      const detalhe = json.errors ? Object.values(json.errors).flat()[0] : null;
      toast(detalhe || json.message || 'Erro ao salvar e-mail.', 'err');
    }
  }

  async function excluirEmailCriado(id) {
    if (!confirm('Remover este e-mail do contrato?')) return;
    const json = await api(`/back-office/processos/emails/${id}`, 'DELETE');
    toast(json.message || (json.success ? 'Removido.' : 'Erro.'), json.success ? 'ok' : 'err');
    if (json.success) await carregarCancelamentos();
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
    const inativo = String(a.status || 'Y').toUpperCase() === 'N';
    return `<div class="pv-cli-card${inativo ? ' is-inativo' : ''}">
        <div class="pv-cli-head">
          <div class="pv-cli-id">
            <span class="pv-cli-op">${esc(a.nome || 'Acesso')}</span>
            ${a.operadora ? `<span class="pv-chip">${esc(a.operadora)}</span>` : ''}
            ${a.tipo ? `<span class="pv-chip origem">${esc(a.tipo)}</span>` : ''}
            ${inativo ? '<span class="pv-chip inativo">Inativo</span>' : ''}
          </div>
          <button type="button" class="pv-mini-btn pv-cli-editar" data-editar-acesso="${a.id}" title="Editar este acesso">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
            editar
          </button>
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
    acessosCache = acessos;
    setContador('cliente', contratos.length, contratos.length ? 'all-done' : '');

    const contratosHtml = contratos.length
      ? `<div class="pv-cli-grid-cards">${contratos.map(contratoCard).join('')}</div>`
      : '<div class="pv-empty">Nenhum outro contrato com este CNPJ — cliente novo.</div>';

    const acessosHtml = acessos.length
      ? `<div class="pv-cli-grid-cards">${acessos.map(acessoCard).join('')}</div>`
      : '<div class="pv-empty">Nenhum acesso cadastrado para este cliente. <button type="button" class="pv-empty-link" data-add-acesso>Cadastrar acesso</button>.</div>';

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
          <button type="button" class="pv-add-acesso" data-add-acesso>
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Cadastrar acesso
          </button>
        </div>
        ${acessosHtml}
      </div>`;
  }

  // ---------- Acessos do cliente (login/senha): cadastro e edição ----------
  // A mesma modal serve aos dois fluxos. Cadastrar aceita vários acessos de uma
  // vez; editar mexe em um registro só, então o repetidor sai de cena.
  const acModalEl = document.getElementById('pvAcessoModal');
  const acForm = document.getElementById('pvAcessoForm');
  const acBox = document.getElementById('pvac-acessos');
  const acTpl = document.getElementById('pvac-acesso-tpl');
  const acCount = document.getElementById('pvac-acessos-count');
  let acessosCache = [];
  let acEditandoId = null;

  function acAtualizarCount() {
    const linhas = acBox ? acBox.querySelectorAll('.pvac-acesso') : [];
    if (acCount) acCount.textContent = linhas.length ? `(${linhas.length})` : '';
    linhas.forEach((l) => {
      const rem = l.querySelector('.pvac-remove');
      if (rem) rem.style.visibility = linhas.length > 1 ? 'visible' : 'hidden';
    });
  }

  function acAddRow(foco = true) {
    if (!acTpl || !acBox) return;
    const node = acTpl.content.firstElementChild.cloneNode(true);
    acBox.appendChild(node);
    acAtualizarCount();
    if (foco) node.querySelector('.pvac-acesso-nome')?.focus();
  }

  function acReset() {
    acForm?.reset();
    if (acBox) acBox.innerHTML = '';
    acAddRow(false);
  }

  // Alterna a modal entre cadastrar (vários) e editar (um registro).
  function acModo(edicao) {
    acEditandoId = edicao ? edicao.id : null;

    const titulo = acModalEl.querySelector('.pvac-title');
    const sub = acModalEl.querySelector('.pvac-sub');
    const btnSalvar = document.getElementById('pvac-salvar');
    const btnAdd = document.getElementById('pvac-add');
    const cabecalho = acModalEl.querySelector('.pvac-acessos-head');

    if (titulo) titulo.textContent = edicao ? 'Editar acesso' : 'Cadastrar acesso';
    if (sub) {
      sub.textContent = edicao
        ? 'As alterações ficam registradas no histórico da credencial'
        : 'Um ou vários logins/senhas de portais deste cliente';
    }
    if (btnSalvar) btnSalvar.textContent = edicao ? 'Salvar alterações' : 'Salvar acessos';
    if (btnAdd) btnAdd.classList.toggle('d-none', !!edicao);
    if (cabecalho) cabecalho.classList.toggle('d-none', !!edicao);
  }

  function acAbrir() {
    if (!acModalEl || !window.bootstrap) return;
    acReset();
    acModo(null);
    bootstrap.Modal.getOrCreateInstance(acModalEl).show();
  }

  function acAbrirEdicao(id) {
    if (!acModalEl || !window.bootstrap) return;

    const acesso = acessosCache.find((a) => String(a.id) === String(id));
    if (!acesso) {
      toast('Acesso não encontrado. Recarregue a página.', 'err');
      return;
    }

    acReset();
    acModo(acesso);

    document.getElementById('pvac-operadora').value = acesso.operadora_id || '';
    document.getElementById('pvac-tipo').value = acesso.tipo || '';
    document.getElementById('pvac-status').value = (acesso.status || 'Y').toUpperCase();
    document.getElementById('pvac-observacao').value = acesso.observacao || '';

    const linha = acBox.querySelector('.pvac-acesso');
    linha.querySelector('.pvac-acesso-nome').value = acesso.nome || '';
    linha.querySelector('.pvac-acesso-login').value = acesso.login || '';
    // Senha vem preenchida: salvar sem tocar nela não pode apagar a atual.
    linha.querySelector('.pvac-acesso-senha').value = acesso.senha || '';

    bootstrap.Modal.getOrCreateInstance(acModalEl).show();
    linha.querySelector('.pvac-acesso-nome').focus();
  }

  function acLerAcessos() {
    if (!acBox) return [];
    return Array.from(acBox.querySelectorAll('.pvac-acesso')).map((r) => ({
      nome: (r.querySelector('.pvac-acesso-nome')?.value || '').trim(),
      login: (r.querySelector('.pvac-acesso-login')?.value || '').trim(),
      senha: r.querySelector('.pvac-acesso-senha')?.value || '',
    }));
  }

  function wireAcessoModal() {
    if (!acModalEl) return;

    document.getElementById('pvac-add')?.addEventListener('click', () => acAddRow());

    acBox?.addEventListener('click', (e) => {
      const rem = e.target.closest('.pvac-remove');
      if (rem) {
        const linhas = acBox.querySelectorAll('.pvac-acesso');
        if (linhas.length > 1) rem.closest('.pvac-acesso').remove();
        else rem.closest('.pvac-acesso').querySelectorAll('input,textarea').forEach((i) => (i.value = ''));
        acAtualizarCount();
        return;
      }
      const eye = e.target.closest('.pvac-eye');
      if (eye) {
        const inp = eye.closest('.pvac-senha-wrap').querySelector('.pvac-acesso-senha');
        inp.type = inp.type === 'password' ? 'text' : 'password';
      }
    });

    acForm?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const acessos = acLerAcessos().filter((a) => a.nome);
      if (!acessos.length) {
        toast('Informe ao menos um nome/rótulo.', 'err');
        return;
      }

      const contexto = {
        operadora_id: document.getElementById('pvac-operadora')?.value || null,
        tipo: (document.getElementById('pvac-tipo')?.value || '').trim() || null,
        status: document.getElementById('pvac-status')?.value || 'Y',
        observacao: (document.getElementById('pvac-observacao')?.value || '').trim() || null,
      };

      const btn = document.getElementById('pvac-salvar');
      if (btn) btn.disabled = true;

      const json = acEditandoId
        ? await api(`/back-office/credenciais/${acEditandoId}`, 'PUT', {
            ...contexto,
            nome: acessos[0].nome,
            login: acessos[0].login || null,
            senha: acessos[0].senha || null,
          })
        : await api('/back-office/credenciais/lote', 'POST', {
            venda_id: Number(vendaId),
            ...contexto,
            acessos,
          });

      if (btn) btn.disabled = false;

      if (json.success) {
        bootstrap.Modal.getInstance(acModalEl)?.hide();
        toast(json.message || (acEditandoId ? 'Acesso atualizado!' : 'Acessos cadastrados!'));
        await carregarCancelamentos();
      } else {
        toast(json.message || 'Não foi possível salvar os acessos.', 'err');
      }
    });

    // Sai do modo edição ao fechar, para o próximo "Cadastrar" abrir limpo.
    acModalEl.addEventListener('hidden.bs.modal', () => acModo(null));
  }

  // Abre o modal a partir dos botões da aba Cliente (conteúdo é re-renderizado).
  root.addEventListener('click', (e) => {
    if (e.target.closest('[data-add-acesso]')) {
      acAbrir();
      return;
    }
    const editar = e.target.closest('[data-editar-acesso]');
    if (editar) acAbrirEdicao(editar.dataset.editarAcesso);
  });

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

  // ---------- Eventos da aba E-mails criados ----------
  root.addEventListener('submit', (e) => {
    if (e.target.id === 'pv-email-form') salvarEmailCriado(e);
  });

  root.addEventListener('click', (e) => {
    const editar = e.target.closest('[data-email-editar]');
    if (editar) {
      const emails = JSON.parse(root.querySelector('.pv-emails-host').dataset.emails || '[]');
      preencherFormEmail(emails.find((m) => String(m.id) === editar.dataset.emailEditar) || null);
      return;
    }
    const excluir = e.target.closest('[data-email-excluir]');
    if (excluir) {
      excluirEmailCriado(excluir.dataset.emailExcluir);
      return;
    }
    if (e.target.closest('#pv-email-cancelar')) preencherFormEmail(null);
  });

  // Boas-vindas enviada → o cabeçalho vira selo com a data e o autor do envio.
  document.addEventListener('boasVindasEnviada', (e) => {
    const wrapper = document.getElementById('pv-bv');
    if (!wrapper) return;

    const { enviadoEm, enviadoPor } = e.detail || {};

    if (enviadoEm) {
      const quando = document.getElementById('pv-bv-quando');
      if (quando) quando.textContent = enviadoEm;
    }

    if (enviadoPor) {
      const quem = document.getElementById('pv-bv-quem');
      if (quem) {
        quem.textContent = enviadoPor;
        quem.closest('.pv-bv-selo-por')?.classList.remove('d-none');
      }
    }

    wrapper.dataset.enviado = '1';
  });

  // ---------- Init ----------
  moverPortabilidade();
  wireAcessoModal();
  carregarCancelamentos();
})();
