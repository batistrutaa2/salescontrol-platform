'use strict';

(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  const statusLabel = {
    AGUARDANDO: 'Recebido', RECEBIDO: 'Recebido pelo CRM', VERIFICANDO: 'Verificando segurança',
    AGUARDANDO_ENVIO: 'Aguardando transferência', ENVIANDO: 'Transferindo',
    DISPONIVEL: 'Disponível no servidor', FALHA: 'Falha', BLOQUEADO: 'Bloqueado',
    EXCLUSAO_PENDENTE: 'Exclusão agendada', EXCLUIDO: 'Excluído'
  };

  function escapeHtml(value) {
    return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function formatBytes(bytes) {
    return bytes < 1048576 ? `${Math.max(1, Math.round(bytes / 1024))} KB` : `${(bytes / 1048576).toFixed(1)} MB`;
  }

  function mount(root, vendaId) {
    if (!root || !vendaId) return;
    if (typeof root._vdCleanup === 'function') root._vdCleanup();
    root.dataset.vendaId = vendaId;
    const input = root.querySelector('[data-vd-input]');
    const list = root.querySelector('[data-vd-list]');
    const live = root.querySelector('[data-vd-live]');
    const empty = root.querySelector('[data-vd-empty]');
    const summary = root.querySelector('[data-vd-summary]');
    const path = root.querySelector('[data-vd-path]');
    let polling;
    let echoChannel;
    let lastPayload = { status: 'PENDENTE', diretorio: null, documentos: [] };
    const browserUploads = new Map();
    root._vdCleanup = () => {
      clearTimeout(polling);
      if (echoChannel && window.Echo) window.Echo.leave(echoChannel);
    };

    const announce = (message, error = false) => {
      live.textContent = message;
      live.classList.toggle('is-error', error);
    };

    async function request(url, options = {}) {
      const response = await fetch(url, { ...options, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, ...(options.headers || {}) } });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) {
        const errors = payload.errors ? Object.values(payload.errors).flat() : [];
        throw new Error(errors[0] || payload.message || 'Não foi possível concluir a operação.');
      }
      return payload;
    }

    function render(payload) {
      lastPayload = payload;
      const hasDocuments = payload.documentos.length > 0;
      const inactiveWaiting = hasDocuments && payload.status === 'PENDENTE' && payload.documentos.some(doc => !doc.processamento_ativo);
      summary.textContent = browserUploads.size > 0 ? 'Enviando ao CRM' : (!hasDocuments ? 'Sem documentos' : (inactiveWaiting ? 'Recebido · aguardando servidor' : String(payload.status || 'PENDENTE').replaceAll('_', ' ').toLowerCase().replace(/^./, c => c.toUpperCase())));
      summary.dataset.status = inactiveWaiting ? 'AGUARDANDO_SERVIDOR' : (payload.status || 'PENDENTE');
      path.hidden = !payload.diretorio;
      path.textContent = payload.diretorio ? `Destino: Y:\\${payload.diretorio.replaceAll('/', '\\')}` : '';
      list.innerHTML = Array.from(browserUploads.values()).map(item => `
        <li class="vd-item is-waiting vd-browser-upload">
          <div class="vd-file-icon" aria-hidden="true"><i class="ri-upload-cloud-2-line"></i></div>
          <div class="vd-file-info"><strong title="${escapeHtml(item.name)}">${escapeHtml(item.name)}</strong>
            <span>${formatBytes(item.size)} <b class="vd-file-status is-waiting">Enviando ao CRM · ${item.progress}%</b></span>
            <progress value="${item.progress}" max="100" aria-label="Progresso de ${escapeHtml(item.name)}: ${item.progress}%"></progress>
          </div>
        </li>`).join('');
      empty.hidden = payload.documentos.length > 0 || browserUploads.size > 0;
      payload.documentos.forEach(doc => {
        const canRetry = doc.pode_reenviar;
        const stateClass = ['DISPONIVEL'].includes(doc.status) ? 'is-success' : (['FALHA', 'BLOQUEADO'].includes(doc.status) ? 'is-error' : 'is-waiting');
        const isPdf = doc.mime_type === 'application/pdf';
        const li = document.createElement('li');
        li.className = `vd-item ${stateClass}`;
        li.innerHTML = `
          <div class="vd-file-icon ${isPdf ? 'is-pdf' : ''}" aria-hidden="true"><i class="${isPdf ? 'ri-file-pdf-2-line' : 'ri-image-line'}"></i></div>
          <div class="vd-file-info"><strong title="${escapeHtml(doc.nome)}">${escapeHtml(doc.nome)}</strong><span>${formatBytes(doc.tamanho)} <b class="vd-file-status ${stateClass}">${escapeHtml(doc.etapa || statusLabel[doc.status] || doc.status)}</b></span>${doc.erro ? `<small>${escapeHtml(doc.erro)}</small>` : ''}</div>
          <div class="vd-actions">
            ${canRetry ? `<button type="button" data-vd-retry="${doc.id}">Tentar novamente</button>` : ''}
            ${doc.pode_excluir ? `<button type="button" class="is-danger" data-vd-delete="${doc.id}" aria-label="Excluir ${escapeHtml(doc.nome)}">Excluir</button>` : ''}
          </div>`;
        list.appendChild(li);
      });
      const active = payload.documentos.some(doc => ['AGUARDANDO', 'RECEBIDO', 'VERIFICANDO', 'AGUARDANDO_ENVIO', 'ENVIANDO', 'EXCLUSAO_PENDENTE'].includes(doc.status));
      clearTimeout(polling);
      if (active) polling = setTimeout(load, document.hidden ? 15000 : 4000);

      if (!echoChannel && window.Echo && payload.empresa_id) {
        echoChannel = `venda-documentos.${payload.empresa_id}.${vendaId}`;
        window.Echo.private(echoChannel).listen('.documento.atualizado', () => load());
      }
    }

    async function load() {
      try { render(await request(`/vendas/${vendaId}/documentos`)); }
      catch (error) { announce(error.message, true); }
    }

    function upload(file, clientId, onProgress) {
      if (file.size > 25 * 1024 * 1024) throw new Error(`${file.name} ultrapassa o limite de 25 MB.`);
      const extension = file.name.split('.').pop().toLowerCase();
      const allowed = file.type === 'application/pdf' || (file.type.startsWith('image/') && file.type !== 'image/svg+xml') || ['heic', 'heif', 'tif', 'tiff'].includes(extension);
      if (!allowed) throw new Error(`${file.name} não é um PDF ou imagem permitida.`);
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
          if (xhr.status >= 200 && xhr.status < 300) resolve(payload);
          else reject(new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || 'Não foi possível receber o arquivo.'));
        };
        xhr.onerror = () => reject(new Error('A conexão caiu durante o envio. O CRM verificará duplicidade ao tentar novamente.'));
        xhr.send(data);
      });
    }

    async function uploadComRetry(item) {
      const atualizar = progress => {
        browserUploads.set(item.id, { name: item.file.name, size: item.file.size, progress });
        render(lastPayload);
      };
      try {
        await upload(item.file, item.id, atualizar);
      } catch (firstError) {
        await upload(item.file, item.id, atualizar).catch(() => { throw firstError; });
      } finally {
        browserUploads.delete(item.id);
        render(lastPayload);
      }
    }

    root.querySelector('[data-vd-add]').onclick = () => input.click();
    input.onchange = async () => {
      const files = Array.from(input.files || []);
      input.value = '';
      if (files.length > 30) return announce('Selecione no máximo 30 documentos.', true);
      announce(`Enviando ${files.length} documento(s) ao CRM…`);
      let failures = 0;
      const itens = files.map(file => ({ file, id: crypto.randomUUID() }));
      let cursor = 0;
      const worker = async () => {
        while (cursor < itens.length) {
          const item = itens[cursor++];
          try { await uploadComRetry(item); } catch (error) { failures++; announce(`${item.file.name}: ${error.message}`, true); }
        }
      };
      await Promise.all(Array.from({ length: Math.min(2, itens.length) }, worker));
      await load();
      if (!failures) announce('Documentos recebidos pelo CRM e encaminhados para verificação.');
    };

    list.onclick = async event => {
      const retry = event.target.closest('[data-vd-retry]');
      const remove = event.target.closest('[data-vd-delete]');
      try {
        if (retry) await request(`/vendas/${vendaId}/documentos/${retry.dataset.vdRetry}/reenviar`, { method: 'POST' });
        if (remove) {
          if (!window.confirm('Excluir este documento? A ação será auditada.')) return;
          await request(`/vendas/${vendaId}/documentos/${remove.dataset.vdDelete}`, { method: 'DELETE' });
        }
        await load();
      } catch (error) { announce(error.message, true); }
    };

    load();
  }

  window.VendaDocumentos = { mount };
  document.querySelectorAll('[data-venda-documentos][data-venda-id]').forEach(root => mount(root, root.dataset.vendaId));
})();
