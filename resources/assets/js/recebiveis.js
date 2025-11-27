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

        // Botões de ação
        let actionBtns = '';
        if (parcela.status === 'PAGO') {
          // Parcela paga: mostrar botão para editar data
          actionBtns = `
            <button class="btn btn-sm btn-outline-primary editar-data-recebimento"
                    data-id="${parcela.id}"
                    data-parcela="${parcela.parcela}"
                    data-data="${parcela.data_recebimento || ''}"
                    title="Editar data de recebimento">
              <i class="ri-edit-line"></i> Editar
            </button>`;
        } else if (parcela.status !== 'CANCELADO') {
          // Parcela pendente: botões para marcar como pago ou definir data
          actionBtns = `
            <div class="d-flex gap-1">
              <button class="btn btn-sm btn-success pagar-parcela" data-id="${parcela.id}" title="Marcar como pago (hoje)">
                <i class="ri-check-line"></i>
              </button>
              <button class="btn btn-sm btn-outline-primary editar-data-recebimento"
                      data-id="${parcela.id}"
                      data-parcela="${parcela.parcela}"
                      data-data=""
                      title="Definir data de pagamento">
                <i class="ri-calendar-line"></i>
              </button>
            </div>`;
        } else {
          actionBtns = '<span class="text-muted">—</span>';
        }

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
            <td>${actionBtns}</td>
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

  // Marcar como Pago (com data de hoje)
  $(document).on('click', '.pagar-parcela', function () {
    let parcelaId = $(this).data('id');

    Swal.fire({
      title: 'Confirmar pagamento?',
      text: 'A parcela será marcada como paga com a data de hoje.',
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

  // Editar/Definir data de recebimento
  $(document).on('click', '.editar-data-recebimento', function () {
    let parcelaId = $(this).data('id');
    let numeroParcela = $(this).data('parcela');
    let dataAtual = $(this).data('data') || '';

    // Converter data de dd/mm/yyyy para yyyy-mm-dd (formato do input date)
    let dataFormatada = '';
    if (dataAtual) {
      let partes = dataAtual.split('/');
      if (partes.length === 3) {
        dataFormatada = `${partes[2]}-${partes[1]}-${partes[0]}`;
      }
    }

    Swal.fire({
      title: `<i class="ri-calendar-check-line text-primary"></i> Parcela ${numeroParcela}`,
      html: `
        <div class="text-start">
          <p class="mb-3">${dataAtual ? 'Edite a data de recebimento:' : 'Informe a data em que o pagamento foi recebido:'}</p>
          <div class="mb-3">
            <label class="form-label fw-bold">Data de Recebimento</label>
            <input type="date" id="swal-data-recebimento" class="form-control"
                   value="${dataFormatada}" max="${new Date().toISOString().split('T')[0]}">
          </div>
          <p class="text-muted small mb-0">
            <i class="ri-information-line me-1"></i>
            ${dataAtual ? 'A parcela continuará marcada como paga.' : 'A parcela será marcada como paga na data informada.'}
          </p>
        </div>
      `,
      icon: null,
      showCancelButton: true,
      confirmButtonText: '<i class="ri-check-line me-1"></i> Salvar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-primary me-2',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false,
      preConfirm: () => {
        const data = document.getElementById('swal-data-recebimento').value;
        if (!data) {
          Swal.showValidationMessage('Por favor, informe a data de recebimento.');
          return false;
        }
        return data;
      }
    }).then(result => {
      if (result.isConfirmed && result.value) {
        // Mostrar loading
        Swal.fire({
          title: 'Salvando...',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        $.ajax({
          url: `/financeiro/recebiveis/parcelas/${parcelaId}/data-recebimento`,
          method: 'PUT',
          data: {
            data_recebimento: result.value
          },
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            Swal.fire({
              title: 'Sucesso!',
              text: response.message,
              icon: 'success',
              confirmButtonText: 'OK',
              customClass: { confirmButton: 'btn btn-success' },
              buttonsStyling: false
            }).then(() => {
              // Recarregar parcelas e página
              $('#parcelasModal').modal('hide');
              location.reload();
            });
          },
          error: function (xhr) {
            let errorMsg = 'Erro ao atualizar data de recebimento.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMsg = xhr.responseJSON.message;
            }
            Swal.fire('Erro!', errorMsg, 'error');
          }
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
              let temAlteracoes = response.alteracoes && response.alteracoes.length > 0;
              let temNovasParcelas = response.novas_parcelas && response.novas_parcelas.length > 0;

              // Se houve alterações ou novas parcelas, mostrar detalhes
              if (temAlteracoes || temNovasParcelas) {
                let detalhes = '';

                // Tabela de parcelas atualizadas
                if (temAlteracoes) {
                  detalhes += '<div class="text-start mt-3"><h6 class="mb-2">Parcelas Atualizadas:</h6><table class="table table-sm"><thead><tr><th>Parcela</th><th>Anterior</th><th>Novo</th><th>Dif.</th></tr></thead><tbody>';

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
                }

                // Tabela de novas parcelas criadas
                if (temNovasParcelas) {
                  detalhes += '<div class="text-start mt-3"><h6 class="mb-2 text-success"><i class="ri-add-circle-line me-1"></i>Novas Parcelas Criadas:</h6><table class="table table-sm"><thead><tr><th>Parcela</th><th>Valor</th><th>Vencimento</th></tr></thead><tbody>';

                  response.novas_parcelas.forEach(nova => {
                    detalhes += `
                      <tr class="table-success">
                        <td>${nova.parcela}</td>
                        <td>R$ ${parseFloat(nova.valor_novo).toFixed(2).replace('.', ',')}</td>
                        <td>${nova.data_prevista}</td>
                      </tr>
                    `;
                  });

                  detalhes += '</tbody></table></div>';
                }

                // Resumo
                if (response.resumo) {
                  detalhes += '<div class="alert alert-info mt-3 mb-0 text-start">';

                  if (temAlteracoes) {
                    let corDif = response.resumo.diferenca_atualizacoes >= 0 ? 'text-success' : 'text-danger';
                    let sinalDif = response.resumo.diferenca_atualizacoes >= 0 ? '+' : '';
                    detalhes += `<div><strong>Diferença nas atualizações:</strong> <span class="${corDif}">${sinalDif}R$ ${parseFloat(response.resumo.diferenca_atualizacoes).toFixed(2).replace('.', ',')}</span></div>`;
                  }

                  if (temNovasParcelas) {
                    detalhes += `<div><strong>Total novas parcelas:</strong> <span class="text-success">+R$ ${parseFloat(response.resumo.total_novas_parcelas).toFixed(2).replace('.', ',')}</span></div>`;
                  }

                  detalhes += '</div>';
                }

                Swal.fire({
                  title: 'Recebíveis atualizados!',
                  html: mensagem + detalhes,
                  icon: 'success',
                  width: 650
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
