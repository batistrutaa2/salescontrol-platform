'use strict';

$(document).ready(function () {
  const endpoint = '/vendas/getResultsBroker';
  let faseCarteira = 'todos';
  const STATUS = Object.freeze({
    VENDA: 'VENDA',
    ANALISE_DOCUMENTOS: 'ANALISE_DOCUMENTOS',
    PENDENCIA: 'PENDENCIA',
    ANALISE_OPERADORA: 'ANALISE_OPERADORA',
    CONTRATO_GERADO_AGUARDANDO_ASSINATURA: 'CONTRATO_GERADO_AGUARDANDO_ASSINATURA',
    AGUARDANDO_ASSINATURA_DS: 'AGUARDANDO_ASSINATURA_DS',
    BOLETO_DISPONIVEL: 'BOLETO_DISPONIVEL',
    REGULARIZADO: 'REGULARIZADO',
    IMPLANTADO: 'IMPLANTADO',
    ESTORNO: 'ESTORNO',
    DECLINADO: 'DECLINADO'
  });

  const normalizeStatusCode = value => String(value || '').trim().toUpperCase();

  const formatMoeda = (valor) => new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
    minimumFractionDigits: 2
  }).format(valor ?? 0);

  // helpers p/ tooltip + modal
  function escapeHtml(s) {
    return String(s || '')
      .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
      .replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
  function truncate(s, n) {
    s = String(s || '');
    return s.length <= n ? s : s.slice(0, n) + '…';
  }
  function initTooltipsInDetailedTable() {
    const tableEl = document.querySelector('#tabela-vendas-detalhadas');
    if (!tableEl) return;
    const els = [].slice.call(tableEl.querySelectorAll('[data-bs-toggle="tooltip"]'));
    els.forEach(el => new bootstrap.Tooltip(el, { container: tableEl }));
  }

  function fetchData(dataInicio, dataFim) {
    if (!dataInicio || !dataFim) return;

    $.ajax({
      url: endpoint,
      method: 'GET',
      data: { data_inicio: dataInicio, data_fim: dataFim },
      beforeSend: function () {
        $('.js-valorCadastrado, .js-implantado, .js-quantidadeContatosImportados, .js-conversao').text('...');
      },
      success: function (res) {
        $('.js-valorCadastrado').text(formatMoeda(res.vendasCadastradasMes));
        $('.js-implantado').text(formatMoeda(res.vendasImplantadasMes ?? '0'));
        $('.js-quantidadeContatosImportados').text(res.quantidadeContatosMes ?? '0');
        $('.js-conversao').text(res.conversao ?? '0%');

        const renderStatus = function (data, type, row) {
          const label = String(data || 'N/D');
          const labelEsc = escapeHtml(label);
          const code = normalizeStatusCode(row.codigo);
          if (type !== 'display') return label;
          const motivoFull = row.motivo_pendencia || 'Motivo não informado';
          const motivoEsc = escapeHtml(motivoFull);
          const resumoEsc = escapeHtml(truncate(motivoFull, 140));

          const icons = {
            [STATUS.PENDENCIA]: '<i class="ri-error-warning-line ri-22px text-warning me-2"></i>',
            [STATUS.DECLINADO]: '<i class="ri-close-circle-line ri-22px text-danger me-2"></i>',
            [STATUS.ESTORNO]: '<i class="ri-computer-line ri-22px text-danger me-2"></i>',
            [STATUS.VENDA]: '<i class="ri-pie-chart-line ri-22px text-success me-2"></i>',
            [STATUS.IMPLANTADO]: '<i class="ri-user-line ri-22px text-primary me-2"></i>',
            [STATUS.ANALISE_DOCUMENTOS]: '<i class="ri-file-search-line ri-22px me-2"></i>',
            [STATUS.ANALISE_OPERADORA]: '<i class="ri-building-line ri-22px me-2"></i>',
            [STATUS.BOLETO_DISPONIVEL]: '<i class="ri-bill-line ri-22px text-info me-2"></i>'
          };
          const defaultIcon = '<i class="ri-time-line ri-22px text-secondary me-2"></i>';
          const icon = icons[code] || defaultIcon;

          // PENDÊNCIA / DECLINADO / ESTORNO: botão com tooltip do motivo
          if ([STATUS.PENDENCIA, STATUS.DECLINADO, STATUS.ESTORNO].includes(code)) {
            return `
      <span class="d-flex align-items-center text-heading">
        <button type="button"
                class="btn p-0 border-0 bg-transparent js-view-motivo"
                aria-label="Ver motivo"
                data-motivo="${motivoEsc}"
                data-bs-toggle="tooltip"
                data-bs-placement="top"
                title="${resumoEsc}">
          ${icon}
        </button>
        <span class="ms-1">${labelEsc}</span>
      </span>`;
          }

          // BOLETO DISPONIVEL: mostra botão de download quando houver path
          if (code === STATUS.BOLETO_DISPONIVEL && row.path_boleto_disponivel) {
            // Opção A (via rota backend segura): /vendas/boletos/{id}
            const href = `/vendas/boletos/${row.id}`;

            // Se você preferir servir direto do storage público, use:
            // const href = `/storage/${row.path_boleto_disponivel}`;

            return `
            <span class="d-flex align-items-center text-heading">
              ${icon}<span class="me-2">${labelEsc}</span>
              <a href="${href}"
                class="btn btn-sm btn-outline-primary"
                target="_blank" rel="noopener"
                data-bs-toggle="tooltip"
                data-bs-placement="top"
                title="Baixar boleto">
                <i class="ri-download-2-line"></i>
              </a>
            </span>`;
          }

          // IMPLANTADO: mostra flag de boas vindas
          if (code === STATUS.IMPLANTADO) {
            const boasVindasEnviado = row.boas_vindas_enviado_em;
            const boasVindasFlag = boasVindasEnviado
              ? `<span class="badge bg-label-success ms-2" data-bs-toggle="tooltip" title="Boas Vindas enviado">
                   <i class="ri-mail-check-line me-1"></i>BV OK
                 </span>`
              : `<span class="badge bg-label-warning ms-2" data-bs-toggle="tooltip" title="Boas Vindas pendente">
                   <i class="ri-mail-close-line me-1"></i>BV Pendente
                 </span>`;
            return `<span class="d-flex align-items-center text-heading">${icon}${labelEsc}${boasVindasFlag}</span>`;
          }

          // Demais: ícone estático + texto
          return `<span class="d-flex align-items-center text-heading">${icon}${labelEsc}</span>`;
        };

        const renderDocumentacao = function (data, type, row) {
          const quantidade = Number(row.documentos_count || 0);
          const status = String(data || 'PENDENTE').toUpperCase();
          if (type !== 'display') return `${status} ${quantidade}`;

          if (quantidade === 0) {
            return '<span class="lv-doc-status is-empty"><i class="ri-file-add-line" aria-hidden="true"></i><span><strong>Sem documentos</strong><small>Pendente de envio</small></span></span>';
          }

          const estados = {
            PENDENTE: { classe: 'is-waiting', icone: 'ri-time-line', titulo: 'Recebido', detalhe: 'Aguardando servidor' },
            PROCESSANDO: { classe: 'is-processing', icone: 'ri-loader-4-line', titulo: 'Processando', detalhe: 'Verificação em andamento' },
            DISPONIVEL: { classe: 'is-success', icone: 'ri-checkbox-circle-line', titulo: 'Disponível', detalhe: 'Envio concluído' },
            COM_FALHA: { classe: 'is-error', icone: 'ri-error-warning-line', titulo: 'Com falha', detalhe: 'Verifique os arquivos' }
          };
          const estado = estados[status] || estados.PENDENTE;
          const arquivos = `${quantidade} ${quantidade === 1 ? 'arquivo' : 'arquivos'}`;
          return `<span class="lv-doc-status ${estado.classe}" title="${arquivos}"><i class="${estado.icone}" aria-hidden="true"></i><span><strong>${estado.titulo} <b>${quantidade}</b></strong><small>${estado.detalhe}</small></span></span>`;
        };


        if ($.fn.DataTable.isDataTable('#tabela-vendas-detalhadas')) {
          const dt = $('#tabela-vendas-detalhadas').DataTable();
          dt.clear().rows.add(res.vendas).draw();
        } else {
          $('#tabela-vendas-detalhadas').DataTable({
            destroy: true,
            data: res.vendas,
            columns: [
              { data: 'id' },
              { data: 'nome_contrato' },
              { data: 'descricao', render: renderStatus },
              { data: 'documentacao_status', render: renderDocumentacao },
              {
                data: 'backoffice_nome',
                render: function (data, type, row) {
                  if (!data) {
                    return '<span class="text-muted fst-italic">Não atribuído</span>';
                  }
                  return `<span class="d-flex align-items-center">
                    <i class="ri-user-settings-line text-primary me-2"></i>
                    ${escapeHtml(data)}
                  </span>`;
                }
              },
              {
                data: 'valor_contrato',
                render: function (d, type, row) {
                  let valor = parseFloat(d) || 0;
                  const ano = row.created_at ? new Date(row.created_at).getFullYear() : 0;
                  if (row.angariacao_status === 'SIM') {
                    valor += parseFloat(row.angariacao_valor) || 0;
                  }
                  return formatMoeda(valor);
                }
              },
              {
                data: null,
                orderable: false,
                render: function (data, type, row) {
                  return `
                    <button type="button" class="lv-btn-view btn-visualizar-venda"
                            data-venda-id="${row.id}"
                            aria-label="Ver detalhes do contrato ${escapeHtml(row.nome_contrato)}">
                      <i class="ri-eye-line" aria-hidden="true"></i><span>Ver contrato</span>
                    </button>
                  `;
                }
              }
            ],
            language: {
              url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
            },
            drawCallback: function () {
              initTooltipsInDetailedTable();
            }
          });
        }
      },
      error: function (err) {
        toastr.error('Erro ao carregar dados do relatório.');
      }
    });
  }

  // clique para abrir motivo completo (SweetAlert)
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

  // helpers de data
  function formatDateToBackend(date) {
    const d = new Date(date);
    const ano = d.getFullYear();
    const mes = String(d.getMonth() + 1).padStart(2, '0');
    const dia = String(d.getDate()).padStart(2, '0');
    return `${ano}-${mes}-${dia}`;
  }

  // Campos independentes de data, com máscara e validação do intervalo.
  const now = new Date();
  const ano = now.getFullYear();
  const mes = now.getMonth(); // 0-indexed
  const primeiroDiaMes = `${String(1).padStart(2, '0')}/${String(mes + 1).padStart(2, '0')}/${ano}`;
  const ultimoDia = new Date(ano, mes + 1, 0).getDate();
  const ultimoDiaMes = `${String(ultimoDia).padStart(2, '0')}/${String(mes + 1).padStart(2, '0')}/${ano}`;

  function aplicarMascaraData(valor) {
    const digitos = String(valor || '').replace(/\D/g, '').slice(0, 8);
    return digitos.replace(/^(\d{2})(\d)/, '$1/$2').replace(/^(\d{2}\/\d{2})(\d)/, '$1/$2');
  }

  function parseDataBr(valor) {
    const match = String(valor || '').match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (!match) return null;
    const data = new Date(Number(match[3]), Number(match[2]) - 1, Number(match[1]));
    return data.getFullYear() === Number(match[3]) && data.getMonth() === Number(match[2]) - 1 && data.getDate() === Number(match[1]) ? data : null;
  }

  function exibirErroPeriodo(mensagem = '') {
    $('#lv-periodo-erro').text(mensagem);
    $('#filtro-data-inicio, #filtro-data-fim').attr('aria-invalid', mensagem ? 'true' : 'false');
  }

  function validarPeriodoECarregar() {
    const valorInicio = $('#filtro-data-inicio').val();
    const valorFim = $('#filtro-data-fim').val();
    const inicio = parseDataBr(valorInicio);
    const fim = parseDataBr(valorFim);
    if (!inicio || !fim) {
      if (valorInicio || valorFim) exibirErroPeriodo('Informe as duas datas completas no formato DD/MM/AAAA.');
      return;
    }
    if (fpFim) fpFim.set('minDate', inicio);
    if (fim < inicio) {
      exibirErroPeriodo('A data final deve ser igual ou posterior à data inicial.');
      return;
    }
    exibirErroPeriodo();
    fetchData(formatDateToBackend(inicio), formatDateToBackend(fim));
  }

  let fpFim;
  const fpInicio = flatpickr('#filtro-data-inicio', {
    dateFormat: 'd/m/Y',
    locale: 'pt',
    allowInput: true,
    defaultDate: primeiroDiaMes,
    onChange: function (selectedDates) {
      if (selectedDates[0] && fpFim) fpFim.set('minDate', selectedDates[0]);
      validarPeriodoECarregar();
    }
  });

  fpFim = flatpickr('#filtro-data-fim', {
    dateFormat: 'd/m/Y',
    locale: 'pt',
    allowInput: true,
    minDate: fpInicio.selectedDates[0],
    defaultDate: ultimoDiaMes,
    onChange: validarPeriodoECarregar
  });

  $('.js-date-mask').on('input', function () {
    this.value = aplicarMascaraData(this.value);
    exibirErroPeriodo();
  }).on('blur', validarPeriodoECarregar);

  $('.lv-date-trigger').on('click', function () {
    ($(this).data('calendar-target') === 'inicio' ? fpInicio : fpFim).open();
  });

  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== 'tabela-vendas-detalhadas' || faseCarteira === 'todos') return true;
    const row = settings.aoData[dataIndex]?._aData;
    const implantado = normalizeStatusCode(row?.codigo) === STATUS.IMPLANTADO;
    return faseCarteira === 'implantados' ? implantado : !implantado;
  });

  $('.lv-portfolio-option').on('click', function () {
    faseCarteira = $(this).data('fase');
    $('.lv-portfolio-option').removeClass('is-active').attr('aria-pressed', 'false');
    $(this).addClass('is-active').attr('aria-pressed', 'true');
    if ($.fn.DataTable.isDataTable('#tabela-vendas-detalhadas')) $('#tabela-vendas-detalhadas').DataTable().draw();
  });

  document.querySelectorAll('#vendaDetailsTabs [data-bs-toggle="tab"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', () => {
      document.getElementById('venda-modal-scroll')?.scrollTo({ top: 0, behavior: 'auto' });
    });
  });

  // carregar dados iniciais (mês atual)
  fetchData(
    `${ano}-${String(mes + 1).padStart(2, '0')}-01`,
    `${ano}-${String(mes + 1).padStart(2, '0')}-${String(ultimoDia).padStart(2, '0')}`
  );

  // Handler para visualizar venda
  $(document).on('click', '.btn-visualizar-venda', function () {
    const vendaId = $(this).data('venda-id');
    abrirModalVisualizarVenda(vendaId);
  });

  // Função para abrir modal e carregar dados da venda
  function abrirModalVisualizarVenda(vendaId) {
    const modal = new bootstrap.Modal(document.getElementById('modalVisualizarVenda'));
    modal.show();

    // Mostrar loading
    $('#venda-loading').removeClass('d-none');
    $('#venda-content').addClass('d-none');
    $('#venda-modal-id').text(vendaId);
    bootstrap.Tab.getOrCreateInstance(document.getElementById('tab-contrato')).show();

    // Carregar dados da venda
    $.ajax({
      url: `/vendas/detalhes/${vendaId}`,
      method: 'GET',
      success: function (data) {
        preencherModalVenda(data);
        window.VendaDocumentos?.mount(document.querySelector('#panel-documentos [data-venda-documentos]'), vendaId);
        $('#venda-loading').addClass('d-none');
        $('#venda-content').removeClass('d-none');

        // Carregar histórico da venda
        carregarHistoricoVenda(vendaId);
      },
      error: function (err) {
        toastr.error('Erro ao carregar detalhes da venda.');
        $('#venda-loading').addClass('d-none');
        modal.hide();
      }
    });
  }

  // Função para preencher modal com dados da venda
  function preencherModalVenda(venda) {
    // Informações do Contrato
    $('#venda-nome-contrato').text(venda.nome_contrato || '-');
    $('#venda-cpf-cnpj').text(venda.cpf_cnpj || '-');
    $('#venda-telefone').text(venda.telefone1 || '-');

    // Status badge com novo estilo clean
    const statusBadge = $('#venda-status-badge');
    const statusText = String(venda.descricao || 'N/D');
    const statusCode = normalizeStatusCode(venda.codigo);
    statusBadge.removeClass('lv-status-badge success warning danger primary');
    statusBadge.addClass('lv-status-badge');

    switch (statusCode) {
      case STATUS.IMPLANTADO:
      case STATUS.REGULARIZADO:
        statusBadge.addClass('success');
        break;
      case STATUS.PENDENCIA:
      case STATUS.ANALISE_OPERADORA:
      case STATUS.ANALISE_DOCUMENTOS:
      case STATUS.BOLETO_DISPONIVEL:
        statusBadge.addClass('warning');
        break;
      case STATUS.ESTORNO:
      case STATUS.DECLINADO:
        statusBadge.addClass('danger');
        break;
      default:
        statusBadge.addClass('primary');
    }
    statusBadge.text(statusText);

    // Backoffice responsável
    const backofficeCard = $('#backoffice-card');
    const backofficeNome = venda.backoffice_nome;
    if (backofficeNome) {
      backofficeCard.removeClass('empty');
      $('#backoffice-avatar').text(backofficeNome.charAt(0).toUpperCase());
      $('#venda-backoffice-nome').text(backofficeNome);
    } else {
      backofficeCard.addClass('empty');
      $('#backoffice-avatar').html('<i class="ri-user-line"></i>');
      $('#venda-backoffice-nome').text('Não atribuído');
    }

    $('#venda-angariacao').text(venda.angariacao_status === 'SIM' ? 'Angariação' : 'Normal');
    $('#venda-angariacao-valor').text(venda.angariacao_status === 'SIM' ? formatMoeda(venda.angariacao_valor) : 'Não se aplica');
    $('#venda-data-vigencia').text(venda.data_vigencia ? formatarData(venda.data_vigencia) : '-');
    $('#venda-data-implantacao').text(venda.data_implantacao ? formatarData(venda.data_implantacao) : '-');
    $('#venda-valor').text(formatMoeda(venda.valor_contrato));
    $('#venda-vendedor').text(venda.vendedor_nome || '-');
    $('#venda-numero-proposta').text(venda.numero_proposta || '-');

    $('#venda-empresa-nome').text(venda.nome_contrato || '-');
    $('#venda-empresa-documento').text(venda.cpf_cnpj || '-');
    $('#venda-empresa-email').text(venda.email || '-');
    $('#venda-empresa-telefone1').text(venda.telefone1 || '-');
    $('#venda-empresa-telefone2').text(venda.telefone2 || '-');
    $('#venda-tipo-empresa').text(venda.tipo_empresa || '-');
    $('#venda-tipo-contrato').text(venda.tipo_contrato || '-');
    $('#venda-observacoes').text(venda.obs_contrato || 'Nenhuma observação cadastrada.');

    $('#venda-plano-operadora').text(venda.operadora || 'Operadora não informada');
    $('#venda-plano-nome').text(venda.nome_plano || 'Plano não informado');
    $('#venda-plano-valor').text(formatMoeda(venda.valor_contrato));
    $('#venda-plano-coparticipacao').text(formatCoparticipacao(venda.coparticipacao));
    $('#venda-plano-vidas').text(venda.vidas || '-');
    $('#venda-plano-vigencia').text(venda.data_vigencia ? formatarData(venda.data_vigencia) : '-');
    $('#venda-plano-implantacao').text(venda.data_implantacao ? formatarData(venda.data_implantacao) : 'Em implantação');
    $('#venda-plano-proposta').text(venda.numero_proposta || '-');
    $('#venda-plano-modalidade').text(venda.angariacao_status === 'SIM' ? 'Com angariação' : 'Venda regular');

    renderizarBeneficiarios(venda.titulares || []);
  }

  function pessoaInfo(label, value) {
    return `<div class="lv-person-field"><span>${escapeHtml(label)}</span><strong>${escapeHtml(value || '-')}</strong></div>`;
  }

  function renderizarBeneficiarios(titulares) {
    const container = $('#venda-beneficiarios').empty();
    const totalDependentes = titulares.reduce((total, titular) => total + (titular.dependentes || []).length, 0);
    $('#venda-beneficiarios-count').text(titulares.length + totalDependentes);
    $('#venda-beneficiarios-vazio').toggleClass('d-none', titulares.length > 0);

    titulares.forEach((titular, index) => {
      const dependentes = titular.dependentes || [];
      const dependentesHtml = dependentes.length ? `
        <div class="lv-dependents">
          <h4>Dependentes de ${escapeHtml(titular.nome || `Titular ${index + 1}`)}</h4>
          ${dependentes.map(dep => `<article class="lv-dependent-card">
            <div class="lv-person-heading"><span class="lv-person-avatar is-dependent">${escapeHtml((dep.nome || 'D').charAt(0))}</span><div><strong>${escapeHtml(dep.nome || 'Dependente')}</strong><span>${escapeHtml(dep.parentesco || 'Parentesco não informado')}</span></div></div>
            <div class="lv-person-grid">${pessoaInfo('CPF', dep.cpf)}${pessoaInfo('Nascimento', dep.data_nascimento ? formatarData(dep.data_nascimento) : '-')}${pessoaInfo('Plano', dep.plano?.nome)}${pessoaInfo('Coparticipação', formatCoparticipacao(dep.coparticipacao))}</div>
          </article>`).join('')}
        </div>` : '<p class="lv-no-dependents">Sem dependentes vinculados.</p>';

      container.append(`<article class="lv-holder-card">
        <div class="lv-person-heading"><span class="lv-person-avatar">${escapeHtml((titular.nome || 'T').charAt(0))}</span><div><span class="lv-person-role">Titular ${index + 1}</span><strong>${escapeHtml(titular.nome || 'Titular sem nome')}</strong></div></div>
        <div class="lv-person-grid">${pessoaInfo('CPF', titular.cpf)}${pessoaInfo('Nascimento', titular.data_nascimento ? formatarData(titular.data_nascimento) : '-')}${pessoaInfo('E-mail', titular.email)}${pessoaInfo('Telefone', titular.telefone)}${pessoaInfo('Plano', titular.plano?.nome)}${pessoaInfo('Coparticipação', formatCoparticipacao(titular.coparticipacao))}</div>
        ${dependentesHtml}
      </article>`);
    });
  }

  // Função para formatar data
  function formatarData(dataStr) {
    if (!dataStr) return '-';
    const iso = String(dataStr).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (iso) return `${iso[3]}/${iso[2]}/${iso[1]}`;
    return String(dataStr);
  }

  // Função para formatar coparticipação
  function formatCoparticipacao(value) {
    if (!value) return '-';
    const valueUpper = value.toUpperCase();
    if (valueUpper === 'S' || valueUpper === 'SIM' || valueUpper === 'Y' || valueUpper === 'COM') return 'Sim';
    if (valueUpper === 'N' || valueUpper === 'NAO' || valueUpper === 'NÃO' || valueUpper === 'SEM') return 'Não';
    return value;
  }

  // Função para carregar histórico da venda
  function carregarHistoricoVenda(vendaId) {
    $('#historico-loading').removeClass('d-none');
    $('#historico-content').addClass('d-none');

    $.ajax({
      url: `/vendas/historico/${vendaId}`,
      method: 'GET',
      success: function (response) {
        $('#historico-loading').addClass('d-none');
        $('#historico-content').removeClass('d-none');

        if (response.success && response.historico && response.historico.length > 0) {
          renderizarTimeline(response.historico);
          $('#historico-timeline').removeClass('d-none');
          $('#historico-vazio').addClass('d-none');
        } else {
          $('#historico-timeline').addClass('d-none');
          $('#historico-vazio').removeClass('d-none');
        }
      },
      error: function () {
        $('#historico-loading').addClass('d-none');
        $('#historico-content').removeClass('d-none');
        $('#historico-timeline').addClass('d-none');
        $('#historico-vazio').removeClass('d-none');
      }
    });
  }

  // Função para renderizar a timeline do histórico
  function renderizarTimeline(historico) {
    const container = $('#historico-timeline');
    container.empty();

    // Ordenar do mais recente para o mais antigo
    const historicoOrdenado = [...historico].reverse();

    historicoOrdenado.forEach((item, index) => {
      const statusClass = getStatusClass(item.status_novo_codigo);
      const statusIcon = getStatusIcon(item.status_novo_codigo);
      const statusBadgeColor = getStatusBadgeColor(item.status_novo_codigo);

      let html = `
        <div class="lv-timeline-item ${statusClass}">
          <div class="lv-timeline-card">
            <div class="lv-timeline-header">
              <div class="lv-timeline-status">
                <i class="${statusIcon}"></i>
                <span class="lv-timeline-badge" style="background: ${statusBadgeColor};">
                  ${escapeHtml(item.status_novo || 'Status não informado')}
                </span>
                ${item.tempo_formatado ? `<span class="lv-timeline-tempo"><i class="ri-time-line"></i> ${escapeHtml(item.tempo_formatado)}</span>` : ''}
              </div>
              <span class="lv-timeline-date">
                <i class="ri-calendar-line me-1"></i>${item.data || 'Data não informada'}
              </span>
            </div>
      `;

      // Mostrar transição de status (de → para)
      if (item.status_anterior) {
        html += `
            <div class="lv-timeline-transition">
              <span class="lv-status-from">${escapeHtml(item.status_anterior)}</span>
              <span class="lv-arrow"><i class="ri-arrow-right-line"></i></span>
              <span class="lv-status-to">${escapeHtml(item.status_novo)}</span>
            </div>
        `;
      }

      // Metadados (usuário)
      html += `
            <div class="lv-timeline-meta">
              <span><i class="ri-user-line me-1"></i> ${escapeHtml(item.usuario || 'Sistema')}</span>
            </div>
      `;

      // Observação ou motivo de pendência
      const observacao = item.motivo_pendencia || item.observacao;
      if (observacao) {
        html += `
            <div class="lv-timeline-obs">
              <i class="ri-chat-quote-line me-1 text-muted"></i>
              ${escapeHtml(observacao)}
            </div>
        `;
      }

      html += `
          </div>
        </div>
      `;

      container.append(html);
    });
  }

  // Função para obter classe CSS baseada no status
  function getStatusClass(code) {
    return {
      [STATUS.IMPLANTADO]: 'status-implantado',
      [STATUS.PENDENCIA]: 'status-pendencia',
      [STATUS.ESTORNO]: 'status-estorno',
      [STATUS.DECLINADO]: 'status-declinado',
      [STATUS.VENDA]: 'status-venda',
      [STATUS.ANALISE_DOCUMENTOS]: 'status-analise',
      [STATUS.ANALISE_OPERADORA]: 'status-analise',
      [STATUS.BOLETO_DISPONIVEL]: 'status-boleto',
      [STATUS.REGULARIZADO]: 'status-regularizado'
    }[normalizeStatusCode(code)] || '';
  }

  // Função para obter ícone do status
  function getStatusIcon(code) {
    return {
      [STATUS.IMPLANTADO]: 'ri-checkbox-circle-line text-success',
      [STATUS.PENDENCIA]: 'ri-error-warning-line text-danger',
      [STATUS.ESTORNO]: 'ri-arrow-go-back-line text-danger',
      [STATUS.DECLINADO]: 'ri-close-circle-line text-danger',
      [STATUS.VENDA]: 'ri-shopping-cart-2-line text-primary',
      [STATUS.ANALISE_DOCUMENTOS]: 'ri-file-search-line text-warning',
      [STATUS.ANALISE_OPERADORA]: 'ri-building-2-line text-warning',
      [STATUS.BOLETO_DISPONIVEL]: 'ri-bill-line text-info',
      [STATUS.REGULARIZADO]: 'ri-checkbox-line text-success',
      [STATUS.CONTRATO_GERADO_AGUARDANDO_ASSINATURA]: 'ri-file-text-line text-secondary',
      [STATUS.AGUARDANDO_ASSINATURA_DS]: 'ri-file-text-line text-secondary'
    }[normalizeStatusCode(code)] || 'ri-checkbox-blank-circle-line text-secondary';
  }

  // Função para obter cor do badge do status
  function getStatusBadgeColor(code) {
    return {
      [STATUS.IMPLANTADO]: '#28a745',
      [STATUS.PENDENCIA]: '#dc3545',
      [STATUS.ESTORNO]: '#dc3545',
      [STATUS.DECLINADO]: '#6c757d',
      [STATUS.VENDA]: '#696cff',
      [STATUS.ANALISE_DOCUMENTOS]: '#ffab00',
      [STATUS.ANALISE_OPERADORA]: '#ffab00',
      [STATUS.BOLETO_DISPONIVEL]: '#20c997',
      [STATUS.REGULARIZADO]: '#71dd37',
      [STATUS.CONTRATO_GERADO_AGUARDANDO_ASSINATURA]: '#8c57ff',
      [STATUS.AGUARDANDO_ASSINATURA_DS]: '#8c57ff'
    }[normalizeStatusCode(code)] || '#6c757d';
  }
});
