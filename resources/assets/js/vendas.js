'use strict';

$(document).ready(function () {
  const endpoint = '/vendas/getResultsBroker';

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
        toastr.success('Dados carregados com sucesso!');

        $('.js-valorCadastrado').text(formatMoeda(res.vendasCadastradasMes));
        $('.js-implantado').text(formatMoeda(res.vendasImplantadasMes ?? '0'));
        $('.js-quantidadeContatosImportados').text(res.quantidadeContatosMes ?? '0');
        $('.js-conversao').text(res.conversao ?? '0%');

        const renderStatus = function (data, type, row) {
          const label = (data || '').toString().toUpperCase();
          const motivoFull = row.motivo_pendencia || 'Motivo não informado';
          const motivoEsc = escapeHtml(motivoFull);
          const resumoEsc = escapeHtml(truncate(motivoFull, 140));

          const icons = {
            'PENDENCIA': '<i class="ri-error-warning-line ri-22px text-warning me-2"></i>',
            'DECLINADO': '<i class="ri-close-circle-line ri-22px text-danger me-2"></i>',
            'ESTORNO': '<i class="ri-computer-line ri-22px text-danger me-2"></i>',
            'VENDA': '<i class="ri-pie-chart-line ri-22px text-success me-2"></i>',
            'IMPLANTADO': '<i class="ri-user-line ri-22px text-primary me-2"></i>',
            'ANALISE DOCUMENTO': '<i class="ri-file-search-line ri-22px me-2"></i>',
            'ANALISE OPERADORA': '<i class="ri-building-line ri-22px me-2"></i>',
            'BOLETO DISPONIVEL': '<i class="ri-bill-line ri-22px text-info me-2"></i>'
          };
          const defaultIcon = '<i class="ri-time-line ri-22px text-secondary me-2"></i>';
          const icon = icons[label] || defaultIcon;

          // PENDÊNCIA / DECLINADO / ESTORNO: botão com tooltip do motivo
          if (label === 'PENDENCIA' || label === 'DECLINADO' || label === 'ESTORNO') {
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
        <span class="ms-1">${label}</span>
      </span>`;
          }

          // BOLETO DISPONIVEL: mostra botão de download quando houver path
          if (label === 'BOLETO DISPONIVEL' && row.path_boleto_disponivel) {
            // Opção A (via rota backend segura): /vendas/boletos/{id}
            const href = `/vendas/boletos/${row.id}`;

            // Se você preferir servir direto do storage público, use:
            // const href = `/storage/${row.path_boleto_disponivel}`;

            return `
            <span class="d-flex align-items-center text-heading">
              ${icon}<span class="me-2">${label}</span>
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
          if (label === 'IMPLANTADO') {
            const boasVindasEnviado = row.boas_vindas_enviado_em;
            const boasVindasFlag = boasVindasEnviado
              ? `<span class="badge bg-label-success ms-2" data-bs-toggle="tooltip" title="Boas Vindas enviado">
                   <i class="ri-mail-check-line me-1"></i>BV OK
                 </span>`
              : `<span class="badge bg-label-warning ms-2" data-bs-toggle="tooltip" title="Boas Vindas pendente">
                   <i class="ri-mail-close-line me-1"></i>BV Pendente
                 </span>`;
            return `<span class="d-flex align-items-center text-heading">${icon}${label}${boasVindasFlag}</span>`;
          }

          // Demais: ícone estático + texto
          return `<span class="d-flex align-items-center text-heading">${icon}${label}</span>`;
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
                  if (ano >= 2026 && row.angariacao_status === 'SIM') {
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
                            data-bs-toggle="tooltip"
                            title="Visualizar Detalhes">
                      <i class="ri-eye-line"></i>
                    </button>
                  `;
                }
              }
            ],
            language: {
              url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
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

  // iniciar flatpickr com range do mês atual
  const now = new Date();
  const ano = now.getFullYear();
  const mes = now.getMonth(); // 0-indexed
  const primeiroDiaMes = `${String(1).padStart(2, '0')}/${String(mes + 1).padStart(2, '0')}/${ano}`;
  const ultimoDia = new Date(ano, mes + 1, 0).getDate();
  const ultimoDiaMes = `${String(ultimoDia).padStart(2, '0')}/${String(mes + 1).padStart(2, '0')}/${ano}`;

  const fp = flatpickr('#filtro-periodo', {
    mode: 'range',
    dateFormat: 'd/m/Y',
    locale: 'pt',
    allowInput: true,
    defaultDate: [primeiroDiaMes, ultimoDiaMes],
    onChange: function (selectedDates) {
      if (selectedDates.length === 2) {
        fetchData(formatDateToBackend(selectedDates[0]), formatDateToBackend(selectedDates[1]));
      }
    }
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

    // Carregar dados da venda
    $.ajax({
      url: `/vendas/detalhes/${vendaId}`,
      method: 'GET',
      success: function (data) {
        preencherModalVenda(data);
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
    const statusText = (venda.descricao || 'N/D').toUpperCase();
    statusBadge.removeClass('lv-status-badge success warning danger primary');
    statusBadge.addClass('lv-status-badge');

    switch (statusText) {
      case 'IMPLANTADO':
      case 'REGULARIZADO':
        statusBadge.addClass('success');
        break;
      case 'PENDENTE':
      case 'PENDENCIA':
      case 'ANALISE OPERADORA':
      case 'ANALISE DOCUMENTO':
      case 'BOLETO DISPONIVEL':
        statusBadge.addClass('warning');
        break;
      case 'CANCELADO':
      case 'ESTORNO':
      case 'DECLINADO':
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

    $('#venda-operadora').text(venda.operadora || '-');
    $('#venda-plano').text(venda.nome_plano || '-');
    $('#venda-coparticipacao').text(formatCoparticipacao(venda.coparticipacao));
    $('#venda-angariacao').text(venda.angariacao_status === 'SIM' ? 'Angariação' : 'Normal');
    $('#venda-data-vigencia').text(venda.data_vigencia ? formatarData(venda.data_vigencia) : '-');
    $('#venda-data-implantacao').text(venda.data_implantacao ? formatarData(venda.data_implantacao) : '-');
    $('#venda-vidas').text(venda.vidas || '-');
    $('#venda-valor').text(formatMoeda(venda.valor_contrato));
    $('#venda-vendedor').text(venda.vendedor_nome || '-');
    $('#venda-numero-proposta').text(venda.numero_proposta || '-');

    // Comissão
    $('#venda-comissao-valor').text(formatMoeda(venda.comissao_valor));
    $('#venda-comissao-percentual').text(venda.comissao_percentual ? venda.comissao_percentual + '%' : '-');
  }

  // Função para formatar data
  function formatarData(dataStr) {
    if (!dataStr) return '-';
    const data = new Date(dataStr);
    const dia = String(data.getDate()).padStart(2, '0');
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const ano = data.getFullYear();
    return `${dia}/${mes}/${ano}`;
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
      url: `/back-office/historico/${vendaId}`,
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
      const statusClass = getStatusClass(item.status_novo);
      const statusIcon = getStatusIcon(item.status_novo);
      const statusBadgeColor = getStatusBadgeColor(item.status_novo);

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
  function getStatusClass(status) {
    if (!status) return '';
    const statusUpper = status.toUpperCase();

    if (statusUpper.includes('IMPLANTADO')) return 'status-implantado';
    if (statusUpper.includes('PENDENCIA') || statusUpper.includes('PENDÊNCIA')) return 'status-pendencia';
    if (statusUpper.includes('ESTORNO')) return 'status-estorno';
    if (statusUpper.includes('DECLINADO')) return 'status-declinado';
    if (statusUpper.includes('VENDA')) return 'status-venda';
    if (statusUpper.includes('ANALISE') || statusUpper.includes('ANÁLISE')) return 'status-analise';
    if (statusUpper.includes('BOLETO')) return 'status-boleto';
    if (statusUpper.includes('REGULARIZADO')) return 'status-regularizado';

    return '';
  }

  // Função para obter ícone do status
  function getStatusIcon(status) {
    if (!status) return 'ri-checkbox-blank-circle-line';
    const statusUpper = status.toUpperCase();

    if (statusUpper.includes('IMPLANTADO')) return 'ri-checkbox-circle-line text-success';
    if (statusUpper.includes('PENDENCIA') || statusUpper.includes('PENDÊNCIA')) return 'ri-error-warning-line text-danger';
    if (statusUpper.includes('ESTORNO')) return 'ri-arrow-go-back-line text-danger';
    if (statusUpper.includes('DECLINADO')) return 'ri-close-circle-line text-danger';
    if (statusUpper.includes('VENDA')) return 'ri-shopping-cart-2-line text-primary';
    if (statusUpper.includes('ANALISE DOCUMENTO')) return 'ri-file-search-line text-warning';
    if (statusUpper.includes('ANALISE OPERADORA') || statusUpper.includes('ANÁLISE OPERADORA')) return 'ri-building-2-line text-warning';
    if (statusUpper.includes('BOLETO')) return 'ri-bill-line text-info';
    if (statusUpper.includes('REGULARIZADO')) return 'ri-checkbox-line text-success';
    if (statusUpper.includes('CONTR') || statusUpper.includes('ASSINATURA')) return 'ri-file-text-line text-secondary';

    return 'ri-checkbox-blank-circle-line text-secondary';
  }

  // Função para obter cor do badge do status
  function getStatusBadgeColor(status) {
    if (!status) return '#6c757d';
    const statusUpper = status.toUpperCase();

    if (statusUpper.includes('IMPLANTADO')) return '#28a745';
    if (statusUpper.includes('PENDENCIA') || statusUpper.includes('PENDÊNCIA')) return '#dc3545';
    if (statusUpper.includes('ESTORNO')) return '#dc3545';
    if (statusUpper.includes('DECLINADO')) return '#6c757d';
    if (statusUpper.includes('VENDA')) return '#696cff';
    if (statusUpper.includes('ANALISE') || statusUpper.includes('ANÁLISE')) return '#ffab00';
    if (statusUpper.includes('BOLETO')) return '#20c997';
    if (statusUpper.includes('REGULARIZADO')) return '#71dd37';
    if (statusUpper.includes('CONTR') || statusUpper.includes('ASSINATURA')) return '#8c57ff';

    return '#6c757d';
  }
});
