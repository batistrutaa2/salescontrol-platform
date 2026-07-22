'use strict';

(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let tabela = null;

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2600,
        timerProgressBar: true,
    });

    const escapeHtml = (s) =>
        String(s ?? '').replace(/[&<>"']/g, (c) =>
            ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    // ----------------------------------------------------------------
    // DataTable (server-side)
    // ----------------------------------------------------------------
    function initTabela() {
        if ($.fn.dataTable && $.fn.dataTable.ext) {
            $.fn.dataTable.ext.errMode = (settings, techNote, message) => console.warn('[DataTables]', message);
        }

        tabela = $('#tabela-credenciais').DataTable({
            processing: true,
            serverSide: true,
            ajax: function (data, callback) {
                $.ajax({
                    url: '/back-office/credenciais/data',
                    method: 'GET',
                    data: {
                        draw: data.draw,
                        columns: data.columns,
                        order: data.order,
                        start: data.start,
                        length: data.length,
                        search: data.search,
                        operadora_id: document.getElementById('filtroOperadora').value,
                        status: document.getElementById('filtroStatus').value,
                    },
                    success: callback,
                    error: () => Toast.fire({ icon: 'error', title: 'Erro ao carregar dados' }),
                });
            },
            order: [[2, 'asc']],
            columns: [
                {
                    data: 'operadora_nome', title: 'Operadora', orderable: false,
                    render: (v) => {
                        if (!v || v === '—') return '<span class="cred-muted">—</span>';
                        return `<span class="cred-chip cred-chip-${corOperadora(v)}">${escapeHtml(v)}</span>`;
                    },
                },
                { data: 'tipo', title: 'Tipo', orderable: false, render: (v) => v ? `<span class="cred-tipo">${escapeHtml(v)}</span>` : '<span class="cred-muted">—</span>' },
                { data: 'nome', title: 'Nome', render: (v) => `<span class="cred-nome">${escapeHtml(v)}</span>` },
                { data: 'login', title: 'Login', render: (v) => v ? `<span class="cred-code">${escapeHtml(v)}</span>` : '<span class="cred-muted">—</span>' },
                {
                    data: 'senha', title: 'Senha', orderable: false,
                    render: (v) => {
                        if (!v) return '<span class="cred-muted">—</span>';
                        const safe = escapeHtml(v);
                        return `<div class="cred-secret">
                                    <span class="cred-code senha-mask" data-senha="${safe}">••••••••</span>
                                    <button type="button" class="cred-icon-btn toggle-senha" title="Mostrar / ocultar" aria-label="Mostrar ou ocultar senha"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg></button>
                                    <button type="button" class="cred-icon-btn copiar-senha" data-senha="${safe}" title="Copiar" aria-label="Copiar senha"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button>
                                </div>`;
                    },
                },
                { data: 'observacao', title: 'Observação', orderable: false, render: (v) => v ? `<span class="cred-obs" title="${escapeHtml(v)}">${escapeHtml(v)}</span>` : '<span class="cred-muted">—</span>' },
                {
                    data: null, title: 'Última edição', orderable: false,
                    render: (_, type, row) =>
                        `<div class="cred-edit"><span class="cred-edit-when">${escapeHtml(row.atualizado_em)}</span><span class="cred-edit-who">${escapeHtml(row.atualizado_por_nome)}</span></div>`,
                },
                {
                    data: 'status', title: 'Status', orderable: false,
                    render: (v) => v === 'Y'
                        ? '<span class="cred-status cred-status-on"><span class="cred-dot"></span>Ativo</span>'
                        : '<span class="cred-status cred-status-off"><span class="cred-dot"></span>Inativo</span>',
                },
                {
                    data: null, title: 'Ações', orderable: false, className: 'text-end',
                    render: (_, type, row) =>
                        `<div class="cred-actions">
                            <button type="button" class="cred-icon-btn editar" data-id="${row.id}" title="Editar" aria-label="Editar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
                            <button type="button" class="cred-icon-btn ver-historico" data-id="${row.id}" title="Histórico" aria-label="Histórico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><path d="M12 7v5l4 2"/></svg></button>
                            <button type="button" class="cred-icon-btn cred-icon-danger excluir" data-id="${row.id}" title="Excluir" aria-label="Excluir"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
                        </div>`,
                },
            ],
        });
    }

    // Cor estável do chip por nome de operadora (paleta do design system).
    function corOperadora(nome) {
        const paleta = ['primary', 'info', 'success', 'warning', 'danger'];
        let h = 0;
        for (let i = 0; i < nome.length; i++) h = (h * 31 + nome.charCodeAt(i)) >>> 0;
        return paleta[h % paleta.length];
    }

    // ----------------------------------------------------------------
    // Modal helpers
    // ----------------------------------------------------------------
    const credencialModal = () => bootstrap.Modal.getOrCreateInstance(document.getElementById('credencialModal'));
    const historicoModal = () => bootstrap.Modal.getOrCreateInstance(document.getElementById('historicoModal'));

    // ---- Repetidor de acessos (login/senha) ----
    const acessosBox = () => document.getElementById('cred-acessos');

    function atualizarAcessos() {
        const linhas = acessosBox().querySelectorAll('.cred-acesso');
        linhas.forEach((l) => {
            const rm = l.querySelector('.cred-remove-acesso');
            if (rm) rm.disabled = linhas.length <= 1;
        });
        const count = document.getElementById('cred-acessos-count');
        if (count) count.textContent = linhas.length > 1 ? linhas.length : '';
        const editando = !!document.getElementById('credencial_id').value;
        const btn = document.getElementById('cred-salvar-btn');
        if (btn) btn.textContent = editando ? 'Salvar' : (linhas.length > 1 ? `Salvar ${linhas.length} acessos` : 'Salvar acesso');
    }

    function adicionarAcesso(dados = null, foco = false) {
        const tpl = document.getElementById('cred-acesso-tpl');
        const node = tpl.content.firstElementChild.cloneNode(true);
        if (dados) {
            node.querySelector('.acesso-nome').value = dados.nome ?? '';
            node.querySelector('.acesso-login').value = dados.login ?? '';
            node.querySelector('.acesso-senha').value = dados.senha ?? '';
        }
        acessosBox().appendChild(node);
        atualizarAcessos();
        if (foco) node.querySelector('.acesso-nome').focus();
    }

    function coletarAcessos() {
        return Array.from(acessosBox().querySelectorAll('.cred-acesso')).map((l) => ({
            nome: l.querySelector('.acesso-nome').value.trim(),
            login: l.querySelector('.acesso-login').value.trim() || null,
            senha: l.querySelector('.acesso-senha').value || null,
        }));
    }

    function resetForm() {
        document.getElementById('formCredencial').reset();
        document.getElementById('credencial_id').value = '';
        $('#operadora_id').val('').trigger('change');
        acessosBox().innerHTML = '';
    }

    function abrirNova() {
        resetForm();
        document.getElementById('credencialModalLabel').textContent = 'Novo acesso';
        document.getElementById('credencialModalSub').textContent = 'Cadastre um ou vários logins do mesmo portal';
        document.getElementById('cred-add-acesso').style.display = '';
        adicionarAcesso();
        credencialModal().show();
    }

    async function abrirEdicao(id) {
        try {
            const resp = await fetch(`/back-office/credenciais/${id}`, { headers: { Accept: 'application/json' } });
            if (!resp.ok) throw new Error();
            const c = await resp.json();
            resetForm();
            document.getElementById('credencialModalLabel').textContent = 'Editar acesso';
            document.getElementById('credencialModalSub').textContent = 'Altere os dados deste acesso';
            document.getElementById('credencial_id').value = c.id;
            document.getElementById('tipo').value = c.tipo ?? '';
            document.getElementById('observacao').value = c.observacao ?? '';
            document.getElementById('status').value = c.status ?? 'Y';
            $('#operadora_id').val(c.operadora_id ? String(c.operadora_id) : '').trigger('change');
            document.getElementById('cred-add-acesso').style.display = 'none';
            adicionarAcesso({ nome: c.nome, login: c.login, senha: c.senha });
            credencialModal().show();
        } catch {
            Toast.fire({ icon: 'error', title: 'Não foi possível carregar a credencial' });
        }
    }

    async function salvar(e) {
        e.preventDefault();
        const id = document.getElementById('credencial_id').value;
        const acessos = coletarAcessos();

        if (acessos.some((a) => !a.nome)) {
            Toast.fire({ icon: 'error', title: 'Informe o nome/rótulo de cada acesso' });
            return;
        }

        const contexto = {
            operadora_id: document.getElementById('operadora_id').value || null,
            tipo: document.getElementById('tipo').value || null,
            observacao: document.getElementById('observacao').value || null,
            status: document.getElementById('status').value,
        };

        const url = id ? `/back-office/credenciais/${id}` : '/back-office/credenciais/lote';
        const method = id ? 'PUT' : 'POST';
        const payload = id
            ? { ...contexto, nome: acessos[0].nome, login: acessos[0].login, senha: acessos[0].senha }
            : { ...contexto, acessos };

        try {
            const resp = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify(payload),
            });
            const data = await resp.json();
            if (!resp.ok) {
                const msg = data?.errors ? Object.values(data.errors).flat()[0] : (data?.message ?? 'Erro ao salvar');
                Toast.fire({ icon: 'error', title: msg });
                return;
            }
            credencialModal().hide();
            tabela.ajax.reload(null, false);
            Toast.fire({ icon: 'success', title: data.message ?? 'Salvo!' });
        } catch {
            Toast.fire({ icon: 'error', title: 'Erro de comunicação' });
        }
    }

    async function excluir(id) {
        const r = await Swal.fire({
            title: 'Excluir credencial?',
            text: 'Esta ação não pode ser desfeita.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
            customClass: { confirmButton: 'btn btn-danger me-2', cancelButton: 'btn btn-secondary' },
            buttonsStyling: false,
        });
        if (!r.isConfirmed) return;

        try {
            const resp = await fetch(`/back-office/credenciais/${id}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
            });
            const data = await resp.json();
            if (!resp.ok) throw new Error();
            tabela.ajax.reload(null, false);
            Toast.fire({ icon: 'success', title: data.message ?? 'Excluída!' });
        } catch {
            Toast.fire({ icon: 'error', title: 'Erro ao excluir' });
        }
    }

    async function verHistorico(id) {
        try {
            const resp = await fetch(`/back-office/credenciais/${id}/historico`, { headers: { Accept: 'application/json' } });
            if (!resp.ok) throw new Error();
            const data = await resp.json();
            document.getElementById('historicoNome').textContent = data.credencial.nome;

            const acaoMeta = {
                CRIACAO: { label: 'Criação', cls: 'cred-tl-create' },
                EDICAO: { label: 'Edição', cls: 'cred-tl-edit' },
                EXCLUSAO: { label: 'Exclusão', cls: 'cred-tl-delete' },
            };

            const itens = data.historico.map((h) => {
                const meta = acaoMeta[h.acao] ?? { label: h.acao, cls: '' };
                let detalhe = '';
                if (h.acao === 'EDICAO' && h.campo) {
                    detalhe = `<div class="cred-tl-change">
                                    <span class="cred-tl-field">${escapeHtml(h.campo)}</span>
                                    <span class="cred-tl-from">${h.valor_anterior ? escapeHtml(h.valor_anterior) : '—'}</span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                    <span class="cred-tl-to">${h.valor_novo ? escapeHtml(h.valor_novo) : '—'}</span>
                                </div>`;
                } else if (h.acao === 'EXCLUSAO' && h.valor_anterior) {
                    detalhe = `<div class="cred-tl-change"><span class="cred-tl-from">${escapeHtml(h.valor_anterior)}</span></div>`;
                }
                return `<div class="cred-tl-item ${meta.cls}">
                            <div class="cred-tl-marker"></div>
                            <div class="cred-tl-body">
                                <div class="cred-tl-head">
                                    <span class="cred-tl-acao">${escapeHtml(meta.label)}</span>
                                    <span class="cred-tl-when">${escapeHtml(h.data)}</span>
                                </div>
                                <div class="cred-tl-who">por ${escapeHtml(h.usuario)}</div>
                                ${detalhe}
                            </div>
                        </div>`;
            }).join('');

            document.getElementById('historicoBody').innerHTML =
                itens || '<p class="cred-muted text-center mb-0">Sem histórico.</p>';
            historicoModal().show();
        } catch {
            Toast.fire({ icon: 'error', title: 'Não foi possível carregar o histórico' });
        }
    }

    function copiarSenha(senha) {
        navigator.clipboard?.writeText(senha).then(
            () => Toast.fire({ icon: 'success', title: 'Senha copiada' }),
            () => Toast.fire({ icon: 'error', title: 'Não foi possível copiar' }),
        );
    }

    // ----------------------------------------------------------------
    // Importação por Excel (operadora + mapeamento de colunas)
    // ----------------------------------------------------------------
    const importarModal = () => bootstrap.Modal.getOrCreateInstance(document.getElementById('importarModal'));

    function abrirImport() {
        document.getElementById('import_operadora').value = '';
        document.getElementById('import_arquivo').value = '';
        document.getElementById('import_cabecalho').checked = true;
        document.getElementById('importMapeamento').classList.add('d-none');
        document.getElementById('btnConfirmarImport').classList.add('d-none');
        document.getElementById('importCamposRow').innerHTML = '';
        importarModal().show();
    }

    async function previewImport() {
        const operadora = document.getElementById('import_operadora').value;
        const arquivo = document.getElementById('import_arquivo').files[0];

        if (!operadora) return Toast.fire({ icon: 'warning', title: 'Selecione a operadora' });
        if (!arquivo) return Toast.fire({ icon: 'warning', title: 'Selecione o arquivo' });

        const fd = new FormData();
        fd.append('arquivo', arquivo);
        fd.append('tem_cabecalho', document.getElementById('import_cabecalho').checked ? '1' : '0');

        try {
            const resp = await fetch('/back-office/credenciais/importar/preview', {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: fd,
            });
            const data = await resp.json();
            if (!resp.ok) {
                Toast.fire({ icon: 'error', title: data?.message ?? 'Falha ao ler a planilha' });
                return;
            }
            renderMapeamento(data);
        } catch {
            Toast.fire({ icon: 'error', title: 'Erro de comunicação' });
        }
    }

    function renderMapeamento(data) {
        const obrigatorios = ['nome'];
        const opcoes = (selecionado) =>
            '<option value="">— Ignorar —</option>' +
            data.colunas
                .map((c) => `<option value="${c.index}" ${String(selecionado) === String(c.index) ? 'selected' : ''}>${escapeHtml(c.letra)} — ${escapeHtml(c.label)}</option>`)
                .join('');

        document.getElementById('importCamposRow').innerHTML = Object.entries(data.campos)
            .map(([campo, label]) => {
                const req = obrigatorios.includes(campo) ? ' <span class="text-danger">*</span>' : '';
                const palpite = data.palpite?.[campo] ?? '';
                return `<div class="col-md-4">
                            <label class="form-label">${escapeHtml(label)}${req}</label>
                            <select class="form-select map-campo" data-campo="${campo}">${opcoes(palpite)}</select>
                        </div>`;
            })
            .join('');

        // Cabeçalho da amostra
        const thead = `<tr>${data.colunas.map((c) => `<th>${escapeHtml(c.letra)} — ${escapeHtml(c.label)}</th>`).join('')}</tr>`;
        const tbody = data.amostra
            .map((linha) => `<tr>${linha.map((v) => `<td>${escapeHtml(v) || '<span class="text-muted">—</span>'}</td>`).join('')}</tr>`)
            .join('');
        document.querySelector('#importAmostraTabela thead').innerHTML = thead;
        document.querySelector('#importAmostraTabela tbody').innerHTML = tbody || '<tr><td class="text-muted">Sem linhas de amostra.</td></tr>';

        document.getElementById('importTotalLinhas').textContent = `${data.total_linhas} linha(s)`;
        document.getElementById('importMapeamento').classList.remove('d-none');
        document.getElementById('btnConfirmarImport').classList.remove('d-none');
    }

    async function confirmarImport() {
        const operadora = document.getElementById('import_operadora').value;
        const arquivo = document.getElementById('import_arquivo').files[0];
        if (!operadora || !arquivo) return;

        const mapping = {};
        document.querySelectorAll('.map-campo').forEach((sel) => {
            mapping[sel.dataset.campo] = sel.value;
        });
        if (!mapping.nome) return Toast.fire({ icon: 'warning', title: 'Mapeie a coluna do Nome' });

        const fd = new FormData();
        fd.append('operadora_id', operadora);
        fd.append('arquivo', arquivo);
        fd.append('tem_cabecalho', document.getElementById('import_cabecalho').checked ? '1' : '0');
        Object.entries(mapping).forEach(([campo, idx]) => fd.append(`mapping[${campo}]`, idx));

        const btn = document.getElementById('btnConfirmarImport');
        btn.disabled = true;
        try {
            const resp = await fetch('/back-office/credenciais/importar', {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: fd,
            });
            const data = await resp.json();
            if (!resp.ok) {
                const msg = data?.errors ? Object.values(data.errors).flat()[0] : (data?.message ?? 'Falha na importação');
                Toast.fire({ icon: 'error', title: msg });
                return;
            }
            importarModal().hide();
            tabela.ajax.reload(null, false);
            Swal.fire({ icon: 'success', title: 'Importação concluída', text: data.message, customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false });
        } catch {
            Toast.fire({ icon: 'error', title: 'Erro de comunicação' });
        } finally {
            btn.disabled = false;
        }
    }

    // ----------------------------------------------------------------
    // Eventos
    // ----------------------------------------------------------------
    $(function () {
        initTabela();

        if ($.fn.select2) {
            $('.select2-operadora').select2({ dropdownParent: $('#credencialModal'), width: '100%' });
        }

        document.getElementById('btnNovaCredencial').addEventListener('click', abrirNova);
        document.getElementById('formCredencial').addEventListener('submit', salvar);

        // Repetidor de acessos: adicionar, remover e mostrar/ocultar senha.
        document.getElementById('cred-add-acesso').addEventListener('click', () => adicionarAcesso(null, true));
        acessosBox().addEventListener('click', (e) => {
            const rm = e.target.closest('.cred-remove-acesso');
            if (rm && !rm.disabled) { rm.closest('.cred-acesso').remove(); atualizarAcessos(); return; }
            const eye = e.target.closest('.cred-eye');
            if (eye) {
                const inp = eye.closest('.cred-senha-wrap').querySelector('.acesso-senha');
                inp.type = inp.type === 'password' ? 'text' : 'password';
                eye.classList.toggle('is-on', inp.type === 'text');
            }
        });
        document.getElementById('btnImportar').addEventListener('click', abrirImport);
        document.getElementById('btnPreview').addEventListener('click', previewImport);
        document.getElementById('btnConfirmarImport').addEventListener('click', confirmarImport);
        document.getElementById('filtroOperadora').addEventListener('change', () => tabela.ajax.reload());
        document.getElementById('filtroStatus').addEventListener('change', () => tabela.ajax.reload());

        const tbody = document.querySelector('#tabela-credenciais');
        tbody.addEventListener('click', (e) => {
            const btn = e.target.closest('button');
            if (!btn) return;
            if (btn.classList.contains('editar')) abrirEdicao(btn.dataset.id);
            else if (btn.classList.contains('excluir')) excluir(btn.dataset.id);
            else if (btn.classList.contains('ver-historico')) verHistorico(btn.dataset.id);
            else if (btn.classList.contains('copiar-senha')) copiarSenha(btn.dataset.senha);
            else if (btn.classList.contains('toggle-senha')) {
                const span = btn.closest('.cred-secret').querySelector('.senha-mask');
                const revelado = span.textContent !== '••••••••';
                span.textContent = revelado ? '••••••••' : span.dataset.senha;
                btn.classList.toggle('is-on', !revelado);
            }
        });
    });
})();
