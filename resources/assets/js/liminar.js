'use strict';

(function () {

    // ----------------------------------------------------------------
    // State
    // ----------------------------------------------------------------
    let tabelaLiminares = null;
    let vendaSelecionada = null;
    let beneficiarioSelecionado = null;
    let liminarAtualId = null;

    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // ----------------------------------------------------------------
    // Flatpickr
    // ----------------------------------------------------------------
    const fpConfig = { dateFormat: 'd/m/Y', locale: 'pt', allowInput: true };
    document.querySelectorAll('.flatpickr-date').forEach(el => flatpickr(el, fpConfig));

    // ----------------------------------------------------------------
    // DataTable
    // ----------------------------------------------------------------
    function initTabela() {
        // O bundle datatables-bootstrap5.js inclui jquery-datatables-checkboxes,
        // que injeta referências circulares no estado interno do DataTables.
        // Quando DataTables tenta JSON.stringify esse estado em flushLogs (error
        // reporting), o processo falha. Substituímos o errMode por uma função
        // que só loga no console, sem tentar serializar o estado.
        if ($.fn.dataTable && $.fn.dataTable.ext) {
            $.fn.dataTable.ext.errMode = function (settings, techNote, message) {
                console.warn('[DataTables]', message);
            };
        }

        tabelaLiminares = $('#tabelaLiminares').DataTable({
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                $.ajax({
                    url: '/back-office/liminar/data',
                    method: 'GET',
                    data: {
                        draw:     data.draw,
                        columns:  data.columns,
                        order:    data.order,
                        start:    data.start,
                        length:   data.length,
                        search:   data.search,
                        status:      document.getElementById('filtroStatus').value,
                        fase:        document.getElementById('filtroFase').value,
                        data_inicio: document.getElementById('filtroDataInicio').value,
                        data_fim:    document.getElementById('filtroDataFim').value,
                    },
                    success: callback,
                    error: () => Swal.fire('Erro', 'Não foi possível carregar os dados.', 'error'),
                });
            },
            columns: [
                { data: 'nome_contrato',      title: 'Contrato' },
                { data: 'beneficiario_nome',  title: 'Beneficiário', orderable: false },
                { data: 'beneficiario_tipo_label', title: 'Tipo', orderable: false,
                    render: v => `<span class="badge-fase">${v}</span>` },
                { data: 'fase_label',         title: 'Fase', orderable: false,
                    render: v => v !== '—' ? `<span class="badge-fase">${v}</span>` : '<span class="text-muted">—</span>' },
                { data: 'status_label',       title: 'Status', orderable: false,
                    render: (v, _, row) => {
                        const cls = { 'Não Iniciada': 'nao-iniciada', 'Em Execução': 'em-execucao', 'Bloqueada': 'bloqueada', 'Concluída': 'concluida' };
                        return `<span class="badge-status ${cls[v] ?? ''}">${v}</span>`;
                    }
                },
                { data: 'honorarios_label',   title: 'Honorários', orderable: false },
                { data: 'recebimento_label',  title: 'Recebimento', orderable: false },
                { data: 'corretor_nome',      title: 'Corretor', orderable: false },
                { data: 'responsavel_nome',   title: 'Responsável', orderable: false },
                { data: 'data_criacao',       title: 'Criado em', orderable: false },
                {
                    data: null, title: 'Ações', orderable: false,
                    render: (_, __, row) =>
                        `<button class="lim-btn-acao btn-abrir" data-id="${row.id}" title="Ver detalhes">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>`,
                },
            ],
            order: [[9, 'desc']],
            language: {
                processing:     '<div class="dt-loading">Carregando...</div>',
                search:         'Buscar:',
                lengthMenu:     'Exibir _MENU_ registros',
                info:           'Exibindo _START_ a _END_ de _TOTAL_ registros',
                infoEmpty:      'Exibindo 0 a 0 de 0 registros',
                infoFiltered:   '(filtrado de _MAX_ registros)',
                loadingRecords: 'Carregando...',
                zeroRecords:    'Nenhum processo encontrado',
                emptyTable:     'Nenhum processo cadastrado',
                paginate: {
                    first:    '«',
                    previous: '‹',
                    next:     '›',
                    last:     '»',
                },
            },
            drawCallback: function () {
                atualizarKpis();
            },
        });
    }

    // ----------------------------------------------------------------
    // KPIs — busca separada após cada draw
    // ----------------------------------------------------------------
    function atualizarKpis() {
        fetch('/back-office/liminar/data?length=10000', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(json => {
                const data = json.data ?? [];
                document.getElementById('kpi-total').textContent = data.length;
                document.getElementById('kpi-execucao').textContent = data.filter(r => r.status === 'EM_EXECUCAO').length;
                document.getElementById('kpi-concedidas').textContent = data.filter(r => r.fase === 'LIMINAR_CONCEDIDA').length;
                document.getElementById('kpi-concluidos').textContent = data.filter(r => r.status === 'CONCLUIDA').length;
            })
            .catch(() => {});
    }

    // Atualiza visual da seção ao mudar o status no select (antes de salvar)
    document.getElementById('detalheStatus').addEventListener('change', function () {
        const nomeContrato = document.getElementById('detalheModalSubtitulo').textContent.split(' · ')[0];
        atualizarModalPorStatus(this.value, nomeContrato);
        // Reabilita o botão caso o usuário mude de volta de CONCLUIDA
        document.getElementById('detalheStatus').disabled = false;
    });

    // ----------------------------------------------------------------
    // Filtros
    // ----------------------------------------------------------------
    document.getElementById('btnFiltrar').addEventListener('click', () => {
        tabelaLiminares.ajax.reload();
    });

    // ----------------------------------------------------------------
    // Abrir modal de detalhes
    // ----------------------------------------------------------------
    function abrirDetalhes(id) {
        liminarAtualId = id;
        fetch(`/back-office/liminar/${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(json => {
                const l = json.liminar;

                document.getElementById('detalheId').value = l.id;
                document.getElementById('detalheStatus').value           = l.status;
                document.getElementById('detalheFase').value             = l.fase ?? '';
                document.getElementById('detalheDataEnvio').value        = l.data_envio ?? '';
                document.getElementById('detalheHonorarios').value       = l.status_honorarios;
                document.getElementById('detalheRecebimento').value      = l.status_recebimento;
                document.getElementById('detalheValorRecebimento').value  = l.valor_recebimento ?? '';
                document.getElementById('detalheObservacoes').value      = l.observacoes ?? '';
                document.getElementById('detalheResponsavelNome').value  = l.responsavel?.name ?? '';
                document.getElementById('detalheResponsavelId').value    = l.responsavel_id ?? '';

                // Atualiza header da modal e seção conforme status
                atualizarModalPorStatus(l.status, l.nome_contrato);

                renderDocumentos(json.documentos);
                renderHistorico(json.historico);

                // Reinit flatpickr no campo de data
                flatpickr('#detalheDataEnvio', fpConfig);

                // getOrCreateInstance reutiliza a instância existente em vez de empilhar
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalhes')).show();
            })
            .catch(() => Swal.fire('Erro', 'Não foi possível carregar os detalhes.', 'error'));
    }

    // ----------------------------------------------------------------
    // Render Documentos
    // ----------------------------------------------------------------
    function renderDocumentos(docs) {
        const container = document.getElementById('listaDocumentos');
        container.innerHTML = '';
        docs.forEach(d => {
            const item = document.createElement('div');
            item.className = 'lim-doc-item';
            item.dataset.id = d.id;
            item.innerHTML = `
                <div class="doc-info">
                    <span class="doc-tipo">${d.tipo_documento_label}</span>
                    <span class="doc-nome">${d.nome_original}</span>
                </div>
                <div class="doc-actions">
                    <button class="doc-btn btn-download" data-id="${d.id}" title="Download">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                    </button>
                    <button class="doc-btn btn-remove" data-id="${d.id}" title="Remover">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                        </svg>
                    </button>
                </div>`;
            container.appendChild(item);
        });

        container.querySelectorAll('.btn-download').forEach(btn => {
            btn.addEventListener('click', () => {
                window.location.href = `/back-office/liminar/${liminarAtualId}/documentos/${btn.dataset.id}/download`;
            });
        });
        container.querySelectorAll('.btn-remove').forEach(btn => {
            btn.addEventListener('click', () => removerDocumento(btn.dataset.id));
        });
    }

    // ----------------------------------------------------------------
    // Render Timeline Histórico
    // ----------------------------------------------------------------
    function renderHistorico(historico) {
        const container = document.getElementById('timelineHistorico');
        container.innerHTML = '';
        if (!historico.length) {
            container.innerHTML = '<p class="text-muted text-center" style="font-size:0.8125rem">Sem registros.</p>';
            return;
        }
        historico.forEach(h => {
            const item = document.createElement('div');
            item.className = 'lim-timeline-item';
            const campoLabel = { status: 'Status', fase: 'Fase', status_honorarios: 'Honorários', status_recebimento: 'Recebimento', documento: 'Documento' };
            item.innerHTML = `
                <div class="timeline-dot">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <div class="timeline-content">
                    <div class="timeline-title">${campoLabel[h.campo_alterado] ?? h.campo_alterado}: <strong>${h.valor_novo}</strong></div>
                    ${h.valor_anterior ? `<div class="timeline-detail">Anterior: ${h.valor_anterior}</div>` : ''}
                    ${h.observacao ? `<div class="timeline-detail">${h.observacao}</div>` : ''}
                    <div class="timeline-meta">${h.usuario_nome ?? '—'} · ${h.created_at}</div>
                </div>`;
            container.appendChild(item);
        });
    }

    // ----------------------------------------------------------------
    // Atualiza header da modal e label da seção conforme status
    // ----------------------------------------------------------------
    function atualizarModalPorStatus(status, nomeContrato) {
        const header    = document.getElementById('modalDetalhes').querySelector('.pv-modal-header');
        const subtitulo = document.getElementById('detalheModalSubtitulo');
        const secHeader = document.getElementById('detalheSecaoHeader');
        const secTexto  = document.getElementById('detalheSecaoTexto');

        const cfg = {
            NAO_INICIADA: {
                headerClass: 'pv-modal-header-primary',
                secClass:    'lim-section-primary',
                subtitulo:   `${nomeContrato} · Não Iniciada`,
                secLabel:    'Andamento do Processo',
            },
            EM_EXECUCAO: {
                headerClass: 'pv-modal-header-warning',
                secClass:    'lim-section-warning',
                subtitulo:   `${nomeContrato} · Em Execução`,
                secLabel:    'Andamento do Processo',
            },
            BLOQUEADA: {
                headerClass: 'pv-modal-header-warning',
                secClass:    'lim-section-warning',
                subtitulo:   `${nomeContrato} · Bloqueada`,
                secLabel:    'Processo Bloqueado',
            },
            CONCLUIDA: {
                headerClass: 'pv-modal-header-success',
                secClass:    'lim-section-success',
                subtitulo:   `${nomeContrato} · Processo Concluído`,
                secLabel:    'Processo Concluído',
            },
        };

        const c = cfg[status] ?? cfg.NAO_INICIADA;

        // Troca a classe de cor do header da modal
        header.className = `pv-modal-header ${c.headerClass}`;

        // Atualiza subtitle com nome do contrato + status
        subtitulo.textContent = c.subtitulo;

        // Atualiza label e cor da seção de andamento
        secHeader.className = `lim-modal-section-header ${c.secClass}`;
        secTexto.textContent = c.secLabel;

        // Todos os campos sempre editáveis — inclusive processos concluídos
        ['detalheStatus','detalheFase','detalheDataEnvio','detalheHonorarios',
         'detalheRecebimento','detalheValorRecebimento','detalheObservacoes'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.disabled = false;
        });
        const btnSalvar = document.getElementById('btnSalvarDetalhes');
        if (btnSalvar) {
            btnSalvar.disabled = false;
            btnSalvar.style.display = '';
        }
    }

    // ----------------------------------------------------------------
    // Recarregar apenas documentos e histórico (modal já aberta)
    // ----------------------------------------------------------------
    function recarregarConteudoModal(id) {
        fetch(`/back-office/liminar/${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(json => {
                renderDocumentos(json.documentos);
                renderHistorico(json.historico);
            })
            .catch(() => {});
    }

    // ----------------------------------------------------------------
    // Salvar alterações
    // ----------------------------------------------------------------
    document.getElementById('btnSalvarDetalhes').addEventListener('click', async () => {
        const id = document.getElementById('detalheId').value;
        const btn = document.getElementById('btnSalvarDetalhes');
        btn.disabled = true;

        const payload = {
            status:              document.getElementById('detalheStatus').value,
            fase:                document.getElementById('detalheFase').value || null,
            data_envio:          document.getElementById('detalheDataEnvio').value || null,
            status_honorarios:   document.getElementById('detalheHonorarios').value,
            status_recebimento:  document.getElementById('detalheRecebimento').value,
            valor_recebimento:   document.getElementById('detalheValorRecebimento').value || null,
            observacoes:         document.getElementById('detalheObservacoes').value || null,
        };

        try {
            const res = await fetch(`/back-office/liminar/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload),
            });
            const json = await res.json();
            if (json.success) {
                Swal.fire({ icon: 'success', title: 'Salvo!', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
                tabelaLiminares.ajax.reload(null, false);
                // Só recarrega docs/histórico — não reabre a modal
                recarregarConteudoModal(parseInt(id));
            } else {
                Swal.fire('Erro', json.message ?? 'Erro ao salvar.', 'error');
            }
        } catch (e) {
            Swal.fire('Erro', 'Falha na requisição.', 'error');
        } finally {
            btn.disabled = false;
        }
    });

    // ----------------------------------------------------------------
    // Upload de Documento
    // ----------------------------------------------------------------
    const fileDropZone = document.getElementById('fileDropZone');
    const inputArquivo = document.getElementById('inputArquivo');

    inputArquivo.addEventListener('change', () => {
        if (inputArquivo.files[0]) uploadDocumento(inputArquivo.files[0]);
    });

    fileDropZone.addEventListener('dragover', e => { e.preventDefault(); fileDropZone.classList.add('dragover'); });
    fileDropZone.addEventListener('dragleave', () => fileDropZone.classList.remove('dragover'));
    fileDropZone.addEventListener('drop', e => {
        e.preventDefault();
        fileDropZone.classList.remove('dragover');
        if (e.dataTransfer.files[0]) uploadDocumento(e.dataTransfer.files[0]);
    });

    function uploadDocumento(arquivo) {
        const tipo = document.getElementById('uploadTipoDoc').value;
        if (!tipo) { Swal.fire('Atenção', 'Selecione o tipo de documento antes de enviar.', 'warning'); return; }
        if (!liminarAtualId) return;

        const form = new FormData();
        form.append('tipo_documento', tipo);
        form.append('arquivo', arquivo);
        form.append('_token', CSRF);

        Swal.fire({ title: 'Enviando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch(`/back-office/liminar/${liminarAtualId}/documentos`, { method: 'POST', body: form })
            .then(r => r.json())
            .then(json => {
                Swal.close();
                if (json.success) {
                    Swal.fire({ icon: 'success', title: 'Documento enviado!', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                    recarregarConteudoModal(liminarAtualId);
                } else {
                    Swal.fire('Erro', json.message ?? 'Falha no upload.', 'error');
                }
            })
            .catch(() => { Swal.close(); Swal.fire('Erro', 'Falha no envio.', 'error'); });
    }

    function removerDocumento(docId) {
        Swal.fire({
            title: 'Remover documento?',
            text: 'Esta ação não pode ser desfeita.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, remover',
            cancelButtonText: 'Cancelar',
        }).then(result => {
            if (!result.isConfirmed) return;
            fetch(`/back-office/liminar/${liminarAtualId}/documentos/${docId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(r => r.json())
                .then(json => {
                    if (json.success) {
                        Swal.fire({ icon: 'success', title: 'Removido!', toast: true, position: 'top-end', showConfirmButton: false, timer: 1800 });
                        recarregarConteudoModal(liminarAtualId);
                    }
                })
                .catch(() => Swal.fire('Erro', 'Falha ao remover.', 'error'));
        });
    }

    // ----------------------------------------------------------------
    // Modal Novo Processo
    // ----------------------------------------------------------------
    document.getElementById('btnNovoProcesso').addEventListener('click', () => {
        resetModalNovo();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalNovoProcesso')).show();
    });

    function resetModalNovo() {
        vendaSelecionada = null;
        beneficiarioSelecionado = null;
        document.getElementById('passo1').style.display = '';
        document.getElementById('passo2').style.display = 'none';
        document.getElementById('buscaContrato').value = '';
        document.getElementById('resultadosContratos').innerHTML = '';
        document.getElementById('btnVoltarPasso1').style.display = 'none';
        document.getElementById('btnConfirmarNovo').style.display = 'none';
        document.getElementById('btnCancelarNovo').style.display = '';
    }

    document.getElementById('btnBuscarContrato').addEventListener('click', buscarContratos);
    document.getElementById('buscaContrato').addEventListener('keydown', e => { if (e.key === 'Enter') buscarContratos(); });

    function buscarContratos() {
        const q = document.getElementById('buscaContrato').value.trim();
        if (q.length < 2) { Swal.fire('Atenção', 'Digite ao menos 2 caracteres.', 'warning'); return; }

        fetch(`/back-office/pos-venda/data?search[value]=${encodeURIComponent(q)}&length=20`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(json => {
                const container = document.getElementById('resultadosContratos');
                container.innerHTML = '';
                const data = json.data ?? [];
                if (!data.length) {
                    container.innerHTML = '<p class="text-muted text-center mt-2" style="font-size:0.8125rem">Nenhum contrato encontrado.</p>';
                    return;
                }
                data.forEach(c => {
                    const item = document.createElement('div');
                    item.className = 'lim-contrato-item';
                    item.innerHTML = `
                        <div>
                            <div class="contrato-nome">${c.nome_contrato ?? '—'}</div>
                            <div class="contrato-info">Proposta: ${c.numero_proposta ?? '—'} · CPF/CNPJ: ${c.cpf_cnpj ?? '—'}</div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>`;
                    item.addEventListener('click', () => selecionarContrato(c));
                    container.appendChild(item);
                });
            })
            .catch(() => Swal.fire('Erro', 'Falha ao buscar contratos.', 'error'));
    }

    function selecionarContrato(contrato) {
        vendaSelecionada = contrato;

        document.getElementById('infoContratoSelecionado').innerHTML =
            `<strong>${contrato.nome_contrato}</strong> — Proposta: ${contrato.numero_proposta ?? '—'}`;

        document.getElementById('passo1').style.display = 'none';
        document.getElementById('passo2').style.display = '';
        document.getElementById('btnVoltarPasso1').style.display = '';

        fetch(`/back-office/pos-venda/liminar/beneficiarios/${contrato.id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(json => renderBeneficiarios(json.beneficiarios ?? []))
            .catch(() => Swal.fire('Erro', 'Falha ao carregar beneficiários.', 'error'));
    }

    function renderBeneficiarios(bens) {
        const container = document.getElementById('listaBeneficiarios');
        container.innerHTML = '';
        if (!bens.length) {
            container.innerHTML = '<p class="text-muted text-center" style="font-size:0.8125rem">Nenhum beneficiário encontrado.</p>';
            return;
        }
        bens.forEach(b => {
            const item = document.createElement('div');
            item.className = `lim-beneficiario-item${b.tem_liminar_ativa ? ' disabled' : ''}`;
            item.dataset.id   = b.id;
            item.dataset.tipo = b.tipo;
            item.dataset.nome = b.nome;
            item.innerHTML = `
                <input type="radio" name="beneficiarioSelecionado" value="${b.id}" ${b.tem_liminar_ativa ? 'disabled' : ''}>
                <div>
                    <div class="ben-nome">${b.nome}</div>
                    <div class="ben-tipo">${b.tipo_label}</div>
                </div>
                ${b.tem_liminar_ativa ? '<span class="ben-badge">Liminar Ativa</span>' : ''}`;

            if (!b.tem_liminar_ativa) {
                item.addEventListener('click', () => {
                    container.querySelectorAll('.lim-beneficiario-item').forEach(el => el.classList.remove('selected'));
                    item.classList.add('selected');
                    item.querySelector('input').checked = true;
                    beneficiarioSelecionado = { id: b.id, tipo: b.tipo, nome: b.nome };
                    document.getElementById('btnConfirmarNovo').style.display = '';
                });
            }
            container.appendChild(item);
        });
    }

    document.getElementById('btnVoltarPasso1').addEventListener('click', () => {
        document.getElementById('passo1').style.display = '';
        document.getElementById('passo2').style.display = 'none';
        document.getElementById('btnVoltarPasso1').style.display = 'none';
        document.getElementById('btnConfirmarNovo').style.display = 'none';
        beneficiarioSelecionado = null;
    });

    document.getElementById('btnConfirmarNovo').addEventListener('click', async () => {
        if (!vendaSelecionada || !beneficiarioSelecionado) return;

        const btn = document.getElementById('btnConfirmarNovo');
        btn.disabled = true;

        try {
            const res = await fetch('/back-office/liminar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    venda_id:          vendaSelecionada.id,
                    beneficiario_tipo: beneficiarioSelecionado.tipo,
                    beneficiario_id:   beneficiarioSelecionado.id,
                }),
            });
            const json = await res.json();
            if (json.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalNovoProcesso')).hide();
                Swal.fire({ icon: 'success', title: 'Processo iniciado!', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
                tabelaLiminares.ajax.reload();
                setTimeout(() => abrirDetalhes(json.id), 800);
            } else {
                Swal.fire('Erro', json.message ?? 'Falha ao criar processo.', 'error');
            }
        } catch (e) {
            Swal.fire('Erro', 'Falha na requisição.', 'error');
        } finally {
            btn.disabled = false;
        }
    });

    // ----------------------------------------------------------------
    // Auto-abrir modal Novo Processo se vier com ?venda_id=X na URL
    // ----------------------------------------------------------------
    function verificarParamsURL() {
        const params = new URLSearchParams(window.location.search);
        const vendaId = params.get('venda_id');
        if (!vendaId) return;

        // Remove o param da URL sem recarregar
        const url = new URL(window.location.href);
        url.searchParams.delete('venda_id');
        window.history.replaceState({}, '', url.toString());

        // Abre o modal e busca o contrato diretamente pelo ID
        resetModalNovo();
        new bootstrap.Modal(document.getElementById('modalNovoProcesso')).show();

        fetch(`/back-office/pos-venda/liminar/beneficiarios/${vendaId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(json => {
                // Busca info do contrato para exibir no passo 2
                fetch(`/back-office/liminar/data?venda_id=${vendaId}&length=1`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(r2 => r2.json())
                    .then(j2 => {
                        const c = j2.data?.[0];
                        vendaSelecionada = { id: parseInt(vendaId), nome_contrato: c?.nome_contrato ?? `Contrato #${vendaId}` };
                        document.getElementById('infoContratoSelecionado').innerHTML =
                            `<strong>${vendaSelecionada.nome_contrato}</strong>`;
                    })
                    .catch(() => {
                        vendaSelecionada = { id: parseInt(vendaId), nome_contrato: `Contrato #${vendaId}` };
                        document.getElementById('infoContratoSelecionado').innerHTML =
                            `<strong>Contrato #${vendaId}</strong>`;
                    })
                    .finally(() => {
                        document.getElementById('passo1').style.display = 'none';
                        document.getElementById('passo2').style.display = '';
                        document.getElementById('btnVoltarPasso1').style.display = '';
                        renderBeneficiarios(json.beneficiarios ?? []);
                    });
            })
            .catch(() => {});
    }

    // ----------------------------------------------------------------
    // Init
    // ----------------------------------------------------------------
    initTabela();

    // Event delegation para botões da tabela — configurado UMA vez,
    // funciona mesmo após redraws do DataTables
    $('#tabelaLiminares tbody').on('click', '.btn-abrir', function () {
        abrirDetalhes(parseInt(this.dataset.id));
    });

    verificarParamsURL();

})();
