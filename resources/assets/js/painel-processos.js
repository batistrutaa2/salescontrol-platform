/**
 * Painel operacional de processos (visão do gestor).
 * KPIs clicáveis + fila filtrável com prazo/atraso (SLA), responsável e ações.
 * Consome backoffice.painelProcessos.data / .atribuir / .concluir.
 */
(function () {
  'use strict';

  const root = document.getElementById('painel-processos');
  if (!root) return;

  const csrf = root.dataset.csrf;
  const cfg = window.__painel || { responsaveis: [], tipos: {} };
  let filtros = { grupo: '', tipo: '', responsavel_id: '', busca: '', so_atrasados: '' };
  let pagina = 1;
  let fasesPortab = [];

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

  // ---- Popular selects ----
  function initSelects() {
    const tipoSel = $('pp-tipo');
    Object.keys(cfg.tipos).forEach((k) => {
      const o = document.createElement('option');
      o.value = k; o.textContent = cfg.tipos[k]; tipoSel.appendChild(o);
    });
    const respSel = $('pp-responsavel');
    cfg.responsaveis.forEach((r) => {
      const o = document.createElement('option');
      o.value = r.id; o.textContent = r.name; respSel.appendChild(o);
    });
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
    params.append('pagina', pagina);

    const tbody = $('pp-tbody');
    tbody.innerHTML = '<tr><td colspan="7" class="pp-loading">Carregando…</td></tr>';

    const data = await api(`/back-office/painel-processos/data?${params.toString()}`);
    if (!data.success) { tbody.innerHTML = `<tr><td colspan="7" class="pp-empty">${esc(data.message || 'Erro.')}</td></tr>`; return; }

    fasesPortab = data.fases_portabilidade || [];
    renderKpis(data.kpis);
    renderFila(data.fila);
    renderPaginacao(data.paginacao);
  }

  function renderPaginacao(p) {
    const bar = $('pp-pagination');
    if (!p || p.total <= p.por_pagina) { bar.style.display = 'none'; return; }
    bar.style.display = 'flex';
    $('pp-pag-info').textContent = `Mostrando ${p.de}–${p.ate} de ${p.total} processos`;
    $('pp-pag-atual').textContent = `${p.pagina} / ${p.total_paginas}`;
    $('pp-pag-prev').disabled = p.pagina <= 1;
    $('pp-pag-next').disabled = p.pagina >= p.total_paginas;
    $('pp-pag-prev').onclick = () => { pagina = Math.max(1, p.pagina - 1); load(); };
    $('pp-pag-next').onclick = () => { pagina = Math.min(p.total_paginas, p.pagina + 1); load(); };
  }

  function renderKpis(k) {
    $('kpi-cancel').textContent = k.cancelamentos_abertos;
    $('kpi-portab').textContent = k.portabilidades_abertas;
    $('kpi-atrasados').textContent = k.atrasados;
    $('kpi-total').textContent = k.total_abertos;
    $('kpi-concluidos').textContent = k.concluidos_mes;
  }

  function renderFila(fila) {
    const tbody = $('pp-tbody');
    if (!fila.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="pp-empty">Nenhum processo em aberto com esses filtros. 🎉</td></tr>';
      return;
    }
    tbody.innerHTML = fila.map(linha).join('');
  }

  // Cor do chip de fase por etapa (documentos = âmbar, análise = azul, concluída = verde, negada = vermelho).
  function faseClasse(valor) {
    const v = String(valor || '').toUpperCase();
    if (v === 'REUNINDO_DOCUMENTOS' || v === 'SOLICITADO') return 'f-inicio';
    if (v === 'ENVIADA_ANALISE' || v === 'EM_ANALISE' || v === 'PROTOCOLADO') return 'f-analise';
    if (v === 'CONCLUIDA' || v === 'CONCLUIDO') return 'f-ok';
    if (v === 'NEGADA') return 'f-negada';
    return 'f-inicio';
  }

  function linha(p) {
    const prazo = p.atrasado
      ? `<span class="pp-badge danger">Atrasado há ${p.dias_atraso}d</span>`
      : `<span class="pp-badge ok">Vence ${esc(p.vence_em)}</span>`;
    const fase = p.fase ? `<span class="pp-chip pp-fase ${faseClasse(p.fase_valor)}">${esc(p.fase)}</span>` : '';
    const link = p.venda_id ? `/back-office/abrir-contrato/${p.venda_id}` : '#';

    // Portabilidade: o passo a passo é a própria ação (fase final fecha o processo).
    const acao = p.fonte === 'portabilidade'
      ? `<select class="pp-input pp-fase-select" data-id="${p.id}" title="Fase da portabilidade">
          ${fasesPortab.map((f) => `<option value="${esc(f.value)}" ${p.fase_valor === f.value ? 'selected' : ''}>${esc(f.label)}</option>`).join('')}
        </select>`
      : `<button type="button" class="pp-btn pp-btn-sm pp-btn-primary pp-concluir" data-fonte="${esc(p.fonte)}" data-id="${p.id}">Concluir</button>`;

    return `<tr class="${p.atrasado ? 'is-late' : ''}">
        <td><a href="${link}" class="pp-link" target="_blank">${esc(p.contrato)}</a></td>
        <td><span class="pp-tipo">${esc(p.tipo_label)}</span>${fase}</td>
        <td>${esc(p.quem || '—')}</td>
        <td>
          <select class="pp-input pp-assign" data-fonte="${esc(p.fonte)}" data-id="${p.id}">
            ${opcoesResponsavel(p.responsavel_id)}
          </select>
        </td>
        <td class="pp-num">${p.dias_aberto}d</td>
        <td>${prazo}</td>
        <td class="pp-actions">${acao}</td>
      </tr>`;
  }

  // ---- Ações ----
  async function atribuir(sel) {
    const json = await api('/back-office/painel-processos/atribuir', 'POST', {
      fonte: sel.dataset.fonte, id: Number(sel.dataset.id), responsavel_id: sel.value ? Number(sel.value) : null,
    });
    toast(json.success ? 'Responsável atualizado.' : (json.message || 'Erro.'), json.success ? 'ok' : 'err');
  }

  async function concluir(btn) {
    if (!confirm('Marcar este processo como concluído?')) return;
    const json = await api('/back-office/painel-processos/concluir', 'POST', {
      fonte: btn.dataset.fonte, id: Number(btn.dataset.id),
    });
    toast(json.success ? 'Processo concluído.' : (json.message || 'Erro.'), json.success ? 'ok' : 'err');
    if (json.success) load();
  }

  async function mudarFasePortabilidade(sel) {
    const faseEscolhida = fasesPortab.find((f) => f.value === sel.value);
    if (faseEscolhida && faseEscolhida.final && !confirm(`Marcar esta portabilidade como "${faseEscolhida.label}"? Isso encerra o processo.`)) {
      load();
      return;
    }
    sel.disabled = true;
    const json = await api('/back-office/painel-processos/portabilidade/fase', 'POST', {
      id: Number(sel.dataset.id), fase: sel.value,
    });
    toast(json.success ? 'Fase atualizada.' : (json.message || 'Erro.'), json.success ? 'ok' : 'err');
    load();
  }

  // ---- Eventos ----
  root.addEventListener('change', (e) => {
    if (e.target.classList.contains('pp-assign')) { atribuir(e.target); return; }
    if (e.target.classList.contains('pp-fase-select')) mudarFasePortabilidade(e.target);
  });
  root.addEventListener('click', (e) => {
    const btn = e.target.closest('.pp-concluir');
    if (btn) { concluir(btn); return; }
    const kpi = e.target.closest('[data-filter]');
    if (kpi) {
      filtros = { grupo: '', tipo: '', responsavel_id: '', busca: '', so_atrasados: '' };
      const f = kpi.dataset.filter;
      if (f) { const [k, v] = f.split(':'); filtros[k] = v; }
      pagina = 1;
      root.querySelectorAll('.pp-kpi[data-filter]').forEach((el) => el.classList.toggle('active', el === kpi && !!f));
      sincronizarControles();
      load();
    }
  });

  function sincronizarControles() {
    $('pp-tipo').value = filtros.tipo || '';
    $('pp-responsavel').value = filtros.responsavel_id || '';
    $('pp-busca').value = filtros.busca || '';
    $('pp-atrasados').checked = !!filtros.so_atrasados;
  }

  function limparKpiAtivo() {
    root.querySelectorAll('.pp-kpi.active').forEach((el) => el.classList.remove('active'));
  }

  let buscaTimer;
  $('pp-busca').addEventListener('input', (e) => {
    clearTimeout(buscaTimer);
    buscaTimer = setTimeout(() => { filtros.busca = e.target.value.trim(); pagina = 1; load(); }, 350);
  });
  $('pp-tipo').addEventListener('change', (e) => { filtros.tipo = e.target.value; filtros.grupo = ''; pagina = 1; limparKpiAtivo(); load(); });
  $('pp-responsavel').addEventListener('change', (e) => { filtros.responsavel_id = e.target.value; pagina = 1; load(); });
  $('pp-atrasados').addEventListener('change', (e) => { filtros.so_atrasados = e.target.checked ? '1' : ''; pagina = 1; load(); });
  $('pp-limpar').addEventListener('click', () => {
    filtros = { grupo: '', tipo: '', responsavel_id: '', busca: '', so_atrasados: '' };
    pagina = 1;
    limparKpiAtivo();
    sincronizarControles();
    load();
  });

  // ---- Init ----
  initSelects();
  load();
})();
