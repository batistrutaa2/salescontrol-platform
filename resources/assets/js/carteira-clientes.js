'use strict';

(function () {
  const state = {
    view: 'recentes',
    period: 30,
    pagination: { currentPage: 1, totalPages: 1, perPage: 15, total: 0 },
    request: null
  };

  const el = {
    viewButtons: document.querySelectorAll('[data-cc-view]'),
    periodPanel: document.getElementById('cc-period-panel'),
    periodButtons: document.querySelectorAll('[data-period]'),
    recentOnly: document.querySelectorAll('.cc-recentes-only'),
    portfolioOnly: document.querySelectorAll('.cc-carteira-only'),
    responsaveis: document.getElementById('cc-responsaveis'),
    responsaveisLista: document.getElementById('cc-responsaveis-lista'),
    responsaveisPeriodo: document.getElementById('cc-responsaveis-periodo'),
    loading: document.getElementById('cc-loading'),
    error: document.getElementById('cc-error'),
    tableWrap: document.querySelector('.cc-table-wrap'),
    tbody: document.getElementById('cc-tbody'),
    thead: document.getElementById('cc-thead'),
    empty: document.getElementById('cc-empty'),
    emptyTitle: document.getElementById('cc-empty-title'),
    emptyCopy: document.getElementById('cc-empty-copy'),
    emptyAction: document.getElementById('cc-empty-action'),
    retry: document.getElementById('cc-btn-retry'),
    totalBadge: document.getElementById('cc-total-badge'),
    tableTitle: document.getElementById('cc-table-title'),
    tableSubtitle: document.getElementById('cc-table-subtitle'),
    paginationWrap: document.getElementById('cc-pagination'),
    paginationInfo: document.getElementById('cc-pagination-info'),
    pageIndicator: document.getElementById('cc-page-indicator'),
    perPageSelect: document.getElementById('cc-per-page'),
    btnFirst: document.getElementById('cc-btn-first'),
    btnPrev: document.getElementById('cc-btn-prev'),
    btnNext: document.getElementById('cc-btn-next'),
    btnLast: document.getElementById('cc-btn-last'),
    inputBusca: document.getElementById('cc-busca'),
    selectStatus: document.getElementById('cc-status'),
    selectOperadora: document.getElementById('cc-operadora'),
    selectBackoffice: document.getElementById('cc-backoffice'),
    selectAcao: document.getElementById('cc-acao'),
    btnClear: document.getElementById('cc-btn-clear'),
    kpiTotal: document.getElementById('kpi-total'),
    kpiAtencao: document.getElementById('kpi-atencao'),
    kpiPortabilidades: document.getElementById('kpi-portabilidades'),
    kpiCancelamentos: document.getElementById('kpi-cancelamentos'),
    kpiVidas: document.getElementById('kpi-vidas'),
    kpiTotalLabel: document.getElementById('kpi-total-label'),
    kpiAttentionLabel: document.getElementById('kpi-attention-label'),
    kpiPortLabel: document.getElementById('kpi-port-label'),
    kpiCancelLabel: document.getElementById('kpi-cancel-label'),
    kpiVidasLabel: document.getElementById('kpi-vidas-label'),
    modalDetalhe: document.getElementById('modalDetalheCliente'),
    detalheNome: document.getElementById('cc-detalhe-nome'),
    detalheCnpj: document.getElementById('cc-detalhe-cnpj'),
    detalheTempo: document.getElementById('cc-detalhe-tempo'),
    detalheBody: document.getElementById('cc-detalhe-body')
  };

  function init() {
    bindEvents();
    updateView();
    loadData();
  }

  function bindEvents() {
    el.viewButtons.forEach(button => button.addEventListener('click', () => changeView(button.dataset.ccView)));
    el.periodButtons.forEach(button => button.addEventListener('click', () => changePeriod(Number(button.dataset.period))));
    el.perPageSelect?.addEventListener('change', () => {
      state.pagination.perPage = Number(el.perPageSelect.value);
      goToPage(1);
    });
    el.btnFirst?.addEventListener('click', () => goToPage(1));
    el.btnPrev?.addEventListener('click', () => goToPage(state.pagination.currentPage - 1));
    el.btnNext?.addEventListener('click', () => goToPage(state.pagination.currentPage + 1));
    el.btnLast?.addEventListener('click', () => goToPage(state.pagination.totalPages));
    el.btnClear?.addEventListener('click', clearFilters);
    el.emptyAction?.addEventListener('click', clearFilters);
    el.retry?.addEventListener('click', loadData);
    el.inputBusca?.addEventListener('input', debounce(() => goToPage(1), 350));
    [el.selectStatus, el.selectOperadora, el.selectBackoffice, el.selectAcao].forEach(select => {
      select?.addEventListener('change', () => goToPage(1));
    });
    el.tbody?.addEventListener('click', handleTableClick);
    el.responsaveisLista?.addEventListener('click', handleBackofficeClick);
  }

  function changeView(view) {
    if (!['recentes', 'carteira'].includes(view) || state.view === view) return;
    state.view = view;
    state.pagination.currentPage = 1;
    updateView();
    loadData();
  }

  function changePeriod(period) {
    if (![30, 60, 365].includes(period) || state.period === period) return;
    state.period = period;
    state.pagination.currentPage = 1;
    el.periodButtons.forEach(button => {
      const active = Number(button.dataset.period) === period;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-pressed', String(active));
    });
    loadData();
  }

  function updateView() {
    const recentes = state.view === 'recentes';
    el.viewButtons.forEach(button => {
      const active = button.dataset.ccView === state.view;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-pressed', String(active));
    });
    el.periodPanel?.classList.toggle('d-none', !recentes);
    el.responsaveis?.classList.toggle('d-none', !recentes);
    el.recentOnly.forEach(item => item.classList.toggle('d-none', !recentes));
    el.portfolioOnly.forEach(item => item.classList.toggle('d-none', recentes));
    if (el.tableTitle) el.tableTitle.textContent = recentes ? 'Contratos que acabaram de ser implantados' : 'Consulta da carteira completa';
    if (el.tableSubtitle) el.tableSubtitle.textContent = recentes
      ? 'Mais recentes primeiro, para facilitar o acompanhamento.'
      : 'Clientes agrupados por CPF ou CNPJ, com todo o histórico da carteira.';
  }

  function buildParams() {
    const params = new URLSearchParams({
      visao: state.view,
      page: state.pagination.currentPage,
      per_page: state.pagination.perPage
    });
    if (state.view === 'recentes') params.set('periodo', state.period);
    if (el.inputBusca?.value.trim()) params.set('busca', el.inputBusca.value.trim());
    if (el.selectOperadora?.value) params.set('operadora', el.selectOperadora.value);
    if (state.view === 'recentes' && el.selectBackoffice?.value) params.set('backoffice', el.selectBackoffice.value);
    if (state.view === 'recentes' && el.selectAcao?.value) params.set('acao', el.selectAcao.value);
    if (state.view === 'carteira' && el.selectStatus?.value) params.set('status', el.selectStatus.value);
    return params;
  }

  async function loadData() {
    state.request?.abort();
    const request = new AbortController();
    state.request = request;
    showLoading(true);

    try {
      const response = await fetch(`/back-office/carteira-clientes/data?${buildParams()}`, {
        signal: request.signal,
        headers: { Accept: 'application/json' }
      });
      const data = await response.json();
      if (!response.ok || !data.success) throw new Error(data.message || 'Erro ao carregar dados');

      showError(false);
      renderKpis(data.kpis);
      if (state.view === 'recentes') {
        renderRecentContracts(data.contratos);
        renderBackofficeDistribution(data.distribuicao_backoffice || []);
      } else {
        renderPortfolio(data.clientes);
      }
      renderPagination(data.pagination);
    } catch (error) {
      if (error.name === 'AbortError') return;
      console.error(error);
      showError(true);
    } finally {
      if (state.request === request) showLoading(false);
    }
  }

  function renderKpis(kpis) {
    if (state.view === 'recentes') {
      setText(el.kpiTotalLabel, 'Implantados no período');
      setText(el.kpiAttentionLabel, 'Com ação pendente');
      setText(el.kpiPortLabel, 'Com portabilidade');
      setText(el.kpiCancelLabel, 'Com cancelamento');
      setText(el.kpiVidasLabel, 'Vidas implantadas');
      setNumber(el.kpiTotal, kpis.implantados);
      setNumber(el.kpiAtencao, kpis.atencao);
      setNumber(el.kpiPortabilidades, kpis.portabilidades);
      setNumber(el.kpiCancelamentos, kpis.cancelamentos);
      setNumber(el.kpiVidas, kpis.total_vidas);
      return;
    }

    setText(el.kpiTotalLabel, 'Total de clientes');
    setText(el.kpiAttentionLabel, 'Clientes ativos');
    setText(el.kpiPortLabel, 'Clientes inativos');
    setText(el.kpiCancelLabel, 'Valor da carteira');
    setText(el.kpiVidasLabel, 'Total de vidas');
    setNumber(el.kpiTotal, kpis.total_clientes);
    setNumber(el.kpiAtencao, kpis.clientes_ativos);
    setNumber(el.kpiPortabilidades, kpis.clientes_inativos);
    setText(el.kpiCancelamentos, formatCurrency(kpis.valor_carteira));
    setNumber(el.kpiVidas, kpis.total_vidas);
  }

  function renderRecentContracts(contracts) {
    el.thead.innerHTML = `<tr><th>Contrato</th><th>Implantação</th><th>Backoffice</th><th>Ação necessária</th><th>Plano</th><th>Vidas / valor</th><th><span class="visually-hidden">Ações</span></th></tr>`;
    if (!contracts?.length) {
      showEmpty(true);
      return;
    }
    showEmpty(false);
    el.tbody.innerHTML = contracts.map(contract => `
      <tr class="${contract.precisa_atencao ? 'cc-row-attention' : ''}">
        <td data-label="Contrato">
          <div class="cc-cell-cliente"><strong>${escapeHtml(contract.nome_contrato || 'Cliente sem nome')}</strong><span>${escapeHtml(contract.cpf_cnpj_formatado || '')}${contract.numero_proposta ? ` · Proposta ${escapeHtml(contract.numero_proposta)}` : ''}</span></div>
        </td>
        <td data-label="Implantação">
          <div class="cc-implantacao"><strong>${escapeHtml(contract.data_implantacao)}</strong><span class="${ageClass(contract.dias_implantado)}">${escapeHtml(contract.idade_label)}</span>${contract.data_vigencia ? `<small>Vigência ${escapeHtml(contract.data_vigencia)}</small>` : ''}</div>
        </td>
        <td data-label="Backoffice"><div class="cc-owner"><span>${initials(contract.backoffice)}</span><div><strong>${escapeHtml(contract.backoffice)}</strong><small>${contract.vendedor ? `Vendedor: ${escapeHtml(contract.vendedor)}` : 'Vendedor não informado'}</small></div></div></td>
        <td data-label="Ação necessária">${renderAttention(contract)}</td>
        <td data-label="Plano"><div class="cc-plan"><strong>${escapeHtml(contract.operadora || 'Não informada')}</strong><span>${escapeHtml(contract.nome_plano || 'Plano não informado')}</span>${contract.boas_vindas ? '<small class="is-done">Boas-vindas enviadas</small>' : '<small>Boas-vindas pendentes</small>'}</div></td>
        <td data-label="Vidas / valor"><div class="cc-numbers"><strong>${formatCurrency(contract.valor_contrato)}</strong><span>${formatNumber(contract.vidas)} ${contract.vidas === 1 ? 'vida' : 'vidas'}</span></div></td>
        <td class="cc-actions-cell">
          <a href="/back-office/abrir-contrato/${contract.id}" class="cc-btn-open">Abrir contrato</a>
          <button type="button" class="cc-btn-detail" data-client-detail data-cnpj="${escapeHtml(contract.cpf_cnpj)}" data-name="${escapeHtml(contract.nome_contrato)}" data-document="${escapeHtml(contract.cpf_cnpj_formatado)}" data-context="${escapeHtml(contract.idade_label)}">Histórico</button>
        </td>
      </tr>`).join('');
  }

  function renderPortfolio(clients) {
    el.thead.innerHTML = '<tr><th>Cliente</th><th>Contratos</th><th>Operadoras</th><th>Valor ativo</th><th>Vidas</th><th>Cliente desde</th><th>Status</th><th><span class="visually-hidden">Ações</span></th></tr>';
    if (!clients?.length) {
      showEmpty(true);
      return;
    }
    showEmpty(false);
    el.tbody.innerHTML = clients.map(client => `
      <tr>
        <td data-label="Cliente"><div class="cc-cell-cliente"><strong>${escapeHtml(client.nome_contrato)}</strong><span>${escapeHtml(client.cpf_cnpj_formatado)}</span></div></td>
        <td data-label="Contratos"><div class="cc-contract-count"><strong>${formatNumber(client.total_contratos)}</strong><span>${formatNumber(client.contratos_ativos)} ativos${client.contratos_cancelados ? ` · ${formatNumber(client.contratos_cancelados)} cancelados` : ''}</span></div></td>
        <td data-label="Operadoras"><div class="cc-tags">${String(client.operadoras || '').split(', ').filter(Boolean).map(item => `<span>${escapeHtml(item)}</span>`).join('')}</div></td>
        <td data-label="Valor ativo"><strong class="cc-money">${formatCurrency(client.valor_ativo)}</strong></td>
        <td data-label="Vidas">${formatNumber(client.vidas_ativas)}</td>
        <td data-label="Cliente desde"><div class="cc-plan"><strong>${escapeHtml(client.primeiro_contrato || '—')}</strong><span>Há ${escapeHtml(client.tempo_label)}</span></div></td>
        <td data-label="Status">${getStatusBadge(client.status)}</td>
        <td class="cc-actions-cell"><button type="button" class="cc-btn-open" data-client-detail data-cnpj="${escapeHtml(client.cpf_cnpj)}" data-name="${escapeHtml(client.nome_contrato)}" data-document="${escapeHtml(client.cpf_cnpj_formatado)}" data-context="Cliente há ${escapeHtml(client.tempo_label)}">Ver contratos</button></td>
      </tr>`).join('');
  }

  function renderAttention(contract) {
    const actions = [];
    if (contract.portabilidades_pendentes > 0) actions.push(`<span class="cc-action-chip is-port">${formatNumber(contract.portabilidades_pendentes)} portabilidade${contract.portabilidades_pendentes > 1 ? 's' : ''}</span>`);
    if (contract.cancelamentos_pendentes > 0) actions.push(`<span class="cc-action-chip is-cancel">${formatNumber(contract.cancelamentos_pendentes)} cancelamento${contract.cancelamentos_pendentes > 1 ? 's' : ''}</span>`);
    return actions.length ? `<div class="cc-action-list">${actions.join('')}</div>` : '<span class="cc-no-action">Sem ação sinalizada</span>';
  }

  function renderBackofficeDistribution(items) {
    if (!el.responsaveisLista) return;
    const max = Math.max(...items.map(item => item.total), 1);
    el.responsaveisLista.innerHTML = items.length ? items.map(item => `
      <button type="button" class="cc-owner-summary ${String(el.selectBackoffice?.value) === String(item.id) ? 'is-active' : ''}" data-backoffice-id="${escapeHtml(item.id)}" role="listitem">
        <span class="cc-owner-summary-avatar">${initials(item.nome)}</span>
        <span class="cc-owner-summary-copy"><strong>${escapeHtml(item.nome)}</strong><span><i style="--cc-progress:${Math.round((item.total / max) * 100)}%"></i></span></span>
        <b>${formatNumber(item.total)}</b>
      </button>`).join('') : '<p class="cc-distribution-empty">Nenhum contrato implantado neste período.</p>';
    if (el.responsaveisPeriodo) el.responsaveisPeriodo.textContent = periodLabel(state.period);
  }

  function renderPagination(pagination) {
    state.pagination.currentPage = pagination.current_page;
    state.pagination.totalPages = pagination.total_pages;
    state.pagination.total = pagination.total;
    const from = pagination.total ? ((pagination.current_page - 1) * pagination.per_page) + 1 : 0;
    const to = Math.min(pagination.current_page * pagination.per_page, pagination.total);
    const subject = state.view === 'recentes' ? 'contrato' : 'cliente';
    setText(el.paginationInfo, `Mostrando ${from}–${to} de ${pagination.total}`);
    setText(el.pageIndicator, `${pagination.current_page} / ${pagination.total_pages}`);
    setText(el.totalBadge, `${formatNumber(pagination.total)} ${subject}${pagination.total === 1 ? '' : 's'}`);
    if (el.btnFirst) el.btnFirst.disabled = !pagination.has_prev;
    if (el.btnPrev) el.btnPrev.disabled = !pagination.has_prev;
    if (el.btnNext) el.btnNext.disabled = !pagination.has_next;
    if (el.btnLast) el.btnLast.disabled = !pagination.has_next;
    el.paginationWrap?.classList.toggle('d-none', pagination.total === 0);
  }

  function handleTableClick(event) {
    const button = event.target.closest('[data-client-detail]');
    if (!button) return;
    openDetail(button.dataset.cnpj, button.dataset.name, button.dataset.document, button.dataset.context);
  }

  function handleBackofficeClick(event) {
    const button = event.target.closest('[data-backoffice-id]');
    if (!button || !el.selectBackoffice) return;
    el.selectBackoffice.value = el.selectBackoffice.value === button.dataset.backofficeId ? '' : button.dataset.backofficeId;
    goToPage(1);
  }

  async function openDetail(cnpj, name, documentNumber, context) {
    setText(el.detalheNome, name);
    setText(el.detalheCnpj, documentNumber);
    setText(el.detalheTempo, context);
    if (el.detalheBody) el.detalheBody.innerHTML = '<div class="cc-modal-skeleton"><span></span><span></span><span></span></div>';
    if (typeof bootstrap !== 'undefined' && el.modalDetalhe) new bootstrap.Modal(el.modalDetalhe).show();

    try {
      const response = await fetch(`/back-office/carteira-clientes/detalhe/${encodeURIComponent(cnpj)}`, { headers: { Accept: 'application/json' } });
      const data = await response.json();
      if (!response.ok || !data.success) throw new Error(data.message || 'Erro ao carregar contratos');
      renderDetail(data.contratos);
    } catch (error) {
      if (el.detalheBody) el.detalheBody.innerHTML = '<div class="cc-modal-state"><strong>Não foi possível carregar o histórico.</strong><span>Feche esta janela e tente novamente.</span></div>';
    }
  }

  function renderDetail(contracts) {
    if (!el.detalheBody) return;
    if (!contracts?.length) {
      el.detalheBody.innerHTML = '<div class="cc-modal-state"><strong>Nenhum contrato encontrado.</strong></div>';
      return;
    }
    el.detalheBody.innerHTML = `<div class="cc-contract-list">${contracts.map(contract => `
      <article class="cc-contract-item ${contract.is_ativo ? 'is-active' : 'is-inactive'}">
        <header><div><strong>${escapeHtml(contract.operadora || 'Operadora não informada')}</strong><span>${escapeHtml(contract.nome_plano || 'Plano não informado')}</span></div>${getStatusBadge(contract.is_ativo ? 'ativo' : 'inativo', contract.status_descricao)}</header>
        <dl><div><dt>Valor</dt><dd>${formatCurrency(contract.valor_contrato)}</dd></div><div><dt>Vidas</dt><dd>${formatNumber(contract.vidas)}</dd></div><div><dt>Implantação</dt><dd>${escapeHtml(contract.data_implantacao || '—')}</dd></div><div><dt>Vigência</dt><dd>${escapeHtml(contract.data_vigencia || '—')}</dd></div></dl>
        <footer><span>${escapeHtml(contract.vendedor || 'Vendedor não informado')}</span>${contract.numero_proposta ? `<span>Proposta ${escapeHtml(contract.numero_proposta)}</span>` : ''}<a href="/back-office/abrir-contrato/${contract.id}">Abrir contrato</a></footer>
      </article>`).join('')}</div>`;
  }

  function showLoading(show) {
    el.loading?.classList.toggle('d-none', !show);
    if (show) {
      el.tableWrap?.classList.add('d-none');
      el.empty?.classList.add('d-none');
      el.error?.classList.add('d-none');
      el.paginationWrap?.classList.add('d-none');
    }
  }

  function showEmpty(show) {
    el.empty?.classList.toggle('d-none', !show);
    el.tableWrap?.classList.toggle('d-none', show);
    if (show) {
      setText(el.emptyTitle, state.view === 'recentes' ? 'Nenhum contrato neste período' : 'Nenhum cliente encontrado');
      setText(el.emptyCopy, state.view === 'recentes' ? 'Amplie o período ou limpe os filtros para consultar outros contratos.' : 'Ajuste a busca ou limpe os filtros da carteira.');
    }
  }

  function showError(show) {
    el.error?.classList.toggle('d-none', !show);
    if (show) {
      el.empty?.classList.add('d-none');
      el.tableWrap?.classList.add('d-none');
      el.paginationWrap?.classList.add('d-none');
    }
  }

  function clearFilters() {
    if (el.inputBusca) el.inputBusca.value = '';
    if (el.selectStatus) el.selectStatus.value = '';
    if (el.selectOperadora) el.selectOperadora.value = '';
    if (el.selectBackoffice) el.selectBackoffice.value = '';
    if (el.selectAcao) el.selectAcao.value = '';
    goToPage(1);
  }

  function goToPage(page) {
    state.pagination.currentPage = Math.max(1, Math.min(page, state.pagination.totalPages || 1));
    loadData();
  }

  function getStatusBadge(status, label) {
    const badges = { ativo: ['is-active', 'Ativo'], misto: ['is-mixed', 'Misto'], inativo: ['is-inactive', 'Inativo'] };
    const badge = badges[status] || badges.inativo;
    return `<span class="cc-status ${badge[0]}"><i></i>${escapeHtml(label || badge[1])}</span>`;
  }

  function ageClass(days) {
    if (days <= 30) return 'is-new';
    if (days <= 60) return 'is-recent';
    return 'is-year';
  }

  function periodLabel(period) {
    return { 30: 'Último mês', 60: 'Últimos 2 meses', 365: 'Últimos 12 meses' }[period] || 'Período selecionado';
  }

  function initials(name) {
    return String(name || '?').trim().split(/\s+/).slice(0, 2).map(part => part[0]).join('').toUpperCase();
  }

  function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0));
  }

  function formatNumber(value) {
    return Number(value || 0).toLocaleString('pt-BR');
  }

  function setText(element, value) {
    if (element) element.textContent = value;
  }

  function setNumber(element, value) {
    setText(element, formatNumber(value));
  }

  function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value ?? '');
    return div.innerHTML;
  }

  function debounce(callback, wait) {
    let timeout;
    return (...args) => {
      clearTimeout(timeout);
      timeout = setTimeout(() => callback(...args), wait);
    };
  }

  document.addEventListener('DOMContentLoaded', init);
}());
