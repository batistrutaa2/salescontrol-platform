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
      }
    });
  }
});
