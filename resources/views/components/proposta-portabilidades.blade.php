<div class="np-portabilidade-editor" data-portabilidade-editor>
    <input type="hidden" id="portabilidade_status" name="portabilidade_status" value="NAO">
    <input type="hidden" id="qtd_portabilidade" name="qtd_portabilidade" value="0">

    <div class="np-portabilidade-heading">
        <div class="np-portabilidade-copy">
            <div class="np-portabilidade-title-row">
                <span class="toggle-label">Portabilidade</span>
                <span class="status-badge badge-inativo" id="portabilidade-badge">Nenhuma</span>
            </div>
            <p>Informe a origem e o plano de destino de cada pessoa.</p>
        </div>
        <button type="button" class="np-portabilidade-add" id="btn-add-portabilidade">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v8M8 12h8"></path></svg>
            Adicionar Portabilidade
        </button>
    </div>

    <div class="np-portabilidade-empty" id="portabilidade-empty">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="m16 11 2 2 4-4"></path></svg>
        <div>
            <strong>Nenhum cliente em portabilidade</strong>
            <span>Use o botão acima para registrar CPF, origem e destino.</span>
        </div>
    </div>

    <div id="portabilidade-container" class="portabilidade-list" role="list" aria-label="Clientes em portabilidade"></div>
    <p class="visually-hidden" id="portabilidade-live" role="status" aria-live="polite"></p>
</div>

<div class="port-modal-overlay" id="portabilidade-modal" aria-hidden="true">
    <div class="port-modal" role="dialog" aria-modal="true" aria-labelledby="portabilidade-modal-title" aria-describedby="portabilidade-modal-description">
        <div class="port-modal-header">
            <div>
                <h3 id="portabilidade-modal-title" tabindex="-1">Adicionar Portabilidade</h3>
                <p id="portabilidade-modal-description">Cadastre o destino que o pós-venda deverá executar.</p>
            </div>
            <button type="button" class="port-modal-close" id="btn-close-portabilidade" aria-label="Fechar modal">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="port-modal-body">
            <div class="np-field span-2">
                <label class="required" for="portabilidade_cpf">CPF</label>
                <div class="np-enriched-input np-portability-lookup">
                    <input type="text" id="portabilidade_cpf" class="np-input js-documento-pessoa" autocomplete="off" placeholder="000.000.000-00">
                    <span class="np-lookup-spinner" aria-hidden="true"></span>
                    <button type="button" class="np-lookup-button" id="btn-consultar-portabilidade-cpf">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
                        <span>Consultar CPF</span>
                    </button>
                </div>
                <p class="np-lookup-status" data-lookup-status-for="portabilidade_cpf" role="status" aria-live="polite">Digite um CPF válido para consultar na Lemit.</p>
            </div>
            <div class="np-field span-2">
                <label class="required" for="portabilidade_nome">Nome completo</label>
                <input type="text" id="portabilidade_nome" class="np-input" maxlength="100" autocomplete="name" placeholder="Nome completo do cliente">
                <small>Identifique exatamente a pessoa que entrará via portabilidade.</small>
            </div>
            <div class="np-field">
                <label class="required" for="portabilidade_operadora_anterior_id">Operadora anterior</label>
                <select id="portabilidade_operadora_anterior_id" class="np-input">
                    <option value="">Selecione a origem...</option>
                    @foreach ($operadoras as $op)
                        <option value="{{ $op->id }}">{{ strtoupper($op->nome) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="np-field">
                <label class="required" for="portabilidade_operadora_destino_id">Operadora de destino</label>
                <select id="portabilidade_operadora_destino_id" class="np-input">
                    <option value="">Selecione a operadora...</option>
                    @foreach ($operadoras as $op)
                        <option value="{{ $op->id }}">{{ strtoupper($op->nome) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="np-field">
                <label class="required" for="portabilidade_plano_destino_id">Plano de destino</label>
                <select id="portabilidade_plano_destino_id" class="np-input" disabled>
                    <option value="">Selecione primeiro a operadora</option>
                </select>
            </div>
            <div class="port-modal-guidance">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5M12 8h.01"></path></svg>
                <span>CPF, nome, origem, operadora e plano de destino são obrigatórios para o pós-venda.</span>
            </div>
            <p class="port-modal-error" id="portabilidade-modal-error" role="alert" hidden></p>
        </div>

        <div class="port-modal-footer">
            <button type="button" class="port-modal-cancel" id="btn-cancel-portabilidade">Cancelar</button>
            <button type="button" class="port-modal-save" id="btn-save-portabilidade">Adicionar Portabilidade</button>
        </div>
    </div>
</div>
