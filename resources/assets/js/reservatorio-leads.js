document.addEventListener('DOMContentLoaded', () => {
  const root = document.querySelector('.lead-vault');
  if (!root) return;

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  const vendors = JSON.parse(root.dataset.vendors || '[]');
  const activeVendors = vendors.filter(vendor => vendor.ativo === 'Y' || vendor.ativo === true || vendor.ativo === 1);
  const state = { page: 1, lastPage: 1, historyPage: 1, historyLastPage: 1, strategies: [], editingId: null, executingId: null, executionMode: null, executionAvailable: null };
  let conditionCounter = 0;
  const strategyModal = new bootstrap.Modal(document.getElementById('strategy-modal'));
  const executionModal = new bootstrap.Modal(document.getElementById('execution-modal'));
  const migrationElement = document.getElementById('migration-modal');
  const migrationModal = migrationElement ? new bootstrap.Modal(migrationElement) : null;
  const confirmElement = document.getElementById('confirm-modal');
  const confirmModal = new bootstrap.Modal(confirmElement);
  let confirmResolver = null;

  const fields = {
    origem: { label: 'Origem', type: 'list' },
    nome_base: { label: 'Nome da base', type: 'text' },
    plano: { label: 'Plano', type: 'text' },
    categoria: { label: 'Categoria', type: 'text' },
    entidade: { label: 'Entidade', type: 'text' },
    idades: { label: 'Idades', type: 'text' },
    tipo_layout: { label: 'Tipo de layout', type: 'text' },
    tipo_criativo: { label: 'Tipo de criativo', type: 'text' },
    is_ads: { label: 'É lead de anúncio', type: 'boolean' },
    plano_ativo: { label: 'Possui plano ativo', type: 'boolean' },
    possui_cnpj: { label: 'Possui CNPJ', type: 'boolean' },
    vidas: { label: 'Quantidade de vidas', type: 'number' },
    valor_plano_atual: { label: 'Valor do plano atual', type: 'number' },
    valor_negociacao: { label: 'Valor de negociação', type: 'number' },
    entrou_em: { label: 'Data de entrada', type: 'date' }
  };
  const operators = {
    text: [['igual', 'é igual a'], ['contem', 'contém'], ['em', 'é um destes'], ['preenchido', 'está preenchido'], ['vazio', 'está vazio']],
    list: [['igual', 'é igual a'], ['em', 'é uma destas']],
    boolean: [['igual', 'é igual a']],
    number: [['igual', 'é igual a'], ['maior_ou_igual', 'é maior ou igual a'], ['menor_ou_igual', 'é menor ou igual a'], ['entre', 'está entre'], ['preenchido', 'está preenchido'], ['vazio', 'está vazio']],
    date: [['igual', 'é igual a'], ['maior_ou_igual', 'é a partir de'], ['menor_ou_igual', 'é até'], ['entre', 'está entre']]
  };

  const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[char]);
  const number = value => new Intl.NumberFormat('pt-BR').format(Number(value || 0));
  const money = value => value === null || value === '' ? '—' : new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value));
  const announce = message => { document.getElementById('reservoir-live').textContent = message; };
  const toast = (type, message) => {
    if (window.toastr) toastr[type](message);
    announce(message);
  };
  const setBusy = (button, busy, label = 'Processando…') => {
    if (!button) return;
    if (busy) { button.dataset.original = button.innerHTML; button.disabled = true; button.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>${label}`; }
    else { button.disabled = false; if (button.dataset.original) button.innerHTML = button.dataset.original; }
  };
  const request = async (url, options = {}) => {
    const response = await fetch(url, {
      ...options,
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, ...(options.headers || {}) }
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {})[0]?.[0] || 'Não foi possível concluir a operação.');
    return payload;
  };

  function askConfirmation({ title, copy, label }) {
    document.getElementById('confirm-modal-title').textContent = title;
    document.getElementById('confirm-modal-copy').textContent = copy;
    document.getElementById('confirm-modal-action').textContent = label;
    confirmModal.show();
    return new Promise(resolve => { confirmResolver = resolve; });
  }

  function switchTab(name) {
    document.querySelectorAll('[data-tab]').forEach(button => {
      const active = button.dataset.tab === name;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-selected', String(active));
      button.tabIndex = active ? 0 : -1;
    });
    document.querySelectorAll('[data-panel]').forEach(panel => {
      const active = panel.dataset.panel === name;
      panel.classList.toggle('is-active', active);
      panel.hidden = !active;
    });
    if (name === 'strategies') loadStrategies();
    if (name === 'history') loadHistory();
  }

  async function loadLeads() {
    const body = document.getElementById('leads-body');
    const stateBox = document.getElementById('leads-state');
    body.innerHTML = '<tr><td colspan="5"><div class="lv-loading"><span class="spinner-border spinner-border-sm"></span> Atualizando reservatório…</div></td></tr>';
    stateBox.classList.add('d-none');
    const params = new URLSearchParams({ page: state.page });
    const search = document.getElementById('lead-search').value.trim();
    const origin = document.getElementById('lead-origin').value;
    const base = document.getElementById('lead-base').value;
    if (search) params.set('busca', search);
    if (origin) params.set('origem', origin);
    if (base) params.set('base', base);
    try {
      const payload = await request(`${root.dataset.listUrl}?${params}`);
      const page = payload.itens;
      state.lastPage = page.last_page;
      document.getElementById('metric-available').textContent = number(payload.metricas.disponiveis);
      document.getElementById('metric-entered').textContent = number(payload.metricas.entradas_30_dias);
      document.getElementById('metric-distributed').textContent = number(payload.metricas.distribuidos_mes);
      document.getElementById('metric-blocked').textContent = number(payload.metricas.bloqueados);
      hydrateBases(payload.bases, base);
      body.innerHTML = page.data.map(lead => {
        const origin = originMeta(lead.origem);
        return `
        <tr>
          <td><div class="lv-lead"><strong>${escapeHtml(lead.nome_cliente || 'Lead sem nome')}</strong><span>${escapeHtml(lead.cpf || 'CPF não informado')} · ${escapeHtml(lead.telefone1 || 'sem telefone')}</span></div></td>
          <td><span class="lv-origin ${origin.className}">${escapeHtml(origin.label)}</span><small>${escapeHtml(lead.nome_base || 'Base não informada')}</small></td>
          <td><strong>${escapeHtml(lead.plano || lead.categoria || 'Não informado')}</strong><small>${escapeHtml([lead.entidade, lead.idades ? `${lead.idades} anos` : null].filter(Boolean).join(' · ') || 'Perfil não detalhado')}</small></td>
          <td><strong>${money(lead.valor_plano_atual)}</strong><small>${lead.vidas ? `${escapeHtml(lead.vidas)} vida(s)` : 'Vidas não informadas'}</small></td>
          <td><time>${escapeHtml(lead.entrou_em)}</time><small>#${lead.contato_id}</small></td>
        </tr>`;
      }).join('');
      if (!page.data.length) showTableState(stateBox, body, 'ri-inbox-2-line', 'Nenhum lead disponível', 'Ajuste os filtros ou aguarde novas entradas de mailing e marketing.');
      document.getElementById('leads-range').textContent = page.total ? `${number(page.from)}–${number(page.to)} de ${number(page.total)} leads` : '0 leads';
      document.getElementById('leads-prev').disabled = page.current_page <= 1;
      document.getElementById('leads-next').disabled = page.current_page >= page.last_page;
    } catch (error) {
      ['metric-available', 'metric-entered', 'metric-distributed', 'metric-blocked'].forEach(id => { document.getElementById(id).textContent = '—'; });
      document.getElementById('leads-range').textContent = 'Dados indisponíveis';
      document.getElementById('leads-prev').disabled = true;
      document.getElementById('leads-next').disabled = true;
      showTableState(stateBox, body, 'ri-error-warning-line', 'Não foi possível carregar os leads', error.message);
    }
  }

  function hydrateBases(bases, selected) {
    const select = document.getElementById('lead-base');
    select.innerHTML = '<option value="">Todas as bases</option>' + bases.map(base => `<option value="${escapeHtml(base)}" ${base === selected ? 'selected' : ''}>${escapeHtml(base)}</option>`).join('');
  }
  function originMeta(origin) {
    return ({
      IMPORTACAO: { label: 'Mailing', className: 'is-importacao' },
      MARKETING: { label: 'Marketing', className: 'is-marketing' },
      MIGRACAO_INICIAL: { label: 'Carga inicial', className: 'is-migracao_inicial' }
    })[origin] || { label: 'Outra origem', className: 'is-unknown' };
  }
  function showTableState(box, body, icon, title, copy) {
    body.innerHTML = '';
    box.innerHTML = `<i class="${icon}"></i><strong>${escapeHtml(title)}</strong><span>${escapeHtml(copy)}</span>`;
    box.classList.remove('d-none');
  }

  async function loadStrategies() {
    const list = document.getElementById('strategy-list');
    const stateBox = document.getElementById('strategies-state');
    list.innerHTML = '<div class="lv-loading"><span class="spinner-border spinner-border-sm"></span> Carregando estratégias…</div>';
    stateBox.classList.add('d-none');
    try {
      const payload = await request(root.dataset.strategiesUrl);
      state.strategies = payload.data;
      list.innerHTML = payload.data.map(strategy => {
        const conditions = (strategy.condicoes || []).map(condition => `<span>${escapeHtml(fields[condition.campo]?.label || condition.campo)} ${escapeHtml(operatorLabel(condition))}</span>`).join('');
        return `<article class="lv-strategy ${strategy.ativo ? '' : 'is-archived'}" data-strategy-id="${strategy.id}">
          <div class="lv-strategy-main"><div><h3>${escapeHtml(strategy.nome)}</h3><span>${strategy.ativo ? 'Ativa' : 'Arquivada'} · ${(strategy.condicoes || []).length} condição(ões)</span></div><div class="lv-condition-summary">${conditions}</div></div>
          <div class="lv-strategy-actions">${strategy.ativo ? `<button class="lv-button lv-button-primary lv-button-small" data-run-strategy="${strategy.id}"><i class="ri-send-plane-line"></i> Distribuir</button><button class="lv-icon-button" data-edit-strategy="${strategy.id}" aria-label="Editar ${escapeHtml(strategy.nome)}"><i class="ri-edit-line"></i></button><button class="lv-icon-button" data-archive-strategy="${strategy.id}" aria-label="Arquivar ${escapeHtml(strategy.nome)}"><i class="ri-archive-line"></i></button>` : ''}</div>
        </article>`;
      }).join('');
      if (!payload.data.length) {
        list.innerHTML = '';
        showTableState(stateBox, list, 'ri-filter-off-line', 'Nenhuma estratégia salva', 'Crie uma combinação de critérios para começar a distribuir os leads.');
      }
    } catch (error) {
      list.innerHTML = '';
      showTableState(stateBox, list, 'ri-error-warning-line', 'Não foi possível carregar as estratégias', error.message);
    }
  }

  function operatorLabel(condition) {
    const pair = (operators[fields[condition.campo]?.type] || []).find(item => item[0] === condition.operador);
    const raw = Array.isArray(condition.valor) ? condition.valor.join(' e ') : condition.valor;
    return `${pair?.[1] || condition.operador}${['preenchido', 'vazio'].includes(condition.operador) ? '' : ` “${raw}”`}`;
  }

  function openStrategy(strategy = null) {
    state.editingId = strategy?.id || null;
    document.getElementById('strategy-modal-title').textContent = strategy ? 'Editar estratégia' : 'Nova estratégia';
    document.getElementById('strategy-name').value = strategy?.nome || '';
    document.getElementById('condition-list').innerHTML = '';
    (strategy?.condicoes?.length ? strategy.condicoes : [{ campo: 'nome_base', operador: 'igual', valor: '' }]).forEach(addCondition);
    document.getElementById('strategy-preview').innerHTML = '<span>Prévia da seleção</span><strong>Calcule a prévia para conferir o alcance atual.</strong>';
    strategyModal.show();
  }

  function invalidateDraftPreview(message = 'Condições alteradas. Calcule uma nova prévia.') {
    document.getElementById('strategy-preview').innerHTML = `<span>Prévia da seleção</span><strong>${escapeHtml(message)}</strong>`;
  }

  function addCondition(condition = {}) {
    const list = document.getElementById('condition-list');
    const row = document.createElement('div');
    const index = ++conditionCounter;
    row.className = 'lv-condition';
    row.innerHTML = `<select class="form-select lv-control lv-field" aria-label="Campo da condição ${index}">${Object.entries(fields).map(([key, config]) => `<option value="${key}" ${condition.campo === key ? 'selected' : ''}>${config.label}</option>`).join('')}</select><select class="form-select lv-control lv-operator" aria-label="Operador da condição ${index}"></select><input class="form-control lv-control lv-value" aria-label="Valor da condição ${index}" placeholder="Valor"><button type="button" class="lv-icon-button lv-remove-condition" aria-label="Remover condição ${index}"><i class="ri-delete-bin-line"></i></button>`;
    list.append(row);
    const fieldSelect = row.querySelector('.lv-field');
    const updateOperator = (initialValue = '') => {
      const type = fields[fieldSelect.value].type;
      row.querySelector('.lv-operator').innerHTML = operators[type].map(([key, label]) => `<option value="${key}" ${condition.operador === key ? 'selected' : ''}>${label}</option>`).join('');
      renderValueControl(row, index, initialValue);
    };
    fieldSelect.addEventListener('change', () => { condition = {}; updateOperator(); invalidateDraftPreview(); });
    row.querySelector('.lv-operator').addEventListener('change', () => { renderValueControl(row, index); invalidateDraftPreview(); });
    row.querySelector('.lv-remove-condition').addEventListener('click', () => {
      if (list.children.length > 1) { row.remove(); invalidateDraftPreview(); }
    });
    updateOperator(condition.valor ?? '');
  }

  function renderValueControl(row, index, initialValue = '') {
    const current = row.querySelector('.lv-value');
    const field = row.querySelector('.lv-field').value;
    const operator = row.querySelector('.lv-operator').value;
    const values = Array.isArray(initialValue) ? initialValue.map(String) : [String(initialValue ?? '')];
    let control;

    if (fields[field].type === 'boolean') {
      control = document.createElement('select');
      control.className = 'form-select lv-control lv-value';
      control.innerHTML = `<option value="Y" ${['Y', '1', 'true', 'Sim'].includes(values[0]) ? 'selected' : ''}>Sim</option><option value="N" ${['N', '0', 'false', 'Não'].includes(values[0]) ? 'selected' : ''}>Não</option>`;
    } else if (field === 'origem') {
      control = document.createElement('select');
      control.className = 'form-select lv-control lv-value';
      control.multiple = operator === 'em';
      control.innerHTML = [['IMPORTACAO', 'Mailing'], ['MARKETING', 'Marketing'], ['MIGRACAO_INICIAL', 'Carga inicial']]
        .map(([value, label]) => `<option value="${value}" ${values.includes(value) ? 'selected' : ''}>${label}</option>`).join('');
    } else {
      control = document.createElement('input');
      control.className = 'form-control lv-control lv-value';
      control.type = fields[field].type === 'date' && operator !== 'entre' ? 'date' : (fields[field].type === 'number' && operator !== 'entre' ? 'number' : 'text');
      control.value = values.join(', ');
      control.placeholder = operator === 'entre' ? 'Início, fim' : operator === 'em' ? 'Valor 1, valor 2' : 'Valor';
    }

    control.setAttribute('aria-label', `Valor da condição ${index}`);
    control.disabled = ['preenchido', 'vazio'].includes(operator);
    control.required = !control.disabled;
    control.addEventListener('input', () => invalidateDraftPreview());
    control.addEventListener('change', () => invalidateDraftPreview());
    current.replaceWith(control);
  }

  function collectConditions() {
    return [...document.querySelectorAll('.lv-condition')].map(row => {
      const operador = row.querySelector('.lv-operator').value;
      const control = row.querySelector('.lv-value');
      let valor = control.multiple ? [...control.selectedOptions].map(option => option.value) : control.value.trim();
      if (['em', 'entre'].includes(operador) && !Array.isArray(valor)) valor = valor.split(',').map(item => item.trim()).filter(Boolean);
      return { campo: row.querySelector('.lv-field').value, operador, ...(!['preenchido', 'vazio'].includes(operador) ? { valor } : {}) };
    });
  }

  async function previewDraft(button = null) {
    setBusy(button, true, 'Calculando…');
    try {
      const payload = await request(root.dataset.previewUrl, { method: 'POST', body: JSON.stringify({ condicoes: collectConditions() }) });
      document.getElementById('strategy-preview').innerHTML = `<span>Prévia da seleção</span><strong>${number(payload.total_elegivel)} lead(s) disponíveis agora</strong>`;
      return payload;
    } catch (error) {
      invalidateDraftPreview(`Não foi possível calcular: ${error.message}`);
      toast('error', error.message);
      throw error;
    }
    finally { setBusy(button, false); }
  }

  async function saveStrategy(event) {
    event.preventDefault();
    const button = document.getElementById('save-strategy');
    setBusy(button, true, 'Salvando…');
    const url = state.editingId ? `${root.dataset.strategyBaseUrl}/${state.editingId}` : root.dataset.strategiesUrl;
    try {
      const payload = await request(url, { method: state.editingId ? 'PUT' : 'POST', body: JSON.stringify({ nome: document.getElementById('strategy-name').value.trim(), condicoes: collectConditions() }) });
      toast('success', payload.message);
      strategyModal.hide();
      switchTab('strategies');
    } catch (error) { toast('error', error.message); }
    finally { setBusy(button, false); }
  }

  async function openExecution(id) {
    const strategy = state.strategies.find(item => item.id === id);
    if (!strategy) return;
    await prepareExecution({
      mode: 'strategy',
      id,
      title: strategy.nome,
      copy: 'A seleção é aleatória dentro dos critérios salvos.'
    });
  }

  async function openRandomExecution() {
    await prepareExecution({
      mode: 'random',
      id: null,
      title: 'Distribuição rápida',
      copy: 'Sem filtros: o sorteio considera todos os leads disponíveis e respeita as cotas informadas.'
    });
  }

  async function prepareExecution({ mode, id, title, copy }) {
    state.executionMode = mode;
    state.executingId = id;
    state.executionAvailable = null;
    document.getElementById('execution-modal-title').textContent = title;
    document.getElementById('execution-copy').textContent = copy;
    document.getElementById('execution-available-label').textContent = mode === 'random' ? 'Leads disponíveis no reservatório' : 'Leads disponíveis nesta estratégia';
    document.getElementById('allocation-list').innerHTML = activeVendors.map(vendor => `<label class="lv-allocation"><span>${escapeHtml(vendor.name)}</span><input class="form-control lv-control" type="number" min="0" max="1000" value="0" data-vendor-id="${vendor.id}" aria-label="Quantidade para ${escapeHtml(vendor.name)}"></label>`).join('');
    document.getElementById('allocation-total').textContent = '0';
    document.getElementById('execution-available').textContent = '…';
    document.getElementById('execute-strategy').disabled = true;
    document.getElementById('execution-status').textContent = 'Calculando a disponibilidade atual…';
    executionModal.show();
    await loadExecutionPreview();
  }

  async function loadExecutionPreview() {
    if (!state.executionMode) return;
    const retry = document.getElementById('refresh-execution-preview');
    state.executionAvailable = null;
    updateExecutionState();
    setBusy(retry, true, 'Atualizando…');
    try {
      const previewUrl = state.executionMode === 'random'
        ? root.dataset.randomPreviewUrl
        : `${root.dataset.strategyBaseUrl}/${state.executingId}/preview`;
      const payload = await request(previewUrl, { method: 'POST', body: '{}' });
      state.executionAvailable = Number(payload.total_elegivel);
      document.getElementById('execution-available').textContent = number(payload.total_elegivel);
      updateExecutionState();
    } catch (error) {
      document.getElementById('execution-available').textContent = '—';
      document.getElementById('execution-status').textContent = `A prévia falhou: ${error.message}. Tente atualizar.`;
      toast('error', error.message);
    } finally {
      setBusy(retry, false);
      updateExecutionState();
    }
  }

  function updateExecutionState() {
    const total = [...document.querySelectorAll('[data-vendor-id]')].reduce((sum, input) => sum + Number(input.value || 0), 0);
    const button = document.getElementById('execute-strategy');
    const status = document.getElementById('execution-status');
    document.getElementById('allocation-total').textContent = number(total);

    if (state.executionAvailable === null) {
      button.disabled = true;
      if (!/(falhou|não foi)/i.test(status.textContent)) status.textContent = 'Aguarde uma prévia válida antes de confirmar.';
    } else if (total <= 0) {
      button.disabled = true;
      status.textContent = 'Informe a quantidade de pelo menos um vendedor.';
    } else if (total > state.executionAvailable) {
      button.disabled = true;
      status.textContent = `Reduza as cotas em ${number(total - state.executionAvailable)} lead(s) para respeitar o estoque disponível.`;
    } else {
      button.disabled = false;
      status.textContent = `${number(total)} lead(s) serão selecionados aleatoriamente e distribuídos nas cotas informadas.`;
    }
  }

  async function executeStrategy(event) {
    event.preventDefault();
    const button = document.getElementById('execute-strategy');
    const distribuicoes = [...document.querySelectorAll('[data-vendor-id]')].map(input => ({ vendedor_id: Number(input.dataset.vendorId), quantidade: Number(input.value) })).filter(item => item.quantidade > 0);
    const total = distribuicoes.reduce((sum, item) => sum + item.quantidade, 0);
    if (!distribuicoes.length || state.executionAvailable === null || total > state.executionAvailable) {
      updateExecutionState();
      return toast('warning', 'Revise a prévia e as quantidades antes de distribuir.');
    }
    setBusy(button, true, 'Distribuindo…');
    try {
      const executeUrl = state.executionMode === 'random'
        ? root.dataset.randomExecuteUrl
        : `${root.dataset.strategyBaseUrl}/${state.executingId}/executar`;
      const payload = await request(executeUrl, { method: 'POST', body: JSON.stringify({ distribuicoes }) });
      toast('success', payload.message);
      executionModal.hide();
      state.page = 1;
      await loadLeads();
      await loadStrategies();
    } catch (error) {
      state.executionAvailable = null;
      document.getElementById('execution-status').textContent = `A distribuição não foi concluída: ${error.message}. Atualize a prévia.`;
      toast('error', error.message);
    } finally {
      setBusy(button, false);
      updateExecutionState();
    }
  }

  async function archiveStrategy(id) {
    if (!await askConfirmation({ title: 'Arquivar estratégia?', copy: 'Ela deixará de aceitar novas distribuições. O histórico das execuções será preservado.', label: 'Arquivar estratégia' })) return;
    try { const payload = await request(`${root.dataset.strategyBaseUrl}/${id}`, { method: 'DELETE', body: '{}' }); toast('success', payload.message); loadStrategies(); }
    catch (error) { toast('error', error.message); }
  }

  async function loadHistory() {
    const body = document.getElementById('history-body');
    const stateBox = document.getElementById('history-state');
    body.innerHTML = '<tr><td colspan="5"><div class="lv-loading"><span class="spinner-border spinner-border-sm"></span> Carregando histórico…</div></td></tr>';
    stateBox.classList.add('d-none');
    try {
      const payload = await request(`${root.dataset.historyUrl}?page=${state.historyPage}`);
      state.historyLastPage = payload.data.last_page;
      body.innerHTML = payload.data.data.map(item => {
        const allocations = typeof item.distribuicoes === 'string' ? JSON.parse(item.distribuicoes || '[]') : (item.distribuicoes || []);
        const allocationCopy = allocations.map(allocation => {
          const vendor = vendors.find(candidate => candidate.id === Number(allocation.vendedor_id));
          return `${vendor?.name || `Vendedor #${allocation.vendedor_id}`}: ${allocation.quantidade}`;
        }).join(' · ');
        const isMigration = item.tipo === 'MIGRACAO_INICIAL';
        const isRandom = item.tipo === 'DISTRIBUICAO_ALEATORIA';
        const typeLabel = isMigration ? 'Carga inicial' : (isRandom ? 'Distribuição rápida' : 'Distribuição por estratégia');
        const subject = isMigration ? item.vendedor_origem_nome : (isRandom ? 'Sorteio geral, sem filtros' : item.estrategia_nome);
        const detail = isMigration ? 'Origem excepcional' : (allocationCopy || 'Cotas preservadas no histórico');
        return `<tr><td><strong>${typeLabel}</strong><small>#${item.id} · ${escapeHtml(item.status)}</small></td><td><strong>${escapeHtml(subject || 'Operação')}</strong><small>${escapeHtml(detail)}</small></td><td><strong>${number(item.total_executado)} executados</strong><small>${number(item.total_ignorado)} ignorados</small></td><td>${escapeHtml(item.autor_nome || 'Sistema')}</td><td>${new Date(item.executada_em || item.created_at).toLocaleString('pt-BR')}</td></tr>`;
      }).join('');
      document.getElementById('history-range').textContent = payload.data.total ? `${number(payload.data.from)}–${number(payload.data.to)} de ${number(payload.data.total)} registros` : '0 registros';
      document.getElementById('history-prev').disabled = payload.data.current_page <= 1;
      document.getElementById('history-next').disabled = payload.data.current_page >= payload.data.last_page;
      if (!payload.data.data.length) showTableState(stateBox, body, 'ri-history-line', 'Nenhuma execução registrada', 'As distribuições e a carga inicial aparecerão aqui.');
    } catch (error) {
      document.getElementById('history-range').textContent = 'Dados indisponíveis';
      document.getElementById('history-prev').disabled = true;
      document.getElementById('history-next').disabled = true;
      showTableState(stateBox, body, 'ri-error-warning-line', 'Não foi possível carregar o histórico', error.message);
    }
  }

  async function previewMigration() {
    const button = document.getElementById('preview-migration');
    const vendorId = document.getElementById('migration-vendor').value;
    if (!vendorId) return toast('warning', 'Selecione o vendedor de origem.');
    setBusy(button, true, 'Conferindo…');
    try {
      const payload = await request(root.dataset.migrationPreviewUrl, { method: 'POST', body: JSON.stringify({ vendedor_id: vendorId }) });
      const box = document.getElementById('migration-preview');
      box.innerHTML = `<div><span>Total na carteira</span><strong>${number(payload.total)}</strong></div><div class="is-success"><span>Aptos para mover</span><strong>${number(payload.aptos)}</strong></div><div><span>Protegidos pelas regras</span><strong>${number(Math.max(0, payload.total - payload.aptos))}</strong></div><small>Os motivos de proteção podem se sobrepor. Nenhum registro protegido será alterado.</small>`;
      box.classList.remove('d-none');
      document.getElementById('execute-migration').disabled = payload.aptos < 1;
    } catch (error) {
      const box = document.getElementById('migration-preview');
      box.innerHTML = `<div class="is-error"><span>Prévia indisponível</span><strong>${escapeHtml(error.message)}</strong></div><small>Revise o vendedor selecionado e use “Conferir impacto” para tentar novamente.</small>`;
      box.classList.remove('d-none');
      document.getElementById('execute-migration').disabled = true;
      toast('error', error.message);
    }
    finally { setBusy(button, false); }
  }

  async function executeMigration(event) {
    event.preventDefault();
    const button = document.getElementById('execute-migration');
    if (!await askConfirmation({ title: 'Confirmar carga inicial?', copy: 'Esta exceção remove o vínculo atual dos leads aptos e poderá ser executada apenas uma vez para a empresa.', label: 'Confirmar carga inicial' })) return;
    setBusy(button, true, 'Movendo…');
    try {
      const payload = await request(root.dataset.migrationUrl, { method: 'POST', body: JSON.stringify({ vendedor_id: document.getElementById('migration-vendor').value }) });
      toast('success', payload.message);
      migrationModal.hide();
      document.getElementById('open-migration')?.remove();
      state.page = 1;
      await loadLeads();
    } catch (error) { toast('error', error.message); }
    finally { setBusy(button, false); }
  }

  let searchTimer;
  const tabButtons = [...document.querySelectorAll('[role="tab"]')];
  tabButtons.forEach((button, index) => {
    button.addEventListener('click', () => switchTab(button.dataset.tab));
    button.addEventListener('keydown', event => {
      let targetIndex = null;
      if (event.key === 'ArrowRight') targetIndex = (index + 1) % tabButtons.length;
      if (event.key === 'ArrowLeft') targetIndex = (index - 1 + tabButtons.length) % tabButtons.length;
      if (event.key === 'Home') targetIndex = 0;
      if (event.key === 'End') targetIndex = tabButtons.length - 1;
      if (targetIndex === null) return;
      event.preventDefault();
      switchTab(tabButtons[targetIndex].dataset.tab);
      tabButtons[targetIndex].focus();
    });
  });
  document.getElementById('new-strategy').addEventListener('click', () => openStrategy());
  document.getElementById('random-distribution').addEventListener('click', openRandomExecution);
  document.querySelector('[data-create-strategy]').addEventListener('click', () => openStrategy());
  document.getElementById('add-condition').addEventListener('click', () => addCondition({ campo: 'nome_base', operador: 'igual', valor: '' }));
  document.getElementById('history-prev').addEventListener('click', () => { if (state.historyPage > 1) { state.historyPage--; loadHistory(); } });
  document.getElementById('history-next').addEventListener('click', () => { if (state.historyPage < state.historyLastPage) { state.historyPage++; loadHistory(); } });
  document.getElementById('confirm-modal-action').addEventListener('click', () => {
    const resolve = confirmResolver;
    confirmResolver = null;
    confirmModal.hide();
    resolve?.(true);
  });
  confirmElement.addEventListener('hidden.bs.modal', () => {
    const resolve = confirmResolver;
    confirmResolver = null;
    resolve?.(false);
  });
  document.getElementById('preview-draft').addEventListener('click', event => previewDraft(event.currentTarget).catch(() => {}));
  document.getElementById('strategy-name').addEventListener('input', () => invalidateDraftPreview());
  document.getElementById('strategy-form').addEventListener('submit', saveStrategy);
  document.getElementById('execution-form').addEventListener('submit', executeStrategy);
  document.getElementById('allocation-list').addEventListener('input', updateExecutionState);
  document.getElementById('refresh-execution-preview').addEventListener('click', loadExecutionPreview);
  document.getElementById('strategy-list').addEventListener('click', event => {
    const run = event.target.closest('[data-run-strategy]');
    const edit = event.target.closest('[data-edit-strategy]');
    const archive = event.target.closest('[data-archive-strategy]');
    if (run) openExecution(Number(run.dataset.runStrategy));
    if (edit) openStrategy(state.strategies.find(item => item.id === Number(edit.dataset.editStrategy)));
    if (archive) archiveStrategy(Number(archive.dataset.archiveStrategy));
  });
  document.getElementById('lead-search').addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => { state.page = 1; loadLeads(); }, 350); });
  ['lead-origin', 'lead-base'].forEach(id => document.getElementById(id).addEventListener('change', () => { state.page = 1; loadLeads(); }));
  document.getElementById('refresh-leads').addEventListener('click', loadLeads);
  document.getElementById('leads-prev').addEventListener('click', () => { if (state.page > 1) { state.page--; loadLeads(); } });
  document.getElementById('leads-next').addEventListener('click', () => { if (state.page < state.lastPage) { state.page++; loadLeads(); } });
  document.getElementById('open-migration')?.addEventListener('click', () => migrationModal.show());
  document.getElementById('preview-migration').addEventListener('click', previewMigration);
  document.getElementById('migration-form').addEventListener('submit', executeMigration);
  document.getElementById('migration-vendor').addEventListener('change', () => { document.getElementById('migration-preview').classList.add('d-none'); document.getElementById('execute-migration').disabled = true; });

  const initialOrigin = new URLSearchParams(window.location.search).get('origem');
  if (initialOrigin && [...document.getElementById('lead-origin').options].some(option => option.value === initialOrigin)) document.getElementById('lead-origin').value = initialOrigin;
  loadLeads();
});
