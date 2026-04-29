'use strict';

(function () {
    const cfg = window.lkbLeadNovo || {};

    const form = document.getElementById('lkb-form-lead-novo');
    const cpfCnpjInput = document.getElementById('lkb-cpf-cnpj');
    const tipoSelect = document.getElementById('lkb-cliente-tipo');
    const lemitBtn = document.getElementById('lkb-btn-lemit');
    const telefoneInput = document.getElementById('lkb-telefone');

    const limpar = (v) => String(v || '').replace(/\D+/g, '');

    // ---------- Máscara CPF/CNPJ (dinâmica por select PF/PJ) ----------
    let cpfCnpjCleave = null;

    function buildCpfCnpjOpts(value) {
        const digits = limpar(value);
        const tipo = (tipoSelect && tipoSelect.value) || (digits.length > 11 ? 'PJ' : 'PF');
        return tipo === 'PJ'
            ? { delimiters: ['.', '.', '/', '-'], blocks: [2, 3, 3, 4, 2], numericOnly: true }
            : { delimiters: ['.', '.', '-'],      blocks: [3, 3, 3, 2],    numericOnly: true };
    }

    function applyCpfCnpjMask({ keepRaw = true } = {}) {
        if (!cpfCnpjInput || typeof Cleave === 'undefined') return;
        const raw = limpar(cpfCnpjInput.value);
        if (cpfCnpjCleave) { cpfCnpjCleave.destroy(); cpfCnpjCleave = null; }
        cpfCnpjCleave = new Cleave(cpfCnpjInput, buildCpfCnpjOpts(raw));
        if (keepRaw) cpfCnpjCleave.setRawValue(raw);
    }

    if (cpfCnpjInput) applyCpfCnpjMask({ keepRaw: false });
    if (tipoSelect) {
        tipoSelect.addEventListener('change', () => applyCpfCnpjMask({ keepRaw: true }));
    }

    // ---------- Máscara Telefone (10/11 dígitos BR, opcional +55) ----------
    let telefoneCleave = null;

    function buildTelefoneOpts(value) {
        const digits = limpar(value);
        if (digits.startsWith('55') && digits.length > 11) {
            return { delimiters: [' ', ' (', ') ', '-'], blocks: [2, 2, 5, 4], numericOnly: true };
        }
        return digits.length > 10
            ? { delimiters: ['(', ') ', '-'], blocks: [0, 2, 5, 4], numericOnly: true }
            : { delimiters: ['(', ') ', '-'], blocks: [0, 2, 4, 4], numericOnly: true };
    }

    function applyTelefoneMask({ keepRaw = true } = {}) {
        if (!telefoneInput || typeof Cleave === 'undefined') return;
        const raw = limpar(telefoneInput.value);
        if (telefoneCleave) { telefoneCleave.destroy(); telefoneCleave = null; }
        telefoneCleave = new Cleave(telefoneInput, buildTelefoneOpts(raw));
        if (keepRaw) telefoneCleave.setRawValue(raw);
    }

    if (telefoneInput) {
        applyTelefoneMask({ keepRaw: false });
        telefoneInput.addEventListener('input', () => applyTelefoneMask({ keepRaw: true }));
    }

    // ---------- Modal Lemit ----------
    const nomeInput     = document.getElementById('lkb-nome');
    const emailInput    = document.getElementById('lkb-email');

    const modalEl       = document.getElementById('lkbLemitModal');
    const modalDocEl    = document.getElementById('lkb-lemit-modal-doc');
    const stateLoading  = document.getElementById('lkb-lemit-loading');
    const stateError    = document.getElementById('lkb-lemit-error');
    const stateErrorMsg = document.getElementById('lkb-lemit-error-msg');
    const stateResult   = document.getElementById('lkb-lemit-result');
    const fieldsIdent   = document.getElementById('lkb-lemit-fields-identificacao');
    const listTel       = document.getElementById('lkb-lemit-list-telefones');
    const countTel      = document.getElementById('lkb-lemit-count-telefones');
    const emptyTel      = document.getElementById('lkb-lemit-empty-telefones');
    const listEm        = document.getElementById('lkb-lemit-list-emails');
    const countEm       = document.getElementById('lkb-lemit-count-emails');
    const emptyEm       = document.getElementById('lkb-lemit-empty-emails');
    const fillAllBtn    = document.getElementById('lkb-lemit-fill-all');

    let bsModal = null;
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bsModal = new bootstrap.Modal(modalEl);
    }

    let lastLemitData = null;

    function setModalState(state) {
        [stateLoading, stateError, stateResult].forEach(el => el && el.classList.add('d-none'));
        const target = { loading: stateLoading, error: stateError, result: stateResult }[state];
        if (target) target.classList.remove('d-none');
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c =>
            ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function fmtDoc(digits) {
        const d = limpar(digits);
        if (d.length === 11) return `${d.slice(0,3)}.${d.slice(3,6)}.${d.slice(6,9)}-${d.slice(9)}`;
        if (d.length === 14) return `${d.slice(0,2)}.${d.slice(2,5)}.${d.slice(5,8)}/${d.slice(8,12)}-${d.slice(12)}`;
        return d;
    }

    function fmtTelefone(ddd, numero) {
        const dd = limpar(ddd);
        const n = limpar(numero);
        if (!dd && !n) return '';
        if (n.length === 9) return `(${dd}) ${n.slice(0,5)}-${n.slice(5)}`;
        if (n.length === 8) return `(${dd}) ${n.slice(0,4)}-${n.slice(4)}`;
        return `(${dd}) ${n}`;
    }

    function fmtData(iso) {
        if (!iso) return '';
        const m = String(iso).match(/^(\d{4})-(\d{2})-(\d{2})/);
        return m ? `${m[3]}/${m[2]}/${m[1]}` : iso;
    }

    function fieldHtml(label, value, isMono) {
        if (!value) return '';
        return `<div class="lkb-lemit-field">
            <span class="lkb-lemit-field-label">${escapeHtml(label)}</span>
            <span class="lkb-lemit-field-value${isMono ? ' is-mono' : ''}">${escapeHtml(value)}</span>
        </div>`;
    }

    const useIconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';

    function makeUseBtn(label, payload) {
        const enc = encodeURIComponent(JSON.stringify(payload));
        return `<button type="button" class="lkb-lemit-use-btn" data-lkb-use="${enc}">${useIconSvg}${escapeHtml(label)}</button>`;
    }

    function renderIdentificacao(data, tipo) {
        const parts = [];
        if (tipo === 'PJ') {
            const e = data.empresa || {};
            parts.push(fieldHtml('Razão social', e.razao_social));
            parts.push(fieldHtml('Nome fantasia', e.nome_fantasia));
            parts.push(fieldHtml('CNPJ', fmtDoc(e.cnpj), true));
            parts.push(fieldHtml('Situação', e.situacao));
            parts.push(fieldHtml('Fundação', fmtData(e.data_fundacao)));
            parts.push(fieldHtml('Tipo', e.tipo));
            const useNomeBtn = e.razao_social || e.nome_fantasia
                ? `<div class="lkb-lemit-field" style="grid-column: 1 / -1;">
                    ${makeUseBtn('Usar como nome', { campo: 'nome', valor: e.razao_social || e.nome_fantasia })}
                  </div>`
                : '';
            fieldsIdent.innerHTML = parts.filter(Boolean).join('') + useNomeBtn;
        } else {
            const p = data.pessoa || {};
            parts.push(fieldHtml('Nome', p.nome));
            parts.push(fieldHtml('CPF', fmtDoc(p.cpf), true));
            parts.push(fieldHtml('Nascimento', fmtData(p.data_nascimento)));
            parts.push(fieldHtml('Sexo', p.sexo));
            parts.push(fieldHtml('Mãe', p.nome_mae));
            parts.push(fieldHtml('Situação CPF', p.situacao_cpf));
            const useNomeBtn = p.nome
                ? `<div class="lkb-lemit-field" style="grid-column: 1 / -1;">
                    ${makeUseBtn('Usar como nome', { campo: 'nome', valor: p.nome })}
                  </div>`
                : '';
            fieldsIdent.innerHTML = parts.filter(Boolean).join('') + useNomeBtn;
        }
    }

    function renderTelefones(data, tipo) {
        const root = (tipo === 'PJ' ? data.empresa : data.pessoa) || {};
        const cels = (root.celulares || []).map(c => ({ ...c, _tipo: 'cel' }));
        const fix = (root.fixos    || []).map(c => ({ ...c, _tipo: 'fix' }));
        const all = [...cels, ...fix];
        countTel.textContent = all.length;
        listTel.innerHTML = '';
        emptyTel.classList.toggle('d-none', all.length > 0);

        all.forEach(t => {
            const formatted = fmtTelefone(t.ddd, t.numero);
            if (!formatted) return;
            const badges = [];
            if (t.ranking) badges.push(`<span class="lkb-lemit-badge is-rank">#${t.ranking}</span>`);
            if (t._tipo === 'cel' && t.whatsapp) badges.push('<span class="lkb-lemit-badge is-whatsapp">WhatsApp</span>');
            if (t._tipo === 'fix') badges.push('<span class="lkb-lemit-badge is-fixo">Fixo</span>');

            const li = document.createElement('li');
            li.className = 'lkb-lemit-list-item';
            li.innerHTML = `
                <span class="lkb-lemit-list-value">${escapeHtml(formatted)}</span>
                <span class="lkb-lemit-list-badges">${badges.join('')}</span>
                ${makeUseBtn('Usar', { campo: 'telefone', valor: `${limpar(t.ddd)}${limpar(t.numero)}` })}
            `;
            listTel.appendChild(li);
        });
    }

    function renderEmails(data, tipo) {
        const root = (tipo === 'PJ' ? data.empresa : data.pessoa) || {};
        const emails = (root.emails || []).filter(e => e && e.email);
        countEm.textContent = emails.length;
        listEm.innerHTML = '';
        emptyEm.classList.toggle('d-none', emails.length > 0);

        emails.forEach(em => {
            const badges = em.ranking ? `<span class="lkb-lemit-badge is-rank">#${em.ranking}</span>` : '';
            const li = document.createElement('li');
            li.className = 'lkb-lemit-list-item';
            li.innerHTML = `
                <span class="lkb-lemit-list-value">${escapeHtml(em.email)}</span>
                <span class="lkb-lemit-list-badges">${badges}</span>
                ${makeUseBtn('Usar', { campo: 'email', valor: em.email })}
            `;
            listEm.appendChild(li);
        });
    }

    function applyValueToField(campo, valor) {
        if (campo === 'nome' && nomeInput) {
            nomeInput.value = valor;
        } else if (campo === 'telefone' && telefoneInput) {
            telefoneInput.value = valor;
            applyTelefoneMask({ keepRaw: true });
        } else if (campo === 'email' && emailInput) {
            emailInput.value = valor;
        }
    }

    function markBtnApplied(btn) {
        btn.classList.add('is-applied');
        btn.innerHTML = useIconSvg + 'Aplicado';
    }

    if (modalEl) {
        modalEl.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-lkb-use]');
            if (!btn) return;
            try {
                const payload = JSON.parse(decodeURIComponent(btn.getAttribute('data-lkb-use')));
                applyValueToField(payload.campo, payload.valor);
                modalEl.querySelectorAll(`[data-lkb-use]`).forEach(b => {
                    try {
                        const p = JSON.parse(decodeURIComponent(b.getAttribute('data-lkb-use')));
                        if (p.campo === payload.campo) {
                            b.classList.remove('is-applied');
                            b.innerHTML = useIconSvg + (b.dataset.originalLabel || (p.campo === 'nome' ? 'Usar como nome' : 'Usar'));
                        }
                    } catch (_) {}
                });
                markBtnApplied(btn);
            } catch (_) {}
        });

        modalEl.addEventListener('hidden.bs.modal', () => {
            lastLemitData = null;
        });
    }

    if (fillAllBtn) {
        fillAllBtn.addEventListener('click', () => {
            if (!lastLemitData) return;
            const tipo = lastLemitData._tipo;
            const root = (tipo === 'PJ' ? lastLemitData.empresa : lastLemitData.pessoa) || {};

            const nome = tipo === 'PJ'
                ? (root.razao_social || root.nome_fantasia || '')
                : (root.nome || '');
            if (nome && nomeInput) nomeInput.value = nome;

            const cel = (root.celulares && root.celulares[0]) || (root.fixos && root.fixos[0]) || null;
            if (cel && telefoneInput) {
                telefoneInput.value = `${limpar(cel.ddd)}${limpar(cel.numero)}`;
                applyTelefoneMask({ keepRaw: true });
            }

            const em = (root.emails && root.emails.find(x => x && x.email)) || null;
            if (em && emailInput) emailInput.value = em.email;

            if (bsModal) bsModal.hide();
        });
    }

    if (lemitBtn) {
        lemitBtn.addEventListener('click', async () => {
            const cpfCnpj = limpar(cpfCnpjInput.value);
            const tipo = tipoSelect.value;
            const url = tipo === 'PJ' ? cfg.lemitCnpjUrl : cfg.lemitCpfUrl;
            const key = tipo === 'PJ' ? 'cnpj' : 'cpf';
            const tamanho = tipo === 'PJ' ? 14 : 11;

            if (cpfCnpj.length !== tamanho) {
                alert(`Informe um ${key.toUpperCase()} válido com ${tamanho} dígitos.`);
                return;
            }

            if (modalDocEl) modalDocEl.textContent = fmtDoc(cpfCnpj);
            if (fillAllBtn) fillAllBtn.disabled = true;
            setModalState('loading');
            if (bsModal) bsModal.show();

            lemitBtn.disabled = true;

            try {
                const resp = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ [key]: cpfCnpj }),
                });
                const body = await resp.json();
                if (!resp.ok) {
                    if (stateErrorMsg) stateErrorMsg.textContent = body.error || 'Falha ao consultar Lemit.';
                    setModalState('error');
                    return;
                }

                lastLemitData = { ...body, _tipo: tipo };
                renderIdentificacao(body, tipo);
                renderTelefones(body, tipo);
                renderEmails(body, tipo);
                setModalState('result');
                if (fillAllBtn) fillAllBtn.disabled = false;
            } catch (err) {
                if (stateErrorMsg) stateErrorMsg.textContent = 'Erro de rede ao consultar Lemit.';
                setModalState('error');
            } finally {
                lemitBtn.disabled = false;
            }
        });
    }

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('lkb-btn-salvar');
            const payload = {
                cliente_tipo: tipoSelect.value,
                cpf_cnpj: cpfCnpjCleave ? cpfCnpjCleave.getRawValue() : limpar(cpfCnpjInput.value),
                nome: document.getElementById('lkb-nome').value,
                email: document.getElementById('lkb-email').value,
                telefone: document.getElementById('lkb-telefone').value,
                produto_interesse_id: document.getElementById('lkb-produto').value,
                observacoes: document.getElementById('lkb-observacoes').value,
            };

            btn.disabled = true;
            try {
                const resp = await fetch(cfg.storeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                const body = await resp.json();
                if (!resp.ok) {
                    alert(body.error || (body.errors ? Object.values(body.errors).flat().join('\n') : 'Falha ao criar lead.'));
                    return;
                }
                window.location.href = cfg.kanbanUrl;
            } finally {
                btn.disabled = false;
            }
        });
    }
})();
