'use strict';

(function () {
    const cfg = window.lkbLeadNovo || {};

    const form = document.getElementById('lkb-form-lead-novo');
    const cpfCnpjInput = document.getElementById('lkb-cpf-cnpj');
    const tipoSelect = document.getElementById('lkb-cliente-tipo');
    const lemitBtn = document.getElementById('lkb-btn-lemit');

    const limpar = (v) => String(v || '').replace(/\D+/g, '');

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

            lemitBtn.disabled = true;
            lemitBtn.innerHTML = '<i class="ri-loader-4-line me-1"></i> Consultando...';

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
                    alert(body.error || 'Falha ao consultar Lemit.');
                    return;
                }

                if (tipo === 'PJ') {
                    const emp = body.empresa || body.data?.empresa;
                    if (emp) {
                        document.getElementById('lkb-nome').value = emp.razao_social || emp.nome_fantasia || '';
                        const cel = (emp.celulares && emp.celulares[0]) || (emp.fixos && emp.fixos[0]) || null;
                        if (cel) document.getElementById('lkb-telefone').value = `(${cel.ddd || ''}) ${cel.numero || ''}`.trim();
                        const em = emp.emails && emp.emails[0];
                        if (em) document.getElementById('lkb-email').value = em.email || '';
                    }
                } else {
                    const p = body.pessoa || {};
                    if (p.nome) document.getElementById('lkb-nome').value = p.nome;
                    const cel = (p.celulares && p.celulares[0]) || null;
                    if (cel) document.getElementById('lkb-telefone').value = `(${cel.ddd || ''}) ${cel.numero || ''}`.trim();
                    const em = p.emails && p.emails[0];
                    if (em) document.getElementById('lkb-email').value = em.email || '';
                }
            } finally {
                lemitBtn.disabled = false;
                lemitBtn.innerHTML = '<i class="ri-search-line me-1"></i> Lemit';
            }
        });
    }

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('lkb-btn-salvar');
            const payload = {
                cliente_tipo: tipoSelect.value,
                cpf_cnpj: limpar(cpfCnpjInput.value),
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
