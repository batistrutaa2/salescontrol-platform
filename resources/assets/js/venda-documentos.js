'use strict';

(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  const maxFiles = 30;
  const activeStatuses = ['AGUARDANDO', 'RECEBIDO', 'VERIFICANDO', 'AGUARDANDO_ENVIO', 'ENVIANDO', 'EXCLUSAO_PENDENTE'];
  const statusLabel = {
    AGUARDANDO: 'Recebido',
    RECEBIDO: 'Recebido pelo CRM',
    VERIFICANDO: 'Preparando transferência',
    AGUARDANDO_ENVIO: 'Aguardando transferência',
    ENVIANDO: 'Transferindo',
    DISPONIVEL: 'Disponível no servidor',
    FALHA: 'Falha',
    BLOQUEADO: 'Bloqueado',
    EXCLUSAO_PENDENTE: 'Exclusão agendada',
    EXCLUIDO: 'Excluído'
  };

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function normalizeName(value) {
    const basename = String(value || '').replace(/^.*[\\/]/u, '');
    const normalized = typeof basename.normalize === 'function' ? basename.normalize('NFC') : basename;

    return normalized
      .replace(/^[\p{Z}\s]+|[\p{Z}\s]+$/gu, '')
      .replace(/[\p{Z}\s]+/gu, ' ')
      .toLocaleLowerCase('pt-BR');
  }

  function formatBytes(bytes) {
    return bytes < 1048576 ? `${Math.max(1, Math.round(bytes / 1024))} KB` : `${(bytes / 1048576).toFixed(1)} MB`;
  }

  function documentCountLabel(count) {
    return `${count} ${count === 1 ? 'documento' : 'documentos'}`;
  }

  function createClientUploadId() {
    const browserCrypto = window.crypto;
    if (typeof browserCrypto?.randomUUID === 'function') return browserCrypto.randomUUID();
    if (typeof browserCrypto?.getRandomValues === 'function') {
      return `${Date.now()}-${browserCrypto.getRandomValues(new Uint32Array(2)).join('-')}`;
    }
    return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
  }

  function friendlySummary(payload, uploadsCount) {
    const documents = Array.isArray(payload.documentos) ? payload.documentos : [];
    if (uploadsCount > 0) return { label: 'Enviando', status: 'PROCESSANDO' };
    if (documents.length === 0) return { label: 'Sem documentos', status: 'PENDENTE' };

    const inactiveWaiting = payload.status === 'PENDENTE' && documents.some(doc => !doc.processamento_ativo);
    if (inactiveWaiting) return { label: 'Aguardando servidor', status: 'AGUARDANDO_SERVIDOR' };

    const status = payload.status || 'PENDENTE';
    const label = String(status).replace(/_/g, ' ').toLowerCase().replace(/^./, character => character.toUpperCase());
    return { label, status };
  }

  function createUploadError(message, transient = false) {
    const error = new Error(message);
    error.transient = transient;
    return error;
  }

  function mount(root, vendaId) {
    if (!root || !vendaId) return;
    if (typeof root._vdCleanup === 'function') root._vdCleanup();

    root.dataset.vendaId = vendaId;
    const input = root.querySelector('[data-vd-input]');
    const addButton = root.querySelector('[data-vd-add]');
    const dropzone = root.querySelector('[data-vd-dropzone]');
    const list = root.querySelector('[data-vd-list]');
    const listShell = root.querySelector('[data-vd-list-shell]');
    const listCount = root.querySelector('[data-vd-list-count]');
    const live = root.querySelector('[data-vd-live]');
    const empty = root.querySelector('[data-vd-empty]');
    const summary = root.querySelector('[data-vd-summary]');
    const path = root.querySelector('[data-vd-path]');
    const loading = root.querySelector('[data-vd-loading]');
    const loadError = root.querySelector('[data-vd-load-error]');
    const loadErrorMessage = root.querySelector('[data-vd-load-error-message]');
    const reloadButton = root.querySelector('[data-vd-reload]');
    const launcherCount = root.querySelector('[data-vd-launcher-count]');
    const launcherStatus = root.querySelector('[data-vd-launcher-status]');
    const modal = root.querySelector('[data-vd-modal]');
    const modalTitle = modal?.querySelector('[tabindex="-1"]');

    if (!input || !addButton || !dropzone || !list || !live || !empty || !summary || !loading || !loadError || !listShell) return;

    let polling;
    let echoChannel;
    let loaded = false;
    let loadingDocuments = false;
    let uploadingBatch = false;
    let pendingDeleteId = null;
    let currentLoad = null;
    let lastPayload = { status: 'PENDENTE', diretorio: null, documentos: [] };
    const browserUploads = new Map();

    const modalShownHandler = () => {
      modalTitle?.focus();
      load();
    };

    root._vdCleanup = () => {
      clearTimeout(polling);
      if (echoChannel && window.Echo) window.Echo.leave(echoChannel);
      modal?.removeEventListener('shown.bs.modal', modalShownHandler);
    };

    const announce = (message, error = false) => {
      live.setAttribute('role', error ? 'alert' : 'status');
      live.setAttribute('aria-live', error ? 'assertive' : 'polite');
      live.textContent = message;
      live.classList.toggle('is-error', error);
    };

    const updateControls = () => {
      const activeBrowserUploads = Array.from(browserUploads.values()).filter(item => item.state !== 'error').length;
      const usedSlots = (lastPayload.documentos || []).length + activeBrowserUploads;
      const disabled = !loaded || !loadError.hidden || loadingDocuments || uploadingBatch || usedSlots >= maxFiles;
      addButton.disabled = disabled;
      input.disabled = disabled;
      dropzone.classList.toggle('is-disabled', disabled);
      dropzone.setAttribute('aria-disabled', String(disabled));
      if (usedSlots >= maxFiles) addButton.textContent = 'Limite atingido';
      else addButton.textContent = 'Selecionar arquivos';
    };

    const showLoading = () => {
      loadingDocuments = true;
      loading.hidden = false;
      loadError.hidden = true;
      if (!loaded) listShell.hidden = true;
      if (launcherCount && !loaded) launcherCount.textContent = 'Carregando arquivos…';
      updateControls();
    };

    const showLoadError = message => {
      loadingDocuments = false;
      loading.hidden = true;
      loadError.hidden = false;
      loadErrorMessage.textContent = message;
      if (!loaded) listShell.hidden = true;
      if (launcherCount && !loaded) launcherCount.textContent = 'Falha ao carregar';
      if (launcherStatus && !loaded) {
        launcherStatus.textContent = 'Indisponível';
        launcherStatus.dataset.status = 'COM_FALHA';
      }
      updateControls();
    };

    async function request(url, options = {}) {
      const response = await fetch(url, {
        ...options,
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, ...(options.headers || {}) }
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) {
        const errors = payload.errors ? Object.values(payload.errors).flat() : [];
        throw new Error(errors[0] || payload.message || 'Não foi possível concluir a operação.');
      }
      return payload;
    }

    function render(payload) {
      payload.documentos = Array.isArray(payload.documentos) ? payload.documentos : [];
      lastPayload = payload;
      loaded = true;
      loadingDocuments = false;
      loading.hidden = true;
      loadError.hidden = true;
      listShell.hidden = false;

      const documentsCount = payload.documentos.length;
      const activeUploadsCount = Array.from(browserUploads.values()).filter(item => item.state !== 'error').length;
      const overall = friendlySummary(payload, activeUploadsCount);
      summary.textContent = overall.label;
      summary.dataset.status = overall.status;
      if (launcherCount) launcherCount.textContent = documentCountLabel(documentsCount);
      if (launcherStatus) {
        launcherStatus.textContent = overall.label;
        launcherStatus.dataset.status = overall.status;
      }
      if (listCount) listCount.textContent = documentCountLabel(documentsCount);

      path.hidden = !payload.diretorio;
      path.textContent = payload.diretorio ? `Destino: Y:\\${payload.diretorio.replace(/\//g, '\\')}` : '';

      list.innerHTML = Array.from(browserUploads.values()).map(item => {
        const stateClass = item.state === 'error' ? 'is-error' : 'is-waiting';
        const stateLabel = item.state === 'error' ? item.error : `Enviando ao CRM · ${item.progress}%`;
        return `
          <li class="vd-item ${stateClass} vd-browser-upload" data-vd-name-key="${escapeHtml(normalizeName(item.name))}" tabindex="-1">
            <div class="vd-file-icon" aria-hidden="true"><i class="${item.state === 'error' ? 'ri-error-warning-line' : 'ri-upload-cloud-2-line'}"></i></div>
            <div class="vd-file-info">
              <strong title="${escapeHtml(item.name)}">${escapeHtml(item.name)}</strong>
              <span>${formatBytes(item.size)} <b class="vd-file-status ${stateClass}">${escapeHtml(stateLabel)}</b></span>
              ${item.state === 'error' ? '' : `<progress value="${item.progress}" max="100" aria-label="Progresso de ${escapeHtml(item.name)}: ${item.progress}%"></progress>`}
            </div>
            ${item.state === 'error' ? `<div class="vd-actions"><button type="button" data-vd-dismiss-upload="${item.id}">Remover aviso</button></div>` : ''}
          </li>`;
      }).join('');

      empty.hidden = documentsCount > 0 || browserUploads.size > 0;
      payload.documentos.forEach(doc => {
        const canRetry = doc.pode_reenviar;
        const stateClass = doc.status === 'DISPONIVEL' ? 'is-success' : (['FALHA', 'BLOQUEADO'].includes(doc.status) ? 'is-error' : 'is-waiting');
        const isPdf = doc.mime_type === 'application/pdf';
        const isConfirmingDelete = String(pendingDeleteId) === String(doc.id);
        const li = document.createElement('li');
        li.className = `vd-item ${stateClass}`;
        li.dataset.vdNameKey = normalizeName(doc.nome);
        li.tabIndex = -1;
        li.innerHTML = `
          <div class="vd-file-icon ${isPdf ? 'is-pdf' : ''}" aria-hidden="true"><i class="${isPdf ? 'ri-file-pdf-2-line' : 'ri-image-line'}"></i></div>
          <div class="vd-file-info">
            <strong title="${escapeHtml(doc.nome)}">${escapeHtml(doc.nome)}</strong>
            <span>${formatBytes(doc.tamanho)} <b class="vd-file-status ${stateClass}">${escapeHtml(doc.etapa || statusLabel[doc.status] || doc.status)}</b></span>
            ${doc.erro ? `<small>${escapeHtml(doc.erro)}</small>` : ''}
          </div>
          <div class="vd-actions" ${isConfirmingDelete ? 'hidden' : ''}>
            ${canRetry ? `<button type="button" data-vd-retry="${doc.id}">Tentar novamente</button>` : ''}
            ${doc.pode_excluir ? `<button type="button" class="is-danger" data-vd-delete="${doc.id}" aria-label="Excluir ${escapeHtml(doc.nome)}">Excluir</button>` : ''}
          </div>
          ${doc.pode_excluir ? `
            <div class="vd-delete-confirm" data-vd-delete-confirmation="${doc.id}" ${isConfirmingDelete ? '' : 'hidden'}>
              <span>Excluir este arquivo?</span>
              <button type="button" data-vd-delete-cancel="${doc.id}">Cancelar</button>
              <button type="button" class="is-danger" data-vd-delete-confirm="${doc.id}">Excluir</button>
            </div>` : ''}`;
        list.appendChild(li);
      });

      updateControls();
      clearTimeout(polling);
      if (payload.documentos.some(doc => activeStatuses.includes(doc.status))) {
        polling = setTimeout(load, document.hidden ? 15000 : 4000);
      }

      if (!echoChannel && window.Echo && payload.empresa_id) {
        echoChannel = `venda-documentos.${payload.empresa_id}.${vendaId}`;
        window.Echo.private(echoChannel).listen('.documento.atualizado', () => load());
      }
    }

    async function load() {
      if (currentLoad) return currentLoad;
      if (!loaded) showLoading();
      currentLoad = request(`/vendas/${vendaId}/documentos`)
        .then(render)
        .catch(error => {
          showLoadError(error.message);
          announce(error.message, true);
        })
        .finally(() => { currentLoad = null; });
      return currentLoad;
    }

    function validateFile(file) {
      if (file.size > 25 * 1024 * 1024) throw createUploadError(`${file.name} ultrapassa o limite de 25 MB.`);
      const extension = file.name.split('.').pop().toLowerCase();
      const allowed = file.type === 'application/pdf'
        || (file.type.startsWith('image/') && file.type !== 'image/svg+xml')
        || ['heic', 'heif', 'tif', 'tiff'].includes(extension);
      if (!allowed) throw createUploadError(`${file.name} não é um PDF ou imagem permitida.`);
    }

    function upload(file, clientId, onProgress) {
      validateFile(file);
      return new Promise((resolve, reject) => {
        const data = new FormData();
        data.append('arquivo', file);
        data.append('client_upload_id', clientId);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', `/vendas/${vendaId}/documentos`);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
        xhr.upload.onprogress = event => event.lengthComputable && onProgress(Math.round((event.loaded / event.total) * 100));
        xhr.onload = () => {
          const payload = (() => { try { return JSON.parse(xhr.responseText); } catch (_) { return {}; } })();
          if (xhr.status >= 200 && xhr.status < 300) {
            resolve(payload);
            return;
          }
          const message = Object.values(payload.errors || {}).flat()[0] || payload.message || 'Não foi possível receber o arquivo.';
          const transient = xhr.status === 408 || xhr.status === 429 || xhr.status >= 500;
          reject(createUploadError(message, transient));
        };
        xhr.onerror = () => reject(createUploadError('A conexão caiu durante o envio.', true));
        xhr.send(data);
      });
    }

    async function uploadWithTransientRetry(item) {
      const updateProgress = progress => {
        browserUploads.set(item.id, { ...item, progress, state: 'uploading', error: null });
        render(lastPayload);
      };

      try {
        await upload(item.file, item.id, updateProgress);
      } catch (firstError) {
        if (!firstError.transient) throw firstError;
        await upload(item.file, item.id, updateProgress);
      }
    }

    function highlightExisting(nameKey) {
      window.requestAnimationFrame(() => {
        const existing = Array.from(list.querySelectorAll('[data-vd-name-key]'))
          .find(item => item.dataset.vdNameKey === nameKey);
        if (!existing) return;
        existing.classList.remove('is-duplicate');
        void existing.offsetWidth;
        existing.classList.add('is-duplicate');
        existing.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        existing.focus({ preventScroll: true });
      });
    }

    async function handleFiles(fileList) {
      const files = Array.from(fileList || []);
      if (files.length === 0) return;

      const existingNames = new Set((lastPayload.documentos || []).map(doc => normalizeName(doc.nome)));
      browserUploads.forEach(item => existingNames.add(normalizeName(item.name)));
      const accepted = [];
      const duplicates = [];
      const invalid = [];
      const activeBrowserUploads = Array.from(browserUploads.values()).filter(item => item.state !== 'error').length;
      const usedSlots = (lastPayload.documentos || []).length + activeBrowserUploads;
      const availableSlots = Math.max(0, maxFiles - usedSlots);

      files.forEach(file => {
        const nameKey = normalizeName(file.name);
        if (existingNames.has(nameKey)) {
          duplicates.push({ name: file.name, key: nameKey });
          return;
        }
        if (accepted.length >= availableSlots) {
          invalid.push(`${file.name}: a venda já atingiu o limite de ${maxFiles} documentos.`);
          return;
        }
        try {
          validateFile(file);
          const item = { file, id: createClientUploadId(), name: file.name, size: file.size, progress: 0, state: 'uploading', error: null };
          accepted.push(item);
          existingNames.add(nameKey);
        } catch (error) {
          invalid.push(error.message);
        }
      });

      if (duplicates.length > 0) {
        const names = duplicates.map(item => `“${item.name}”`).join(', ');
        announce(`Não enviado: já existe documento com este nome nesta venda: ${names}. Renomeie ou exclua o arquivo existente.`, true);
        highlightExisting(duplicates[0].key);
      } else if (invalid.length > 0) {
        announce(invalid[0], true);
      }

      if (accepted.length === 0) return;
      uploadingBatch = true;
      accepted.forEach(item => browserUploads.set(item.id, item));
      render(lastPayload);
      if (duplicates.length === 0 && invalid.length === 0) {
        announce(`Enviando ${documentCountLabel(accepted.length)} ao CRM…`);
      }

      let failures = 0;
      const failureMessages = [];
      let cursor = 0;
      const worker = async () => {
        while (cursor < accepted.length) {
          const item = accepted[cursor++];
          try {
            await uploadWithTransientRetry(item);
            browserUploads.delete(item.id);
          } catch (error) {
            failures += 1;
            failureMessages.push(`${item.name}: ${error.message}`);
            browserUploads.set(item.id, { ...item, state: 'error', error: error.message });
          }
          render(lastPayload);
        }
      };

      await Promise.all(Array.from({ length: Math.min(2, accepted.length) }, worker));
      uploadingBatch = false;
      await load();

      const rejected = duplicates.length + invalid.length + failures;
      if (rejected === 0) {
        announce('Documentos recebidos pelo CRM e encaminhados para verificação.');
      } else {
        const reasons = [
          ...duplicates.map(item => `${item.name}: já existe um documento com este nome nesta venda.`),
          ...invalid,
          ...failureMessages
        ];
        announce(`${accepted.length - failures} enviado(s); ${rejected} não enviado(s). ${reasons.join(' ')}`, true);
      }
      updateControls();
    }

    addButton.onclick = () => input.click();
    input.onchange = async () => {
      const files = Array.from(input.files || []);
      input.value = '';
      await handleFiles(files);
    };

    reloadButton.onclick = () => load();
    dropzone.ondragenter = event => {
      event.preventDefault();
      if (!addButton.disabled) dropzone.classList.add('is-dragging');
    };
    dropzone.ondragover = event => {
      event.preventDefault();
      if (!addButton.disabled) dropzone.classList.add('is-dragging');
    };
    dropzone.ondragleave = event => {
      if (!dropzone.contains(event.relatedTarget)) dropzone.classList.remove('is-dragging');
    };
    dropzone.ondrop = async event => {
      event.preventDefault();
      dropzone.classList.remove('is-dragging');
      if (!addButton.disabled) await handleFiles(event.dataTransfer?.files);
    };

    list.onclick = async event => {
      const retry = event.target.closest('[data-vd-retry]');
      const remove = event.target.closest('[data-vd-delete]');
      const dismissUpload = event.target.closest('[data-vd-dismiss-upload]');
      const cancelDelete = event.target.closest('[data-vd-delete-cancel]');
      const confirmDelete = event.target.closest('[data-vd-delete-confirm]');

      if (dismissUpload) {
        browserUploads.delete(dismissUpload.dataset.vdDismissUpload);
        render(lastPayload);
        announce('Aviso de upload removido.');
        addButton.focus();
        return;
      }

      if (remove) {
        pendingDeleteId = remove.dataset.vdDelete;
        render(lastPayload);
        list.querySelector(`[data-vd-delete-confirm="${pendingDeleteId}"]`)?.focus();
        return;
      }

      if (cancelDelete) {
        const id = cancelDelete.dataset.vdDeleteCancel;
        pendingDeleteId = null;
        render(lastPayload);
        list.querySelector(`[data-vd-delete="${id}"]`)?.focus();
        return;
      }

      try {
        if (retry) {
          retry.disabled = true;
          await request(`/vendas/${vendaId}/documentos/${retry.dataset.vdRetry}/reenviar`, { method: 'POST' });
          announce('Documento encaminhado para uma nova tentativa.');
        }
        if (confirmDelete) {
          confirmDelete.disabled = true;
          await request(`/vendas/${vendaId}/documentos/${confirmDelete.dataset.vdDeleteConfirm}`, { method: 'DELETE' });
          pendingDeleteId = null;
          announce('Exclusão solicitada. O nome ficará disponível quando o processo terminar.');
        }
        await load();
        if (confirmDelete) addButton.focus();
      } catch (error) {
        announce(error.message, true);
        if (retry) retry.disabled = false;
        if (confirmDelete) confirmDelete.disabled = false;
      }
    };

    modal?.addEventListener('shown.bs.modal', modalShownHandler);
    load();
  }

  window.VendaDocumentos = { mount };
  document.querySelectorAll('[data-venda-documentos][data-venda-id]').forEach(root => mount(root, root.dataset.vendaId));
})();
