'use strict';

$(function () {
  let tabela = null;

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

  function atualizarPreditiva() {
    $.ajax({
      url: '/getPreditiva',
      method: 'GET',
      success: function (response) {
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
              `R$ ${parseFloat(lead.valor_plano_atual || 0).toFixed(2)}`,
              lead.tentativas,
              `
                <div class="btn-group btn-group-sm" role="group">
                  <button class="btn btn-outline-primary me-1 btn-transferir"
                          data-id="${lead.contato_id}"
                          title="Transferir">
                    <i class="ri-share-forward-line"></i>
                  </button>
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
      url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
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

  $('#tabela-fila-preditiva tbody').on('click', '.btn-transferir', function () {
    const contatoId = $(this).data('id');
    abrirModalTransferencia(contatoId);
  });

  $('#tabela-fila-preditiva tbody').on('click', '.btn-desativar', function () {
    const contatoId = $(this).data('id');
    console.log('Desativar lead:', contatoId);
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

    if (!confirm(`Deseja realmente remover ${selecionados.length} leads da fila?`)) return;

    $.ajax({
      url: '/comercial/descartar-multiplos-leads',
      method: 'POST',
      data: {
        ids: selecionados,
        clearPreditiva: true,
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function () {
        toastr.success('Fila preditiva atualizada!');
        atualizarPreditiva();
      },
      error: function () {
        toastr.error('Erro ao limpar fila preditiva.');
      }
    });
  });

  $('#select-all-leads').on('click', function () {
    const isChecked = $(this).is(':checked');
    $('#tabela-fila-preditiva tbody input.select-lead').prop('checked', isChecked).trigger('change');
  });

  atualizarPreditiva();
  setInterval(atualizarPreditiva, 20000);
});
