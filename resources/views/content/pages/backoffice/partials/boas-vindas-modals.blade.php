{{-- Modais de Boas-Vindas (WhatsApp) — compartilhados entre Pós-Venda e Demandas de Pós-Venda --}}

<!-- Modal Boas Vindas -->
<div class="modal fade" id="modalBoasVindas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl bv-modal-dialog">
        <div class="modal-content pv-modal-modern">
            <div class="pv-modal-header pv-modal-header-info">
                <div class="pv-modal-header-content">
                    <div class="pv-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                    </div>
                    <div class="pv-modal-title-group">
                        <h5 class="pv-modal-title">Boas Vindas ao Cliente</h5>
                        <span class="pv-modal-subtitle" id="bv-modal-subtitle">Escolha como enviar a mensagem</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="bv-token-btn" id="btn-config-token" title="Configurar token WhatsApp">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>
                            <path d="M12 2v2M12 20v2M2 12h2M20 12h2"/>
                        </svg>
                    </button>
                    <button type="button" class="pv-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="pv-modal-body">
                <input type="hidden" id="boas-vindas-venda-id">

                <!-- Resumo do Contrato -->
                <div class="pv-contract-summary mb-3">
                    <div class="pv-summary-row">
                        <span class="pv-summary-label">Contrato:</span>
                        <span class="pv-summary-value" id="bv-contrato-nome">-</span>
                    </div>
                    <div class="pv-summary-row">
                        <span class="pv-summary-label">Operadora:</span>
                        <span class="pv-summary-value" id="bv-operadora">-</span>
                    </div>
                    <div class="pv-summary-row">
                        <span class="pv-summary-label">Plano:</span>
                        <span class="pv-summary-value" id="bv-plano">-</span>
                    </div>
                    <div class="pv-summary-row">
                        <span class="pv-summary-label">Implantado em:</span>
                        <span class="pv-summary-value" id="bv-data-implantacao">-</span>
                    </div>
                </div>

                <!-- Aviso sem token -->
                <div class="bv-no-token-alert d-none" id="bv-no-token-alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    Token do WhatsApp não configurado. Clique no ícone de engrenagem para configurar.
                </div>

                <!-- Conteúdo da mensagem -->
                <label class="pv-form-label">Conteúdo da mensagem</label>
                <div class="bv-mode-selector bv-mode-selector-2" id="bv-mode-selector">
                    <div class="bv-mode-card active" data-mode="padrao" onclick="selecionarModoBoasVindas('padrao')">
                        <div class="bv-mode-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10 9 9 9 8 9"/>
                            </svg>
                        </div>
                        <div class="bv-mode-label">Mensagem Padrão</div>
                        <div class="bv-mode-desc">Template pré-definido com dados do plano</div>
                    </div>
                    <div class="bv-mode-card" data-mode="personalizado" onclick="selecionarModoBoasVindas('personalizado')">
                        <div class="bv-mode-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </div>
                        <div class="bv-mode-label">Personalizado</div>
                        <div class="bv-mode-desc">Escreva a mensagem livremente</div>
                    </div>
                </div>

                <!-- Canais de envio -->
                <label class="pv-form-label">Canais de envio</label>
                <div class="bv-canais-options" id="bv-canais-options">
                    <label class="bv-canal-option" data-canal="whatsapp">
                        <input type="checkbox" class="bv-canal-check" id="bv-canal-whatsapp" checked>
                        <span class="bv-canal-icon bv-canal-icon-wpp">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884"/></svg>
                        </span>
                        <span class="bv-canal-text">
                            <span class="bv-canal-nome">WhatsApp</span>
                            <span class="bv-canal-desc">Mensagem instantânea</span>
                        </span>
                        <span class="bv-canal-mark">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                    </label>
                    <label class="bv-canal-option" data-canal="email">
                        <input type="checkbox" class="bv-canal-check" id="bv-canal-email">
                        <span class="bv-canal-icon bv-canal-icon-email">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        </span>
                        <span class="bv-canal-text">
                            <span class="bv-canal-nome">E-mail</span>
                            <span class="bv-canal-desc">E-mail de boas-vindas LK</span>
                        </span>
                        <span class="bv-canal-mark">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                    </label>
                </div>

                <!-- Destinatários WhatsApp (telefone) -->
                <div id="bv-destinatarios-section" class="mb-3 d-none">
                    <label class="pv-form-label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.62 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.59a16 16 0 0 0 7.5 7.5l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        Destinatários (WhatsApp)
                    </label>
                    <div id="bv-destinatarios-list" class="bv-destinatarios-list"></div>
                </div>

                <!-- Destinatários E-mail -->
                <div id="bv-destinatarios-email-section" class="mb-3 d-none">
                    <label class="pv-form-label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        Destinatários (E-mail)
                    </label>
                    <div id="bv-destinatarios-email-list" class="bv-destinatarios-list"></div>
                </div>

                <!-- Formulário Modo Padrão -->
                <div id="bv-form-padrao" class="bv-form-section">
                    <div class="mb-3">
                        <label class="pv-form-label">Beneficiários e Códigos</label>
                        <div id="bv-beneficiarios-list" class="bv-beneficiarios-list"></div>
                    </div>

                    {{-- Acessos ao aplicativo (um ou vários login/senha) --}}
                    <div class="bv-acessos-head mb-2">
                        <label class="pv-form-label mb-0">Acessos ao aplicativo <span class="bv-acessos-count" id="bv-acessos-count"></span></label>
                        <button type="button" class="bv-add-acesso" id="bv-add-acesso">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Adicionar acesso
                        </button>
                    </div>
                    <div class="bv-acessos-app mb-3" id="bv-acessos-app"></div>

                    <template id="bv-acesso-app-tpl">
                        <div class="bv-app-row">
                            <input type="text" class="pv-form-input bv-app-rotulo" placeholder="Rótulo (ex.: titular)" oninput="atualizarPreviewPadrao()">
                            <input type="text" class="pv-form-input bv-app-login" placeholder="Login / CPF" oninput="atualizarPreviewPadrao()">
                            <input type="text" class="pv-form-input bv-app-senha" placeholder="Senha (ex.: Saude2026)" oninput="atualizarPreviewPadrao()">
                            <button type="button" class="bv-app-remove" title="Remover acesso" aria-label="Remover acesso">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </div>
                    </template>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="pv-form-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                                Link iOS (opcional)
                            </label>
                            <input type="url" class="pv-form-input" id="bv-link-ios" placeholder="https://apps.apple.com/..." oninput="atualizarPreviewPadrao()">
                        </div>
                        <div class="col-6">
                            <label class="pv-form-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M6 18c0 .55.45 1 1 1h1v3.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5V19h2v3.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5V19h1c.55 0 1-.45 1-1V8H6v10zM3.5 8C2.67 8 2 8.67 2 9.5v7c0 .83.67 1.5 1.5 1.5S5 17.33 5 16.5v-7C5 8.67 4.33 8 3.5 8zm17 0c-.83 0-1.5.67-1.5 1.5v7c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5v-7c0-.83-.67-1.5-1.5-1.5zm-4.97-5.84l1.3-1.3c.2-.2.2-.51 0-.71-.2-.2-.51-.2-.71 0l-1.48 1.48C13.85 1.23 12.95 1 12 1c-.96 0-1.86.23-2.66.63L7.85.15c-.2-.2-.51-.2-.71 0-.2.2-.2.51 0 .71l1.31 1.31C6.97 3.26 6 5.01 6 7h12c0-1.99-.97-3.75-2.47-4.84zM10 5H9V4h1v1zm5 0h-1V4h1v1z"/></svg>
                                Link Android (opcional)
                            </label>
                            <input type="url" class="pv-form-input" id="bv-link-android" placeholder="https://play.google.com/..." oninput="atualizarPreviewPadrao()">
                        </div>
                    </div>

                    <div class="bv-portal-toggle mb-2">
                        <button type="button" class="bv-toggle-link" id="btn-toggle-portal" onclick="togglePortal()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                            Incluir acesso ao portal corporativo (opcional)
                        </button>
                    </div>
                    <div id="bv-portal-fields" class="row g-2 mb-3 d-none">
                        <div class="col-6">
                            <label class="pv-form-label">Usuário do Portal</label>
                            <input type="text" class="pv-form-input" id="bv-portal-user" placeholder="Ex: JL821952299">
                        </div>
                        <div class="col-6">
                            <label class="pv-form-label">Senha do Portal</label>
                            <input type="text" class="pv-form-input" id="bv-portal-senha" placeholder="Ex: Empresa#26">
                        </div>
                    </div>

                    <div class="bv-preview-actions mb-3">
                        <button type="button" class="bv-preview-btn" id="btn-visualizar-previa" onclick="abrirPreviewWhatsapp()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884"/></svg>
                            Prévia WhatsApp
                        </button>
                        <button type="button" class="bv-preview-btn bv-preview-btn-email" onclick="abrirPreviewEmail()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            Prévia E-mail
                        </button>
                    </div>
                    {{-- campo oculto para manter compatibilidade com o JS --}}
                    <textarea id="bv-preview-padrao" class="d-none"></textarea>
                </div>

                <!-- Formulário Modo Personalizado -->
                <div id="bv-form-personalizado" class="bv-form-section d-none">
                    <div class="pv-form-group mb-3">
                        <label class="pv-form-label">Mensagem</label>
                        <textarea class="pv-form-textarea" id="bv-mensagem-personalizada" rows="7" placeholder="Digite a mensagem completa aqui..."></textarea>
                    </div>
                    <div class="bv-preview-actions mb-3">
                        <button type="button" class="bv-preview-btn" onclick="abrirPreviewWhatsappPersonalizado()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884"/></svg>
                            Prévia WhatsApp
                        </button>
                        <button type="button" class="bv-preview-btn bv-preview-btn-email" onclick="abrirPreviewEmailPersonalizado()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            Prévia E-mail
                        </button>
                    </div>
                </div>

                <div class="pv-modal-actions mt-3">
                    <button type="button" class="pv-btn pv-btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="pv-btn pv-btn-info" id="btn-confirmar-boas-vindas">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.62 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.59a16 16 0 0 0 7.5 7.5l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        Enviar via WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Configurar Token WhatsApp -->
