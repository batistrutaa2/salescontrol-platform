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
        // Atualiza cards
        animarAtualizacao('#total-leads', response.total_leads_fila);
        animarAtualizacao('#tentativas-hoje', response.tentativas_hoje);
        animarAtualizacao('#conversoes-hoje', response.conversoes_hoje);
        animarAtualizacao('#recusas-hoje', response.recusas_hoje);

        // Atualiza DataTable
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
                <button class="btn btn-primary">Transferir</button>
                <button class="btn btn-warning">Desativar</button>
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

  // Inicializa DataTable
  tabela = $('#tabela-fila-preditiva').DataTable({
    pageLength: 10,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
    }
  });

  atualizarPreditiva();
  setInterval(atualizarPreditiva, 5000);
});
