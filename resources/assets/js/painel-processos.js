/**
 * Painel operacional de processos (visão do gestor) — abas por trilha.
 * Abas: Cancelamento · Portabilidade (cada uma com raias de urgência
 * Atrasado/Vencendo/Aguardando implantação/Em dia) · Concluídos (baixados no mês).
 * O prazo conta a partir da implantação (ver ProcessoVendaRepository::linhaProcesso).
 * Consome backoffice.painelProcessos.data / .atribuir / .faseCancelamento / .fasePortabilidade.
 */
(function () {
  'use strict';

  const root = document.getElementById('painel-processos');
  if (!root) return;

  const csrf = root.dataset.csrf;
  const cfg = window.__painel || { responsaveis: [], tipos: {} };
  let filtros = { responsavel_id: '', busca: '' };
  let urgFiltro = '';
  let abaAtiva = 'cancelamentos';
  let fasesCancel = [];
  let fasesPortab = [];
  let ultimaFila = [];
  let ultimosConcluidos = [];
  const colapsadas = new Set(['em_dia']); // "Em dia" começa recolhida (foco no que precisa de ação)

  const LANES = [
    { key: 'atrasado', label: 'Atrasado', hint: 'passaram do prazo', cls: 'lane-danger' },
    { key: 'vencendo', label: 'Vencendo', hint: 'vencem em até 7 dias', cls: 'lane-warning' },
    { key: 'aguardando_implantacao', label: 'Aguardando implantação', hint: 'travados até a apólice implantar', cls: 'lane-muted' },
    { key: 'em_dia', label: 'Em dia', hint: 'dentro do prazo, com folga', cls: 'lane-success' },
  ];
  const URG_LABEL = { atrasado: 'Atrasados', vencendo: 'Vencendo', aguardando_implantacao: 'Aguardando implantação', em_dia: 'Em dia' };

  const esc = (s) =>
    String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const $ = (id) => document.getElementById(id);

  function toast(msg, type = 'ok') {
    const el = document.createElement('div');
    el.className = `pp-toast ${type}`;
    el.textContent = msg;
    document.body.appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 300); }, 2400);
  }

  const ICONS = {
    check: '<svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
    alert: '<svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
  };

  /** Confirmação própria do painel (substitui o confirm() nativo). Resolve boolean. */
  function ppConfirm({ title, message, confirmLabel = 'Confirmar', tone = 'primary', icon = 'check' }) {
    return new Promise((resolve) => {
      const overlay = document.createElement('div');
      overlay.className = 'pp-modal-overlay';
      overlay.innerHTML = `
        <div class="pp-modal tone-${tone}" role="dialog" aria-modal="true" aria-labelledby="pp-modal-title">
          <div class="pp-modal-icon">${ICONS[icon] || ICONS.check}</div>
          <h3 class="pp-modal-title" id="pp-modal-title">${esc(title)}</h3>
          <p class="pp-modal-text">${message}</p>
          <div class="pp-modal-actions">
            <button type="button" class="pp-btn pp-btn-ghost" data-cancel>Cancelar</button>
            <button type="button" class="pp-btn pp-btn-solid" data-ok>${esc(confirmLabel)}</button>
          </div>
        </div>`;
      document.body.appendChild(overlay);
      requestAnimationFrame(() => overlay.classList.add('show'));

      const fechar = (val) => {
        document.removeEventListener('keydown', onKey);
        overlay.classList.remove('show');
        setTimeout(() => overlay.remove(), 200);
        resolve(val);
      };
      const onKey = (e) => {
        if (e.key === 'Escape') fechar(false);
        else if (e.key === 'Enter') fechar(true);
      };
      document.addEventListener('keydown', onKey);
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay || e.target.closest('[data-cancel]')) fechar(false);
        else if (e.target.closest('[data-ok]')) fechar(true);
      });
      overlay.querySelector('[data-ok]').focus();
    });
  }

  async function api(url, method = 'GET', body = null) {
    const opts = { method, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' } };
    if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
    const res = await fetch(url, opts);
    return res.json().catch(() => ({ success: false, message: 'Resposta inválida.' }));
  }

  function opcoesResponsavel(selecionado) {
    const sem = `<option value="" ${!selecionado ? 'selected' : ''}>— Sem responsável —</option>`;
    return sem + cfg.responsaveis
      .map((r) => `<option value="${r.id}" ${Number(selecionado) === r.id ? 'selected' : ''}>${esc(r.name)}</option>`)
      .join('');
  }

  // ---- Carregar ----
  async function load() {
    const params = new URLSearchParams();
    Object.entries(filtros).forEach(([k, v]) => { if (v !== '' && v !== null) params.append(k, v); });

    $('pp-content').innerHTML = '<div class="pp-board-loading">Carregando…</div>';

    const data = await api(`/back-office/painel-processos/data?${params.toString()}`);
    if (!data.success) {
      $('pp-content').innerHTML = `<div class="pp-board-erro">${esc(data.message || 'Erro ao carregar.')}</div>`;
      return;
    }

    fasesCancel = data.fases_cancelamento || [];
    fasesPortab = data.fases_portabilidade || [];
    ultimaFila = data.fila || [];
    ultimosConcluidos = data.concluidos || [];
    renderKpis(data.kpis);
    atualizarContadoresAbas();
    renderConteudo();
  }

  function renderKpis(k) {
    $('kpi-atrasados').textContent = k.atrasados;
    $('kpi-vencendo').textContent = k.vencendo;
    $('kpi-aguardando').textContent = k.aguardando_implantacao;
    $('kpi-concluidos').textContent = k.concluidos_mes;
  }

  function atualizarContadoresAbas() {
    $('tabcount-cancelamentos').textContent = ultimaFila.filter((p) => p.grupo === 'cancelamentos').length;
    $('tabcount-portabilidade').textContent = ultimaFila.filter((p) => p.grupo === 'portabilidade').length;
    $('tabcount-concluidos').textContent = ultimosConcluidos.length;
  }

  // ---- Conteúdo por aba ----
  function renderConteudo() {
    if (abaAtiva === 'concluidos') return renderConcluidos(ultimosConcluidos);
    renderLanes(ultimaFila.filter((p) => p.grupo === abaAtiva));
  }

  function renderLanes(itens) {
    const host = $('pp-content');
    const grupos = { atrasado: [], vencendo: [], aguardando_implantacao: [], em_dia: [] };
    itens.forEach((p) => (grupos[p.urgencia] || (grupos[p.urgencia] = [])).push(p));

    const lanes = urgFiltro ? LANES.filter((l) => l.key === urgFiltro) : LANES;
    host.innerHTML = lanes.map((l) => laneHtml(l, grupos[l.key] || [])).join('')
      || '<div class="pp-board-loading">Nenhum processo em aberto nesta trilha. 🎉</div>';
  }

  function laneHtml(l, itens) {
    const collapsed = colapsadas.has(l.key) && !urgFiltro;
    const corpo = itens.length
      ? itens.map(row).join('')
      : '<div class="pp-lane-empty">nada nesta faixa</div>';

    return `<section class="pp-lane ${l.cls} ${collapsed ? 'is-collapsed' : ''}" data-lane="${esc(l.key)}">
        <button type="button" class="pp-lane-head" data-lane-toggle="${esc(l.key)}">
          <span class="pp-lane-dot"></span>
          <span class="pp-lane-name">${esc(l.label)}</span>
          <span class="pp-lane-count">${itens.length}</span>
          <span class="pp-lane-hint">${esc(l.hint)}</span>
          <span class="pp-lane-chev" aria-hidden="true">▾</span>
        </button>
        <div class="pp-lane-body">${corpo}</div>
      </section>`;
  }

  function badgePrazo(p) {
    if (p.urgencia === 'atrasado') return `<span class="pp-badge danger">Venceu há ${p.dias_atraso}d</span>`;
    if (p.urgencia === 'vencendo') return `<span class="pp-badge warn">Vence em ${p.dias_para_vencer}d</span>`;
    return `<span class="pp-badge ok">Vence ${esc(p.vence_em || '—')}</span>`;
  }

  function row(p) {
    const link = p.venda_id ? `/back-office/abrir-contrato/${p.venda_id}` : '#';
    const ehPortab = p.fonte === 'portabilidade';
    const fases = ehPortab ? fasesPortab : fasesCancel;
    const faseCls = ehPortab ? 'pp-fase-portab' : 'pp-fase-cancel';
    const faseSel = `<select class="pp-input pp-mini ${faseCls}" data-id="${p.id}" title="Avançar fase">
        ${fases.map((f) => `<option value="${esc(f.value)}" ${p.fase_valor === f.value ? 'selected' : ''}>${esc(f.label)}</option>`).join('')}
      </select>`;

    // Situação: no portão explica o bloqueio; senão o badge de prazo.
    const situacao = p.bloqueado
      ? `<span class="pp-when muted" title="Contrato ainda não implantado">⏸ ${esc(p.situacao_contrato)} · há ${p.dias_bloqueado}d</span>`
      : badgePrazo(p);

    return `<div class="pp-row urg-${esc(p.urgencia)}">
        <div class="pp-row-main">
          <a href="${link}" target="_blank" class="pp-row-contrato">${esc(p.contrato)}</a>
          <span class="pp-row-pessoa">${esc(p.quem || '—')}${p.qtd > 1 ? ` <span class="pp-qtd" title="${p.qtd} titulares neste contrato — a ação vale para todos">×${p.qtd}</span>` : ''}</span>
        </div>
        <span class="pp-chip pp-row-fase">${esc(p.fase || '—')}</span>
        <span class="pp-row-when">${situacao}</span>
        <select class="pp-input pp-mini pp-assign" data-fonte="${esc(p.fonte)}" data-id="${p.id}" title="Responsável">${opcoesResponsavel(p.responsavel_id)}</select>
        ${faseSel}
      </div>`;
  }

  // ---- Concluídos (somente leitura) ----
  function renderConcluidos(lista) {
    const host = $('pp-content');
    if (!lista.length) {
      host.innerHTML = '<div class="pp-board-loading">Nenhum processo concluído neste mês.</div>';
      return;
    }
    host.innerHTML = `<section class="pp-lane lane-success">
        <div class="pp-lane-head is-static">
          <span class="pp-lane-dot"></span>
          <span class="pp-lane-name">Concluídos no mês</span>
          <span class="pp-lane-count">${lista.length}</span>
          <span class="pp-lane-hint">cancelamentos e portabilidades baixados neste mês</span>
        </div>
        <div class="pp-lane-body">${lista.map(concluidoRow).join('')}</div>
      </section>`;
  }

  function concluidoRow(c) {
    const link = c.venda_id ? `/back-office/abrir-contrato/${c.venda_id}` : '#';
    const ehPortab = c.grupo === 'portabilidade';
    const wsCls = ehPortab ? 'ws-portab' : 'ws-cancel';
    const wsLabel = ehPortab ? 'Portabilidade' : 'Cancelamento';
    const desfCls = c.desfecho === 'Negada' ? 'danger' : 'ok';
    const meta = `${esc(c.concluido_em || '—')}${c.por ? ' · por ' + esc(c.por) : ''}`;

    return `<div class="pp-row is-done">
        <span class="pp-ws ${wsCls}">${esc(wsLabel)}</span>
        <div class="pp-row-main">
          <a href="${link}" target="_blank" class="pp-row-contrato">${esc(c.contrato)}</a>
          <span class="pp-row-pessoa">${esc(c.quem || '—')}</span>
        </div>
        <span class="pp-badge ${desfCls}">${esc(c.desfecho)}</span>
        <span class="pp-row-when pp-done-meta">${meta}</span>
      </div>`;
  }

  // ---- Ações ----
  async function atribuir(sel) {
    const json = await api('/back-office/painel-processos/atribuir', 'POST', {
      fonte: sel.dataset.fonte, id: Number(sel.dataset.id), responsavel_id: sel.value ? Number(sel.value) : null,
    });
    toast(json.success ? 'Responsável atualizado.' : (json.message || 'Erro.'), json.success ? 'ok' : 'err');
  }

  async function mudarFase(sel, tipo) {
    const fases = tipo === 'portabilidade' ? fasesPortab : fasesCancel;
    const escolhida = fases.find((f) => f.value === sel.value);
    if (escolhida && escolhida.final) {
      const fonte = tipo === 'portabilidade' ? 'portabilidade' : 'demanda';
      const item = ultimaFila.find((p) => p.fonte === fonte && p.id === Number(sel.dataset.id));
      const negada = sel.value === 'NEGADA';
      const ok = await ppConfirm({
        title: negada ? 'Registrar como negada?' : 'Concluir processo?',
        message: `<b>${esc(item ? item.contrato : 'Este processo')}</b> vai para <b>${esc(escolhida.label)}</b> e sai da fila. Isso encerra o processo.`,
        confirmLabel: negada ? 'Registrar negada' : 'Concluir',
        tone: negada ? 'danger' : 'success',
        icon: negada ? 'alert' : 'check',
      });
      if (!ok) { load(); return; }
    }
    sel.disabled = true;
    const url = tipo === 'portabilidade'
      ? '/back-office/painel-processos/portabilidade/fase'
      : '/back-office/painel-processos/cancelamento/fase';
    const json = await api(url, 'POST', { id: Number(sel.dataset.id), fase: sel.value });
    toast(json.success ? 'Fase atualizada.' : (json.message || 'Erro.'), json.success ? 'ok' : 'err');
    load();
  }

  // ---- Filtro de urgência (KPIs) ----
  function aplicarUrgencia(urg) {
    urgFiltro = urgFiltro === urg ? '' : urg;
    root.querySelectorAll('.pp-kpi[data-urg]').forEach((el) => el.classList.toggle('active', el.dataset.urg === urgFiltro));
    const tag = $('pp-urg-tag');
    if (urgFiltro) {
      // Filtro de urgência só faz sentido nas trilhas; sai de "Concluídos".
      if (abaAtiva === 'concluidos') trocarAba('cancelamentos');
      tag.style.display = '';
      tag.innerHTML = `Filtrando: ${esc(URG_LABEL[urgFiltro] || urgFiltro)} <button type="button" class="pp-urg-x" aria-label="Remover filtro">✕</button>`;
    } else {
      tag.style.display = 'none';
    }
    renderConteudo();
  }

  function trocarAba(aba) {
    abaAtiva = aba;
    root.querySelectorAll('.pp-tab').forEach((t) => t.classList.toggle('active', t.dataset.tab === aba));
    renderConteudo();
  }

  // ---- Eventos ----
  root.addEventListener('change', (e) => {
    const t = e.target;
    if (t.classList.contains('pp-assign')) return atribuir(t);
    if (t.classList.contains('pp-fase-portab')) return mudarFase(t, 'portabilidade');
    if (t.classList.contains('pp-fase-cancel')) return mudarFase(t, 'cancelamento');
  });

  root.addEventListener('click', (e) => {
    if (e.target.closest('.pp-urg-x')) { aplicarUrgencia(urgFiltro); return; }
    const tab = e.target.closest('.pp-tab');
    if (tab) { trocarAba(tab.dataset.tab); return; }
    const kpi = e.target.closest('.pp-kpi[data-urg]');
    if (kpi) { aplicarUrgencia(kpi.dataset.urg); return; }
    const toggle = e.target.closest('[data-lane-toggle]');
    if (toggle) {
      const key = toggle.dataset.laneToggle;
      if (colapsadas.has(key)) colapsadas.delete(key); else colapsadas.add(key);
      renderConteudo();
    }
  });

  // ---- Filtros de servidor ----
  function initSelects() {
    const respSel = $('pp-responsavel');
    cfg.responsaveis.forEach((r) => {
      const o = document.createElement('option');
      o.value = r.id; o.textContent = r.name; respSel.appendChild(o);
    });
  }

  let buscaTimer;
  $('pp-busca').addEventListener('input', (e) => {
    clearTimeout(buscaTimer);
    buscaTimer = setTimeout(() => { filtros.busca = e.target.value.trim(); load(); }, 350);
  });
  $('pp-responsavel').addEventListener('change', (e) => { filtros.responsavel_id = e.target.value; load(); });
  $('pp-limpar').addEventListener('click', () => {
    filtros = { responsavel_id: '', busca: '' };
    urgFiltro = '';
    $('pp-busca').value = '';
    $('pp-responsavel').value = '';
    root.querySelectorAll('.pp-kpi.active').forEach((el) => el.classList.remove('active'));
    $('pp-urg-tag').style.display = 'none';
    load();
  });

  // ---- Init ----
  initSelects();
  load();
})();
