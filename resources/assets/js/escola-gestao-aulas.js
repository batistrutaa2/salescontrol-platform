/**
 * Academia Comercial — Gestão de aulas: CRUD, upload de vídeo e materiais.
 */
'use strict';

(function () {
    const page = document.querySelector('.esc-aulas-gestao');
    if (!page) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const storeAulaUrl = page.dataset.storeAulaUrl;
    const aulaBase = page.dataset.aulaBase;       // .../escola/gestao/aulas
    const materialBase = page.dataset.materialBase; // .../escola/gestao/materiais
    const presignUrl = page.dataset.presignUrl;

    const modalAula = new bootstrap.Modal(document.getElementById('modal-aula'));
    const modalVideo = new bootstrap.Modal(document.getElementById('modal-video'));
    const modalMateriais = new bootstrap.Modal(document.getElementById('modal-materiais'));

    const toast = window.escolaToast;

    function jsonFetch(url, method, body) {
        return fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: body ? JSON.stringify(body) : undefined
        }).then(async r => ({ ok: r.ok, data: await r.json().catch(() => ({})) }));
    }

    // ----------------------------------------------------------- Aula CRUD
    const formAula = document.getElementById('form-aula');

    document.getElementById('btn-nova-aula')?.addEventListener('click', () => {
        formAula.reset();
        document.getElementById('aula-id').value = '';
        document.getElementById('aula-ativo').checked = true;
        document.querySelector('#modal-aula .esc-modal-title').textContent = 'Nova aula';
        modalAula.show();
    });

    page.querySelectorAll('.btn-editar-aula').forEach(btn => btn.addEventListener('click', () => {
        const tr = btn.closest('tr');
        formAula.reset();
        document.getElementById('aula-id').value = tr.dataset.id;
        document.getElementById('aula-titulo').value = tr.dataset.titulo || '';
        document.getElementById('aula-descricao').value = tr.dataset.descricao || '';
        document.getElementById('aula-ordem').value = tr.dataset.ordem || 0;
        document.getElementById('aula-ativo').checked = tr.dataset.ativo === '1';
        document.querySelector('#modal-aula .esc-modal-title').textContent = 'Editar aula';
        modalAula.show();
    }));

    formAula.addEventListener('submit', e => {
        e.preventDefault();
        const id = document.getElementById('aula-id').value;
        const payload = {
            titulo: document.getElementById('aula-titulo').value,
            descricao: document.getElementById('aula-descricao').value,
            ordem: parseInt(document.getElementById('aula-ordem').value || '0', 10),
            ativo: document.getElementById('aula-ativo').checked ? 1 : 0
        };
        const url = id ? `${aulaBase}/${id}` : storeAulaUrl;
        jsonFetch(url, id ? 'PUT' : 'POST', payload).then(({ ok, data }) => {
            if (ok && data.success) { toast('success', 'Pronto!', 'Aula salva com sucesso.'); setTimeout(() => location.reload(), 800); }
            else toast('error', 'Verifique os dados', data.message || 'Revise os campos e tente novamente.');
        }).catch(() => toast('error', 'Ops!', 'Erro ao salvar a aula.'));
    });

    page.querySelectorAll('.btn-excluir-aula').forEach(btn => btn.addEventListener('click', () => {
        const tr = btn.closest('tr');
        Swal.fire({
            title: 'Excluir aula?', text: 'O vídeo e os materiais serão removidos.', icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Excluir', cancelButtonText: 'Cancelar',
            customClass: { confirmButton: 'btn btn-danger me-2', cancelButton: 'btn btn-label-secondary' }, buttonsStyling: false
        }).then(res => {
            if (!res.isConfirmed) return;
            fetch(`${aulaBase}/${tr.dataset.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } })
                .then(r => r.json()).then(d => {
                    if (d.success) { tr.remove(); toast('success', 'Pronto!', 'Aula excluída com sucesso.'); }
                    else toast('error', 'Ops!', 'Não foi possível excluir a aula.');
                }).catch(() => toast('error', 'Ops!', 'Não foi possível excluir a aula.'));
        });
    }));

    // -------------------------------------------------- Upload de vídeo (S3)
    let aulaVideoTr = null;

    page.querySelectorAll('.btn-video-aula').forEach(btn => btn.addEventListener('click', () => {
        aulaVideoTr = btn.closest('tr');
        document.getElementById('video-aula-id').value = aulaVideoTr.dataset.id;
        document.getElementById('video-file').value = '';
        document.getElementById('video-progress-wrap').classList.add('d-none');
        document.getElementById('video-progress-bar').style.width = '0%';
        document.getElementById('video-progress-text').textContent = '0%';
        modalVideo.show();
    }));

    function lerDuracao(file) {
        return new Promise(resolve => {
            try {
                const v = document.createElement('video');
                v.preload = 'metadata';
                v.onloadedmetadata = () => { URL.revokeObjectURL(v.src); resolve(Math.floor(v.duration) || 0); };
                v.onerror = () => resolve(0);
                v.src = URL.createObjectURL(file);
            } catch (e) { resolve(0); }
        });
    }

    function putParaS3(url, headers, file, onProgress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('PUT', url, true);
            // Content-Type bate com o que foi assinado no backend
            xhr.setRequestHeader('Content-Type', file.type);
            // Aplica headers assinados retornados (exceto Host, que o browser controla)
            Object.entries(headers || {}).forEach(([k, v]) => {
                if (k.toLowerCase() === 'host') return;
                xhr.setRequestHeader(k, Array.isArray(v) ? v.join(',') : v);
            });
            xhr.upload.onprogress = e => { if (e.lengthComputable) onProgress(e.loaded / e.total); };
            xhr.onload = () => (xhr.status >= 200 && xhr.status < 300) ? resolve() : reject(new Error('S3 ' + xhr.status));
            xhr.onerror = () => reject(new Error('Falha de rede no upload'));
            xhr.send(file);
        });
    }

    document.getElementById('btn-enviar-video')?.addEventListener('click', async () => {
        const fileInput = document.getElementById('video-file');
        const file = fileInput.files[0];
        const aulaId = document.getElementById('video-aula-id').value;
        if (!file) { toast('warning', 'Atenção', 'Selecione um arquivo de vídeo.'); return; }

        const wrap = document.getElementById('video-progress-wrap');
        const bar = document.getElementById('video-progress-bar');
        const txt = document.getElementById('video-progress-text');
        wrap.classList.remove('d-none');
        const btn = document.getElementById('btn-enviar-video');
        btn.disabled = true;

        try {
            const duracao = await lerDuracao(file);

            const pres = await jsonFetch(presignUrl, 'POST', {
                aula_id: parseInt(aulaId, 10),
                filename: file.name,
                content_type: file.type,
                size: file.size
            });
            if (!pres.ok || !pres.data.success) throw new Error(pres.data.message || 'Falha ao gerar URL de upload');

            await putParaS3(pres.data.url, pres.data.headers, file, frac => {
                const pct = Math.round(frac * 100);
                bar.style.width = pct + '%';
                txt.textContent = pct + '%';
            });

            const conf = await jsonFetch(`${aulaBase}/${aulaId}/video/confirmar`, 'POST', {
                key: pres.data.key,
                nome_original: file.name,
                mime: file.type,
                tamanho: file.size,
                duracao_segundos: duracao
            });
            if (!conf.ok || !conf.data.success) throw new Error(conf.data.message || 'Falha ao confirmar o vídeo');

            toast('success', 'Vídeo enviado!', 'O vídeo foi salvo com sucesso.');
            setTimeout(() => location.reload(), 900);
        } catch (err) {
            toast('error', 'Falha no envio', err.message || 'Não foi possível enviar o vídeo.');
            btn.disabled = false;
        }
    });

    // ----------------------------------------------------------- Materiais
    function renderMateriais(lista) {
        const ul = document.getElementById('materiais-lista');
        ul.innerHTML = '';
        if (!lista || !lista.length) {
            ul.innerHTML = '<li class="text-muted small">Nenhum material anexado.</li>';
            return;
        }
        lista.forEach(m => {
            const li = document.createElement('li');
            li.innerHTML = `<span>${m.titulo}</span>
                <button class="esc-btn esc-btn-sm esc-btn-danger btn-del-material" data-id="${m.id}">Remover</button>`;
            ul.appendChild(li);
        });
        ul.querySelectorAll('.btn-del-material').forEach(b => b.addEventListener('click', () => {
            fetch(`${materialBase}/${b.dataset.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } })
                .then(r => r.json()).then(d => {
                    if (d.success) { toast('success', 'Pronto!', 'Material removido.'); setTimeout(() => location.reload(), 700); }
                    else toast('error', 'Ops!', 'Não foi possível remover o material.');
                });
        }));
    }

    page.querySelectorAll('.btn-materiais-aula').forEach(btn => btn.addEventListener('click', () => {
        const tr = btn.closest('tr');
        document.getElementById('material-aula-id').value = tr.dataset.id;
        document.getElementById('material-file').value = '';
        let lista = [];
        try { lista = JSON.parse(tr.dataset.materiais || '[]'); } catch (e) {}
        renderMateriais(lista);
        modalMateriais.show();
    }));

    document.getElementById('btn-add-material')?.addEventListener('click', () => {
        const aulaId = document.getElementById('material-aula-id').value;
        const file = document.getElementById('material-file').files[0];
        if (!file) { toast('warning', 'Atenção', 'Selecione um PDF.'); return; }
        const fd = new FormData();
        fd.append('arquivo', file);
        fetch(`${aulaBase}/${aulaId}/materiais`, {
            method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: fd
        }).then(async r => {
            const d = await r.json().catch(() => ({}));
            if (r.ok && d.success) { toast('success', 'Pronto!', 'Material enviado com sucesso.'); setTimeout(() => location.reload(), 800); }
            else toast('error', 'Ops!', d.message || 'Apenas PDF, até 20 MB.');
        }).catch(() => toast('error', 'Ops!', 'Erro ao enviar material.'));
    });
})();
