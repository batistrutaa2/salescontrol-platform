'use strict';

/**
 * Boas-Vindas via WhatsApp — módulo compartilhado.
 *
 * Reaproveita os endpoints existentes do backoffice:
 *   GET  /back-office/pos-venda/beneficiarios/{vendaId}
 *   POST /back-office/pos-venda/boas-vindas
 *   GET/POST /back-office/configuracoes/whatsapp-token
 *
 * Uso: window.abrirBoasVindas(vendaId)  (ex.: botão "Boas-vindas" do card).
 * Ao concluir um envio com sucesso, dispara o evento `boasVindasEnviada` no
 * document para a página recarregar suas listas.
 *
 * Depende dos modais do partial `partials/boas-vindas-modals.blade.php`,
 * de Bootstrap (modais) e SweetAlert2 (toasts) — ambos já carregados no layout.
 */
(function () {
    const modalBoasVindasEl = document.getElementById('modalBoasVindas');
    if (!modalBoasVindasEl) return; // partial não incluído nesta página

    let bvModoSelecionado = 'padrao';
    let bvTitulares = [];
    let bvVendaInfo = {};

    const LINKS_APP_OPERADORAS = {
        amil: {
            ios: 'https://apps.apple.com/br/app/amil-clientes/id471890526',
            android: 'https://play.google.com/store/apps/details?id=br.com.amil.beneficiarios&hl=pt_BR',
        },
    };

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Toast padrão do projeto (componente .custom-toast — estilos em pos-venda.scss)
    function toastSucesso(message) {
        if (!window.Swal) return;
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            background: 'transparent',
            showClass: { popup: 'animate__animated animate__slideInRight animate__faster' },
            hideClass: { popup: 'animate__animated animate__slideOutRight animate__faster' },
            customClass: { popup: 'custom-toast-popup' },
            html: `
                <div class="custom-toast custom-toast-success">
                    <div class="toast-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div class="toast-content">
                        <div class="toast-title">Sucesso!</div>
                        <div class="toast-message">${escapeHtml(message)}</div>
                    </div>
                    <div class="toast-close" onclick="Swal.close()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </div>
                </div>`,
        });
    }

    function getLinksAppPorOperadora(operadora) {
        const key = (operadora || '').toLowerCase().trim();
        for (const [nome, links] of Object.entries(LINKS_APP_OPERADORAS)) {
            if (key.includes(nome)) return links;
        }
        return { ios: '', android: '' };
    }

    function applyPhoneMask(el) {
        if (typeof Cleave === 'undefined') return;
        new Cleave(el, { delimiters: ['(', ') ', '-'], blocks: [0, 2, 5, 4], numericOnly: true });
    }

    // -------------------------------------------------- abrir modal
    window.abrirBoasVindas = async function (vendaId) {
        document.getElementById('boas-vindas-venda-id').value = vendaId;
        document.getElementById('bv-no-token-alert').classList.add('d-none');

        selecionarModoBoasVindas('padrao');

        const modal = new bootstrap.Modal(modalBoasVindasEl);
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

            const linksApp = getLinksAppPorOperadora(data.venda.operadora || '');
            document.getElementById('bv-link-ios').value = linksApp.ios;
            document.getElementById('bv-link-android').value = linksApp.android;

            renderBeneficiarios(bvTitulares);
            renderDestinatarios(data.titulares || [], data.dependentes || [], data.venda.telefone1 || '');

            if (!data.has_token) {
                document.getElementById('bv-no-token-alert').classList.remove('d-none');
            }
        } catch (e) {
            console.error('Erro ao carregar dados da venda:', e);
        }
    };

    // -------------------------------------------------- destinatários
    function renderDestinatarios(titulares, dependentes, telVenda) {
        const container = document.getElementById('bv-destinatarios-list');
        container.innerHTML = '';

        if (titulares.length > 0) {
            titulares.forEach(t => {
                const tel = t.telefone || telVenda || '';
                container.innerHTML += buildDestinatarioRow(t.nome, tel, 'Titular', true);
            });
        } else {
            container.innerHTML += buildDestinatarioRow('', telVenda, 'Titular', true);
        }

        dependentes.forEach(d => {
            const tel = d.telefone1 || '';
            const label = d.parentesco ? `Dependente (${d.parentesco})` : 'Dependente';
            container.innerHTML += buildDestinatarioRow(d.nome, tel, label, false);
        });

        container.querySelectorAll('.bv-dest-tel').forEach(applyPhoneMask);
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

    // -------------------------------------------------- beneficiários (conteúdo)
    function renderBeneficiarios(titulares) {
        const container = document.getElementById('bv-beneficiarios-list');
        container.innerHTML = '';
        if (!titulares || titulares.length === 0) {
            container.innerHTML = '<p class="bv-empty-titulares">Nenhum titular cadastrado. Adicione manualmente abaixo.</p>';
            container.innerHTML += buildBeneficiarioRow('', '');
            return;
        }
        titulares.forEach(t => {
            container.innerHTML += buildBeneficiarioRow(t.nome, '');
        });
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

    window.adicionarBeneficiario = function () {
        const container = document.getElementById('bv-beneficiarios-list');
        const btn = container.querySelector('.bv-add-beneficiario');
        const row = document.createElement('div');
        row.innerHTML = buildBeneficiarioRow('', '');
        container.insertBefore(row.firstElementChild, btn);
    };

    // -------------------------------------------------- modos
    window.selecionarModoBoasVindas = function (modo) {
        bvModoSelecionado = modo;

        document.querySelectorAll('.bv-mode-card').forEach(c => c.classList.remove('active'));
        document.querySelector(`.bv-mode-card[data-mode="${modo}"]`)?.classList.add('active');

        document.getElementById('bv-destinatarios-section').classList.toggle('d-none', modo === 'sem_whatsapp');
        document.getElementById('bv-form-padrao').classList.toggle('d-none', modo !== 'padrao');
        document.getElementById('bv-form-personalizado').classList.toggle('d-none', modo !== 'personalizado');
        document.getElementById('bv-form-sem-whatsapp').classList.toggle('d-none', modo !== 'sem_whatsapp');

        const btn = document.getElementById('btn-confirmar-boas-vindas');
        if (modo === 'sem_whatsapp') {
            btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Registrar sem WhatsApp`;
        } else {
            btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.62 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.59a16 16 0 0 0 7.5 7.5l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg> Enviar via WhatsApp`;
        }

        if (modo === 'padrao') atualizarPreviewPadrao();
    };

    window.togglePortal = function () {
        const fields = document.getElementById('bv-portal-fields');
        const btn = document.getElementById('btn-toggle-portal');
        const hidden = fields.classList.toggle('d-none');
        btn.innerHTML = hidden
            ? `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg> Incluir acesso ao portal corporativo (opcional)`
            : `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg> Ocultar portal corporativo`;
        atualizarPreviewPadrao();
    };

    window.atualizarPreviewPadrao = function () {
        const nomeContrato = document.getElementById('bv-contrato-nome').textContent;
        const operadora = document.getElementById('bv-operadora').textContent;
        const loginApp = document.getElementById('bv-login-app').value.trim();
        const senhaApp = document.getElementById('bv-senha-app').value.trim();
        const portalUser = document.getElementById('bv-portal-user').value.trim();
        const portalSenha = document.getElementById('bv-portal-senha').value.trim();
        const linkIos = document.getElementById('bv-link-ios').value.trim();
        const linkAndroid = document.getElementById('bv-link-android').value.trim();

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
            if (linkIos) msg += `• iOS: ${linkIos}\n`;
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

    // -------------------------------------------------- confirmar envio
    async function confirmarBoasVindas() {
        const vendaId = document.getElementById('boas-vindas-venda-id').value;
        const btnConfirmar = document.getElementById('btn-confirmar-boas-vindas');

        if (!vendaId) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'ID da venda não encontrado.', confirmButtonColor: '#7C3AED' });
            return;
        }

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

        const payload = { venda_id: vendaId, tipo_envio: bvModoSelecionado, destinatarios };

        if (bvModoSelecionado === 'padrao') {
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
            payload.login_app = document.getElementById('bv-login-app').value.trim();
            payload.senha_app = document.getElementById('bv-senha-app').value.trim();
            payload.link_ios = document.getElementById('bv-link-ios').value.trim();
            payload.link_android = document.getElementById('bv-link-android').value.trim();
            payload.portal_user = document.getElementById('bv-portal-user').value.trim();
            payload.portal_senha = document.getElementById('bv-portal-senha').value.trim();
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
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify(payload),
            });
            const data = await response.json();

            if (data.success) {
                bootstrap.Modal.getInstance(modalBoasVindasEl)?.hide();
                toastSucesso(data.message || 'Boas-vindas registrado!');
                document.dispatchEvent(new CustomEvent('boasVindasEnviada', { detail: { vendaId } }));
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

    // -------------------------------------------------- token WhatsApp
    window.salvarWhatsappToken = async function () {
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
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ whatsapp_token: token }),
            });
            const data = await res.json();

            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalWhatsappToken'))?.hide();
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

    // -------------------------------------------------- prévia WhatsApp
    window.abrirPreviewWhatsappPersonalizado = function () {
        const texto = document.getElementById('bv-mensagem-personalizada').value.trim();
        if (!texto) {
            Swal.fire({ icon: 'info', title: 'Escreva a mensagem', text: 'Digite o conteúdo da mensagem para visualizar a prévia.', confirmButtonColor: '#25D366' });
            return;
        }
        abrirModalPreview(texto);
    };

    window.abrirPreviewWhatsapp = function () {
        atualizarPreviewPadrao();
        const texto = document.getElementById('bv-preview-padrao').value;
        if (!texto.trim()) {
            Swal.fire({ icon: 'info', title: 'Preencha os dados', text: 'Preencha os campos do formulário para visualizar a prévia.', confirmButtonColor: '#25D366' });
            return;
        }
        abrirModalPreview(texto);
    };

    function abrirModalPreview(texto) {
        const html = escapeHtml(texto)
            .replace(/\*(.*?)\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');

        document.getElementById('wpp-bubble-content').innerHTML = html;
        document.getElementById('wpp-preview-contact').textContent =
            document.getElementById('bv-contrato-nome').textContent || 'Cliente';

        const now = new Date();
        document.getElementById('wpp-preview-time').textContent =
            now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

        new bootstrap.Modal(document.getElementById('modalPreviewWhatsapp')).show();
    }

    // -------------------------------------------------- wiring
    document.getElementById('btn-confirmar-boas-vindas')?.addEventListener('click', confirmarBoasVindas);
    document.getElementById('btn-config-token')?.addEventListener('click', abrirConfigToken);
    ['bv-login-app', 'bv-senha-app', 'bv-portal-user', 'bv-portal-senha'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', () => atualizarPreviewPadrao());
    });
})();
