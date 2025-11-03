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

  function fetchData(mes, ano) {
    if (!mes || !ano) return;

    $.ajax({
      url: endpoint,
      method: 'GET',
      data: { mes, ano },
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
              { data: 'valor_contrato', render: (d) => formatMoeda(d) },
              {
                data: null,
                orderable: false,
                render: function (data, type, row) {
                  return `
                    <button type="button" class="btn btn-sm btn-icon btn-info btn-visualizar-venda"
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

  // filtros mês/ano
  $('#select-month, #select-year').on('change', function () {
    const mes = parseInt($('#select-month').val(), 10);
    const ano = parseInt($('#select-year').val(), 10);
    if (!isNaN(mes) && !isNaN(ano)) fetchData(mes, ano);
  });

  // iniciar com mês/ano atual
  const currentMonth = new Date().getMonth() + 1;
  const currentYear = new Date().getFullYear();
  $('#select-month').val(currentMonth);
  $('#select-year').val(currentYear);
  fetchData(currentMonth, currentYear);

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

    // Status badge
    const statusBadge = $('#venda-status-badge');
    statusBadge.removeClass().addClass('badge');
    switch ((venda.descricao || '').toUpperCase()) {
      case 'IMPLANTADO':
        statusBadge.addClass('bg-success').text('IMPLANTADO');
        break;
      case 'PENDENTE':
        statusBadge.addClass('bg-warning').text('PENDENTE');
        break;
      case 'CANCELADO':
        statusBadge.addClass('bg-danger').text('CANCELADO');
        break;
      default:
        statusBadge.addClass('bg-secondary').text(venda.descricao || 'N/D');
    }

    $('#venda-operadora').text(venda.operadora || '-');
    $('#venda-plano').text(venda.nome_plano || '-');
    $('#venda-angariacao').text(venda.angariacao || '-');
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
});
