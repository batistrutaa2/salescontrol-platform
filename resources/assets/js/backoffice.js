'use strict';

$(function () {

    // ================= Helpers =================
    function parseBrDateTime(str) {
        if (!str) return null;
        const s = String(str).trim();
        // ISO ou YYYY-MM-DD HH:MM:SS
        if (/^\d{4}-\d{2}-\d{2}/.test(s)) return new Date(s.replace(' ', 'T'));
        // BR: dd/mm/yyyy HH:MM:SS
        const [d, t = '00:00:00'] = s.split(' ');
        const [day, month, year] = (d || '').split('/').map(Number);
        const [hour, minute, second = 0] = (t || '').split(':').map(Number);
        return new Date(year, (month || 1) - 1, day || 1, hour || 0, minute || 0, second || 0);
    }

    function escapeHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function truncate(s, n) {
        s = String(s || '');
        return s.length <= n ? s : s.slice(0, n) + '…';
    }

    function initTooltipsInTable() {
        const tableEl = document.querySelector('.datatables-ajax');
        if (!tableEl) return;
        const els = [].slice.call(tableEl.querySelectorAll('[data-bs-toggle="tooltip"]'));
        els.forEach(el => new bootstrap.Tooltip(el, { container: tableEl }));
    }

    let table;

    // ====== Filtro customizado (STATUS + MÊS/ANO baseado em created_at) ======
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!table || settings.nTable !== table.table().node()) return true;

        const row = table.row(dataIndex).data() || {};
        const statusSel = ($('#status_filter').val() || '').toString().trim().toUpperCase();
        const mesSel = parseInt($('#periodo_mes').val(), 10);
        const anoSel = parseInt($('#periodo_ano').val(), 10);

        if (statusSel && (String(row.descricao || '').toUpperCase() !== statusSel)) return false;

        if (mesSel || anoSel) {
            const dt = parseBrDateTime(row.created_at);
            if (!(dt instanceof Date) || isNaN(dt)) return false;
            if (mesSel && (dt.getMonth() + 1) !== mesSel) return false;
            if (anoSel && dt.getFullYear() !== anoSel) return false;
        }

        return true;
    });

    // ================= DataTable =================
    table = $('.datatables-ajax').DataTable({
        processing: true,
        serverSide: false,
        searching: true,
        ordering: false,
        ajax: {
            url: '/back-office/lista-vendas-filtro',
            dataSrc: 'data'
        },
        rowCallback: function (row, data) {
            const role = data.descricao || '';
            const updatedAt = parseBrDateTime(data.updated_at);
            let overdue = false;

            if (updatedAt && !isNaN(updatedAt)) {
                const diffHours = (new Date() - updatedAt) / 36e5;
                const diffDays = diffHours / 24;
                if (role === 'ANALISE DOCUMENTO' && diffHours > 48) overdue = true;
                else if (role === 'ANALISE OPERADORA' && diffDays > 10) overdue = true;
                else if (role === 'PENDENCIA' && diffHours > 48) overdue = true;
            }

            if (overdue) $('td', row).css({ color: '#dc3545', 'font-weight': '700' });
            else $('td', row).css({ color: '', 'font-weight': '' });
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'nome_contrato' },
            {
                data: 'descricao',
                render: function (data, type, full) {
                    const role = full.descricao || '';
                    const updatedAt = parseBrDateTime(full.updated_at);
                    let diffHours = 0, diffDays = 0;

                    if (updatedAt && !isNaN(updatedAt)) {
                        const now = new Date();
                        diffHours = (now - updatedAt) / 36e5;
                        diffDays = diffHours / 24;
                    }

                    let overdue = false;
                    if (role === 'ANALISE DOCUMENTO' && diffHours > 48) overdue = true;
                    else if (role === 'ANALISE OPERADORA' && diffDays > 10) overdue = true;
                    else if (role === 'PENDENCIA' && diffHours > 48) overdue = true;

                    const roleBadgeObj = {
                        'IMPLANTADO': '<i class="ri-user-line ri-22px text-primary me-2"></i>',
                        'VENDA': '<i class="ri-pie-chart-line ri-22px text-success me-2"></i>',
                        'ESTORNO': '<i class="ri-computer-line ri-22px text-danger me-2"></i>',     // fixo vermelho
                        'DEVELOPER': '<i class="ri-vip-crown-line ri-22px text-warning me-2"></i>',
                        'DECLINADO': '<i class="ri-close-circle-line ri-22px text-danger me-2"></i>', // fixo vermelho
                        'ANALISE DOCUMENTO': '<i class="ri-file-search-line ri-22px me-2"></i>',
                        'ANALISE OPERADORA': '<i class="ri-building-line ri-22px me-2"></i>',
                        'PENDENCIA': '<i class="ri-error-warning-line ri-22px text-warning me-2"></i>' // fixo amarelo
                    };

                    let icon = roleBadgeObj[role] || '<i class="ri-time-line ri-22px text-secondary me-2"></i>';

                    // só pinta de vermelho por atraso quando NÃO for pendência/estorno/declinado
                    if (!['PENDENCIA', 'ESTORNO', 'DECLINADO'].includes(role) && overdue) {
                        icon = icon
                            .replace('ri-22px', 'ri-22px text-danger')
                            .replace('text-secondary', 'text-danger')
                            .replace('text-primary', 'text-danger')
                            .replace('text-success', 'text-danger')
                            .replace('text-warning', 'text-danger')
                            .replace('text-danger text-danger', 'text-danger');
                    }

                    // estes 3 têm motivo -> o ÚNICO ícone vira botão clicável (tooltip + SweetAlert)
                    if (['PENDENCIA', 'DECLINADO', 'ESTORNO'].includes(role)) {
                        const motivoFull = full.motivo_pendencia || 'Motivo não informado';
                        const motivoEsc = escapeHtml(motivoFull);
                        const resumoEsc = escapeHtml(truncate(motivoFull, 140));

                        return `
                        <span class="text-truncate d-flex align-items-center text-heading">
                        <button type="button"
                                class="btn p-0 border-0 bg-transparent js-view-motivo"
                                aria-label="Ver motivo"
                                data-motivo="${motivoEsc}"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="${resumoEsc}">
                            ${icon}
                        </button>
                        <span class="ms-1">${role}</span>
                        </span>`;
                    }

                    // demais status: ícone estático + texto
                    return `<span class="text-truncate d-flex align-items-center text-heading">${icon}${role}</span>`;
                }
            },
            {
                data: 'valor_contrato',
                render: function (data) {
                    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(data);
                }
            },
            { data: 'created_at' },
            {
                data: 'prazo',
                render: function (data) {
                    const icon = '<i class="ri-time-fill ri-16px text-muted ms-1"></i>';
                    return data ? `${data} ${icon}` : `N/A ${icon}`;
                }
            },
            { data: 'updated_at' },
            {
                data: 'id',
                render: function (data, type, full) {
                    let actions =
                        '<div class="d-flex align-items-center">' +
                        '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ri-more-2-line ri-22px"></i></button>' +
                        '<div class="dropdown-menu dropdown-menu-end m-0">' +
                        '<a href="/back-office/abrir-contrato/' + full['id'] + '" class="dropdown-item"><i class="ri-edit-box-line me-2"></i><span>Ver contrato</span></a>' +
                        '<button type="button" class="dropdown-item js-alterar-status" data-bs-toggle="modal" data-bs-target="#modalcomments" data-id="' + full['id'] + '">' +
                        '<i class="ri-arrow-left-right-fill me-2"></i><span>Alterar Status</span>' +
                        '</button>';

                    if (full['descricao'] === 'IMPLANTADO') {
                        actions += '<a href="/back-office/comprovante/' + full['id'] + '" class="dropdown-item"><i class="ri-download-line me-2"></i><span>Baixar comprovante</span></a>';
                        actions += '<button type="button" class="dropdown-item js-gerar-recebivel" data-id="' + full['id'] + '" data-nome="' + escapeHtml(full['nome_contrato']) + '">' +
                            '<i class="ri-money-dollar-circle-line me-2"></i><span>Gerar Recebíveis</span>' +
                            '</button>';
                    }

                    actions +=
                        '<a href="/back-office/deletar-contrato/' + full['id'] + '" class="dropdown-item"><i class="ri-delete-bin-line me-2"></i><span>Excluir</span></a>' +
                        '</div>' +
                        '</div>';
                    return actions;
                }
            }
        ],
        // reativa tooltips após cada draw
        drawCallback: function () {
            initTooltipsInTable();
            updateMetrics();
        }
    });

    // ====== Filtros: disparam no change (sem botão Filtrar) ======
    $('#status_filter, #periodo_mes, #periodo_ano').on('change', function () {
        table.draw();
    });

    $('#btn_limpar_filtro').on('click', function () {
        $('#status_filter').val('');
        $('#periodo_mes').val('');
        $('#periodo_ano').val('');
        table.draw();
    });

    // ====== Modal status (mantido) ======
    $(document).on('click', '.open-status-modal', function () {
        const id = $(this).data('id');
        $('#idSale').val(id);
        $('#modalcomments').modal('show');
    });

    $(document).on('click', '.js-alterar-status', function () {
        const id = $(this).data('id');
        $('#idSale').val(id);
    });

    // ====== Mostrar/ocultar comprovante conforme status (mantido) ======
    $(document).on('change', '#label', function () {
        if ($(this).val() === '18') {
            $('#proof-group').show();
            $('#comprovante').prop('required', true);
            $('#proof-group-data-implantacao').show();
            $('#proof-group-numero_proposta').show();
            $('#data_implantacao').prop('required', true);
            $('#numero_proposta').prop('required', true);
            // Mostrar seção de acesso da empresa (opcional)
            $('#proof-group-acesso-empresa').slideDown(300);
        } else {
            $('#proof-group').hide();
            $('#comprovante').prop('required', false).val('');
            $('#proof-group-data-implantacao').hide();
            $('#proof-group-numero_proposta').hide();
            $('#data_implantacao').prop('required', false).val('');
            $('#numero_proposta').prop('required', false).val('');
            // Ocultar e limpar campos de acesso da empresa
            $('#proof-group-acesso-empresa').slideUp(300);
            $('#acesso_email, #acesso_senha, #acesso_cpf').val('');
        }

        if ($(this).val() === '55' || $(this).val() === '17' || $(this).val() === '53') {
            $('#proof-group-data-pendencia').show();
            $('#data_pendencia').prop('required', true);
        } else {
            $('#proof-group-data-pendencia').hide();
            $('#data_pendencia').prop('required', false).val('');
        }

        if ($(this).val() == '58') {
            $('#proof-group-boleto-disponivel').show();
            $('#boleto_disponivel').prop('required', true);
        } else {
            $('#proof-group-boleto-disponivel').hide();
            $('#boleto_disponivel').prop('required', false).val('');
        }
    });

    $(document).on('click', '.js-view-motivo', function (e) {
        e.preventDefault();
        const motivo = $(this).data('motivo') || 'Motivo não informado';
        Swal.fire({
            html: `<div class="text-start" style="white-space:pre-wrap">${escapeHtml(motivo)}</div>`,
            icon: 'warning',
            width: 700,
            confirmButtonText: 'Fechar',
            customClass: { confirmButton: 'btn btn-warning' },
            buttonsStyling: false
        });
    });

    // ====== Gerar Recebível para um Contrato Específico ======
    $(document).on('click', '.js-gerar-recebivel', function () {
        const vendaId = $(this).data('id');
        const nomeContrato = $(this).data('nome') || 'este contrato';

        // Primeiro, verificar se já existem recebíveis
        Swal.fire({
            title: 'Verificando...',
            text: 'Consultando informações do contrato',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: `/back-office/verificar-recebiveis/${vendaId}`,
            method: 'GET'
        }).then(response => {
            Swal.close();

            if (response.possui_recebiveis) {
                // Já possui recebíveis - mostrar alerta de atualização
                const valorFormatado = new Intl.NumberFormat('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                }).format(response.valor_total);

                Swal.fire({
                    title: '<i class="ri-error-warning-line text-warning"></i> Atenção!',
                    html: `
                        <div class="text-start">
                            <p class="mb-3">O contrato <strong>"${escapeHtml(nomeContrato)}"</strong> já possui recebíveis cadastrados:</p>
                            <div class="alert alert-warning d-flex align-items-center mb-3">
                                <i class="ri-information-line me-2 fs-4"></i>
                                <div>
                                    <strong>${response.quantidade} parcela(s)</strong> no valor total de <strong>${valorFormatado}</strong>
                                </div>
                            </div>
                            <p class="text-primary fw-bold mb-2">
                                <i class="ri-refresh-line me-1"></i>
                                Os valores serão ATUALIZADOS conforme as regras de comissionamento atuais.
                            </p>
                            <p class="text-muted small">Deseja continuar?</p>
                        </div>
                    `,
                    icon: null,
                    showCancelButton: true,
                    confirmButtonText: '<i class="ri-refresh-line me-1"></i> Atualizar Recebíveis',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        popup: 'swal-wide',
                        confirmButton: 'btn btn-warning me-2',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false,
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        executarGeracaoRecebiveis(vendaId, nomeContrato);
                    }
                });
            } else {
                // Não possui recebíveis - confirmação simples
                Swal.fire({
                    title: 'Gerar Recebíveis',
                    html: `
                        <div class="text-start">
                            <p>Deseja gerar os recebíveis para o contrato:</p>
                            <p class="fw-bold text-primary">"${escapeHtml(nomeContrato)}"</p>
                            <p class="text-muted small mt-3">Os recebíveis serão gerados com base nas regras de comissionamento configuradas.</p>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="ri-check-line me-1"></i> Gerar Recebíveis',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        confirmButton: 'btn btn-success me-2',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false,
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        executarGeracaoRecebiveis(vendaId, nomeContrato);
                    }
                });
            }
        }).catch(error => {
            Swal.fire({
                title: 'Erro',
                text: error.responseJSON?.message || 'Não foi possível verificar os recebíveis do contrato.',
                icon: 'error',
                confirmButtonText: 'OK',
                customClass: { confirmButton: 'btn btn-primary' },
                buttonsStyling: false
            });
        });
    });

    // Função auxiliar para executar a geração de recebíveis
    function executarGeracaoRecebiveis(vendaId, nomeContrato) {
        Swal.fire({
            title: 'Processando...',
            html: `Gerando recebíveis para <strong>"${escapeHtml(nomeContrato)}"</strong>`,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: `/back-office/gerar-recebivel/${vendaId}`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        }).then(response => {
            let iconHtml = '<i class="ri-checkbox-circle-line text-success"></i>';
            let alertClass = 'alert-success';

            if (response.atualizados > 0 && response.criados === 0) {
                iconHtml = '<i class="ri-refresh-line text-warning"></i>';
                alertClass = 'alert-warning';
            }

            Swal.fire({
                title: `${iconHtml} Sucesso!`,
                html: `
                    <div class="text-start">
                        <p>${escapeHtml(response.message)}</p>
                        <div class="alert ${alertClass} mt-3">
                            ${response.criados > 0 ? `<div><i class="ri-add-line me-1"></i> <strong>${response.criados}</strong> parcela(s) criada(s)</div>` : ''}
                            ${response.atualizados > 0 ? `<div><i class="ri-refresh-line me-1"></i> <strong>${response.atualizados}</strong> parcela(s) atualizada(s)</div>` : ''}
                        </div>
                    </div>
                `,
                icon: null,
                confirmButtonText: 'OK',
                customClass: { confirmButton: 'btn btn-success' },
                buttonsStyling: false
            });

            // Atualizar tabela
            table.ajax.reload();
        }).catch(error => {
            Swal.fire({
                title: 'Erro',
                text: error.responseJSON?.message || 'Falha ao gerar recebíveis.',
                icon: 'error',
                confirmButtonText: 'OK',
                customClass: { confirmButton: 'btn btn-primary' },
                buttonsStyling: false
            });
        });
    }

    // ================= Atualizar Métricas =================
    function updateMetrics() {
        if (!table) return;

        const allData = table.rows({ search: 'applied' }).data().toArray();
        const total = allData.length;

        // Contar por status
        const implantado = allData.filter(d => d.descricao === 'IMPLANTADO').length;
        const analise = allData.filter(d =>
            d.descricao === 'ANALISE DOCUMENTO' ||
            d.descricao === 'ANALISE OPERADORA'
        ).length;

        // Contar atrasados
        let atrasados = 0;
        allData.forEach(data => {
            const role = data.descricao || '';
            const updatedAt = parseBrDateTime(data.updated_at);
            if (updatedAt && !isNaN(updatedAt)) {
                const diffHours = (new Date() - updatedAt) / 36e5;
                const diffDays = diffHours / 24;

                if (role === 'ANALISE DOCUMENTO' && diffHours > 48) atrasados++;
                else if (role === 'ANALISE OPERADORA' && diffDays > 10) atrasados++;
                else if (role === 'PENDENCIA' && diffHours > 48) atrasados++;
            }
        });

        // Atualizar os cards
        $('#total-contratos').text(total);
        $('#total-implantado').text(implantado);
        $('#total-analise').text(analise);
        $('#total-atrasados').text(atrasados);
    }

    // ================= Click nos Cards para Filtrar =================
    $('.metric-card').on('click', function() {
        const cardElement = $(this);

        // Remover classe active de todos os cards
        $('.metric-card').removeClass('active-filter');

        // Adicionar classe active no card clicado
        cardElement.addClass('active-filter');

        // Identificar qual card foi clicado e aplicar filtro correspondente
        if (cardElement.find('#total-contratos').length) {
            // Card "Total de Contratos" - limpar todos os filtros
            $('#status_filter').val('');
            $('#periodo_mes').val('');
            $('#periodo_ano').val('');
        } else if (cardElement.find('#total-implantado').length) {
            // Card "Implantados"
            $('#status_filter').val('IMPLANTADO');
        } else if (cardElement.find('#total-analise').length) {
            // Card "Em Análise" - filtrar apenas ANALISE OPERADORA
            $('#status_filter').val('ANALISE OPERADORA');
        } else if (cardElement.find('#total-atrasados').length) {
            // Card "Atrasados" - limpar filtro (atrasados é calculado dinamicamente)
            $('#status_filter').val('');
        }

        // Aplicar o filtro
        table.draw();
    });

    // ====== Acesso Empresa - Toggle Password Visibility ======
    $(document).on('click', '#toggle-acesso-senha', function () {
        const $input = $('#acesso_senha');
        const $iconEye = $('#icon-eye');
        const $iconEyeOff = $('#icon-eye-off');

        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $iconEye.addClass('d-none');
            $iconEyeOff.removeClass('d-none');
        } else {
            $input.attr('type', 'password');
            $iconEye.removeClass('d-none');
            $iconEyeOff.addClass('d-none');
        }
    });

    // ====== Acesso Empresa - Máscara CPF ======
    function initAcessoCpfMask() {
        const cpfInput = document.querySelector('.acesso-mask-cpf');
        if (cpfInput && typeof Cleave !== 'undefined') {
            new Cleave(cpfInput, {
                delimiters: ['.', '.', '-'],
                blocks: [3, 3, 3, 2],
                numericOnly: true
            });
        }
    }

    // Inicializar máscara quando a modal abrir
    $('#modalcomments').on('shown.bs.modal', function () {
        initAcessoCpfMask();
    });

    // Limpar campos de acesso quando modal fechar
    $('#modalcomments').on('hidden.bs.modal', function () {
        $('#acesso_email, #acesso_senha, #acesso_cpf').val('');
        $('#proof-group-acesso-empresa').hide();
        // Reset password visibility
        $('#acesso_senha').attr('type', 'password');
        $('#icon-eye').removeClass('d-none');
        $('#icon-eye-off').addClass('d-none');
    });
});
