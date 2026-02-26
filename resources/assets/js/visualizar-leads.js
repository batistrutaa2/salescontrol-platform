/**
 * app-ecommerce-product-list
 */

'use strict';

// Datatable (jquery)
$(function () {
  let borderColor, bodyBg, headingColor;

  if (isDarkStyle) {
    borderColor = config.colors_dark.borderColor;
    bodyBg = config.colors_dark.bodyBg;
    headingColor = config.colors_dark.headingColor;
  } else {
    borderColor = config.colors.borderColor;
    bodyBg = config.colors.bodyBg;
    headingColor = config.colors.headingColor;
  }

  // Variable declaration for table
  var dt_product_table = $('.datatables-products');
  // E-commerce Products datatable
  if (dt_product_table.length) {
    var dt_products = dt_product_table.DataTable({
      ajax: '/mailing/getLeads',
      columns: [
        { data: 'id', title: '' },
        { data: 'id', title: 'ID' },
        { data: 'nome_corretor', title: 'Corretor' },
        { data: 'nome_cliente', title: 'Cliente' },
        { data: 'cpf', title: 'CPF' },
        { data: 'telefone', title: 'Telefone' },
        { data: 'valor_plano_atual', title: 'Valor do Plano' },
        { data: 'status', title: 'Status' },
        { data: 'nome_base', title: 'Base' },
        { data: 'created_at', title: 'Criado Em' },
        { data: null, title: 'Ações' }
      ],
      columnDefs: [
        {
          targets: 0,
          orderable: false,
          render: function () {
            return '<input type="checkbox" class="dt-checkboxes form-check-input">';
          },
          checkboxes: {
            selectAllRender: '<input type="checkbox" class="form-check-input">',
            selectRow: true
          },
          responsivePriority: 4
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            if (!data) return ''; // Se for null, undefined ou string vazia, retorna vazio

            let value = data.toString().replace(/\D/g, '');

            if (value.length <= 11) {
              // Aplica a máscara de CPF
              value = value
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else {
              // Aplica a máscara de CNPJ
              value = value
                .replace(/^(\d{2})(\d)/, '$1.$2')
                .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1/$2')
                .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
            }

            return value;
          }
        },
        {
          targets: 5, // Coluna com telefone
          render: function (data, type, row) {
            if (!data) return '';
            let value = data.toString().replace(/\D/g, ''); // Remove caracteres não numéricos

            if (value.length === 11) {
              // Aplica a máscara para telefone celular (ex.: (11) 94556-7166)
              value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            } else if (value.length === 10) {
              // Aplica a máscara para telefone fixo (ex.: (11) 1234-5678)
              value = value.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
            }

            return value;
          }
        },
        {
          targets: 6, // Coluna com valor
          render: function (data, type, row) {
            if (data === null || data === undefined || isNaN(parseFloat(data))) {
              return 'R$ 0,00'; // Valor padrão se o dado for null ou não numérico
            }

            let value = parseFloat(data); // Converte o valor para número
            return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
          }
        },
        {
          targets: 9, // Coluna com data
          render: function (data, type, row) {
            if (!data) {
              return 'Data não disponível';
            }

            let date = new Date(data);
            let day = String(date.getDate()).padStart(2, '0');
            let month = String(date.getMonth() + 1).padStart(2, '0');
            let year = date.getFullYear();
            return `${day}/${month}/${year}`;
          }
        },
        {
          targets: -1,
          title: 'AÇÕES',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            var editarButton = '';
            if (full['status'] !== 'PREDITIVA') {
              editarButton =
                '<a href="/comercial/abrir-cliente/' +
                full['id'] +
                '" class="dropdown-item">' +
                '<i class="ri-edit-box-line me-2"></i><span>Editar Usuario</span></a>';
            }

            return (
              '<div class="d-flex align-items-center">' +
              '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' +
              '<i class="ri-more-2-line ri-22px"></i></button>' +
              '<div class="dropdown-menu dropdown-menu-end m-0">' +
              editarButton +
              '<button type="button" class="dropdown-item js-transferir-leads" data-bs-toggle="modal" data-bs-target="#modalcomments" data-id="' +
              full['id'] +
              '">' +
              '<i class="ri-arrow-left-right-fill"></i><span>Transferir Contato</span></button>' +
              '<a href="javascript:void(0);" class="dropdown-item js-ver-comentarios" data-id="' +
              full['id'] +
              '">' +
              '<i class="ri-chat-3-line me-2"></i><span>Ver Comentários</span></a>' +
              '<a href="/mailing/excluir-lead/' +
              full['id'] +
              '" class="dropdown-item">' +
              '<i class="ri-delete-bin-line me-2"></i><span>Excluir Lead</span></a>' +
              '</div>' +
              '</div>'
            );
          }
        }
      ],
      select: {
        style: 'multi',
        selector: 'td:first-child input[type="checkbox"]'
      },
      order: [[2, 'asc']],
      lengthMenu: [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, 'TODOS']
      ],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Buscar',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ entradas'
      },
      initComplete: function () {
        this.api()
          .columns(2)
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
          .columns(7)
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
  }

  $('#btn-transferir-massa').on('click', function () {
    var selectedRows = dt_products.rows({ selected: true }).data();

    if (selectedRows.length === 0) {
      alert('Por favor, selecione pelo menos um item.');
      $('#modalTransferContacts').modal('hide');
      return;
    }

    var selectedLeads = [];

    selectedRows.each(function (data) {
      selectedLeads.push(data.id);
    });

    $('#selectedLeadIds').val(selectedLeads.join(','));
    $('#modalTransferContacts').modal('show');
  });

  document.addEventListener('click', function (event) {
    if (event.target.closest('.js-transferir-leads')) {
      var button = event.target.closest('.js-transferir-leads');
      var leadId = button.getAttribute('data-id');
      document.querySelector('#idMailing').value = leadId;
    }
  });
});

