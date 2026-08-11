'use strict';

/**
 * Envio de Cotação por E-mail — composição + preview ao vivo.
 *
 * O remetente é o e-mail do vendedor logado (validado no backend: precisa ser
 * @lkbrokers.com). Reaproveita Quill (editor) e SweetAlert2 (toasts).
 */
(function () {
    const cfg = window.envioCotacao || {};
    const editorEl = document.getElementById('ec-editor');
    if (!editorEl) return;

    let anexoFile = null;

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function toast(type, title, message) {
        if (!window.Swal) return;
        const ok = type === 'success';
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            background: 'transparent',
            showClass: { popup: 'animate__animated animate__slideInRight animate__faster' },
            hideClass: { popup: 'animate__animated animate__slideOutRight animate__faster' },
            customClass: { popup: 'custom-toast-popup' },
            html: `
                <div class="custom-toast custom-toast-${ok ? 'success' : 'error'}">
                    <div class="toast-icon">
                        ${ok
                            ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
                            : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'}
                    </div>
                    <div class="toast-content">
                        <div class="toast-title">${escapeHtml(title)}</div>
                        <div class="toast-message">${escapeHtml(message)}</div>
                    </div>
                    <button type="button" class="toast-close" aria-label="Fechar notificação" onclick="Swal.close()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>`,
        });
    }

    function alerta(title, text) {
        if (window.Swal) Swal.fire({ icon: 'warning', title, text, confirmButtonColor: '#7C3AED' });
    }

    // -------------------------------------------------- Quill
    const quill = new Quill(editorEl, {
        modules: { toolbar: '#ec-toolbar' },
        placeholder: 'Escreva aqui a mensagem para o cliente…',
        theme: 'snow',
    });

    function atualizarPreview() {
        const body = document.getElementById('ec-preview-body');
        const html = quill.root.innerHTML;
        const vazio = quill.getText().trim() === '';
        body.innerHTML = vazio
            ? '<p class="ec-preview-placeholder">A mensagem aparecerá aqui conforme você digita…</p>'
            : html;
    }

    quill.on('text-change', atualizarPreview);

    // -------------------------------------------------- anexo
    const inputAnexo = document.getElementById('ec-anexo');
    const dzEmpty = document.getElementById('ec-dropzone-empty');
    const dzFile = document.getElementById('ec-dropzone-file');
    const previewAttach = document.getElementById('ec-preview-attach');

    function formatarTamanho(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
        return (bytes / 1024 / 1024).toFixed(1) + ' MB';
    }

    function setAnexo(file) {
        if (!file) return;
        if (file.type !== 'application/pdf') {
            alerta('Arquivo inválido', 'O anexo precisa ser um PDF.');
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            alerta('Arquivo grande demais', 'O PDF não pode passar de 10 MB.');
            return;
        }
        anexoFile = file;
        document.getElementById('ec-file-name').textContent = file.name;
        document.getElementById('ec-file-size').textContent = formatarTamanho(file.size);
        dzEmpty.classList.add('d-none');
        dzFile.classList.remove('d-none');

        document.getElementById('ec-preview-attach-name').textContent = file.name;
        previewAttach.classList.remove('d-none');
    }

    function limparAnexo() {
        anexoFile = null;
        inputAnexo.value = '';
        dzFile.classList.add('d-none');
        dzEmpty.classList.remove('d-none');
        previewAttach.classList.add('d-none');
    }

    inputAnexo.addEventListener('change', e => setAnexo(e.target.files[0]));
    document.getElementById('ec-file-remove').addEventListener('click', e => {
        e.preventDefault();
        e.stopPropagation();
        limparAnexo();
    });

    // drag & drop
    const dz = document.getElementById('ec-dropzone');
    ['dragenter', 'dragover'].forEach(ev => dz.addEventListener(ev, e => {
        e.preventDefault();
        dz.classList.add('is-dragover');
    }));
    ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => {
        e.preventDefault();
        dz.classList.remove('is-dragover');
    }));
    dz.addEventListener('drop', e => {
        if (e.dataTransfer.files.length) setAnexo(e.dataTransfer.files[0]);
    });

    // -------------------------------------------------- destinatários
    function parseDestinatarios() {
        const raw = document.getElementById('ec-destinatarios').value || '';
        return raw.split(/[,;\s]+/).map(s => s.trim()).filter(Boolean);
    }

    function emailValido(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // -------------------------------------------------- envio
    async function enviar() {
        if (cfg.emailValido === false) {
            alerta('Envio bloqueado', 'Seu e-mail de acesso não é @lkbrokers.com.');
            return;
        }

        const destinatarios = parseDestinatarios();
        if (destinatarios.length === 0) {
            alerta('Atenção', 'Informe ao menos um destinatário.');
            return;
        }
        const invalidos = destinatarios.filter(e => !emailValido(e));
        if (invalidos.length) {
            alerta('E-mail inválido', 'Verifique: ' + invalidos.join(', '));
            return;
        }

        const assunto = document.getElementById('ec-assunto').value.trim();
        if (!assunto) {
            alerta('Atenção', 'Informe o assunto do e-mail.');
            return;
        }

        if (quill.getText().trim() === '') {
            alerta('Atenção', 'Escreva a mensagem antes de enviar.');
            return;
        }

        if (!anexoFile) {
            alerta('Atenção', 'Anexe o PDF da cotação.');
            return;
        }

        const fd = new FormData();
        destinatarios.forEach(e => fd.append('destinatarios[]', e));
        fd.append('assunto', assunto);
        fd.append('mensagem', quill.root.innerHTML);
        fd.append('anexo', anexoFile);

        const btn = document.getElementById('ec-btn-enviar');
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Enviando…';

        try {
            const res = await fetch(cfg.urlEnviar, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                body: fd,
            });
            const data = await res.json();

            if (res.ok && data.success) {
                toast('success', 'Enviado!', data.message || 'Cotação enviada.');
                // limpa o formulário para um novo envio
                document.getElementById('ec-destinatarios').value = '';
                document.getElementById('ec-assunto').value = '';
                quill.setText('');
                limparAnexo();
                atualizarPreview();
            } else {
                const msg = data.message
                    || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Não foi possível enviar.');
                toast('error', 'Erro', msg);
            }
        } catch (e) {
            console.error('Erro ao enviar cotação:', e);
            toast('error', 'Erro', 'Falha de conexão ao enviar.');
        } finally {
            btn.disabled = cfg.emailValido === false;
            btn.innerHTML = original;
        }
    }

    document.getElementById('ec-btn-enviar').addEventListener('click', enviar);

    atualizarPreview();
})();
