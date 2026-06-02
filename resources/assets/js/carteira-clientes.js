'use strict';

(function () {
  // ============================================
  // Estado
  // ============================================
  let pagination = { currentPage: 1, totalPages: 1, perPage: 15, total: 0 };
  let filtersAtivos = {};

  const el = {
    loading:         document.getElementById('cc-loading'),
    tableCard:       document.getElementById('cc-table-card'),
    tbody:           document.getElementById('cc-tbody'),
    empty:           document.getElementById('cc-empty'),
    totalBadge:      document.getElementById('cc-total-badge'),
    paginationWrap:  document.getElementById('cc-pagination'),
    paginationInfo:  document.getElementById('cc-pagination-info'),
    pageIndicator:   document.getElementById('cc-page-indicator'),
    perPageSelect:   document.getElementById('cc-per-page'),
    btnFirst:        document.getElementById('cc-btn-first'),
    btnPrev:         document.getElementById('cc-btn-prev'),
    btnNext:         document.getElementById('cc-btn-next'),
    btnLast:         document.getElementById('cc-btn-last'),
    // Filtros
    inputBusca:      document.getElementById('cc-busca'),
    selectStatus:    document.getElementById('cc-status'),
    selectOperadora: document.getElementById('cc-operadora'),
    btnClear:        document.getElementById('cc-btn-clear'),
    // KPIs
    kpiTotal:        document.getElementById('kpi-total'),
    kpiAtivos:       document.getElementById('kpi-ativos'),
    kpiInativos:     document.getElementById('kpi-inativos'),
    kpiValor:        document.getElementById('kpi-valor'),
    kpiVidas:        document.getElementById('kpi-vidas'),
    // Modal detalhe
    modalDetalhe:    document.getElementById('modalDetalheCliente'),
    detalheNome:     document.getElementById('cc-detalhe-nome'),
    detalheCnpj:     document.getElementById('cc-detalhe-cnpj'),
    detalheTempo:    document.getElementById('cc-detalhe-tempo'),
    detalheBody:     document.getElementById('cc-detalhe-body'),
  };

  // ============================================
  // Init
  // ============================================
  function init() {
    bindEvents();
    loadData();
  }

  function bindEvents() {
    el.perPageSelect?.addEventListener('change', () => {
      pagination.perPage = parseInt(el.perPageSelect.value);
      goToPage(1);
    });
    el.btnFirst?.addEventListener('click', () => goToPage(1));
    el.btnPrev?.addEventListener('click',  () => goToPage(pagination.currentPage - 1));
    el.btnNext?.addEventListener('click',  () => goToPage(pagination.currentPage + 1));
    el.btnLast?.addEventListener('click',  () => goToPage(pagination.totalPages));
    el.btnClear?.addEventListener('click', clearFilters);

    el.inputBusca?.addEventListener('input',    debounce(() => goToPage(1), 350));
    el.selectStatus?.addEventListener('change',    () => goToPage(1));
    el.selectOperadora?.addEventListener('change', () => goToPage(1));
  }

  // ============================================
  // Data Loading
  // ============================================
  async function loadData() {
    showLoading(true);

    const params = new URLSearchParams({
      page:     pagination.currentPage,
      per_page: pagination.perPage,
    });
    if (el.inputBusca?.value.trim())      params.set('busca',     el.inputBusca.value.trim());
    if (el.selectStatus?.value)           params.set('status',    el.selectStatus.value);
    if (el.selectOperadora?.value)        params.set('operadora', el.selectOperadora.value);

    try {
      const res  = await fetch(`/back-office/carteira-clientes/data?${params}`);
      const data = await res.json();

      if (!data.success) throw new Error(data.message || 'Erro ao carregar dados');

      renderKpis(data.kpis);
      renderTable(data.clientes);
      renderPagination(data.pagination);
    } catch (e) {
      console.error(e);
      showEmpty(true);
    } finally {
      showLoading(false);
    }
  }

  function goToPage(page) {
    pagination.currentPage = page;
    loadData();
  }

  // ============================================
  // Render
  // ============================================
  function renderKpis(kpis) {
    if (el.kpiTotal)    el.kpiTotal.textContent    = kpis.total_clientes.toLocaleString('pt-BR');
    if (el.kpiAtivos)   el.kpiAtivos.textContent   = kpis.clientes_ativos.toLocaleString('pt-BR');
    if (el.kpiInativos) el.kpiInativos.textContent = kpis.clientes_inativos.toLocaleString('pt-BR');
    if (el.kpiValor)    el.kpiValor.textContent     = formatCurrency(kpis.valor_carteira);
    if (el.kpiVidas)    el.kpiVidas.textContent     = kpis.total_vidas.toLocaleString('pt-BR');
  }

  function renderTable(clientes) {
    if (!clientes || clientes.length === 0) {
      showEmpty(true);
      return;
    }
    showEmpty(false);

    el.tbody.innerHTML = clientes.map(c => `
      <tr>
        <td>
          <div class="cc-cell-cliente">
            <div class="cc-nome">${escapeHtml(c.nome_contrato)}</div>
            <div class="cc-cnpj">${escapeHtml(c.cpf_cnpj_formatado)}</div>
          </div>
        </td>
        <td>
          <div class="cc-contratos-badges">
            <span class="cc-badge badge-total">
              <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              ${c.total_contratos} total
            </span>
            ${c.contratos_ativos > 0 ? `<span class="cc-badge badge-ativo">${c.contratos_ativos} ativo${c.contratos_ativos > 1 ? 's' : ''}</span>` : ''}
            ${c.contratos_cancelados > 0 ? `<span class="cc-badge badge-cancelado">${c.contratos_cancelados} cancel.</span>` : ''}
          </div>
        </td>
        <td>
          <div class="cc-operadoras-chips">
            ${(c.operadoras || '').split(', ').filter(Boolean).map(op => `<span class="cc-chip">${escapeHtml(op)}</span>`).join('')}
          </div>
        </td>
        <td><span class="cc-valor">${formatCurrency(c.valor_ativo)}</span></td>
        <td><span class="cc-valor" style="font-size:0.82rem">${c.vidas_ativas.toLocaleString('pt-BR')}</span></td>
        <td style="font-size:0.8rem;color:var(--cc-text-secondary)">${c.primeiro_contrato || '—'}</td>
        <td>
          <div class="cc-tempo">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            ${escapeHtml(c.tempo_label)}
          </div>
        </td>
        <td>${getStatusBadge(c.status)}</td>
        <td>
          <button class="cc-btn-detalhe" onclick="carteiraClientes.abrirDetalhe('${escapeHtml(c.cpf_cnpj)}', '${escapeHtml(c.nome_contrato)}', '${escapeHtml(c.cpf_cnpj_formatado)}', '${escapeHtml(c.tempo_label)}')">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            Ver Detalhes
          </button>
        </td>
      </tr>
    `).join('');
  }

  function renderPagination(pag) {
    pagination.currentPage = pag.current_page;
    pagination.totalPages  = pag.total_pages;
    pagination.total       = pag.total;

    const from = Math.min((pag.current_page - 1) * pag.per_page + 1, pag.total);
    const to   = Math.min(pag.current_page * pag.per_page, pag.total);

    if (el.paginationInfo)
      el.paginationInfo.innerHTML = `Mostrando <strong>${from}–${to}</strong> de <strong>${pag.total}</strong> clientes`;
    if (el.pageIndicator)
      el.pageIndicator.textContent = `${pag.current_page} / ${pag.total_pages}`;
    if (el.totalBadge)
      el.totalBadge.textContent = `${pag.total} cliente${pag.total !== 1 ? 's' : ''}`;

    if (el.btnFirst) el.btnFirst.disabled = !pag.has_prev;
    if (el.btnPrev)  el.btnPrev.disabled  = !pag.has_prev;
    if (el.btnNext)  el.btnNext.disabled  = !pag.has_next;
    if (el.btnLast)  el.btnLast.disabled  = !pag.has_next;

    if (el.paginationWrap) el.paginationWrap.classList.toggle('d-none', pag.total === 0);
  }

  // ============================================
  // Modal Detalhe
  // ============================================
  window.carteiraClientes = {
    abrirDetalhe: async function(cnpj, nome, cnpjFormatado, tempoLabel) {
      if (el.detalheNome) el.detalheNome.textContent = nome;
      if (el.detalheCnpj) el.detalheCnpj.textContent = cnpjFormatado;
      if (el.detalheTempo) el.detalheTempo.textContent = `Cliente há ${tempoLabel}`;
      if (el.detalheBody) el.detalheBody.innerHTML = `
        <div class="cc-loading"><span class="spinner-border spinner-border-sm"></span> Carregando contratos...</div>
      `;

      new bootstrap.Modal(el.modalDetalhe).show();

      try {
        const cnpjEnc = encodeURIComponent(cnpj);
        const res  = await fetch(`/back-office/carteira-clientes/detalhe/${cnpjEnc}`);
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        renderDetalhe(data.contratos);
      } catch (e) {
        if (el.detalheBody) el.detalheBody.innerHTML = `<p class="text-center text-muted p-4">Erro ao carregar contratos.</p>`;
      }
    }
  };

  function renderDetalhe(contratos) {
    if (!contratos || contratos.length === 0) {
      el.detalheBody.innerHTML = `<p class="text-center text-muted p-4">Nenhum contrato encontrado.</p>`;
      return;
    }

    const maisAntigo = contratos[contratos.length - 1]?.id;

    el.detalheBody.innerHTML = `<div class="cc-contratos-list">${contratos.map(c => `
      <div class="cc-contrato-item ${c.is_ativo ? 'contrato-ativo' : 'contrato-inativo'}">
        ${c.id === maisAntigo ? '<span class="cc-primeiro-badge">⭐ 1º Contrato</span>' : ''}
        <div class="cc-contrato-top">
          <div>
            <div class="cc-contrato-operadora">${escapeHtml(c.operadora || '—')}</div>
            <div class="cc-contrato-plano">${escapeHtml(c.nome_plano || '—')}</div>
          </div>
          ${getStatusBadge(c.is_ativo ? 'ativo' : 'inativo', c.status_descricao)}
        </div>
        <div class="cc-contrato-grid">
          <div class="cc-contrato-field">
            <div class="cc-field-label">Valor</div>
            <div class="cc-field-value mono">${formatCurrency(c.valor_contrato)}</div>
          </div>
          <div class="cc-contrato-field">
            <div class="cc-field-label">Vidas</div>
            <div class="cc-field-value">${c.vidas || '—'}</div>
          </div>
          <div class="cc-contrato-field">
            <div class="cc-field-label">Implantado</div>
            <div class="cc-field-value">${c.data_implantacao || '—'}</div>
          </div>
          <div class="cc-contrato-field">
            <div class="cc-field-label">Vigência</div>
            <div class="cc-field-value">${c.data_vigencia || '—'}</div>
          </div>
        </div>
        <div class="cc-contrato-footer">
          <span class="cc-vendedor">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            ${escapeHtml(c.vendedor || '—')}
          </span>
          <span class="cc-proposta">${c.numero_proposta ? `Proposta: ${escapeHtml(c.numero_proposta)}` : ''}</span>
        </div>
        <div class="cc-contrato-acoes">
          <a href="/back-office/abrir-contrato/${c.id}" target="_blank" rel="noopener" class="cc-btn-abrir-contrato">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
            Abrir / Editar contrato
          </a>
        </div>
      </div>
    `).join('')}</div>`;
  }

  // ============================================
  // Helpers
  // ============================================
  function getStatusBadge(status, label) {
    const map = {
      ativo:   { cls: 'status-ativo',   txt: label || 'Ativo' },
      misto:   { cls: 'status-misto',   txt: label || 'Misto' },
      inativo: { cls: 'status-inativo', txt: label || 'Inativo' },
    };
    const s = map[status] || map.inativo;
    return `<span class="cc-status-badge ${s.cls}"><span class="cc-dot"></span>${escapeHtml(s.txt)}</span>`;
  }

  function formatCurrency(val) {
    return 'R$ ' + parseFloat(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function showLoading(show) {
    el.loading?.classList.toggle('d-none', !show);
    if (el.tableCard) el.tableCard.style.opacity = show ? '0.5' : '1';
  }

  function showEmpty(show) {
    el.empty?.classList.toggle('d-none', !show);
    if (el.tbody) el.tbody.style.display = show ? 'none' : '';
  }

  function clearFilters() {
    if (el.inputBusca)      el.inputBusca.value      = '';
    if (el.selectStatus)    el.selectStatus.value    = '';
    if (el.selectOperadora) el.selectOperadora.value = '';
    goToPage(1);
  }

  function escapeHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  function debounce(fn, wait) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
  }

  // ============================================
  // Boot
  // ============================================
  document.addEventListener('DOMContentLoaded', init);
})();
