'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('#mailing-import-form');
  const uploadPanel = document.querySelector('#upload-panel');
  const analysisPanel = document.querySelector('#analysis-panel');
  const analyzeButton = document.querySelector('#analyze-button');
  const importNewButton = document.querySelector('#import-new-button');
  const resolveButton = document.querySelector('#resolve-button');
  const discardButton = document.querySelector('#discard-analysis');
  const destination = document.querySelector('#duplicate-destination');
  const searchInput = document.querySelector('#duplicate-search');
  const selectAll = document.querySelector('#select-all-duplicates');
  const tableBody = document.querySelector('#duplicate-table-body');
  const liveRegion = document.querySelector('#mailing-live-region');
  const pageSize = 50;
  let current = null;
  let selected = new Set();
  let page = 1;

  toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 5000, preventDuplicates: true };
  Dropzone.autoDiscover = false;
  const dropzone = new Dropzone('#dropzone-basic', {
    url: '/mailing/importaMailing',
    autoProcessQueue: false,
    maxFiles: 1,
    maxFilesize: 5,
    acceptedFiles: '.xls,.xlsx',
    addRemoveLinks: true,
    dictRemoveFile: 'Remover arquivo',
    dictInvalidFileType: 'Selecione um arquivo XLS ou XLSX.',
    dictFileTooBig: 'O arquivo excede o limite de 5 MB.'
  });

  dropzone.on('addedfile', () => {
    if (dropzone.files.length > 1) dropzone.removeFile(dropzone.files[0]);
    analyzeButton.disabled = false;
  });
  dropzone.on('removedfile', () => { analyzeButton.disabled = dropzone.files.length === 0; });

  form.addEventListener('submit', async event => {
    event.preventDefault();
    const file = dropzone.getAcceptedFiles()[0];
    if (!form.reportValidity() || !file) {
      notify('error', file ? 'Revise os campos obrigatórios.' : 'Selecione uma planilha para continuar.');
      return;
    }
    const formData = new FormData(form);
    formData.append('file', file);
    await withLoading(analyzeButton, 'Analisando arquivo…', async () => {
      const response = await request('/mailing/importaMailing', { method: 'POST', body: formData });
      current = response.data;
      selected.clear();
      page = 1;
      render();
      notify('success', response.message);
    });
  });

  importNewButton.addEventListener('click', async () => {
    if (!current) return;
    await withLoading(importNewButton, 'Importando leads…', async () => {
      const response = await request(`/mailing/importacoes/${current.importacao.id}/importar-novos`, { method: 'POST' });
      current = response.data;
      render();
      notify('success', response.message);
    });
  });

  resolveButton.addEventListener('click', async () => {
    if (!current || selected.size === 0) return;
    const payload = {
      itens: Array.from(selected),
      destino: destination.value,
      vendedor_id: document.querySelector('#duplicate-vendor').value || null,
      tabulacao_id: document.querySelector('#duplicate-status').value || null
    };
    await withLoading(resolveButton, 'Aplicando destino…', async () => {
      const response = await request(`/mailing/importacoes/${current.importacao.id}/resolver-duplicados`, {
        method: 'POST', body: JSON.stringify(payload)
      });
      current = response.data;
      selected.clear();
      render();
      notify('success', response.message);
    });
  });

  discardButton.addEventListener('click', async () => {
    if (!current || !window.confirm('Descartar esta análise? Os leads já importados ou movimentados não serão desfeitos.')) return;
    await withLoading(discardButton, 'Descartando…', async () => {
      const response = await request(`/mailing/importacoes/${current.importacao.id}`, { method: 'DELETE' });
      current = null;
      selected.clear();
      analysisPanel.classList.add('d-none');
      uploadPanel.classList.remove('d-none');
      document.querySelector('#pending-label').classList.add('d-none');
      form.reset();
      dropzone.removeAllFiles(true);
      notify('success', response.message);
    });
  });

  destination.addEventListener('change', () => {
    document.querySelectorAll('.mi-vendor-field').forEach(field => field.classList.toggle('d-none', destination.value === 'PREDITIVA'));
  });
  searchInput.addEventListener('input', () => { page = 1; renderTable(); });
  selectAll.addEventListener('change', () => {
    visibleItems().forEach(item => {
      if (item.elegivel_movimentacao && !item.resolucao) selectAll.checked ? selected.add(item.id) : selected.delete(item.id);
    });
    renderTable();
  });
  tableBody.addEventListener('change', event => {
    const checkbox = event.target.closest('.mi-row-check');
    if (!checkbox) return;
    const id = Number(checkbox.value);
    checkbox.checked ? selected.add(id) : selected.delete(id);
    checkbox.closest('tr')?.classList.toggle('is-selected', checkbox.checked);
    updateSelection();
  });
  document.querySelector('#previous-page').addEventListener('click', () => {
    if (page > 1) { page--; renderTable(); }
  });
  document.querySelector('#next-page').addEventListener('click', () => {
    const totalPages = Math.max(1, Math.ceil(filteredItems().length / pageSize));
    if (page < totalPages) { page++; renderTable(); }
  });

  loadPending();

  async function loadPending() {
    try {
      const response = await request('/mailing/importacoes/pendente');
      if (response.data) {
        current = response.data;
        render();
        notify('info', 'Sua última análise pendente foi restaurada.');
      }
    } catch (error) {
      notify('error', error.message || 'Não foi possível recuperar a análise pendente. Recarregue a página para tentar novamente.');
    }
  }

  function render() {
    if (!current) return;
    const data = current.importacao;
    uploadPanel.classList.add('d-none');
    analysisPanel.classList.remove('d-none');
    document.querySelector('#pending-label').classList.toggle('d-none', data.status === 'CONCLUIDA');
    document.querySelector('#analysis-description').textContent = `${data.nome_base} · ${data.arquivo_nome || 'arquivo analisado'}`;
    document.querySelector('#summary-total').textContent = number(data.total_itens);
    document.querySelector('#summary-new').textContent = number(data.total_novos);
    document.querySelector('#summary-duplicate').textContent = number(data.total_duplicados);
    document.querySelector('#summary-invalid').textContent = number(data.total_invalidos);
    document.querySelector('#summary-resolved').textContent = number(data.total_resolvidos);
    const remainingNew = Math.max(0, Number(data.total_novos) - Number(data.total_importados));
    document.querySelector('#new-leads-title').textContent = remainingNew > 0
      ? `${number(remainingNew)} lead(s) novo(s) pronto(s) para importar`
      : `${number(data.total_importados)} lead(s) novo(s) importado(s)`;
    document.querySelector('#new-leads-copy').textContent = remainingNew > 0
      ? 'Somente os não duplicados serão cadastrados; os demais continuarão disponíveis abaixo.'
      : 'A importação dos leads novos foi concluída.';
    importNewButton.disabled = remainingNew === 0;
    importNewButton.textContent = remainingNew > 0 ? `Importar ${number(remainingNew)} não duplicado(s)` : 'Novos já importados';
    renderTable();
  }

  function renderTable() {
    if (!current) return;
    const filtered = filteredItems();
    const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
    page = Math.min(page, totalPages);
    const start = (page - 1) * pageSize;
    const visible = filtered.slice(start, start + pageSize);
    tableBody.innerHTML = visible.map(item => {
      const proposals = item.propostas.length
        ? item.propostas.map(proposal => `<div class="mi-proposal" title="Proposta ${escapeHtml(proposal.numero || proposal.id)}">
            <span class="mi-proposal-number">${escapeHtml(proposal.numero || `#${proposal.id}`)}</span>
            <span class="mi-proposal-status">${escapeHtml(proposal.status)}</span>
          </div>`).join('')
        : '<span class="mi-muted">Nenhuma proposta</span>';
      const statusClass = { PREDITIVA: 'is-predictive', DESCARTADO: 'is-discarded', 'SEM ATRIBUIÇÃO': 'is-unassigned', 'AGUARDANDO IMPORTAÇÃO': 'is-unassigned' }[item.situacao] || '';
      const leadName = item.nome_arquivo || item.nome_cadastrado || 'Nome não informado';
      const protectedLead = !item.elegivel_movimentacao && item.resolucao !== 'ENVIADO_PREDITIVA' && item.resolucao !== 'ATRIBUIDO_VENDEDOR';
      const decision = protectedLead
        ? '<div class="mi-decision is-protected"><i class="ri-lock-2-line" aria-hidden="true"></i><div><span>Movimentação bloqueada</span><small>Lead já está em atendimento</small></div></div>'
        : (item.resolucao
          ? `<div class="mi-decision is-done"><i class="ri-checkbox-circle-fill" aria-hidden="true"></i><div><span>${resolutionLabel(item.resolucao)}</span><small>${escapeHtml(item.resolvido_em || '')}</small></div></div>`
          : '<div class="mi-decision is-pending"><span class="mi-decision-dot" aria-hidden="true"></span><div><span>Pronto para reciclar</span><small>Selecione para definir o destino</small></div></div>');
      return `<tr class="${item.resolucao ? 'is-resolved' : ''} ${protectedLead ? 'is-protected' : ''} ${selected.has(item.id) ? 'is-selected' : ''}">
        <td class="mi-check-column"><input class="form-check-input mi-row-check" type="checkbox" value="${item.id}"
          ${selected.has(item.id) ? 'checked' : ''} ${item.resolucao || !item.elegivel_movimentacao ? 'disabled' : ''}
          aria-label="Selecionar ${escapeHtml(leadName)}"></td>
        <td><div class="mi-person-cell">
          <span class="mi-avatar mi-lead-avatar" aria-hidden="true">${escapeHtml(initials(leadName))}</span>
          <div class="mi-person-copy">
            <span class="mi-lead-name" title="${escapeHtml(leadName)}">${escapeHtml(leadName)}</span>
            <span class="mi-document">${formatDocument(item.cpf)} <span class="mi-row-number">Linha ${item.linha}</span></span>
            ${item.nome_cadastrado && item.nome_cadastrado !== item.nome_arquivo ? `<span class="mi-lead-existing"><i class="ri-database-2-line" aria-hidden="true"></i> Cadastrado como ${escapeHtml(item.nome_cadastrado)}</span>` : ''}
          </div>
        </div></td>
        <td><div class="mi-journey-cell"><span class="mi-status ${statusClass}">${escapeHtml(item.situacao)}</span><span class="mi-tabulation">${escapeHtml(item.tabulacao || 'Sem status comercial')}</span></div></td>
        <td><div class="mi-owner-cell"><span class="mi-avatar mi-owner-avatar" aria-hidden="true">${escapeHtml(initials(item.vendedor || 'Sem vendedor'))}</span><div><span class="mi-owner">${escapeHtml(item.vendedor || 'Sem vendedor')}</span><span class="mi-owner-caption">Responsável atual</span></div></div></td>
        <td><div class="mi-proposals">${proposals}</div></td>
        <td>${decision}</td>
      </tr>`;
    }).join('');
    const pending = current.itens.filter(item => item.elegivel_movimentacao && !item.resolucao).length;
    document.querySelector('#duplicates-empty').classList.toggle('d-none', filtered.length !== 0);
    document.querySelector('.mi-table').classList.toggle('d-none', filtered.length === 0);
    document.querySelector('#bulk-bar').classList.toggle('d-none', pending === 0);
    document.querySelector('#table-range').textContent = filtered.length
      ? `${number(start + 1)}–${number(Math.min(start + pageSize, filtered.length))} de ${number(filtered.length)}` : '0 resultados';
    document.querySelector('#previous-page').disabled = page <= 1;
    document.querySelector('#next-page').disabled = page >= totalPages;
    document.querySelector('.mi-table-footer').classList.toggle('d-none', filtered.length === 0);
    const selectable = visible.filter(item => item.elegivel_movimentacao && !item.resolucao);
    selectAll.checked = selectable.length > 0 && selectable.every(item => selected.has(item.id));
    selectAll.indeterminate = selectable.some(item => selected.has(item.id)) && !selectAll.checked;
    updateSelection();
  }

  function filteredItems() {
    if (!current) return [];
    const term = normalize(searchInput.value);
    if (!term) return current.itens;
    return current.itens.filter(item => normalize(`${item.nome_arquivo} ${item.nome_cadastrado} ${item.cpf}`).includes(term));
  }
  function visibleItems() {
    const filtered = filteredItems();
    return filtered.slice((page - 1) * pageSize, page * pageSize);
  }
  function updateSelection() {
    document.querySelector('#selected-count').textContent = number(selected.size);
    resolveButton.disabled = selected.size === 0;
  }

  async function request(url, options = {}) {
    const headers = { Accept: 'application/json', ...(options.headers || {}) };
    if (!(options.body instanceof FormData)) headers['Content-Type'] = 'application/json';
    if (options.method && options.method !== 'GET') headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
    const response = await fetch(url, { ...options, headers });
    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.error) {
      const validationMessage = data.errors ? Object.values(data.errors).flat()[0] : null;
      throw new Error(validationMessage || data.message || 'Não foi possível concluir a operação. Tente novamente.');
    }
    return data;
  }

  async function withLoading(button, text, action) {
    const original = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>${escapeHtml(text)}`;
    try { await action(); } catch (error) { notify('error', error.message); }
    finally {
      button.innerHTML = original;
      if (button === analyzeButton) button.disabled = dropzone.getAcceptedFiles().length === 0;
      else if (button === resolveButton) button.disabled = selected.size === 0;
      else if (button === importNewButton && current) button.disabled = Number(current.importacao.total_novos) <= Number(current.importacao.total_importados);
      else button.disabled = false;
    }
  }

  function notify(type, message) { toastr[type](message); liveRegion.textContent = message; }
  function formatDocument(value) {
    const digits = String(value || '').replace(/\D/g, '');
    if (digits.length === 11) return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6, 9)}-${digits.slice(9)}`;
    if (digits.length === 14) return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8, 12)}-${digits.slice(12)}`;
    return escapeHtml(value || 'CPF não informado');
  }
  function resolutionLabel(value) { return { ENVIADO_PREDITIVA: 'Enviado à preditiva', ATRIBUIDO_VENDEDOR: 'Atribuído como novo lead', MANTIDO_ATUAL: 'Mantido com vendedor atual' }[value] || escapeHtml(value); }
  function initials(value) {
    const parts = String(value || '').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return '—';
    return `${parts[0][0] || ''}${parts.length > 1 ? parts[parts.length - 1][0] : ''}`.toUpperCase();
  }
  function normalize(value) { return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase(); }
  function number(value) { return new Intl.NumberFormat('pt-BR').format(Number(value || 0)); }
  function escapeHtml(value) { const div = document.createElement('div'); div.textContent = String(value ?? ''); return div.innerHTML; }
});
