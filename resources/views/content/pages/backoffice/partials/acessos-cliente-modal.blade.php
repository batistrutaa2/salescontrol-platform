{{-- Modal: cadastrar um ou vários acessos (login/senha) do cliente, direto da
     aba Cliente do contrato. Envia para backoffice.credenciais.storeMultiplo com
     o venda_id — o backend amarra o CNPJ e o acesso já aparece na lista. --}}
<div class="modal fade pvac-modal" id="pvAcessoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered pvac-dialog">
        <form id="pvAcessoForm">
            <div class="modal-content pvac-content">
                <div class="modal-header pvac-head">
                    <div class="pvac-head-txt">
                        <span class="pvac-head-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 8.3-8.3M17 5l3 3M14 8l3 3"/></svg>
                        </span>
                        <div>
                            <h5 class="modal-title pvac-title">Cadastrar acesso</h5>
                            <span class="pvac-sub">Um ou vários logins/senhas de portais deste cliente</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body pvac-body">
                    {{-- Contexto compartilhado por todos os acessos --}}
                    <div class="pvac-ctx">
                        <div class="pvac-field">
                            <label class="pvac-label" for="pvac-operadora">Operadora</label>
                            <select class="form-select pvac-input" id="pvac-operadora" name="operadora_id">
                                <option value="">— Selecione —</option>
                                @foreach ($operadoras ?? collect() as $operadora)
                                    <option value="{{ $operadora->id }}">{{ $operadora->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pvac-field">
                            <label class="pvac-label" for="pvac-tipo">Tipo de acesso</label>
                            <input type="text" class="form-control pvac-input" id="pvac-tipo" name="tipo"
                                list="pvac-tipo-sugestoes" placeholder="Empresa, Pessoa Física...">
                            <datalist id="pvac-tipo-sugestoes">
                                <option value="Empresa"></option>
                                <option value="Pessoa Física"></option>
                                <option value="Adesão"></option>
                            </datalist>
                        </div>
                        <div class="pvac-field">
                            <label class="pvac-label" for="pvac-status">Status</label>
                            <select class="form-select pvac-input" id="pvac-status" name="status">
                                <option value="Y">Ativo</option>
                                <option value="N">Inativo</option>
                            </select>
                        </div>
                        <div class="pvac-field pvac-field-full">
                            <label class="pvac-label" for="pvac-observacao">Observação</label>
                            <textarea class="form-control pvac-input" id="pvac-observacao" name="observacao" rows="2"
                                placeholder="Senha secundária, dia de vencimento, e-mail, etc."></textarea>
                        </div>
                    </div>

                    {{-- Acessos (repetidor) --}}
                    <div class="pvac-acessos-head">
                        <span class="pvac-acessos-title">Acessos <span class="pvac-acessos-count" id="pvac-acessos-count"></span></span>
                        <button type="button" class="pvac-add" id="pvac-add">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Adicionar acesso
                        </button>
                    </div>
                    <div class="pvac-acessos" id="pvac-acessos"></div>
                </div>

                <div class="modal-footer pvac-foot">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="pvac-btn-primary" id="pvac-salvar">Salvar acessos</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Template de uma linha de acesso (rótulo/login/senha) --}}
<template id="pvac-acesso-tpl">
    <div class="pvac-acesso">
        <div class="pvac-acesso-f pvac-acesso-f-nome">
            <label class="pvac-label">Nome / rótulo <span class="text-danger">*</span></label>
            <input type="text" class="form-control pvac-acesso-nome" placeholder="Ex.: MASTER, SUBMASTER" required>
        </div>
        <div class="pvac-acesso-f">
            <label class="pvac-label">Login</label>
            <input type="text" class="form-control pvac-mono pvac-acesso-login" placeholder="CPF/CNPJ ou código">
        </div>
        <div class="pvac-acesso-f">
            <label class="pvac-label">Senha</label>
            <div class="pvac-senha-wrap">
                <input type="password" class="form-control pvac-mono pvac-acesso-senha" placeholder="••••••••">
                <button type="button" class="pvac-eye" tabindex="-1" aria-label="Mostrar ou ocultar senha">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>
        <button type="button" class="pvac-remove" aria-label="Remover acesso" title="Remover acesso">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </button>
    </div>
</template>
