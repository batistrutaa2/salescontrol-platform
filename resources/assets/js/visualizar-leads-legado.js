/**
 * app-ecommerce-product-list
 */

'use strict';

$(function () {
  var dt_product_table = $('.datatables-products');

  // E-commerce Products datatable
  if (dt_product_table.length) {
    var dt_products = dt_product_table.DataTable({
      initComplete: function () {
        this.api()
          .columns(1)
          .every(function () {
            var column = this;
            var select = $(
              '<select id="corretorFilter" class="form-select text-capitalize"><option value="">TODOS</option></select>'
            )
              .appendTo('.product_status')
              .on('change', function () {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                column.search(val ? '^' + val + '$' : '', true, false).draw();
              });

            column
              .data()
              .unique()
              .sort()
              .each(function (d, j) {
                if (d != null) {
                  select.append('<option value="' + d + '">' + d + '</option>');
                }
              });
          });
        this.api()
          .columns(5)
          .every(function () {
            var column = this;
            var select = $(
              '<select id="statusFilter" class="form-select text-capitalize"><option value="">TODOS</option></select>'
            )
              .appendTo('.product_category')
              .on('change', function () {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                column.search(val ? '^' + val + '$' : '', true, false).draw();
              });

            column
              .data()
              .unique()
              .sort()
              .each(function (d, j) {
                select.append('<option value="' + d + '">' + d + '</option>');
              });
          });
      }
    });

    // Usando delegação de eventos para o botão "Visualizar"
    dt_product_table.on('click', '.js-click-button-modal', function () {
      var idMailing = $(this).data('idmailing');

      $('#getDate').modal('show');

      fetch('/mailing/getLeadsLegacy/' + idMailing)
        .then(response => response.json())
        .then(data => {
          $('#name').val(data.contato.nameClient || '');
          $('#email').val(data.contato.email || '');
          $('#cpf').val(data.contato.cpf || '');
          $('#phone1').val(data.contato.phone1 || '');
          $('#phone2').val(data.contato.phone2 || '');
          $('#phone3').val(data.contato.phone3 || '');
          $('#ages').val(data.contato.birthDate || '');
          $('#plan').val(data.contato.plan || '');
          $('#prince').val(data.contato.prince || '');
          $('#situation').val(data.contato.situation || '');
          $('#entity').val(data.contato.entity || '');
          $('#category').val(data.contato.category || '');
          let comments = data.comentarios || [];
          let commentsList = $('.js-list-commentsOne');
          commentsList.empty();

          comments.forEach(comment => {
            var comentario = comment.notes || '';
            var autor = comment.name || '';

            const date = new Date(comment.dateCreate);
            const formattedDate = date.toLocaleDateString('pt-BR', {
              day: '2-digit',
              month: '2-digit',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit'
            });

            let commentHtml = `
              <ul class="timeline pb-0 mb-0">
                  <li class="timeline-item timeline-item-transparent border-primary">
                      <span class="timeline-point timeline-point-primary"></span>
                      <div class="timeline-event">
                          <div class="timeline-header mb-1">
                              <h6 class="mb-0">Feito por:
                                  <span class="badge bg-label-success">${autor}</span>
                              </h6>
                              <small class="text-muted">${formattedDate}</small>
                          </div>
                          <p class="mt-1 mb-3">${comentario}</p>
                      </div>
                  </li>
              </ul>`;

            commentsList.append(commentHtml);
          });
        })
        .catch(error => {
          console.error('Erro ao buscar os dados do mailing:', error);
        });
    });
  }
});
