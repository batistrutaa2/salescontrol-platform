'use strict';

/**
 * Pós-Venda Page JavaScript
 * Manages post-sale contracts and anniversary tracking
 */

(function () {
  // State
  let contratos = [];
  let filteredContratos = [];
  let pagination = {
    currentPage: 1,
    perPage: 15,
    total: 0,
    totalPages: 0
  };

  // DOM Elements
  const elements = {
    loading: document.getElementById('pv-loading'),
    tableCard: document.getElementById('pv-table-card'),
    tbody: document.getElementById('contratos-tbody'),
    emptyState: document.getElementById('pv-empty-state'),
    tableCount: document.getElementById('table-count'),

    // KPIs
    kpiTotal: document.getElementById('kpi-total'),
    kpiAniversarios: document.getElementById('kpi-aniversarios'),
    kpiProximos: document.getElementById('kpi-proximos'),
    kpiValor: document.getElementById('kpi-valor'),

    // Filters
    filterOperadora: document.getElementById('filter-operadora'),
    filterVendedor: document.getElementById('filter-vendedor'),
    filterMes: document.getElementById('filter-mes'),
    filterBusca: document.getElementById('filter-busca'),
    btnClear: document.getElementById('btn-clear-filters'),

    // Modals
    modalAnotacoes: document.getElementById('modalAnotacoes'),
    modalHistorico: document.getElementById('modalHistorico'),

    // Pagination
    paginationWrapper: document.getElementById('pv-pagination'),
    paginationFrom: document.getElementById('pagination-from'),
    paginationTo: document.getElementById('pagination-to'),
    paginationTotal: document.getElementById('pagination-total'),
    currentPageSpan: document.getElementById('current-page'),
    totalPagesSpan: document.getElementById('total-pages'),
    perPageSelect: document.getElementById('per-page'),
    btnFirst: document.getElementById('btn-first'),
    btnPrev: document.getElementById('btn-prev'),
    btnNext: document.getElementById('btn-next'),
    btnLast: document.getElementById('btn-last'),
  };

  // Initialize
  function init() {
    loadData();
    bindEvents();
  }

  // Bind events
  function bindEvents() {
    // Filter change events
    elements.filterOperadora.addEventListener('change', applyFilters);
    elements.filterVendedor.addEventListener('change', applyFilters);
    elements.filterMes.addEventListener('change', applyFilters);
    elements.filterBusca.addEventListener('input', debounce(applyFilters, 300));
    elements.btnClear.addEventListener('click', clearFilters);

    // Anotações modal events
    document.getElementById('btn-salvar-anotacao')?.addEventListener('click', saveAnotacao);

    // Pagination events
    elements.perPageSelect?.addEventListener('change', handlePerPageChange);
    elements.btnFirst?.addEventListener('click', () => goToPage(1));
    elements.btnPrev?.addEventListener('click', () => goToPage(pagination.currentPage - 1));
    elements.btnNext?.addEventListener('click', () => goToPage(pagination.currentPage + 1));
    elements.btnLast?.addEventListener('click', () => goToPage(pagination.totalPages));
  }

  // Load data from API
  async function loadData() {
    showLoading(true);

    try {
      const params = new URLSearchParams();

      // Pagination params
      params.append('page', pagination.currentPage);
      params.append('per_page', pagination.perPage);

      // Filter params
      if (elements.filterOperadora.value) {
        params.append('operadora', elements.filterOperadora.value);
      }
      if (elements.filterVendedor.value) {
        params.append('vendedor_id', elements.filterVendedor.value);
      }
      if (elements.filterMes.value) {
        params.append('mes_aniversario', elements.filterMes.value);
      }
      if (elements.filterBusca.value) {
        params.append('busca', elements.filterBusca.value);
      }

      const response = await fetch(`/back-office/pos-venda/data?${params.toString()}`);
      const data = await response.json();

      if (data.success) {
        contratos = data.contratos;
        filteredContratos = contratos;

        // Update pagination state
        if (data.pagination) {
          pagination.currentPage = data.pagination.current_page;
          pagination.perPage = data.pagination.per_page;
          pagination.total = data.pagination.total;
          pagination.totalPages = data.pagination.total_pages;
        }

        updateKPIs(data.kpis);
        renderTable();
        updatePagination();
      } else {
        showError('Erro ao carregar dados');
      }
    } catch (error) {
      console.error('Erro ao carregar dados:', error);
      showError('Erro ao carregar dados');
    } finally {
      showLoading(false);
    }
  }

  // Apply filters
  function applyFilters() {
    pagination.currentPage = 1; // Reset to first page when filters change
    loadData();
  }

  // Clear filters
  function clearFilters() {
    elements.filterOperadora.value = '';
    elements.filterVendedor.value = '';
    elements.filterMes.value = '';
    elements.filterBusca.value = '';
    pagination.currentPage = 1;
    loadData();
  }

  // Pagination functions
  function updatePagination() {
    if (pagination.total === 0) {
      elements.paginationWrapper.style.display = 'none';
      return;
    }

    elements.paginationWrapper.style.display = 'flex';

    // Calculate from/to
    const from = ((pagination.currentPage - 1) * pagination.perPage) + 1;
    const to = Math.min(pagination.currentPage * pagination.perPage, pagination.total);

    // Update info
    elements.paginationFrom.textContent = from;
    elements.paginationTo.textContent = to;
    elements.paginationTotal.textContent = pagination.total;
    elements.currentPageSpan.textContent = pagination.currentPage;
    elements.totalPagesSpan.textContent = pagination.totalPages;

    // Update button states
    const isFirstPage = pagination.currentPage <= 1;
    const isLastPage = pagination.currentPage >= pagination.totalPages;

    elements.btnFirst.disabled = isFirstPage;
    elements.btnPrev.disabled = isFirstPage;
    elements.btnNext.disabled = isLastPage;
    elements.btnLast.disabled = isLastPage;
  }

  function goToPage(page) {
    if (page < 1 || page > pagination.totalPages || page === pagination.currentPage) {
      return;
    }
    pagination.currentPage = page;
    loadData();
  }

  function handlePerPageChange() {
    const newPerPage = parseInt(elements.perPageSelect.value, 10);
    if (newPerPage !== pagination.perPage) {
      pagination.perPage = newPerPage;
      pagination.currentPage = 1; // Reset to first page
      loadData();
    }
  }

  // Update KPIs
  function updateKPIs(kpis) {
    elements.kpiTotal.textContent = kpis.total_implantados.toLocaleString('pt-BR');
    elements.kpiAniversarios.textContent = kpis.aniversarios_mes.toLocaleString('pt-BR');
    elements.kpiProximos.textContent = kpis.proximos_aniversarios.toLocaleString('pt-BR');
    elements.kpiValor.textContent = formatCurrency(kpis.valor_carteira);
  }

  // Render table
  function renderTable() {
    if (filteredContratos.length === 0) {
      elements.tbody.innerHTML = '';
      elements.emptyState.style.display = 'block';
      elements.tableCount.textContent = '0';
      return;
    }

    elements.emptyState.style.display = 'none';
    elements.tableCount.textContent = filteredContratos.length.toLocaleString('pt-BR');

    const html = filteredContratos.map(contrato => renderRow(contrato)).join('');
    elements.tbody.innerHTML = html;
  }

  // Render single row
  function renderRow(contrato) {
    const anniversaryBadge = getAnniversaryBadge(contrato.dias_para_aniversario, contrato.proximo_aniversario);
    const mesesTexto = contrato.meses_implantado === 1 ? '1 mês' : `${contrato.meses_implantado} meses`;

    return `
      <tr data-id="${contrato.id}">
        <td>
          <div class="contract-cell">
            <div class="contract-name">${escapeHtml(contrato.nome_contrato || 'N/A')}</div>
            <div class="contract-info">
              <span class="contract-id">#${contrato.numero_proposta || contrato.id}</span>
              <span class="separator"></span>
              <span>${escapeHtml(contrato.vendedor || 'N/A')}</span>
            </div>
          </div>
        </td>
        <td>
          <span>${escapeHtml(contrato.operadora || 'N/A')}</span>
          ${contrato.nome_plano ? `<br><small class="text-muted">${escapeHtml(contrato.nome_plano)}</small>` : ''}
        </td>
        <td>
          <div class="value-cell">
            <span class="value-main">${formatCurrency(contrato.valor_contrato)}</span>
            ${contrato.vidas ? `<div class="value-info">${contrato.vidas} ${contrato.vidas === 1 ? 'vida' : 'vidas'}</div>` : ''}
          </div>
        </td>
        <td>
          <div class="implantado-cell">
            <span class="implantado-time">${mesesTexto}</span>
            <div class="implantado-date">desde ${contrato.data_implantacao}</div>
          </div>
        </td>
        <td>
          ${anniversaryBadge}
        </td>
        <td class="actions-cell">
          <div class="dropdown">
            <button type="button" class="btn-action" data-bs-toggle="dropdown" aria-expanded="false">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="1"/>
                <circle cx="12" cy="5" r="1"/>
                <circle cx="12" cy="19" r="1"/>
              </svg>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="/back-office/abrir-contrato/${contrato.id}">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                  Ver Contrato
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="javascript:void(0)" onclick="posVenda.openAnotacoes(${contrato.id})">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                  </svg>
                  Anotações Pós-Venda
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="javascript:void(0)" onclick="posVenda.openHistorico(${contrato.id})">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                  </svg>
                  Ver Histórico
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item" href="javascript:void(0)" onclick="posVenda.gerarRecebiveis(${contrato.id})">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                  </svg>
                  Gerar Recebíveis
                </a>
              </li>
            </ul>
          </div>
        </td>
      </tr>
    `;
  }

  // Get anniversary badge based on days
  function getAnniversaryBadge(dias, dataAniversario) {
    let badgeClass = 'badge-normal';
    let icon = '📅';

    if (dias !== null && dias !== undefined) {
      if (dias <= 7) {
        badgeClass = 'badge-urgent';
        icon = '🎂';
      } else if (dias <= 30) {
        badgeClass = 'badge-soon';
        icon = '🎂';
      }

      const diasTexto = dias === 0 ? 'Hoje!' :
                        dias === 1 ? 'em 1 dia' :
                        `em ${Math.floor(dias)} dias`;

      return `
        <span class="anniversary-badge ${badgeClass}">
          <span class="anniversary-icon">${icon}</span>
          ${dataAniversario}
          <span class="anniversary-days">(${diasTexto})</span>
        </span>
      `;
    }

    return `
      <span class="anniversary-badge badge-normal">
        <span class="anniversary-icon">📅</span>
        ${dataAniversario || 'N/A'}
      </span>
    `;
  }

  // Open anotações timeline modal
  async function openAnotacoes(vendaId) {
    document.getElementById('anotacao-venda-id').value = vendaId;
    document.getElementById('anotacao-texto').value = '';

    // Reset modal state
    document.getElementById('anotacao-contrato-nome').textContent = 'Carregando...';
    document.getElementById('anotacao-contrato-detalhe').textContent = '';
    document.getElementById('timeline-loading').style.display = 'flex';
    document.getElementById('timeline-empty').style.display = 'none';
    document.getElementById('anotacoes-timeline').style.display = 'none';

    const modal = new bootstrap.Modal(elements.modalAnotacoes);
    modal.show();

    // Load anotações
    await loadAnotacoes(vendaId);
  }

  // Load anotações from API
  async function loadAnotacoes(vendaId) {
    try {
      const response = await fetch(`/back-office/pos-venda/anotacoes/${vendaId}`);
      const data = await response.json();

      if (data.success) {
        // Update contract info
        document.getElementById('anotacao-contrato-nome').textContent = data.venda.nome_contrato || 'Contrato';
        document.getElementById('anotacao-contrato-detalhe').textContent = data.venda.operadora || '';

        // Render timeline
        renderAnotacoesTimeline(data.anotacoes);
      } else {
        showError(data.message || 'Erro ao carregar anotações');
      }
    } catch (error) {
      console.error('Erro ao carregar anotações:', error);
      showError('Erro ao carregar anotações');
    } finally {
      document.getElementById('timeline-loading').style.display = 'none';
    }
  }

  // Render anotações timeline
  function renderAnotacoesTimeline(anotacoes) {
    const timelineEl = document.getElementById('anotacoes-timeline');
    const emptyEl = document.getElementById('timeline-empty');

    if (!anotacoes || anotacoes.length === 0) {
      timelineEl.style.display = 'none';
      emptyEl.style.display = 'flex';
      return;
    }

    emptyEl.style.display = 'none';
    timelineEl.style.display = 'block';

    const html = anotacoes.map((anotacao, index) => `
      <div class="timeline-item ${index === 0 ? 'timeline-item-latest' : ''}">
        <div class="timeline-dot"></div>
        <div class="timeline-content">
          <div class="timeline-header">
            <div class="timeline-user">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
              <span>${escapeHtml(anotacao.usuario)}</span>
            </div>
            <div class="timeline-date" title="${anotacao.data}">
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
              </svg>
              <span>${anotacao.data_relativa}</span>
            </div>
          </div>
          <div class="timeline-text">${escapeHtml(anotacao.descricao)}</div>
        </div>
      </div>
    `).join('');

    timelineEl.innerHTML = html;
  }

  // Save anotação
  async function saveAnotacao() {
    const vendaId = document.getElementById('anotacao-venda-id').value;
    const texto = document.getElementById('anotacao-texto').value.trim();

    if (!texto) {
      Swal.fire({
        icon: 'warning',
        title: 'Atenção',
        text: 'Por favor, descreva a tratativa realizada.',
        confirmButtonColor: '#7C3AED'
      });
      return;
    }

    const btnSalvar = document.getElementById('btn-salvar-anotacao');
    const originalContent = btnSalvar.innerHTML;
    btnSalvar.disabled = true;
    btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Salvando...';

    try {
      const response = await fetch('/back-office/pos-venda/anotacoes', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
          venda_id: vendaId,
          descricao: texto
        })
      });

      const data = await response.json();

      if (data.success) {
        // Clear textarea
        document.getElementById('anotacao-texto').value = '';

        // Add new anotação to timeline
        const timelineEl = document.getElementById('anotacoes-timeline');
        const emptyEl = document.getElementById('timeline-empty');

        emptyEl.style.display = 'none';
        timelineEl.style.display = 'block';

        // Create new timeline item
        const newItemHtml = `
          <div class="timeline-item timeline-item-latest timeline-item-new">
            <div class="timeline-dot"></div>
            <div class="timeline-content">
              <div class="timeline-header">
                <div class="timeline-user">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                  </svg>
                  <span>${escapeHtml(data.anotacao.usuario)}</span>
                </div>
                <div class="timeline-date" title="${data.anotacao.data}">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                  </svg>
                  <span>${data.anotacao.data_relativa}</span>
                </div>
              </div>
              <div class="timeline-text">${escapeHtml(data.anotacao.descricao)}</div>
            </div>
          </div>
        `;

        // Remove latest class from previous items
        timelineEl.querySelectorAll('.timeline-item-latest').forEach(el => {
          el.classList.remove('timeline-item-latest');
        });

        // Prepend new item
        timelineEl.insertAdjacentHTML('afterbegin', newItemHtml);

        // Toast success
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: 'Anotação salva com sucesso!',
          showConfirmButton: false,
          timer: 2000
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Erro',
          text: data.message || 'Não foi possível salvar a anotação.',
          confirmButtonColor: '#7C3AED'
        });
      }
    } catch (error) {
      console.error('Erro ao salvar anotação:', error);
      Swal.fire({
        icon: 'error',
        title: 'Erro',
        text: 'Não foi possível salvar a anotação.',
        confirmButtonColor: '#7C3AED'
      });
    } finally {
      btnSalvar.disabled = false;
      btnSalvar.innerHTML = originalContent;
    }
  }

  // Open histórico modal
  async function openHistorico(vendaId) {
    const historicoContent = document.getElementById('historico-content');
    historicoContent.innerHTML = `
      <div class="p-4 text-center">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Carregando...</span>
        </div>
        <p class="mt-2 mb-0 text-muted">Carregando histórico...</p>
      </div>
    `;

    const modal = new bootstrap.Modal(elements.modalHistorico);
    modal.show();

    try {
      const response = await fetch(`/back-office/historico/${vendaId}`);
      const data = await response.json();

      if (data.success) {
        renderHistorico(data);
      } else {
        historicoContent.innerHTML = `
          <div class="p-4 text-center text-muted">
            <p>Não foi possível carregar o histórico.</p>
          </div>
        `;
      }
    } catch (error) {
      console.error('Erro ao carregar histórico:', error);
      historicoContent.innerHTML = `
        <div class="p-4 text-center text-danger">
          <p>Erro ao carregar histórico.</p>
        </div>
      `;
    }
  }

  // Render histórico
  function renderHistorico(data) {
    const historicoContent = document.getElementById('historico-content');

    if (!data.historico || data.historico.length === 0) {
      historicoContent.innerHTML = `
        <div class="p-4 text-center text-muted">
          <p>Nenhum histórico encontrado para este contrato.</p>
        </div>
      `;
      return;
    }

    let html = `
      <div class="p-4">
        <div class="mb-3 pb-3 border-bottom">
          <strong>${escapeHtml(data.venda?.nome_contrato || 'Contrato')}</strong>
          <br><small class="text-muted">${data.venda?.operadora || ''} - ${formatCurrency(data.venda?.valor_contrato || 0)}</small>
        </div>
        <div class="timeline">
    `;

    data.historico.forEach((item, index) => {
      html += `
        <div class="timeline-item ${index === 0 ? 'active' : ''}">
          <div class="timeline-dot"></div>
          <div class="timeline-content">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <strong>${escapeHtml(item.status_anterior || 'Início')}</strong>
                <span class="mx-1">→</span>
                <strong class="text-primary">${escapeHtml(item.status_novo)}</strong>
              </div>
              <small class="text-muted">${item.data}</small>
            </div>
            <div class="mt-1">
              <small class="text-muted">Por: ${escapeHtml(item.usuario)}</small>
              ${item.observacao ? `<p class="mb-0 mt-1 small">${escapeHtml(item.observacao)}</p>` : ''}
            </div>
          </div>
        </div>
      `;
    });

    html += '</div></div>';
    historicoContent.innerHTML = html;
  }

  // Gerar recebíveis
  async function gerarRecebiveis(vendaId) {
    const result = await Swal.fire({
      title: 'Gerar Recebíveis',
      text: 'Deseja gerar/atualizar os recebíveis para este contrato?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sim, gerar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#7C3AED'
    });

    if (!result.isConfirmed) return;

    try {
      const response = await fetch(`/back-office/gerar-recebivel/${vendaId}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      });

      const data = await response.json();

      if (data.success) {
        Swal.fire({
          icon: 'success',
          title: 'Sucesso',
          text: data.message,
          confirmButtonColor: '#7C3AED'
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Erro',
          text: data.message,
          confirmButtonColor: '#7C3AED'
        });
      }
    } catch (error) {
      console.error('Erro ao gerar recebíveis:', error);
      Swal.fire({
        icon: 'error',
        title: 'Erro',
        text: 'Não foi possível gerar os recebíveis.',
        confirmButtonColor: '#7C3AED'
      });
    }
  }

  // Utility: Show/hide loading
  function showLoading(show) {
    elements.loading.style.display = show ? 'flex' : 'none';
    elements.tableCard.style.display = show ? 'none' : 'block';
  }

  // Utility: Show error
  function showError(message) {
    Swal.fire({
      icon: 'error',
      title: 'Erro',
      text: message,
      confirmButtonColor: '#7C3AED'
    });
  }

  // Utility: Format currency
  function formatCurrency(value) {
    if (value === null || value === undefined) return 'R$ 0,00';
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(value);
  }

  // Utility: Escape HTML
  function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Utility: Debounce
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // Export public functions
  window.posVenda = {
    openAnotacoes,
    openHistorico,
    gerarRecebiveis
  };

  // Initialize when DOM is ready
  document.addEventListener('DOMContentLoaded', init);
})();