// Adicione este código no final do seu arquivo JavaScript
document.addEventListener('click', function (event) {
  if (event.target.closest('.js-ver-comentarios')) {
    var button = event.target.closest('.js-ver-comentarios');
    var leadId = button.getAttribute('data-id');

    // Fazer requisição AJAX para buscar os comentários
    $.ajax({
      url: '/relatorios/lead-comentarios/' + leadId,
      method: 'GET',
      beforeSend: function () {
        $('#modalComentarios .modal-body').html(
          '<div class="text-center"><div class="spinner-border" role="status"></div></div>'
        );
        $('#modalComentarios').modal('show');
      },
      success: function (response) {
        if (response.success) {
          var html = gerarHtmlComentarios(response.data);
          $('#modalComentarios .modal-body').html(html);
        } else {
          $('#modalComentarios .modal-body').html('<div class="alert alert-danger">' + response.message + '</div>');
        }
      },
      error: function () {
        $('#modalComentarios .modal-body').html('<div class="alert alert-danger">Erro ao carregar comentários</div>');
      }
    });
  }
});

function gerarHtmlComentarios(data) {
  var comentarios = data.comentarios || [];
  var atividades = data.atividades || [];
  var lead = data.lead || {};

  if (comentarios.length === 0 && atividades.length === 0) {
    return `
      <div class="text-center py-4">
        <i class="ri-chat-3-line ri-48px text-muted mb-3"></i>
        <p class="text-muted">Nenhum comentário encontrado para este lead.</p>
      </div>
    `;
  }

  var html = `
    <div class="col-12">
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="card-title mb-0">Anotações - ${lead.nome_cliente || 'Lead'}</h5>
        </div>
        <div class="card-body">
          <div class="card mb-6">
            <div class="card-header">
              <h5 class="card-title m-2">Últimas atividades</h5>
            </div>
            <div class="card-body mt-5" style="max-height: 400px; overflow-y: auto;">
  `;

  // Combinar comentários e atividades e ordenar por data
  var todosItens = [];

  // Adicionar comentários
  comentarios.forEach(function (comment) {
    todosItens.push({
      tipo: 'comentario',
      data: comment.created_at,
      autor: comment.autor || comment.usuario || 'Sistema',
      conteudo: comment.anotacao,
      legado: comment.legado,
      supervisao: comment.supervisao
    });
  });

  // Adicionar atividades
  atividades.forEach(function (atividade) {
    todosItens.push({
      tipo: 'atividade',
      data: atividade.created_at,
      autor: atividade.usuario || 'Sistema',
      conteudo: atividade.log_descricao,
      tabulacao_anterior: atividade.tabulacao_anterior,
      tabulacao_atual: atividade.tabulacao_atual
    });
  });

  // Ordenar por data (mais recente primeiro)
  todosItens.sort(function (a, b) {
    return new Date(b.data) - new Date(a.data);
  });

  // Gerar HTML para cada item
  todosItens.forEach(function (item) {
    var badgeClass = '';
    var badgeText = '';

    if (item.tipo === 'comentario') {
      if (item.supervisao === 'Y') {
        badgeClass = 'bg-label-warning';
        badgeText = 'SUPERVISÃO';
      } else if (item.legado === 'Y') {
        badgeClass = 'bg-label-info';
        badgeText = 'LEGADO';
      } else {
        badgeClass = 'bg-label-primary';
        badgeText = 'COMENTÁRIO';
      }
    } else {
      badgeClass = 'bg-label-success';
      badgeText = 'ATIVIDADE';
    }

    // Formatar a data
    var dataFormatada = new Date(item.data).toLocaleString('pt-BR');

    html += `
      <ul class="timeline pb-0 mb-0">
        <li class="timeline-item timeline-item-transparent border-primary">
          <span class="timeline-point timeline-point-primary"></span>
          <div class="timeline-event">
            <div class="timeline-header mb-1">
              <h6 class="mb-0">Feito por: (${item.autor})
                <span class="badge ${badgeClass}">${badgeText}</span>
              </h6>
              <small class="text-muted">${dataFormatada}</small>
            </div>
            <p class="mt-1 mb-3">${item.conteudo || ''}</p>
    `;

    // Se for atividade e tiver mudança de tabulação, mostrar
    if (item.tipo === 'atividade' && item.tabulacao_anterior && item.tabulacao_atual) {
      html += `
        <div class="mt-2">
          <small class="text-muted">
            <strong>Mudança:</strong> ${item.tabulacao_anterior} → ${item.tabulacao_atual}
          </small>
        </div>
      `;
    }

    html += `
          </div>
        </li>
      </ul>
    `;
  });

  html += `
            </div>
          </div>
        </div>
      </div>
    </div>
  `;

  return html;
}
