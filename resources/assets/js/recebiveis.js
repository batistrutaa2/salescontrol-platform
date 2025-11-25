'use strict';

$(function () {
  // Variável para armazenar o ID da venda atual no modal
  let currentVendaId = null;

  // Inicia tabela principal
  let table = $('#recebiveisTable').DataTable({
    pageLength: 10,
    responsive: true,
    order: [[4, 'desc']],
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
    }
  });

  // Filtro por status
  $('.btn-filter').on('click', function () {
    let status = $(this).data('status');

    // Atualizar botão ativo
    $('.btn-filter').removeClass('active');
    $(this).addClass('active');

    // Aplicar filtro
    if (status === 'todos') {
      table.column(7).search('').draw(); // Coluna 7 = Status
    } else {
      table.column(7).search(status).draw();
    }
  });

  // Função para carregar parcelas
  function carregarParcelas(vendaId) {
    $.get(`/financeiro/recebiveis/${vendaId}/parcelas`, function (data) {
      let tbody = $('#parcelasTable tbody');
      tbody.empty();

      data.forEach(parcela => {
        let statusBadge = parcela.status === 'PAGO'
          ? `<span class="badge bg-success">Pago</span>`
          : parcela.status === 'CANCELADO'
            ? `<span class="badge bg-secondary">Cancelado</span>`
            : `<span class="badge bg-warning">Pendente</span>`;

        let actionBtn = parcela.status === 'PAGO'
          ? '—'
          : `<button class="btn btn-sm btn-success pagar-parcela" data-id="${parcela.id}">
               <i class="ri-check-line"></i> Marcar como Pago
             </button>`;

        let dataRecebimento = parcela.data_recebimento
          ? parcela.data_recebimento
          : '<span class="text-muted">—</span>';

        tbody.append(`
          <tr>
            <td>${parcela.parcela}</td>
            <td>R$ ${parseFloat(parcela.valor).toFixed(2).replace('.', ',')}</td>
            <td>${parcela.data_prevista}</td>
            <td>${dataRecebimento}</td>
            <td>${statusBadge}</td>
            <td>${actionBtn}</td>
          </tr>
        `);
      });

      $('#parcelasModal').modal('show');
    });
  }

  // Ao clicar em "Ver Parcelas"
  $(document).on('click', '.view-parcelas', function () {
    currentVendaId = $(this).data('id');
    carregarParcelas(currentVendaId);
  });

  // Marcar como Pago
  $(document).on('click', '.pagar-parcela', function () {
    let parcelaId = $(this).data('id');

    Swal.fire({
      title: 'Confirmar pagamento?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sim, pagar',
      cancelButtonText: 'Cancelar'
    }).then(result => {
      if (result.isConfirmed) {
        $.post(`/financeiro/recebiveis/parcelas/${parcelaId}/pagar`, {
          _token: $('meta[name="csrf-token"]').attr('content')
        }, function () {
          Swal.fire('Sucesso!', 'Parcela marcada como paga.', 'success');
          $('#parcelasModal').modal('hide');
          location.reload();
        }).fail(() => {
          Swal.fire('Erro!', 'Não foi possível marcar como paga.', 'error');
        });
      }
    });
  });

  // Recalcular valores com nova regra
  $(document).on('click', '#btnRecalcular', function () {
    if (!currentVendaId) {
      Swal.fire('Erro!', 'ID do contrato não encontrado.', 'error');
      return;
    }

    Swal.fire({
      title: 'Recalcular valores?',
      html: `
        <p>Os valores das parcelas serão recalculados com base na <strong>regra de comissionamento atual</strong>.</p>
        <p class="text-muted small">Status, datas de vencimento e recebimento serão mantidos.</p>
      `,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sim, recalcular',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#ffc107'
    }).then(result => {
      if (result.isConfirmed) {
        // Mostrar loading
        Swal.fire({
          title: 'Recalculando...',
          html: 'Aguarde enquanto os valores são atualizados.',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        $.ajax({
          url: `/financeiro/recebiveis/${currentVendaId}/recalcular`,
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            if (response.success) {
              let mensagem = response.message;

              // Se houve alterações, mostrar detalhes
              if (response.alteracoes && response.alteracoes.length > 0) {
                let detalhes = '<div class="text-start mt-3"><table class="table table-sm"><thead><tr><th>Parcela</th><th>Anterior</th><th>Novo</th><th>Dif.</th></tr></thead><tbody>';

                response.alteracoes.forEach(alt => {
                  let corDif = alt.diferenca >= 0 ? 'text-success' : 'text-danger';
                  let sinalDif = alt.diferenca >= 0 ? '+' : '';
                  detalhes += `
                    <tr>
                      <td>${alt.parcela}</td>
                      <td>R$ ${parseFloat(alt.valor_antigo).toFixed(2).replace('.', ',')}</td>
                      <td>R$ ${parseFloat(alt.valor_novo).toFixed(2).replace('.', ',')}</td>
                      <td class="${corDif}">${sinalDif}R$ ${parseFloat(alt.diferenca).toFixed(2).replace('.', ',')}</td>
                    </tr>
                  `;
                });

                detalhes += '</tbody></table></div>';

                if (response.resumo) {
                  let corTotal = response.resumo.diferenca >= 0 ? 'text-success' : 'text-danger';
                  let sinalTotal = response.resumo.diferenca >= 0 ? '+' : '';
                  detalhes += `
                    <div class="alert alert-info mt-2 mb-0">
                      <strong>Diferença total:</strong>
                      <span class="${corTotal}">${sinalTotal}R$ ${parseFloat(response.resumo.diferenca).toFixed(2).replace('.', ',')}</span>
                    </div>
                  `;
                }

                Swal.fire({
                  title: 'Valores recalculados!',
                  html: mensagem + detalhes,
                  icon: 'success',
                  width: 600
                }).then(() => {
                  // Recarregar parcelas no modal
                  carregarParcelas(currentVendaId);
                });
              } else {
                Swal.fire('Sucesso!', mensagem, 'success');
              }
            } else {
              Swal.fire('Atenção!', response.message, 'warning');
            }
          },
          error: function (xhr) {
            let errorMsg = 'Erro ao recalcular valores.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMsg = xhr.responseJSON.message;
            }
            Swal.fire('Erro!', errorMsg, 'error');
          }
        });
      }
    });
  });
});
