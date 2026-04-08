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
    kpiBoasVindas: document.getElementById('kpi-boas-vindas'),

    // Filters
    filterOperadora: document.getElementById('filter-operadora'),
    filterVendedor: document.getElementById('filter-vendedor'),
    filterMes: document.getElementById('filter-mes'),
    filterBoasVindas: document.getElementById('filter-boas-vindas'),
    filterBusca: document.getElementById('filter-busca'),
    btnClear: document.getElementById('btn-clear-filters'),

    // Modals
    modalAnotacoes: document.getElementById('modalAnotacoes'),
    modalHistorico: document.getElementById('modalHistorico'),
    modalDataImplantacao: document.getElementById('modalDataImplantacao'),
    modalBoasVindas: document.getElementById('modalBoasVindas'),

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
    elements.filterBoasVindas?.addEventListener('change', applyFilters);
    elements.filterBusca.addEventListener('input', debounce(applyFilters, 300));
    elements.btnClear.addEventListener('click', clearFilters);

    // Anotações modal events
    document.getElementById('btn-salvar-anotacao')?.addEventListener('click', saveAnotacao);

    // Data Implantação modal events
    document.getElementById('btn-salvar-data-implantacao')?.addEventListener('click', saveDataImplantacao);

    // Boas Vindas modal events
    document.getElementById('btn-confirmar-boas-vindas')?.addEventListener('click', confirmarBoasVindas);
    document.getElementById('btn-config-token')?.addEventListener('click', abrirConfigToken);

    // Live preview no modo padrão
    ['bv-login-app', 'bv-senha-app', 'bv-portal-user', 'bv-portal-senha', 'bv-link-ios', 'bv-link-android'].forEach(id => {
      document.getElementById(id)?.addEventListener('input', atualizarPreviewPadrao);
    });

    // Pagination events
    elements.perPageSelect?.addEventListener('change', handlePerPageChange);
    elements.btnFirst?.addEventListener('click', () => goToPage(1));
    elements.btnPrev?.addEventListener('click', () => goToPage(pagination.currentPage - 1));
    elements.btnNext?.addEventListener('click', () => goToPage(pagination.currentPage + 1));
    elements.btnLast?.addEventListener('click', () => goToPage(pagination.totalPages));

    // Fix dropdown positioning
    document.addEventListener('show.bs.dropdown', handleDropdownShow);
    document.addEventListener('hidden.bs.dropdown', handleDropdownHidden);
  }

  // Handle dropdown position to prevent clipping
  function handleDropdownShow(event) {
    const dropdown = event.target.closest('.actions-cell');
    if (!dropdown) return;

    const button = event.target;
    const menu = dropdown.querySelector('.dropdown-menu');
    if (!menu) return;

    // Wait for Bootstrap to show the menu
    setTimeout(() => {
      const buttonRect = button.getBoundingClientRect();
      const viewportHeight = window.innerHeight;

      // Use fixed positioning for all dropdowns in actions
      menu.style.position = 'fixed';
      menu.style.top = `${buttonRect.bottom + 4}px`;
      menu.style.right = `${window.innerWidth - buttonRect.right}px`;
      menu.style.left = 'auto';
      menu.style.bottom = 'auto';
      menu.style.transform = 'none';

      // Check if menu goes below viewport, if so position above
      requestAnimationFrame(() => {
        const menuRect = menu.getBoundingClientRect();
        if (menuRect.bottom > viewportHeight - 10) {
          menu.style.top = 'auto';
          menu.style.bottom = `${viewportHeight - buttonRect.top + 4}px`;
        }
      });
    }, 0);
  }

  // Clean up dropdown styles when hidden
  function handleDropdownHidden(event) {
    const dropdown = event.target.closest('.actions-cell');
    if (!dropdown) return;

    const menu = dropdown.querySelector('.dropdown-menu');
    if (!menu) return;

    // Reset inline styles
    menu.style.position = '';
    menu.style.top = '';
    menu.style.right = '';
    menu.style.left = '';
    menu.style.bottom = '';
    menu.style.transform = '';
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
      if (elements.filterBoasVindas?.value) {
        params.append('boas_vindas', elements.filterBoasVindas.value);
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
    if (elements.filterBoasVindas) elements.filterBoasVindas.value = '';
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
    if (elements.kpiBoasVindas) {
      elements.kpiBoasVindas.textContent = (kpis.aguardando_boas_vindas || 0).toLocaleString('pt-BR');
    }
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
            </div>
          </div>
        </td>
        <td>
          <div class="vendedor-cell">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
            <span>${escapeHtml(contrato.vendedor || 'N/A')}</span>
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
        <td>
          ${getBoasVindasBadge(contrato)}
        </td>
        <td class="actions-cell">
          <div class="dropdown">
            <button type="button" class="btn-action" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
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
              <li>
                <a class="dropdown-item" href="/back-office/comprovante/${contrato.id}" target="_blank">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                  </svg>
                  Baixar Comprovante
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="javascript:void(0)" onclick="posVenda.openAlterarDataImplantacao(${contrato.id}, '${contrato.data_implantacao || ''}')">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                  </svg>
                  Alterar Data Implantação
                </a>
              </li>
              <li>
                <a class="dropdown-item text-info" href="javascript:void(0)" onclick="posVenda.openBoasVindas(${contrato.id})">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                  </svg>
                  ${contrato.boas_vindas_enviado_em ? 'Reenviar Boas Vindas' : 'Marcar Boas Vindas'}
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
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="posVenda.excluirContrato(${contrato.id}, '${escapeHtml(contrato.nome_contrato || '')}')">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    <line x1="10" y1="11" x2="10" y2="17"/>
                    <line x1="14" y1="11" x2="14" y2="17"/>
                  </svg>
                  Excluir Contrato
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

  // Get boas vindas badge
  function getBoasVindasBadge(contrato) {
    if (contrato.boas_vindas_enviado_em) {
      return `
        <span class="boas-vindas-badge badge-enviado" title="Enviado em ${contrato.boas_vindas_enviado_em} por ${contrato.boas_vindas_enviado_por || 'N/A'}">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          Enviado
        </span>
      `;
    }
    return `
      <span class="boas-vindas-badge badge-pendente">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        </svg>
        Pendente
      </span>
    `;
  }

  // =============================================
  // Boas Vindas — estado global do modal
  // =============================================
  let bvModoSelecionado = 'padrao';
  let bvTitulares = [];
  let bvVendaInfo = {};

  // Links dos apps por operadora (adicione novas operadoras conforme necessário)
  const LINKS_APP_OPERADORAS = {
    amil: {
      ios:     'https://apps.apple.com/br/app/amil-clientes/id471890526',
      android: 'https://play.google.com/store/apps/details?id=br.com.amil.beneficiarios&hl=pt_BR',
    },
  };

  function getLinksAppPorOperadora(operadora) {
    const key = operadora.toLowerCase().trim();
    for (const [nome, links] of Object.entries(LINKS_APP_OPERADORAS)) {
      if (key.includes(nome)) return links;
    }
    return { ios: '', android: '' };
  }

  // Abre o modal de Boas Vindas e carrega dados via API
  async function openBoasVindas(vendaId) {
    document.getElementById('boas-vindas-venda-id').value = vendaId;
    document.getElementById('bv-no-token-alert').classList.add('d-none');

    const modal = new bootstrap.Modal(elements.modalBoasVindas);
    modal.show();

    try {
      const res = await fetch(`/back-office/pos-venda/beneficiarios/${vendaId}`);
      const data = await res.json();

      if (!data.success) return;

      bvVendaInfo = data.venda;
      bvTitulares = data.titulares || [];

      document.getElementById('bv-contrato-nome').textContent = data.venda.nome_contrato || '-';
      document.getElementById('bv-operadora').textContent = data.venda.operadora || '-';
      document.getElementById('bv-plano').textContent = data.venda.plano || '-';
      document.getElementById('bv-data-implantacao').textContent = data.venda.data_implantacao || '-';

      // Pré-preencher links do app conforme operadora
      const linksApp = getLinksAppPorOperadora(data.venda.operadora || '');
      document.getElementById('bv-link-ios').value = linksApp.ios;
      document.getElementById('bv-link-android').value = linksApp.android;

      // Montar lista de beneficiários (conteúdo da mensagem)
      renderBeneficiarios(bvTitulares);

      // Montar lista de destinatários (para quem enviar)
      renderDestinatarios(data.titulares || [], data.dependentes || [], data.venda.telefone1 || '');

      // Aviso se não tem token
      if (!data.has_token) {
        document.getElementById('bv-no-token-alert').classList.remove('d-none');
      }

      // Selecionar modo padrão ao abrir
      selecionarModoBoasVindas('padrao');

    } catch (e) {
      console.error('Erro ao carregar dados da venda:', e);
    }
  }

  function renderDestinatarios(titulares, dependentes, telVenda) {
    const container = document.getElementById('bv-destinatarios-list');
    container.innerHTML = '';

    // Titulares — pré-marcados, phone vem de vendas_titulares.telefone ou fallback vendas.telefone1
    if (titulares.length > 0) {
      titulares.forEach(t => {
        const tel = t.telefone || telVenda || '';
        container.innerHTML += buildDestinatarioRow(t.nome, tel, 'Titular', true);
      });
    } else {
      // Sem titular cadastrado: adicionar linha vazia pré-marcada com telefone da venda
      container.innerHTML += buildDestinatarioRow('', telVenda, 'Titular', true);
    }

    // Dependentes — desmarcados por padrão
    dependentes.forEach(d => {
      const tel = d.telefone1 || '';
      const label = d.parentesco ? `Dependente (${d.parentesco})` : 'Dependente';
      container.innerHTML += buildDestinatarioRow(d.nome, tel, label, false);
    });

    // Aplicar máscara de telefone nos campos recém-criados
    container.querySelectorAll('.bv-dest-tel').forEach(applyPhoneMask);
  }

  function applyPhoneMask(el) {
    new Cleave(el, {
      delimiters: ['(', ') ', '-'],
      blocks: [0, 2, 5, 4],
      numericOnly: true
    });
  }

  function buildDestinatarioRow(nome, telefone, tipo, checked) {
    return `
      <div class="bv-destinatario-row">
        <input type="checkbox" class="bv-dest-check" ${checked ? 'checked' : ''}>
        <div class="bv-dest-info">
          <span class="bv-dest-nome">${escapeHtml(nome || 'Sem nome')}</span>
          <span class="bv-dest-tipo">${escapeHtml(tipo)}</span>
        </div>
        <input type="text" class="pv-form-input bv-dest-tel" placeholder="(85) 99999-8888" value="${escapeHtml(telefone)}">
      </div>`;
  }

  function renderBeneficiarios(titulares) {
    const container = document.getElementById('bv-beneficiarios-list');
    container.innerHTML = '';
    if (!titulares || titulares.length === 0) {
      container.innerHTML = '<p class="bv-empty-titulares">Nenhum titular cadastrado. Adicione manualmente abaixo.</p>';
      // Adiciona uma linha vazia para o usuário preencher
      container.innerHTML += buildBeneficiarioRow('', '');
      return;
    }
    titulares.forEach(t => {
      container.innerHTML += buildBeneficiarioRow(t.nome, '');
    });
    // Botão para adicionar mais
    container.innerHTML += `<button type="button" class="bv-add-beneficiario" onclick="adicionarBeneficiario()">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Adicionar beneficiário
    </button>`;
  }

  function buildBeneficiarioRow(nome, codigo) {
    return `<div class="bv-beneficiario-row">
      <input type="text" class="pv-form-input bv-nome-input" placeholder="Nome do beneficiário" value="${escapeHtml(nome)}" ${nome ? 'readonly' : ''}>
      <input type="text" class="pv-form-input bv-codigo-input" placeholder="Código do beneficiário" value="${escapeHtml(codigo)}" oninput="atualizarPreviewPadrao()">
    </div>`;
  }

  window.adicionarBeneficiario = function() {
    const container = document.getElementById('bv-beneficiarios-list');
    const btn = container.querySelector('.bv-add-beneficiario');
    const row = document.createElement('div');
    row.innerHTML = buildBeneficiarioRow('', '');
    container.insertBefore(row.firstElementChild, btn);
  };

  window.selecionarModoBoasVindas = function(modo) {
    bvModoSelecionado = modo;

    // Atualizar cards
    document.querySelectorAll('.bv-mode-card').forEach(c => c.classList.remove('active'));
    document.querySelector(`.bv-mode-card[data-mode="${modo}"]`).classList.add('active');

    // Destinatários: visível para padrao e personalizado
    document.getElementById('bv-destinatarios-section').classList.toggle('d-none', modo === 'sem_whatsapp');

    // Mostrar formulário correto
    document.getElementById('bv-form-padrao').classList.toggle('d-none', modo !== 'padrao');
    document.getElementById('bv-form-personalizado').classList.toggle('d-none', modo !== 'personalizado');
    document.getElementById('bv-form-sem-whatsapp').classList.toggle('d-none', modo !== 'sem_whatsapp');

    // Atualizar botão
    const btn = document.getElementById('btn-confirmar-boas-vindas');
    if (modo === 'sem_whatsapp') {
      btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Registrar sem WhatsApp`;
    } else {
      btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.62 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.59a16 16 0 0 0 7.5 7.5l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg> Enviar via WhatsApp`;
    }

    if (modo === 'padrao') atualizarPreviewPadrao();
  };

  window.togglePortal = function() {
    const fields = document.getElementById('bv-portal-fields');
    const btn = document.getElementById('btn-toggle-portal');
    const hidden = fields.classList.toggle('d-none');
    btn.innerHTML = hidden
      ? `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg> Incluir acesso ao portal corporativo (opcional)`
      : `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg> Ocultar portal corporativo`;
    atualizarPreviewPadrao();
  };

  window.atualizarPreviewPadrao = function() {
    const nomeContrato = document.getElementById('bv-contrato-nome').textContent;
    const operadora = document.getElementById('bv-operadora').textContent;
    const loginApp = document.getElementById('bv-login-app').value.trim();
    const senhaApp = document.getElementById('bv-senha-app').value.trim();
    const portalUser = document.getElementById('bv-portal-user').value.trim();
    const portalSenha = document.getElementById('bv-portal-senha').value.trim();
    const linkIos = document.getElementById('bv-link-ios').value.trim();
    const linkAndroid = document.getElementById('bv-link-android').value.trim();

    // Coletar beneficiários
    const nomes = document.querySelectorAll('#bv-beneficiarios-list .bv-nome-input');
    const codigos = document.querySelectorAll('#bv-beneficiarios-list .bv-codigo-input');
    let linhasBen = '';
    nomes.forEach((n, i) => {
      const nome = n.value.trim();
      const cod = codigos[i]?.value.trim() || '';
      if (nome || cod) linhasBen += `\n${nome.toUpperCase()} - ${cod}`;
    });

    let msg = `Prezado(a) ${nomeContrato},\n\n`;
    msg += `É com grande prazer que damos as boas-vindas! Parabenizamos pela escolha e confiança depositada em nossos serviços. Estamos certos de que essa parceria será um sucesso!\n\n`;
    msg += `*Detalhes do Acesso e Benefícios*\n\n`;
    msg += `*📋 Matrículas e Beneficiários:*${linhasBen || '\n(preencha os campos acima)'}\n\n`;
    msg += `*📱 Login e Senha — Aplicativo da Operadora (${operadora}):*\n`;
    msg += `• Login: ${loginApp || '...'}\n`;
    msg += `• Senha: ${senhaApp || '...'}\n`;

    if (linkIos || linkAndroid) {
      msg += `\n*📲 Download do Aplicativo:*\n`;
      if (linkIos)     msg += `• iOS: ${linkIos}\n`;
      if (linkAndroid) msg += `• Android: ${linkAndroid}\n`;
    }

    if (!document.getElementById('bv-portal-fields').classList.contains('d-none') && portalUser) {
      msg += `\n*🖥️ Acesso ao Portal Corporativo:*\n`;
      msg += `• Usuário: ${portalUser}\n`;
      msg += `• Senha: ${portalSenha}\n`;
    }

    msg += `\nEstamos à disposição para auxiliá-lo em qualquer dúvida. Nossa equipe está pronta para fornecer todo o suporte necessário! 😊`;

    document.getElementById('bv-preview-padrao').value = msg;
  };

  // Confirmar envio de boas vindas
  async function confirmarBoasVindas() {
    const vendaId = document.getElementById('boas-vindas-venda-id').value;
    const btnConfirmar = document.getElementById('btn-confirmar-boas-vindas');

    if (!vendaId) {
      Swal.fire({ icon: 'error', title: 'Erro', text: 'ID da venda não encontrado.', confirmButtonColor: '#7C3AED' });
      return;
    }

    // Coletar destinatários marcados (compartilhado entre modos)
    const destinatarios = [];
    if (bvModoSelecionado !== 'sem_whatsapp') {
      document.querySelectorAll('#bv-destinatarios-list .bv-destinatario-row').forEach(row => {
        const checked = row.querySelector('.bv-dest-check')?.checked;
        if (!checked) return;
        const nome = row.querySelector('.bv-dest-nome')?.textContent?.trim() || '';
        const telefone = row.querySelector('.bv-dest-tel')?.value?.trim() || '';
        if (telefone) destinatarios.push({ nome, telefone });
      });

      if (destinatarios.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione ao menos um destinatário com telefone preenchido.', confirmButtonColor: '#7C3AED' });
        return;
      }
    }

    // Montar payload conforme modo
    const payload = { venda_id: vendaId, tipo_envio: bvModoSelecionado, destinatarios };

    if (bvModoSelecionado === 'padrao') {
      // Coletar beneficiários
      const nomes = document.querySelectorAll('#bv-beneficiarios-list .bv-nome-input');
      const codigos = document.querySelectorAll('#bv-beneficiarios-list .bv-codigo-input');
      const beneficiarios = [];
      nomes.forEach((n, i) => {
        const nome = n.value.trim();
        const codigo = codigos[i]?.value.trim() || '';
        if (nome || codigo) beneficiarios.push({ nome, codigo });
      });

      if (beneficiarios.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe ao menos um beneficiário.', confirmButtonColor: '#7C3AED' });
        return;
      }

      payload.beneficiarios = beneficiarios;
      payload.nome_contrato = document.getElementById('bv-contrato-nome').textContent;
      payload.login_app     = document.getElementById('bv-login-app').value.trim();
      payload.senha_app     = document.getElementById('bv-senha-app').value.trim();
      payload.link_ios      = document.getElementById('bv-link-ios').value.trim();
      payload.link_android  = document.getElementById('bv-link-android').value.trim();
      payload.portal_user   = document.getElementById('bv-portal-user').value.trim();
      payload.portal_senha  = document.getElementById('bv-portal-senha').value.trim();

    } else if (bvModoSelecionado === 'personalizado') {
      const mensagem = document.getElementById('bv-mensagem-personalizada').value.trim();
      if (!mensagem) {
        Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Escreva a mensagem personalizada.', confirmButtonColor: '#7C3AED' });
        return;
      }
      payload.mensagem_personalizada = mensagem;
    }

    const originalContent = btnConfirmar.innerHTML;
    btnConfirmar.disabled = true;
    btnConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Enviando...';

    try {
      const response = await fetch('/back-office/pos-venda/boas-vindas', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(payload)
      });

      const data = await response.json();

      if (data.success) {
        bootstrap.Modal.getInstance(elements.modalBoasVindas).hide();

        Swal.fire({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3500,
          timerProgressBar: true,
          background: 'transparent',
          showClass: { popup: 'animate__animated animate__slideInRight animate__faster' },
          hideClass: { popup: 'animate__animated animate__slideOutRight animate__faster' },
          html: `
            <div class="custom-toast custom-toast-success">
              <div class="toast-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
              </div>
              <div class="toast-content">
                <div class="toast-title">Sucesso!</div>
                <div class="toast-message">${data.message}</div>
              </div>
              <div class="toast-close" onclick="Swal.close()">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </div>
            </div>`,
          customClass: { popup: 'custom-toast-popup' }
        });

        loadData();
      } else {
        Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Não foi possível registrar o Boas Vindas.', confirmButtonColor: '#7C3AED' });
      }
    } catch (error) {
      console.error('Erro ao registrar boas vindas:', error);
      Swal.fire({ icon: 'error', title: 'Erro', text: 'Não foi possível registrar o Boas Vindas.', confirmButtonColor: '#7C3AED' });
    } finally {
      btnConfirmar.disabled = false;
      btnConfirmar.innerHTML = originalContent;
    }
  }

  // =============================================
  // Token WhatsApp
  // =============================================
  window.salvarWhatsappToken = async function() {
    const token = document.getElementById('input-whatsapp-token').value.trim();
    if (!token) {
      Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Cole o token antes de salvar.', confirmButtonColor: '#25D366' });
      return;
    }

    const btn = document.getElementById('btn-salvar-token');
    btn.disabled = true;

    try {
      const res = await fetch('/back-office/configuracoes/whatsapp-token', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ whatsapp_token: token })
      });
      const data = await res.json();

      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('modalWhatsappToken')).hide();
        document.getElementById('bv-no-token-alert').classList.add('d-none');
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Token salvo!', showConfirmButton: false, timer: 2000 });
      } else {
        Swal.fire({ icon: 'error', title: 'Erro', text: data.message, confirmButtonColor: '#25D366' });
      }
    } catch (e) {
      Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha ao salvar token.', confirmButtonColor: '#25D366' });
    } finally {
      btn.disabled = false;
    }
  };

  // Abrir modal de prévia WhatsApp — modo personalizado
  window.abrirPreviewWhatsappPersonalizado = function() {
    const texto = document.getElementById('bv-mensagem-personalizada').value.trim();
    if (!texto) {
      Swal.fire({ icon: 'info', title: 'Escreva a mensagem', text: 'Digite o conteúdo da mensagem para visualizar a prévia.', confirmButtonColor: '#25D366' });
      return;
    }
    abrirModalPreview(texto);
  };

  // Abrir modal de prévia WhatsApp — modo padrão
  window.abrirPreviewWhatsapp = function() {
    atualizarPreviewPadrao();
    const texto = document.getElementById('bv-preview-padrao').value;
    if (!texto.trim()) {
      Swal.fire({ icon: 'info', title: 'Preencha os dados', text: 'Preencha os campos do formulário para visualizar a prévia.', confirmButtonColor: '#25D366' });
      return;
    }
    abrirModalPreview(texto);
  };

  function abrirModalPreview(texto) {
    const html = texto
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/\*(.*?)\*/g, '<strong>$1</strong>')
      .replace(/\n/g, '<br>');

    document.getElementById('wpp-bubble-content').innerHTML = html;
    document.getElementById('wpp-preview-contact').textContent =
      document.getElementById('bv-contrato-nome').textContent || 'Cliente';

    const now = new Date();
    document.getElementById('wpp-preview-time').textContent =
      now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');

    new bootstrap.Modal(document.getElementById('modalPreviewWhatsapp')).show();
  }

  // Abrir modal de token e carregar info atual
  async function abrirConfigToken() {
    document.getElementById('input-whatsapp-token').value = '';
    const infoEl = document.getElementById('bv-token-current-info');
    infoEl.classList.add('d-none');

    try {
      const res = await fetch('/back-office/configuracoes/whatsapp-token');
      const data = await res.json();
      if (data.has_token) {
        infoEl.textContent = `Token atual: ${data.token_preview}`;
        infoEl.classList.remove('d-none');
      }
    } catch (_) {}

    new bootstrap.Modal(document.getElementById('modalWhatsappToken')).show();
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

  // Open modal to change implantation date
  function openAlterarDataImplantacao(vendaId, dataAtual) {
    document.getElementById('data-implantacao-venda-id').value = vendaId;

    // Convert date from dd/mm/yyyy or yyyy-mm-dd to yyyy-mm-dd for input
    let dataFormatada = '';
    if (dataAtual) {
      if (dataAtual.includes('/')) {
        const partes = dataAtual.split('/');
        if (partes.length === 3) {
          dataFormatada = `${partes[2]}-${partes[1].padStart(2, '0')}-${partes[0].padStart(2, '0')}`;
        }
      } else {
        dataFormatada = dataAtual;
      }
    }
    document.getElementById('nova-data-implantacao').value = dataFormatada;

    const modal = new bootstrap.Modal(elements.modalDataImplantacao);
    modal.show();
  }

  // Save new implantation date
  async function saveDataImplantacao() {
    const vendaId = document.getElementById('data-implantacao-venda-id').value;
    const novaData = document.getElementById('nova-data-implantacao').value;
    const btnSalvar = document.getElementById('btn-salvar-data-implantacao');

    if (!vendaId || !novaData) {
      Swal.fire({
        icon: 'warning',
        title: 'Atenção',
        text: 'Por favor, selecione uma data.',
        confirmButtonColor: '#7C3AED'
      });
      return;
    }

    const originalContent = btnSalvar.innerHTML;
    btnSalvar.disabled = true;
    btnSalvar.innerHTML = `
      <span class="spinner-border spinner-border-sm me-1" role="status"></span>
      Salvando...
    `;

    try {
      const response = await fetch('/back-office/pos-venda/data-implantacao', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
          venda_id: vendaId,
          data_implantacao: novaData
        })
      });

      const data = await response.json();

      if (data.success) {
        // Close modal
        bootstrap.Modal.getInstance(elements.modalDataImplantacao).hide();

        // Modern toast success
        Swal.fire({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          background: 'transparent',
          showClass: {
            popup: 'animate__animated animate__slideInRight animate__faster'
          },
          hideClass: {
            popup: 'animate__animated animate__slideOutRight animate__faster'
          },
          html: `
            <div class="custom-toast custom-toast-success">
              <div class="toast-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                  <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
              </div>
              <div class="toast-content">
                <div class="toast-title">Sucesso!</div>
                <div class="toast-message">Data de implantação atualizada para <strong>${data.data_implantacao}</strong></div>
              </div>
              <div class="toast-close" onclick="Swal.close()">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="18" y1="6" x2="6" y2="18"/>
                  <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </div>
            </div>
          `,
          customClass: {
            popup: 'custom-toast-popup'
          }
        });

        // Reload data to reflect changes
        loadData();
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Erro',
          text: data.message || 'Não foi possível atualizar a data.',
          confirmButtonColor: '#7C3AED'
        });
      }
    } catch (error) {
      console.error('Erro ao atualizar data:', error);
      Swal.fire({
        icon: 'error',
        title: 'Erro',
        text: 'Não foi possível atualizar a data de implantação.',
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

  // Excluir contrato
  async function excluirContrato(vendaId, nomeContrato) {
    const result = await Swal.fire({
      title: 'Excluir Contrato?',
      html: `<p>Tem certeza que deseja excluir o contrato:</p><p class="fw-bold text-danger">"${escapeHtml(nomeContrato)}"</p><p class="text-muted small">Esta ação não pode ser desfeita.</p>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Excluir',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-danger me-2',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false,
      reverseButtons: true
    });

    if (!result.isConfirmed) return;

    try {
      const response = await fetch(`/back-office/deletar-contrato/${vendaId}`, {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Accept': 'application/json'
        }
      });

      const data = await response.json();

      if (data.success) {
        Swal.fire({
          icon: 'success',
          title: 'Contrato excluído',
          text: data.message,
          confirmButtonColor: '#7C3AED'
        });
        loadData();
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Erro',
          text: data.message || 'Não foi possível excluir o contrato.',
          confirmButtonColor: '#7C3AED'
        });
      }
    } catch (error) {
      console.error('Erro ao excluir contrato:', error);
      Swal.fire({
        icon: 'error',
        title: 'Erro',
        text: 'Não foi possível excluir o contrato.',
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
    gerarRecebiveis,
    openAlterarDataImplantacao,
    openBoasVindas,
    excluirContrato
  };

  // Initialize when DOM is ready
  document.addEventListener('DOMContentLoaded', init);
})();