<div class="modal fade" id="modalWhatsappToken" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content pv-modal-modern">
            <div class="pv-modal-header" style="background: linear-gradient(135deg, #25D366, #128C7E);">
                <div class="pv-modal-header-content">
                    <div class="pv-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
                        </svg>
                    </div>
                    <div class="pv-modal-title-group">
                        <h5 class="pv-modal-title">Token WhatsApp</h5>
                        <span class="pv-modal-subtitle">Configuração da conexão Ticketz</span>
                    </div>
                </div>
                <button type="button" class="pv-modal-close" data-bs-dismiss="modal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="pv-modal-body">
                <p class="bv-token-help">Cole o token da conexão Ticketz configurada em <strong>Conexões → Editar → Token</strong>.</p>
                <div class="pv-form-group mb-3">
                    <label class="pv-form-label">Token de Autenticação</label>
                    <input type="text" class="pv-form-input" id="input-whatsapp-token" placeholder="Cole o token aqui...">
                    <span class="bv-token-current d-none" id="bv-token-current-info"></span>
                </div>
                <div class="pv-modal-actions">
                    <button type="button" class="pv-btn pv-btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="pv-btn" id="btn-salvar-token" style="background: #25D366; color: #fff;" onclick="salvarWhatsappToken()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Salvar Token
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Prévia WhatsApp -->
<div class="modal fade" id="modalPreviewWhatsapp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered wpp-preview-dialog">
        <div class="modal-content wpp-preview-modal">
            <!-- Phone frame header -->
            <div class="wpp-phone-bar">
                <div class="wpp-phone-notch"></div>
            </div>
            <!-- WhatsApp header -->
            <div class="wpp-header">
                <button type="button" class="wpp-back-btn" data-bs-dismiss="modal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <div class="wpp-avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
                </div>
                <div class="wpp-contact-info">
                    <span class="wpp-contact-name" id="wpp-preview-contact">Cliente</span>
                    <span class="wpp-contact-status">online</span>
                </div>
                <div class="wpp-header-actions">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.62 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.59a16 16 0 0 0 7.5 7.5l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                </div>
            </div>
            <!-- Chat area -->
            <div class="wpp-chat-area">
                <div class="wpp-date-pill">Hoje</div>
                <div class="wpp-bubble-wrap">
                    <div class="wpp-bubble" id="wpp-bubble-content"></div>
                    <div class="wpp-bubble-meta">
                        <span class="wpp-time" id="wpp-preview-time"></span>
                        <svg class="wpp-tick" xmlns="http://www.w3.org/2000/svg" width="16" height="11" viewBox="0 0 16 11">
                            <path d="M11.071.653a.75.75 0 0 1 .025 1.06l-6.09 6.44a.75.75 0 0 1-1.085 0L1.4 5.307a.75.75 0 1 1 1.085-1.035l2.01 2.108L10.01.677a.75.75 0 0 1 1.06-.024z" fill="#53bdeb"/>
                            <path d="M15.071.653a.75.75 0 0 1 .025 1.06l-6.09 6.44a.75.75 0 0 1-.617.242.75.75 0 0 0 .532-.218l6.09-6.44a.75.75 0 0 0-.025-1.06.75.75 0 0 1 .085-.024z" fill="#53bdeb"/>
                        </svg>
                    </div>
                </div>
            </div>
            <!-- Bottom bar -->
            <div class="wpp-input-bar">
                <div class="wpp-fake-input">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                    <span>Mensagem</span>
                </div>
                <button class="wpp-mic-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1-9c0-.55.45-1 1-1s1 .45 1 1v6c0 .55-.45 1-1 1s-1-.45-1-1V5zm6 6c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Prévia E-mail -->
