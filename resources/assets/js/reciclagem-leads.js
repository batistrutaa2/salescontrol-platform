'use strict';

/**
 * Reciclagem de Leads — leads frios voltando para a preditiva.
 * Listagem de elegíveis (server-side) + envio em massa + config + histórico.
 */
(function () {
    const cfg = window.reciclagemConfig || {};
    const urls = cfg.urls || {};
    const csrf = cfg.csrfToken;

    const selectedIds = new Set();

    function badgeSituacao(s) {
        const map = {
            'DESCARTADO': { cls: 'rl-badge-descartado', label: 'Descartado' },
            'REMARKETING': { cls: 'rl-badge-remarketing', label: 'Remarketing' },
            'SEM ATRIBUICAO': { cls: 'rl-badge-sematribuicao', label: 'Sem atribuição' },
            'SEM_ATRIBUICAO': { cls: 'rl-badge-sematribuicao', label: 'Sem atribuição' }
        };
        const m = map[s] || { cls: 'rl-badge-info', label: s };
        return `<span class="rl-badge ${m.cls}"><span class="rl-dot"></span>${m.label}</span>`;
    }

    const CLOCK_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';

    function badgeDias(d) {
        d = parseInt(d, 10) || 0;
        let cls = 'rl-days-low';
        if (d >= 365) cls = 'rl-days-high';
        else if (d >= 180) cls = 'rl-days-mid';
        return `<span class="rl-days ${cls}">${CLOCK_SVG}${d} dias</span>`;
    }

    function badgeOrigem(o) {
        const map = {
            'AUTOMATICO': { cls: 'rl-badge-primary', label: 'Automático' },
            'MANUAL': { cls: 'rl-badge-info', label: 'Manual' }
        };
        const m = map[o] || { cls: 'rl-badge-info', label: o };
        return `<span class="rl-badge ${m.cls}"><span class="rl-dot"></span>${m.label}</span>`;
    }

    // ---- Tabela de elegíveis ----
    const tabela = $('#tabelaElegiveis').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        ajax: {
            url: urls.elegiveis,
            type: 'GET'
        },
        order: [[6, 'desc']],
        columns: [
            {
                data: 'id',
                orderable: false,
                searchable: false,
                render: function (data) {
                    const checked = selectedIds.has(String(data)) ? 'checked' : '';
                    return `<input type="checkbox" class="form-check-input rl-row-check" value="${data}" ${checked}>`;
                }
            },
            { data: 'nome_cliente', render: d => d || '<span class="text-muted">N/D</span>' },
            { data: 'cpf', render: d => d || '<span class="text-muted">—</span>' },
            { data: 'telefone1', render: d => d || '<span class="text-muted">—</span>' },
            { data: 'situacao', render: d => badgeSituacao(d) },
            { data: 'ultima_atividade', render: d => d || '<span class="text-muted">—</span>' },
            { data: 'dias_parado', render: d => badgeDias(d) }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            sLengthMenu: '_MENU_',
            search: '',
            searchPlaceholder: 'Buscar lead...',
            info: 'Mostrando _START_ a _END_ de _TOTAL_',
            infoFiltered: '(filtrado de _MAX_)',
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Carregando...',
            zeroRecords: 'Nenhum lead frio elegível',
            emptyTable: 'Nenhum lead frio elegível no momento'
        }
    });

    // Mantém o "selecionar todos" coerente ao redesenhar
    tabela.on('draw', function () {
        const allChecked = $('#tabelaElegiveis tbody .rl-row-check').length > 0 &&
            $('#tabelaElegiveis tbody .rl-row-check:not(:checked)').length === 0;
        $('#checkAll').prop('checked', allChecked);
    });

    $('#tabelaElegiveis tbody').on('change', '.rl-row-check', function () {
        const id = String($(this).val());
        if (this.checked) selectedIds.add(id); else selectedIds.delete(id);
        atualizarSelecao();
    });

    $('#checkAll').on('change', function () {
        const check = this.checked;
        $('#tabelaElegiveis tbody .rl-row-check').each(function () {
            const id = String($(this).val());
            $(this).prop('checked', check);
            if (check) selectedIds.add(id); else selectedIds.delete(id);
        });
        atualizarSelecao();
    });

    function atualizarSelecao() {
        $('#selectedCount').text(selectedIds.size);
        $('#btnEnviarSelecionados').prop('disabled', selectedIds.size === 0);
    }

    // ---- Envio em massa ----
    async function postEnviar(payload) {
        return $.ajax({
            url: urls.enviar,
            method: 'POST',
            data: { ...payload, _token: csrf },
            timeout: 120000
        });
    }

    $('#btnEnviarSelecionados').on('click', async function () {
        const ids = Array.from(selectedIds);
        if (ids.length === 0) return;

        const r = await Swal.fire({
            title: 'Enviar para a preditiva?',
            text: `${ids.length} lead(s) selecionado(s) serão enviados para a vitrine.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, enviar',
            cancelButtonText: 'Cancelar'
        });
        if (!r.isConfirmed) return;

        const btn = $(this);
        const original = btn.html();
        btn.prop('disabled', true).html('Enviando...');

        let enviados = 0;
        const batchSize = 100;
        for (let i = 0; i < ids.length; i += batchSize) {
            const batch = ids.slice(i, i + batchSize);
            try {
                const resp = await postEnviar({ ids: batch });
                enviados += resp?.resultado?.enviados || 0;
            } catch (e) {
                console.error('Erro no lote de envio:', e);
            }
        }

        selectedIds.clear();
        btn.html(original);
        atualizarSelecao();
        tabela.ajax.reload(null, false);
        refreshKPIs();
        Swal.fire({ icon: 'success', title: 'Pronto!', text: `${enviados} lead(s) enviados para a preditiva.`, timer: 2500, showConfirmButton: false });
    });

    $('#btnEnviarTodos').on('click', async function () {
        const total = parseInt($('#kpiElegiveis').text().replace(/\D/g, ''), 10) || 0;
        const r = await Swal.fire({
            title: 'Enviar todos os elegíveis?',
            text: `Serão enviados os leads elegíveis (respeitando o limite por execução). Elegíveis agora: ${total}.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, enviar todos',
            cancelButtonText: 'Cancelar'
        });
        if (!r.isConfirmed) return;

        const btn = $(this);
        const original = btn.html();
        btn.prop('disabled', true).html('Enviando...');

        try {
            const resp = await postEnviar({ todos: true });
            const enviados = resp?.resultado?.enviados || 0;
            Swal.fire({ icon: 'success', title: 'Pronto!', text: `${enviados} lead(s) enviados para a preditiva.`, timer: 2500, showConfirmButton: false });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha ao enviar os leads.' });
        } finally {
            btn.prop('disabled', false).html(original);
            selectedIds.clear();
            atualizarSelecao();
            tabela.ajax.reload(null, false);
            refreshKPIs();
        }
    });

    // ---- KPIs ----
    function refreshKPIs() {
        $.getJSON(urls.resumo, function (resp) {
            if (!resp.success) return;
            const r = resp.resumo;
            $('#kpiElegiveis').text(formatNumber(r.elegiveis_agora));
            $('#kpiAguardando').text(formatNumber(r.aguardando));
            $('#kpiVitrine').text(formatNumber(r.na_vitrine));
            $('#kpiEnviados').text(formatNumber(r.enviados_30d));
        });
    }

    function formatNumber(n) {
        return new Intl.NumberFormat('pt-BR').format(n || 0);
    }

    // ---- Configuração ----
    function carregarConfig() {
        $.getJSON(urls.configGet, function (resp) {
            if (!resp.success) return;
            $('#cfgDias').val(resp.config.dias_sem_contato_reenvio);
            $('#cfgLimite').val(resp.config.limite_envio_diario);
            $('#cfgAutomatico').prop('checked', resp.config.envio_automatico_ativo);
        });
    }

    $('#btnAbrirConfig').on('click', function () {
        carregarConfig();
        new bootstrap.Modal(document.getElementById('modalConfigReciclagem')).show();
    });

    $('#btnSalvarConfig').on('click', function () {
        const payload = {
            dias_sem_contato_reenvio: parseInt($('#cfgDias').val(), 10),
            limite_envio_diario: parseInt($('#cfgLimite').val(), 10),
            envio_automatico_ativo: $('#cfgAutomatico').is(':checked') ? 1 : 0,
            _token: csrf
        };
        $.ajax({
            url: urls.configSave,
            method: 'POST',
            data: payload,
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('modalConfigReciclagem')).hide();
                tabela.ajax.reload(null, false);
                refreshKPIs();
                Swal.fire({ icon: 'success', title: 'Salvo!', timer: 1800, showConfirmButton: false });
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Erro ao salvar configurações.';
                Swal.fire({ icon: 'error', title: 'Erro', text: msg });
            }
        });
    });

    // ---- Histórico (lazy: monta ao abrir a aba) ----
    let tabelaHist = null;
    function initHistorico() {
        if (tabelaHist) return;
        tabelaHist = $('#tabelaHistorico').DataTable({
            processing: true,
            serverSide: true,
            ajax: { url: urls.historico, type: 'GET' },
            order: [],
            columns: [
                { data: 'nome_cliente', render: d => d || '<span class="text-muted">N/D</span>' },
                { data: 'cpf', render: d => d || '<span class="text-muted">—</span>' },
                { data: 'origem', orderable: false, render: d => badgeOrigem(d) },
                { data: 'situacao_origem', orderable: false, render: d => d || '<span class="text-muted">—</span>' },
                { data: 'dias_inativo', render: d => (d != null ? `${d} dias` : '—') },
                { data: 'enviado_em' },
                { data: 'enviado_por', render: d => d || '<span class="text-muted">—</span>' }
            ],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
                sLengthMenu: '_MENU_',
                search: '',
                searchPlaceholder: 'Buscar...',
                info: 'Mostrando _START_ a _END_ de _TOTAL_',
                infoFiltered: '(filtrado de _MAX_)',
                processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Carregando...',
                zeroRecords: 'Nenhum envio registrado',
                emptyTable: 'Nenhum envio registrado ainda'
            }
        });
    }

    $('button[data-bs-target="#tab-historico"]').on('shown.bs.tab', function () {
        initHistorico();
    });
})();
