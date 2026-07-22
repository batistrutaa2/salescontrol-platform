/**
 * Painel operacional de processos (visão do gestor) — esteira kanban.
 * Duas trilhas (Cancelamento op. anterior, Portabilidade), cada uma com uma
 * coluna-portão "Aguardando implantação" + colunas de fase. O prazo conta a
 * partir da implantação do contrato (ver ProcessoVendaRepository::linhaProcesso).
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

  const esc = (s) =>
    String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const $ = (id) => document.getElementById(id);

  const URG_LABEL = { atrasado: 'Atrasados', vencendo: 'Vencendo', aguardando_implantacao: 'Aguardando implantação' };

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

    ['board-cancelamentos', 'board-portabilidade'].forEach((id) => {
      $(id).innerHTML = '<div class="pp-board-loading">Carregando…</div>';
    });

    const data = await api(`/back-office/painel-processos/data?${params.toString()}`);
    if (!data.success) {
      $('board-cancelamentos').innerHTML = `<div class="pp-board-erro">${esc(data.message || 'Erro ao carregar.')}</div>`;
      return;
    }

    fasesCancel = data.fases_cancelamento || [];
    fasesPortab = data.fases_portabilidade || [];
    ultimaFila = data.fila || [];
    renderKpis(data.kpis);
    renderBoards(ultimaFila);
  }

  function renderKpis(k) {
    $('kpi-atrasados').textContent = k.atrasados;
    $('kpi-vencendo').textContent = k.vencendo;
    $('kpi-aguardando').textContent = k.aguardando_implantacao;
    $('kpi-concluidos').textContent = k.concluidos_mes;
  }

  // ---- Colunas de cada esteira (portão + fases não-finais) ----
  function colunas(esteira) {
    const fases = esteira === 'cancelamentos' ? fasesCancel : fasesPortab;
    const cols = [{ key: 'aguardando_implantacao', label: 'Aguardando implantação', gate: true }];
    fases.filter((f) => !f.final).forEach((f) => cols.push({ key: f.value, label: f.label }));
    return cols;
  }

  function colunaDoItem(p, cols) {
    if (p.bloqueado) return 'aguardando_implantacao';
    const existe = cols.some((c) => c.key === p.fase_valor);
    return existe ? p.fase_valor : (cols[1] ? cols[1].key : 'aguardando_implantacao');
  }

  // ---- Render dos boards ----
  function renderBoards(fila) {
    const nCancel = renderBoard('cancelamentos', 'board-cancelamentos', fila.filter((p) => p.grupo === 'cancelamentos'));
    const nPortab = renderBoard('portabilidade', 'board-portabilidade', fila.filter((p) => p.grupo === 'portabilidade'));
    $('count-cancelamentos').textContent = nCancel;
    $('count-portabilidade').textContent = nPortab;
  }

  function renderBoard(esteiraKey, elId, itens) {
    const cols = colunas(esteiraKey);
    const buckets = {};
    cols.forEach((c) => (buckets[c.key] = []));

    itens.forEach((p) => {
      if (urgFiltro && p.urgencia !== urgFiltro) return;
      const k = colunaDoItem(p, cols);
      (buckets[k] || (buckets[k] = [])).push(p);
    });

    $(elId).innerHTML = cols.map((c) => {
      const lista = buckets[c.key] || [];
      const corpo = lista.length ? lista.map(card).join('') : '<div class="pp-col-empty">vazio</div>';
      return `<div class="pp-col ${c.gate ? 'is-gate' : ''}">
          <div class="pp-col-head"><span class="pp-col-name">${esc(c.label)}</span><span class="pp-col-count">${lista.length}</span></div>
          <div class="pp-col-body">${corpo}</div>
        </div>`;
    }).join('');

    return cols.reduce((n, c) => n + (buckets[c.key] ? buckets[c.key].length : 0), 0);
  }

  function badgePrazo(p) {
    if (p.urgencia === 'atrasado') return `<span class="pp-badge danger">Venceu há ${p.dias_atraso}d</span>`;
    if (p.urgencia === 'vencendo') return `<span class="pp-badge warn">Vence em ${p.dias_para_vencer}d</span>`;
    if (p.urgencia === 'aguardando_implantacao') return '<span class="pp-badge muted">Sem prazo ainda</span>';
    return `<span class="pp-badge ok">Vence ${esc(p.vence_em || '—')}</span>`;
  }

  function card(p) {
    const link = p.venda_id ? `/back-office/abrir-contrato/${p.venda_id}` : '#';
    const fases = p.fonte === 'portabilidade' ? fasesPortab : fasesCancel;
    const faseCls = p.fonte === 'portabilidade' ? 'pp-fase-portab' : 'pp-fase-cancel';
    const faseSel = `<select class="pp-input pp-mini ${faseCls}" data-id="${p.id}" title="Avançar fase">
        ${fases.map((f) => `<option value="${esc(f.value)}" ${p.fase_valor === f.value ? 'selected' : ''}>${esc(f.label)}</option>`).join('')}
      </select>`;

    // No portão, explica POR QUE está parado (situação do contrato-pai).
    const gate = p.bloqueado
      ? `<div class="pp-card-gate">⏸ Contrato: ${esc(p.situacao_contrato)} · há ${p.dias_bloqueado}d</div>`
      : '';

    return `<div class="pp-card urg-${esc(p.urgencia)}">
        <div class="pp-card-top">
          <a href="${link}" target="_blank" class="pp-card-contrato">${esc(p.contrato)}</a>
          ${badgePrazo(p)}
        </div>
        <div class="pp-card-pessoa">${esc(p.quem || '—')}</div>
        ${gate}
        <div class="pp-card-foot">
          <select class="pp-input pp-mini pp-assign" data-fonte="${esc(p.fonte)}" data-id="${p.id}" title="Responsável">${opcoesResponsavel(p.responsavel_id)}</select>
          ${faseSel}
        </div>
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
    renderBoards(ultimaFila);
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
    if (kpi) aplicarUrgencia(kpi.dataset.urg);
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
