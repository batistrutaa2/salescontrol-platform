'use strict';

/**
 * Boas-Vindas ao cliente — módulo compartilhado (WhatsApp + E-mail).
 *
 * Reaproveita os endpoints existentes do backoffice:
 *   GET  /back-office/pos-venda/beneficiarios/{vendaId}
 *   POST /back-office/pos-venda/boas-vindas
 *   GET/POST /back-office/configuracoes/whatsapp-token
 *
 * O envio combina dois eixos ortogonais:
 *   - Conteúdo (tipo_envio): 'padrao' (template) ou 'personalizado' (texto livre)
 *   - Canais (canais[]): 'whatsapp' e/ou 'email' — nenhum marcado = só registrar.
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

    let bvModoConteudo = 'padrao'; // 'padrao' | 'personalizado'
    let bvTitulares = [];
    let bvVendaInfo = {};
    let bvNomeEmpresa = 'LK Brokers';

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

    function emailValido(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email || '').trim());
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
    // -------------------------------------------------- acessos ao app (repetidor)
    const bvAcessosBox = () => document.getElementById('bv-acessos-app');

    function bvAtualizarAcessosApp() {
        const linhas = bvAcessosBox().querySelectorAll('.bv-app-row');
        linhas.forEach((l) => {
            const rm = l.querySelector('.bv-app-remove');
            if (rm) rm.disabled = linhas.length <= 1;
        });
        const count = document.getElementById('bv-acessos-count');
        if (count) count.textContent = linhas.length > 1 ? linhas.length : '';
    }

    function bvAddAcessoApp(dados = null, foco = false) {
        const tpl = document.getElementById('bv-acesso-app-tpl');
        const node = tpl.content.firstElementChild.cloneNode(true);
        if (dados) {
            node.querySelector('.bv-app-rotulo').value = dados.rotulo ?? '';
            node.querySelector('.bv-app-login').value = dados.login ?? '';
            node.querySelector('.bv-app-senha').value = dados.senha ?? '';
        }
        bvAcessosBox().appendChild(node);
        bvAtualizarAcessosApp();
        if (foco) node.querySelector('.bv-app-rotulo').focus();
    }

    function bvLerAcessosApp() {
        return Array.from(bvAcessosBox().querySelectorAll('.bv-app-row')).map((l) => ({
            rotulo: l.querySelector('.bv-app-rotulo').value.trim(),
            login: l.querySelector('.bv-app-login').value.trim(),
            senha: l.querySelector('.bv-app-senha').value.trim(),
        }));
    }

    function bvResetAcessosApp() {
        bvAcessosBox().innerHTML = '';
        bvAddAcessoApp();
    }

    window.abrirBoasVindas = async function (vendaId) {
        document.getElementById('boas-vindas-venda-id').value = vendaId;
        document.getElementById('bv-no-token-alert').classList.add('d-none');
        document.getElementById('bv-reenvio-alert')?.classList.add('d-none');

        // canais padrão: WhatsApp marcado, E-mail desmarcado
        document.getElementById('bv-canal-whatsapp').checked = true;
        document.getElementById('bv-canal-email').checked = false;

        selecionarModoBoasVindas('padrao');
        atualizarCanaisUI();
        bvResetAcessosApp();

        const modal = new bootstrap.Modal(modalBoasVindasEl);
        modal.show();

        try {
            const res = await fetch(`/back-office/pos-venda/beneficiarios/${vendaId}`);
            const data = await res.json();
            if (!data.success) return;

            bvVendaInfo = data.venda;
            bvTitulares = data.titulares || [];
            bvNomeEmpresa = data.nome_empresa || 'LK Brokers';

            document.getElementById('bv-contrato-nome').textContent = data.venda.nome_contrato || '-';
            document.getElementById('bv-operadora').textContent = data.venda.operadora || '-';
            document.getElementById('bv-plano').textContent = data.venda.plano || '-';
            document.getElementById('bv-data-implantacao').textContent = data.venda.data_implantacao || '-';

            // Segundo disparo em diante: deixa claro que é reenvio, com data e autor do anterior.
            const alertaReenvio = document.getElementById('bv-reenvio-alert');
            if (alertaReenvio && data.venda.boas_vindas_enviado) {
                document.getElementById('bv-reenvio-quando').textContent = data.venda.boas_vindas_enviado_em
                    ? `em ${data.venda.boas_vindas_enviado_em}`
                    : 'anteriormente';
                document.getElementById('bv-reenvio-por').textContent = data.venda.boas_vindas_enviado_por
                    ? `, por ${data.venda.boas_vindas_enviado_por}`
                    : '';
                alertaReenvio.classList.remove('d-none');
            }

            const linksApp = getLinksAppPorOperadora(data.venda.operadora || '');
            document.getElementById('bv-link-ios').value = linksApp.ios;
            document.getElementById('bv-link-android').value = linksApp.android;

            renderBeneficiarios(bvTitulares);
            renderDestinatarios(data.titulares || [], data.dependentes || [], data.venda.telefone1 || '');
            renderDestinatariosEmail(data.titulares || [], data.venda.email || '');

            if (!data.has_token) {
                document.getElementById('bv-no-token-alert').classList.remove('d-none');
            }
        } catch (e) {
            console.error('Erro ao carregar dados da venda:', e);
        }
    };

    // -------------------------------------------------- destinatários (WhatsApp)
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

    // -------------------------------------------------- destinatários (E-mail)
    function renderDestinatariosEmail(titulares, emailVenda) {
        const container = document.getElementById('bv-destinatarios-email-list');
        container.innerHTML = '';

        let algumEmail = false;

        if (titulares.length > 0) {
            titulares.forEach((t, i) => {
                let email = t.email || '';
                if (!email && i === 0) email = emailVenda || '';
                if (email) algumEmail = true;
                container.innerHTML += buildDestinatarioEmailRow(t.nome, email, 'Titular', !!email);
            });
        } else {
            algumEmail = !!emailVenda;
            container.innerHTML += buildDestinatarioEmailRow('', emailVenda || '', 'Titular', !!emailVenda);
        }

        return algumEmail;
    }

    function buildDestinatarioEmailRow(nome, email, tipo, checked) {
        return `
            <div class="bv-destinatario-row bv-destinatario-email-row">
                <input type="checkbox" class="bv-dest-check bv-dest-email-check" ${checked ? 'checked' : ''}>
                <div class="bv-dest-info">
                    <span class="bv-dest-nome">${escapeHtml(nome || 'Sem nome')}</span>
                    <span class="bv-dest-tipo">${escapeHtml(tipo)}</span>
                </div>
                <input type="email" class="pv-form-input bv-dest-email" placeholder="cliente@email.com" value="${escapeHtml(email)}">
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

    // -------------------------------------------------- modo de conteúdo
    window.selecionarModoBoasVindas = function (modo) {
        bvModoConteudo = modo;

        document.querySelectorAll('.bv-mode-card').forEach(c => c.classList.remove('active'));
        document.querySelector(`.bv-mode-card[data-mode="${modo}"]`)?.classList.add('active');

        document.getElementById('bv-form-padrao').classList.toggle('d-none', modo !== 'padrao');
        document.getElementById('bv-form-personalizado').classList.toggle('d-none', modo !== 'personalizado');

        if (modo === 'padrao') atualizarPreviewPadrao();
    };

    // -------------------------------------------------- canais de envio
    function lerCanais() {
        const canais = [];
        if (document.getElementById('bv-canal-whatsapp')?.checked) canais.push('whatsapp');
        if (document.getElementById('bv-canal-email')?.checked) canais.push('email');
        return canais;
    }

    function atualizarCanaisUI() {
        const wpp = document.getElementById('bv-canal-whatsapp')?.checked;
        const email = document.getElementById('bv-canal-email')?.checked;

        document.querySelector('.bv-canal-option[data-canal="whatsapp"]')?.classList.toggle('active', !!wpp);
        document.querySelector('.bv-canal-option[data-canal="email"]')?.classList.toggle('active', !!email);

        document.getElementById('bv-destinatarios-section').classList.toggle('d-none', !wpp);
        document.getElementById('bv-destinatarios-email-section').classList.toggle('d-none', !email);

        const btn = document.getElementById('btn-confirmar-boas-vindas');
        const iconSend = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>`;
        const iconCheck = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>`;

        let label;
        if (wpp && email) label = `${iconSend} Enviar (WhatsApp + E-mail)`;
        else if (wpp) label = `${iconSend} Enviar via WhatsApp`;
        else if (email) label = `${iconSend} Enviar por E-mail`;
        else label = `${iconCheck} Registrar sem envio`;

        btn.innerHTML = label;
    }

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
        const acessosApp = bvLerAcessosApp();
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
        const comConteudo = acessosApp.filter((a) => a.rotulo || a.login || a.senha);
        const paraMostrar = comConteudo.length ? comConteudo : [{ rotulo: '', login: '', senha: '' }];
        msg += `*📱 Login e Senha — Aplicativo da Operadora (${operadora}):*\n`;
        paraMostrar.forEach((a) => {
            if (a.rotulo) msg += `\n*${a.rotulo.toUpperCase()}*\n`;
            msg += `• Login: ${a.login || '...'}\n`;
            msg += `• Senha: ${a.senha || '...'}\n`;
        });

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

    // Coleta os campos de conteúdo (sem validar) conforme o modo atual.
    // Reaproveitado pelo envio e pela prévia de e-mail.
    function coletarConteudo() {
        const out = { tipo_envio: bvModoConteudo };

        if (bvModoConteudo === 'padrao') {
            const nomes = document.querySelectorAll('#bv-beneficiarios-list .bv-nome-input');
            const codigos = document.querySelectorAll('#bv-beneficiarios-list .bv-codigo-input');
            const beneficiarios = [];
            nomes.forEach((n, i) => {
                const nome = n.value.trim();
                const codigo = codigos[i]?.value.trim() || '';
                if (nome || codigo) beneficiarios.push({ nome, codigo });
            });

            out.beneficiarios = beneficiarios;
            out.nome_contrato = document.getElementById('bv-contrato-nome').textContent;
            out.acessos_app = bvLerAcessosApp().filter((a) => a.login || a.senha);
            out.link_ios = document.getElementById('bv-link-ios').value.trim();
            out.link_android = document.getElementById('bv-link-android').value.trim();
            out.portal_user = document.getElementById('bv-portal-user').value.trim();
            out.portal_senha = document.getElementById('bv-portal-senha').value.trim();
        } else {
            out.mensagem_personalizada = document.getElementById('bv-mensagem-personalizada').value.trim();
        }

        return out;
    }

    // -------------------------------------------------- confirmar envio
    async function confirmarBoasVindas() {
        const vendaId = document.getElementById('boas-vindas-venda-id').value;
        const btnConfirmar = document.getElementById('btn-confirmar-boas-vindas');

        if (!vendaId) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'ID da venda não encontrado.', confirmButtonColor: '#7C3AED' });
            return;
        }

        const canais = lerCanais();
        const enviarWpp = canais.includes('whatsapp');
        const enviarEmail = canais.includes('email');

        // Destinatários WhatsApp (telefone)
        const destinatarios = [];
        if (enviarWpp) {
            document.querySelectorAll('#bv-destinatarios-list .bv-destinatario-row').forEach(row => {
                const checked = row.querySelector('.bv-dest-check')?.checked;
                if (!checked) return;
                const nome = row.querySelector('.bv-dest-nome')?.textContent?.trim() || '';
                const telefone = row.querySelector('.bv-dest-tel')?.value?.trim() || '';
                if (telefone) destinatarios.push({ nome, telefone });
            });

            if (destinatarios.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione ao menos um destinatário com telefone para o WhatsApp.', confirmButtonColor: '#7C3AED' });
                return;
            }
        }

        // Destinatários E-mail
        const destinatariosEmail = [];
        if (enviarEmail) {
            let emailInvalido = false;
            document.querySelectorAll('#bv-destinatarios-email-list .bv-destinatario-email-row').forEach(row => {
                const checked = row.querySelector('.bv-dest-email-check')?.checked;
                if (!checked) return;
                const nome = row.querySelector('.bv-dest-nome')?.textContent?.trim() || '';
                const email = row.querySelector('.bv-dest-email')?.value?.trim() || '';
                if (!email) return;
                if (!emailValido(email)) { emailInvalido = true; return; }
                destinatariosEmail.push({ nome, email });
            });

            if (emailInvalido) {
                Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Há um e-mail em formato inválido. Verifique os destinatários.', confirmButtonColor: '#7C3AED' });
                return;
            }
            if (destinatariosEmail.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione ao menos um destinatário com e-mail válido.', confirmButtonColor: '#7C3AED' });
                return;
            }
        }

        const payload = {
            venda_id: vendaId,
            tipo_envio: bvModoConteudo,
            canais,
            destinatarios,
            destinatarios_email: destinatariosEmail,
        };

        const conteudo = coletarConteudo();

        if (bvModoConteudo === 'padrao' && conteudo.beneficiarios.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe ao menos um beneficiário.', confirmButtonColor: '#7C3AED' });
            return;
        }
        if (bvModoConteudo === 'personalizado' && !conteudo.mensagem_personalizada) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Escreva a mensagem personalizada.', confirmButtonColor: '#7C3AED' });
            return;
        }

        Object.assign(payload, conteudo);

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
                document.dispatchEvent(new CustomEvent('boasVindasEnviada', {
                    detail: {
                        vendaId,
                        enviadoEm: data.boas_vindas_enviado_em,
                        enviadoPor: data.boas_vindas_enviado_por,
                        reenvio: data.reenvio === true,
                    },
                }));
            } else {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Não foi possível registrar o Boas Vindas.', confirmButtonColor: '#7C3AED' });
            }
        } catch (error) {
            console.error('Erro ao registrar boas vindas:', error);
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Não foi possível registrar o Boas Vindas.', confirmButtonColor: '#7C3AED' });
        } finally {
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = originalContent;
            atualizarCanaisUI();
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

    // -------------------------------------------------- prévia E-mail
    window.abrirPreviewEmail = function () {
        gerarPreviewEmail();
    };

    window.abrirPreviewEmailPersonalizado = function () {
        const texto = document.getElementById('bv-mensagem-personalizada').value.trim();
        if (!texto) {
            Swal.fire({ icon: 'info', title: 'Escreva a mensagem', text: 'Digite o conteúdo da mensagem para visualizar a prévia.', confirmButtonColor: '#7C3AED' });
            return;
        }
        gerarPreviewEmail();
    };

    async function gerarPreviewEmail() {
        const payload = coletarConteudo();
        payload.venda_id = document.getElementById('boas-vindas-venda-id').value;
        payload.operadora = bvVendaInfo.operadora || '';

        // Faixa de meta (de / para / assunto)
        const primeiroEmail = (document.querySelector('#bv-destinatarios-email-list .bv-dest-email')?.value || '').trim() || 'cliente@email.com';
        document.getElementById('mailcli-from').textContent = `Equipe ${bvNomeEmpresa}`;
        document.getElementById('mailcli-to').textContent = primeiroEmail;
        document.getElementById('mailcli-subject').textContent = `Boas-vindas à ${bvNomeEmpresa}`;

        const modalEl = document.getElementById('modalPreviewEmail');
        const frame = document.getElementById('email-preview-frame');
        const loading = document.getElementById('mailcli-loading');
        frame.style.visibility = 'hidden';
        loading.style.display = 'flex';
        loading.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Gerando prévia…';

        // Só escala quando o modal terminou de abrir E o iframe carregou — caso
        // contrário as dimensões da área ainda estão erradas (animação em curso).
        let modalPronto = false;
        let framePronto = false;
        const revelar = () => {
            if (!modalPronto || !framePronto) return;
            loading.style.display = 'none';
            frame.style.visibility = 'visible';
            requestAnimationFrame(ajustarEscalaPreview);
        };

        modalEl.addEventListener('shown.bs.modal', () => { modalPronto = true; revelar(); }, { once: true });
        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        try {
            const res = await fetch('/back-office/pos-venda/boas-vindas/preview-email', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify(payload),
            });
            if (!res.ok) throw new Error('preview-failed');
            const html = await res.text();
            frame.onload = () => { framePronto = true; revelar(); };
            frame.srcdoc = html;
        } catch (e) {
            console.error('Erro ao gerar prévia do e-mail:', e);
            loading.innerHTML = 'Não foi possível gerar a prévia.';
        }
    }

    // Escala o e-mail (largura fixa de 600px) para caber INTEIRO na área visível,
    // sem rolagem — limitado pela largura ou pela altura disponível.
    function ajustarEscalaPreview() {
        const frame = document.getElementById('email-preview-frame');
        const body = frame?.parentElement;
        if (!frame || !body || !body.clientWidth) return;

        const doc = frame.contentDocument || frame.contentWindow?.document;
        if (!doc) return;

        const EMAIL_W = 600;
        const contentH = Math.max(
            doc.documentElement?.scrollHeight || 0,
            doc.body?.scrollHeight || 0,
        ) || 1200;

        const availW = body.clientWidth;
        const availH = body.clientHeight;
        const scale = Math.min(availW / EMAIL_W, availH / contentH);

        body.style.overflow = 'hidden';
        frame.style.width = EMAIL_W + 'px';
        frame.style.height = contentH + 'px';
        frame.style.transformOrigin = 'top left';
        frame.style.transform = `scale(${scale})`;
        frame.style.marginLeft = Math.max((availW - EMAIL_W * scale) / 2, 0) + 'px';
        frame.style.marginTop = Math.max((availH - contentH * scale) / 2, 0) + 'px';
    }

    window.addEventListener('resize', () => {
        if (document.getElementById('modalPreviewEmail')?.classList.contains('show')) {
            ajustarEscalaPreview();
        }
    });

    // -------------------------------------------------- wiring
    document.getElementById('btn-confirmar-boas-vindas')?.addEventListener('click', confirmarBoasVindas);
    document.getElementById('btn-config-token')?.addEventListener('click', abrirConfigToken);
    document.getElementById('bv-canal-whatsapp')?.addEventListener('change', atualizarCanaisUI);
    document.getElementById('bv-canal-email')?.addEventListener('change', atualizarCanaisUI);
    ['bv-portal-user', 'bv-portal-senha'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', () => atualizarPreviewPadrao());
    });

    // Repetidor de acessos ao app: adicionar / remover.
    document.getElementById('bv-add-acesso')?.addEventListener('click', () => bvAddAcessoApp(null, true));
    document.getElementById('bv-acessos-app')?.addEventListener('click', (e) => {
        const rm = e.target.closest('.bv-app-remove');
        if (rm && !rm.disabled) {
            rm.closest('.bv-app-row').remove();
            bvAtualizarAcessosApp();
            atualizarPreviewPadrao();
        }
    });
})();