<div class="modal fade" id="modalPreviewEmail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog mailcli-dialog">
        <div class="modal-content mailcli-window">
            <!-- Window chrome -->
            <div class="mailcli-chrome">
                <span class="mailcli-dot mailcli-dot-red"></span>
                <span class="mailcli-dot mailcli-dot-amber"></span>
                <span class="mailcli-dot mailcli-dot-green"></span>
                <span class="mailcli-chrome-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    Prévia do e-mail
                </span>
                <button type="button" class="mailcli-close" data-bs-dismiss="modal" aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <!-- Meta (de / para / assunto) -->
            <div class="mailcli-meta">
                <div class="mailcli-avatar">LK</div>
                <div class="mailcli-meta-text">
                    <span class="mailcli-subject" id="mailcli-subject">Boas-vindas</span>
                    <span class="mailcli-row"><strong>De</strong> <span id="mailcli-from">Equipe LK Brokers</span></span>
                    <span class="mailcli-row"><strong>Para</strong> <span id="mailcli-to">cliente@email.com</span></span>
                </div>
            </div>
            <!-- Corpo renderizado (iframe isola o CSS do e-mail) -->
            <div class="mailcli-body">
                <div class="mailcli-loading" id="mailcli-loading">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                    Gerando prévia…
                </div>
                <iframe id="email-preview-frame" class="mailcli-frame" title="Prévia do e-mail de boas-vindas"></iframe>
            </div>
        </div>
    </div>
</div>
