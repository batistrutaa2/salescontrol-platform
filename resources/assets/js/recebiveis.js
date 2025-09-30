'use strict';

$(function () {
  // Inicia tabela principal
  let table = $('#recebiveisTable').DataTable({
    pageLength: 10,
    responsive: true,
    order: [[4, 'desc']]
  });

  // Ao clicar em "Ver Parcelas"
  $(document).on('click', '.view-parcelas', function () {
    let vendaId = $(this).data('id');

    $.get(`/financeiro/recebiveis/${vendaId}/parcelas`, function (data) {
      let tbody = $('#parcelasTable tbody');
      tbody.empty();

      data.forEach(parcela => {
        let statusBadge = parcela.status === 'PAGO'
          ? `<span class="badge bg-success">Pago</span>`
          : `<span class="badge bg-warning">Pendente</span>`;

        let actionBtn = parcela.status === 'PAGO'
          ? '—'
          : `<button class="btn btn-sm btn-success pagar-parcela" data-id="${parcela.id}">
               <i class="ri-check-line"></i> Marcar como Pago
             </button>`;

        tbody.append(`
          <tr>
            <td>${parcela.parcela}</td>
            <td>R$ ${parseFloat(parcela.valor).toFixed(2).replace('.', ',')}</td>
            <td>${parcela.data_prevista}</td>
            <td>${statusBadge}</td>
            <td>${actionBtn}</td>
          </tr>
        `);
      });

      $('#parcelasModal').modal('show');
    });
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
});
