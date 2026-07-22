/**
 * Painel operacional de processos (visão do gestor) — torre de controle.
 * Raias por urgência: Atrasado · Vencendo · Aguardando implantação · Em dia.
 * Uma linha por processo (cancelamento ou portabilidade). O prazo conta a partir
 * da implantação do contrato (ver ProcessoVendaRepository::linhaProcesso).
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
  let fasesCancel = [];
  let fasesPortab = [];
  let ultimaFila = [];
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

    $('pp-lanes').innerHTML = '<div class="pp-board-loading">Carregando…</div>';

    const data = await api(`/back-office/painel-processos/data?${params.toString()}`);
    if (!data.success) {
      $('pp-lanes').innerHTML = `<div class="pp-board-erro">${esc(data.message || 'Erro ao carregar.')}</div>`;
      return;
    }

    fasesCancel = data.fases_cancelamento || [];
    fasesPortab = data.fases_portabilidade || [];
    ultimaFila = data.fila || [];
    renderKpis(data.kpis);
    renderLanes(ultimaFila);
  }

  function renderKpis(k) {
    $('kpi-atrasados').textContent = k.atrasados;
    $('kpi-vencendo').textContent = k.vencendo;
    $('kpi-aguardando').textContent = k.aguardando_implantacao;
    $('kpi-concluidos').textContent = k.concluidos_mes;
  }

  // ---- Raias ----
  function renderLanes(fila) {
    const host = $('pp-lanes');
    const grupos = { atrasado: [], vencendo: [], aguardando_implantacao: [], em_dia: [] };
    fila.forEach((p) => (grupos[p.urgencia] || (grupos[p.urgencia] = [])).push(p));

    const lanes = urgFiltro ? LANES.filter((l) => l.key === urgFiltro) : LANES;
    host.innerHTML = lanes.map((l) => laneHtml(l, grupos[l.key] || [])).join('')
      || '<div class="pp-board-loading">Nenhum processo em aberto. 🎉</div>';
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
    const wsCls = ehPortab ? 'ws-portab' : 'ws-cancel';
    const wsLabel = ehPortab ? 'Portabilidade' : 'Cancelamento';

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
        <span class="pp-ws ${wsCls}" title="${esc(p.tipo_label)}">${esc(wsLabel)}</span>
        <div class="pp-row-main">
          <a href="${link}" target="_blank" class="pp-row-contrato">${esc(p.contrato)}</a>
          <span class="pp-row-pessoa">${esc(p.quem || '—')}</span>
        </div>
        <span class="pp-chip pp-row-fase">${esc(p.fase || '—')}</span>
        <span class="pp-row-when">${situacao}</span>
        <select class="pp-input pp-mini pp-assign" data-fonte="${esc(p.fonte)}" data-id="${p.id}" title="Responsável">${opcoesResponsavel(p.responsavel_id)}</select>
        ${faseSel}
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
    if (escolhida && escolhida.final && !confirm(`Marcar como "${escolhida.label}"? Isso encerra o processo.`)) {
      load();
      return;
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
      tag.style.display = '';
      tag.innerHTML = `Filtrando: ${esc(URG_LABEL[urgFiltro] || urgFiltro)} <button type="button" class="pp-urg-x" aria-label="Remover filtro">✕</button>`;
    } else {
      tag.style.display = 'none';
    }
    renderLanes(ultimaFila);
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
    const kpi = e.target.closest('.pp-kpi[data-urg]');
    if (kpi) { aplicarUrgencia(kpi.dataset.urg); return; }
    const toggle = e.target.closest('[data-lane-toggle]');
    if (toggle) {
      const key = toggle.dataset.laneToggle;
      if (colapsadas.has(key)) colapsadas.delete(key); else colapsadas.add(key);
      renderLanes(ultimaFila);
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
