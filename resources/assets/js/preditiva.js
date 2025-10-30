'use strict';

$(function () {
  let tabela = null;
  let filtrosAtivos = {};

  function animarAtualizacao(selector, novoValor) {
    const elemento = $(selector);
    if (elemento.text() !== String(novoValor)) {
      elemento.text(novoValor ?? '--');
      elemento.addClass('updated-highlight');
      setTimeout(() => {
        elemento.removeClass('updated-highlight');
      }, 800);
    }
  }

  function carregarTabulacoes() {
    $.ajax({
      url: '/comercial/preditiva/tabulacoes',
      method: 'GET',
      success: function (response) {
        if (response.success && response.tabulacoes) {
          const select = $('#filtroUltimaTabulacao');
          select.find('option:not(:first)').remove();

          console.log('Tabulações carregadas:', response.tabulacoes);

          response.tabulacoes.forEach(tabulacao => {
            if (tabulacao && tabulacao.trim() !== '') {
              select.append(`<option value="${tabulacao}">${tabulacao}</option>`);
            }
          });
        }
      },
      error: function (xhr) {
        console.error('Erro ao carregar tabulações:', xhr);
      }
    });
  }

  function atualizarPreditiva() {
    $.ajax({
      url: '/getPreditiva',
      method: 'GET',
      data: filtrosAtivos,
      success: function (response) {
        console.log('Resposta da API:', response);
        console.log('Total de leads retornados:', response.leads.length);

        animarAtualizacao('#total-leads', response.total_leads_fila);
        animarAtualizacao('#tentativas-hoje', response.tentativas_hoje);
        animarAtualizacao('#conversoes-hoje', response.conversoes_hoje);
        animarAtualizacao('#recusas-hoje', response.recusas_hoje);

        if (tabela) {
          tabela.clear().draw();
          response.leads.forEach(lead => {
            tabela.row.add([
              lead.contato_id,
              lead.nome_cliente ?? '--',
              lead.telefone1 ?? '--',
              `R$ ${parseFloat(lead.valor_plano_atual || 0).toFixed(2)}`,
              lead.tentativas,
              `<span class="badge bg-label-info">${lead.ultima_tabulacao ?? 'N/A'}</span>`,
              `
                <div class="dropdown">
                  <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ri-more-2-line"></i>
                  </button>
                  <ul class="dropdown-menu">
                    <li>
                      <a class="dropdown-item btn-transferir" href="javascript:void(0);" data-id="${lead.contato_id}">
                        <i class="ri-share-forward-line me-2"></i> Transferir
                      </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <a class="dropdown-item btn-desativar" href="javascript:void(0);" data-id="${lead.contato_id}">
                        <i class="ri-pause-circle-line me-2"></i> Desativar
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item btn-remover" href="javascript:void(0);" data-id="${lead.contato_id}">
                        <i class="ri-indeterminate-circle-line me-2"></i> Remover da Fila
                      </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <a class="dropdown-item text-danger btn-excluir" href="javascript:void(0);" data-id="${lead.contato_id}">
                        <i class="ri-delete-bin-line me-2"></i> Excluir Permanentemente
                      </a>
                    </li>
                  </ul>
                </div>
              `
            ]);
          });
          tabela.draw();
        }
      },
      error: function () {
        console.warn('Erro ao buscar dados da fila preditiva.');
      }
    });
  }

  tabela = $('#tabela-fila-preditiva').DataTable({
    pageLength: 10,
    lengthMenu: [
      [10, 25, 50, -1],
      [10, 25, 50, 'Todos']
    ],
    language: {
      "sEmptyTable": "Nenhum registro encontrado",
      "sInfo": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
      "sInfoEmpty": "Mostrando 0 até 0 de 0 registros",
      "sInfoFiltered": "(Filtrados de _MAX_ registros)",
      "sInfoPostFix": "",
      "sInfoThousands": ".",
      "sLengthMenu": "_MENU_ resultados por página",
      "sLoadingRecords": "Carregando...",
      "sProcessing": "Processando...",
      "sZeroRecords": "Nenhum registro encontrado",
      "sSearch": "Pesquisar",
      "oPaginate": {
        "sNext": "Próximo",
        "sPrevious": "Anterior",
        "sFirst": "Primeiro",
        "sLast": "Último"
      },
      "oAria": {
        "sSortAscending": ": Ordenar colunas de forma ascendente",
        "sSortDescending": ": Ordenar colunas de forma descendente"
      },
      "select": {
        "rows": {
          "_": "Selecionado %d linhas",
          "0": "Nenhuma linha selecionada",
          "1": "Selecionado 1 linha"
        }
      }
    },
    columnDefs: [
      {
        targets: 0,
        orderable: false,
        searchable: false,
        className: 'dt-body-center',
        render: function (data) {
          return `<input type="checkbox" class="select-lead" value="${data}">`;
        }
      }
    ]
  });

  // Event: Aplicar Filtros
  $('#btnAplicarFiltros').on('click', function () {
    filtrosAtivos = {
      nome_cliente: $('#filtroNomeCliente').val().trim(),
      ultima_tabulacao: $('#filtroUltimaTabulacao').val()
    };

    console.log('Filtros aplicados:', filtrosAtivos);
    atualizarPreditiva();
    toastr.info('Filtros aplicados!');
  });

  // Event: Limpar Filtros
  $('#btnLimparFiltros').on('click', function () {
    $('#filtroNomeCliente').val('');
    $('#filtroUltimaTabulacao').val('');

    filtrosAtivos = {};
    atualizarPreditiva();
    toastr.info('Filtros limpos!');
  });

  // Event: Transferir Lead
  $('#tabela-fila-preditiva tbody').on('click', '.btn-transferir', function () {
    const contatoId = $(this).data('id');
    abrirModalTransferencia(contatoId);
  });

  // Event: Desativar Lead
  $('#tabela-fila-preditiva tbody').on('click', '.btn-desativar', function () {
    const contatoId = $(this).data('id');

    if (!confirm('Deseja desativar este lead da preditiva? O lead ficará inativo mas poderá ser reativado.')) {
      return;
    }

    $.ajax({
      url: `/comercial/preditiva/desativar/${contatoId}`,
      method: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.success) {
          toastr.success(response.message);
          atualizarPreditiva();
        } else {
          toastr.error(response.message);
        }
      },
      error: function (xhr) {
        const message = xhr.responseJSON?.message || 'Erro ao desativar lead.';
        toastr.error(message);
      }
    });
  });

  // Event: Excluir Lead Permanentemente
  $('#tabela-fila-preditiva tbody').on('click', '.btn-excluir', function () {
    const contatoId = $(this).data('id');

    if (!confirm('ATENÇÃO: Esta ação excluirá o lead PERMANENTEMENTE e marcará o contato como inativo. Deseja continuar?')) {
      return;
    }

    $.ajax({
      url: `/comercial/preditiva/excluir/${contatoId}`,
      method: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.success) {
          toastr.success(response.message);
          atualizarPreditiva();
        } else {
          toastr.error(response.message);
        }
      },
      error: function (xhr) {
        const message = xhr.responseJSON?.message || 'Erro ao excluir lead.';
        toastr.error(message);
      }
    });
  });

  // Event: Remover da Fila
  $('#tabela-fila-preditiva tbody').on('click', '.btn-remover', function () {
    const contatoId = $(this).data('id');

    if (!confirm('Deseja remover este lead da fila preditiva? O contato permanecerá ativo no sistema.')) {
      return;
    }

    $.ajax({
      url: `/comercial/preditiva/remover/${contatoId}`,
      method: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.success) {
          toastr.success(response.message);
          atualizarPreditiva();
        } else {
          toastr.error(response.message);
        }
      },
      error: function (xhr) {
        const message = xhr.responseJSON?.message || 'Erro ao remover lead.';
        toastr.error(message);
      }
    });
  });

  function abrirModalTransferencia(contatoId) {
    document.getElementById('idMailing').value = contatoId;
    $('#modalTransferirLead').modal('show');
  }

  $('#tabela-fila-preditiva tbody').on('change', '.select-lead', function () {
    const row = $(this).closest('tr');
    row.toggleClass('selected', this.checked);
  });

  $('#btnLimparFila').on('click', function () {
    const selecionados = [];

    $('#tabela-fila-preditiva tbody input.select-lead:checked').each(function () {
      selecionados.push($(this).val());
    });

    if (selecionados.length === 0) {
      return alert('Nenhum lead selecionado.');
    }

    if (!confirm(`ATENÇÃO: ${selecionados.length} lead(s) serão DESATIVADOS permanentemente e removidos da preditiva. Deseja continuar?`)) return;

    $.ajax({
      url: '/comercial/descartar-multiplos-leads',
      method: 'POST',
      data: {
        ids: selecionados,
        clearPreditiva: true,
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        toastr.success(response.message || 'Leads descartados com sucesso!');
        atualizarPreditiva();
      },
      error: function () {
        toastr.error('Erro ao descartar leads.');
      }
    });
  });

  $('#select-all-leads').on('click', function () {
    const isChecked = $(this).is(':checked');
    $('#tabela-fila-preditiva tbody input.select-lead').prop('checked', isChecked).trigger('change');
  });

  // Inicialização
  carregarTabulacoes();
  atualizarPreditiva();
  setInterval(atualizarPreditiva, 20000);
});
