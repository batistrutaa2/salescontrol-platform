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
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
    }
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

  atualizarPreditiva();
  setInterval(atualizarPreditiva, 5000);
});
