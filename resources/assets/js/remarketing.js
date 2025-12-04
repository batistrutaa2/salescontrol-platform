/**
 * Empresa
 */

'use strict';
let table;
// Configurações globais do Toastr
toastr.options = {
  closeButton: true, // Adiciona um botão de fechar
  debug: false, // Mostra mensagens de debug (opcional)
  newestOnTop: false, // Mostra as mensagens mais recentes no topo
  progressBar: true, // Adiciona uma barra de progresso
  positionClass: 'toast-top-right', // Posição da notificação
  preventDuplicates: false, // Previne a duplicação de notificações
  onclick: null, // Função de clique (opcional)
  showDuration: '300', // Duração do efeito de exibição
  hideDuration: '1000', // Duração do efeito de ocultação
  timeOut: '5000', // Tempo de exibição em milissegundos
  extendedTimeOut: '1000', // Tempo adicional de exibição ao passar o mouse
  showEasing: 'swing', // Efeito de exibição
  hideEasing: 'linear', // Efeito de ocultação
  showMethod: 'fadeIn', // Método de exibição
  hideMethod: 'fadeOut' // Método de ocultação
};

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
  var dt_customer_table = $('.datatables-customers'),
    select2 = $('.select2'),
    statusObj = {
      Y: { title: 'ATIVO', class: 'bg-label-success' },
      N: { title: 'INATIVO', class: 'bg-label-secondary' }
    };
  if (select2.length) {
    var $this = select2;
    select2Focus($this);
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'United States ',
      dropdownParent: $this.parent()
    });
  }

  $('.card-datatable').before(
    `<div class="d-flex justify-content-end gap-2 mb-3">
    <button id="descartarSelecionadosBtn" class="btn btn-danger waves-effect waves-light" disabled>
      <i class="ri-close-line me-1"></i>Descartar Selecionados
    </button>
    <button id="enviarSelecionadosBtn" class="btn btn-primary waves-effect waves-light" disabled>
      <i class="ri-arrow-right-fill me-1"></i>Enviar Selecionados para Preditiva
    </button>
  </div>`
  );


  if (dt_customer_table.length) {
    table = dt_customer_table.DataTable({
      ajax: {
        url: 'getRemarketingLeads',
        dataSrc: '',
        complete: function (jqXHR, textStatus) {
          updateMetrics();
          populateBaseFilter();
        },
        error: function (jqXHR, textStatus, errorThrown) { }
      },
      columns: [
        {
          // Coluna de checkbox
          data: null,
          title: '<input type="checkbox" class="form-check-input" id="selectAll">',
          orderable: false,
          searchable: false,
          width: '30px',
          render: function (data, type, full, meta) {
            return '<input type="checkbox" class="form-check-input lead-checkbox" data-id="' + full.id + '">';
          }
        },
        { data: 'id' },
        { data: 'nome_cliente' },
        {
          data: 'motivo_remarketing',
          render: function (data, type, full, meta) {
            var $motivo = full['motivo_remarketing'];
            if ($motivo == null) {
              $motivo = 'SEM REGISTRO';
            } else {
              $motivo = full['motivo_remarketing'];
            }
            return '<span>' + $motivo + '</span>';
          }
        },
        { data: 'telefone1' },
        { data: 'plano' },
        { data: 'categoria' },
        {
          data: 'nome_base',
          render: function (data, type, full, meta) {
            return data ? data : '<span class="text-muted">N/D</span>';
          }
        },
        {
          data: 'data_importacao',
          render: function (data, type, full, meta) {
            if (!data) return '<span class="text-muted">N/D</span>';
            // Data já vem formatada do backend, apenas exibir
            return data;
          }
        },
        {
          data: 'updated_at',
          render: function (data, type, full, meta) {
            if (!data) return '<span class="text-muted">N/D</span>';
            // Data já vem formatada do backend, apenas exibir
            return data;
          }
        },
        {
          // Actions
          targets: -1,
          title: 'AÇÕES',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return (
              '<div class="d-flex align-items-center">' +
              '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' +
              '<i class="ri-more-2-line ri-22px"></i></button>' +
              '<div class="dropdown-menu dropdown-menu-end m-0">' +
              '<a href="/comercial/abrir-cliente/' +
              full.id +
              '" class="dropdown-item"><i class="ri-edit-box-line me-2"></i><span> Editar Contato</span></a>' +
              '<button type="button" class="dropdown-item js-transferir-leads" data-bs-toggle="modal" data-bs-target="#modalcomments" data-id="' +
              full.id +
              '">' +
              '<i class="ri-arrow-left-right-fill me-2"></i><span> Transferir Contato</span></button>' +
              '<button type="button" class="dropdown-item js-enviar-preditiva" data-id="' +
              full.id +
              '">' +
              '<i class="ri-arrow-right-fill me-2"></i><span> Enviar Preditiva</span></button>' +
              '<button type="button" class="dropdown-item js-descartar-cliente" data-id="' +
              full.id +
              '">' +
              '<i class="ri-close-circle-line me-2"></i><span> Descartar Cliente</span></button>' +
              '</div>' +
              '</div>'
            );
          }
        }
      ],
      lengthMenu: [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, 'TODOS']
      ],
      order: [[9, 'desc']],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Pesquisar leads...'
      },
      drawCallback: function(settings) {
        if (table) {
          updateFilteredMetrics();
        }
      },
      // Para popup responsivo
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Detalhes de ' + data['nome_cliente'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== ''
                ? '<tr data-dt-row="' +
                col.rowIndex +
                '" data-dt-column="' +
                col.columnIndex +
                '">' +
                '<td>' +
                col.title +
                ':' +
                '</td> ' +
                '<td>' +
                col.data +
                '</td>' +
                '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      }
    });
    $('.dataTables_length').addClass('my-0');
    $('.dt-action-buttons').addClass('pt-0');
    $('.dataTables_filter input').addClass('ms-0');
    $('.dt-buttons').addClass('d-flex flex-wrap');

    // Manipulador para o checkbox "Selecionar Todos"
    $(document).on('change', '#selectAll', function () {
      const isChecked = $(this).prop('checked');
      $('.lead-checkbox').prop('checked', isChecked);
      updateEnviarSelecionadosBtn();
    });

    // Manipulador para checkboxes individuais
    $(document).on('change', '.lead-checkbox', function () {
      updateEnviarSelecionadosBtn();

      // Atualizar o estado do checkbox "Selecionar Todos"
      const allChecked = $('.lead-checkbox:checked').length === $('.lead-checkbox').length;
      $('#selectAll').prop('checked', allChecked && $('.lead-checkbox').length > 0);
    });

    $(document).on('click', '.js-descartar-cliente', function () {
      const leadId = $(this).data('id');
      $.ajax({
        url: '/comercial/descartar-cliente/' + leadId,
        method: 'POST',
        data: {
          _token: $('meta[name="csrf-token"]').attr('content'), // CSRF para Laravel
        },
        success: function (response) {
          toastr.success('Cliente descartado com sucesso!');
          table.ajax.reload(null, false); // Atualiza tabela sem resetar a página
        },
        error: function (xhr) {
          toastr.error('Erro ao descartar cliente.');
        }
      });
    });




    // Função para atualizar o estado do botão "Enviar Selecionados"
    function updateEnviarSelecionadosBtn() {
      const hasChecked = $('.lead-checkbox:checked').length > 0;
      $('#enviarSelecionadosBtn, #descartarSelecionadosBtn').prop('disabled', !hasChecked);
    }

    $('#descartarSelecionadosBtn').on('click', function () {
      const selectedIds = [];

      $('.lead-checkbox:checked').each(function () {
        selectedIds.push($(this).data('id'));
      });

      if (selectedIds.length === 0) {
        toastr.warning('Nenhum lead selecionado', 'Atenção');
        return;
      }

      if (confirm(`Deseja descartar ${selectedIds.length} lead(s)?`)) {
        discardMultipleLeads(selectedIds);
      }
    });



    // Manipulador para o botão "Enviar Selecionados"
    $('#enviarSelecionadosBtn').on('click', function () {
      const selectedIds = [];

      $('.lead-checkbox:checked').each(function () {
        selectedIds.push($(this).data('id'));
      });

      if (selectedIds.length === 0) {
        toastr.warning('Nenhum lead selecionado', 'Atenção');
        return;
      }

      // Confirmar antes de enviar
      if (confirm(`Deseja enviar ${selectedIds.length} lead(s) para a fila preditiva?`)) {
        sendLeadsToPredictive(selectedIds);
      }
    });

    function discardMultipleLeads(ids) {
      $.ajaxSetup({
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
      });

      $.ajax({
        url: '/comercial/descartar-multiplos-leads',
        method: 'POST',
        data: { ids: ids },
        success: function (response) {
          if (response.success) {
            toastr.success(response.message || 'Leads descartados com sucesso!', 'Sucesso');
            $('.lead-checkbox, #selectAll').prop('checked', false);
            updateEnviarSelecionadosBtn();
            table.ajax.reload();
          } else {
            toastr.error(response.message || 'Erro ao descartar leads.', 'Erro');
          }
        },
        error: function (xhr) {
          const errorMsg = xhr.responseJSON?.message ?? 'Erro ao descartar leads.';
          toastr.error(errorMsg, 'Erro');
        }
      });
    }



    // Função para enviar leads para a fila preditiva
    function sendLeadsToPredictive(ids) {
      $.ajaxSetup({
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
      });

      $.ajax({
        url: '/comercial/sendMultipleLeadsPredictive',
        type: 'POST',
        data: { ids: ids },
        success: function (response) {
          if (response.success) {
            toastr.success(response.message || 'Leads enviados com sucesso!', 'Sucesso');
            // Desmarcar todos os checkboxes
            $('.lead-checkbox, #selectAll').prop('checked', false);
            updateEnviarSelecionadosBtn();
            // Recarregar a tabela
            table.ajax.reload();
          } else {
            toastr.error(response.message || 'Erro ao enviar leads', 'Erro');
          }
        },
        error: function (xhr) {
          const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Erro ao enviar leads para a fila preditiva';
          toastr.error(errorMsg, 'Erro');
        }
      });
    }
  }

  document.addEventListener('click', function (event) {
    if (event.target.closest('.js-transferir-leads')) {
      var button = event.target.closest('.js-transferir-leads');
      var leadId = button.getAttribute('data-id');
      document.querySelector('#idMailing').value = leadId;
    }
  });

  document.addEventListener('click', function (event) {
    if (event.target.closest('.js-enviar-preditiva')) {
      const button = event.target.closest('.js-enviar-preditiva');
      const id = button.getAttribute('data-id');

      // Criar um formulário temporário
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '/comercial/sendLeadPredictive';
      form.style.display = 'none';

      // Adicionar o token CSRF
      const csrfInput = document.createElement('input');
      csrfInput.type = 'hidden';
      csrfInput.name = '_token';
      csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      form.appendChild(csrfInput);

      // Adicionar o ID
      const idInput = document.createElement('input');
      idInput.type = 'hidden';
      idInput.name = 'id';
      idInput.value = id;
      form.appendChild(idInput);

      // Adicionar o formulário ao documento e enviá-lo
      document.body.appendChild(form);
      form.submit();

      // Prevenir comportamento padrão do botão
      event.preventDefault();
    }
  });

  // Inicializar flatpickr para período personalizado
  if (document.querySelector('#filtro_periodo')) {
    flatpickr('#filtro_periodo', {
      mode: 'range',
      dateFormat: 'd/m/Y',
      locale: 'pt'
    });
  }

  // Função para atualizar métricas gerais
  function updateMetrics() {
    if (!table || !table.rows) return;
    const allData = table.rows().data().toArray();
    const totalRemarketing = allData.length;

    // Contar leads do mês atual
    const currentMonth = moment().month();
    const currentYear = moment().year();
    const totalMesAtual = allData.filter(row => {
      if (!row.data_importacao) return false;
      const rowDate = moment(row.data_importacao);
      return rowDate.month() === currentMonth && rowDate.year() === currentYear;
    }).length;

    // Contar bases únicas
    const basesUnicas = new Set();
    allData.forEach(row => {
      if (row.nome_base) {
        basesUnicas.add(row.nome_base);
      }
    });

    $('#total-remarketing').text(totalRemarketing);
    $('#total-mes-atual').text(totalMesAtual);
    $('#total-bases').text(basesUnicas.size);
  }

  // Função para atualizar métricas de filtros
  function updateFilteredMetrics() {
    if (!table || !table.rows) return;
    const filteredData = table.rows({ search: 'applied' }).data().toArray();
    $('#total-filtrado').text(filteredData.length);
  }

  // Função para popular filtro de bases
  function populateBaseFilter() {
    if (!table || !table.rows) return;
    const allData = table.rows().data().toArray();
    const basesUnicas = new Set();

    allData.forEach(row => {
      if (row.nome_base) {
        basesUnicas.add(row.nome_base);
      }
    });

    const baseSelect = $('#filtro_base');
    baseSelect.find('option:not(:first)').remove();

    Array.from(basesUnicas).sort().forEach(base => {
      baseSelect.append(`<option value="${base}">${base}</option>`);
    });
  }

  // Função customizada de filtro do DataTable
  $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
    if (settings.nTable.id !== 'DataTables_Table_0') {
      return true;
    }

    const mes = $('#filtro_mes').val();
    const ano = $('#filtro_ano').val();
    const base = $('#filtro_base').val();
    const periodoRange = $('#filtro_periodo').val();

    const dataImportacao = data[8]; // índice da coluna data_importacao
    const nomeBase = data[7]; // índice da coluna nome_base

    // Filtro por base
    if (base && nomeBase !== base && !nomeBase.includes('N/D')) {
      return false;
    }

    // Filtro por mês/ano
    if ((mes || ano) && dataImportacao && !dataImportacao.includes('N/D')) {
      const dataParts = dataImportacao.split('/');
      if (dataParts.length === 3) {
        const rowMes = parseInt(dataParts[1]);
        const rowAno = parseInt(dataParts[2]);

        if (mes && rowMes !== parseInt(mes)) {
          return false;
        }
        if (ano && rowAno !== parseInt(ano)) {
          return false;
        }
      }
    }

    // Filtro por período personalizado
    if (periodoRange && dataImportacao && !dataImportacao.includes('N/D')) {
      const dates = periodoRange.split(' to ');
      if (dates.length === 2) {
        const startDate = moment(dates[0], 'DD/MM/YYYY');
        const endDate = moment(dates[1], 'DD/MM/YYYY');
        const rowDate = moment(dataImportacao, 'DD/MM/YYYY');

        if (!rowDate.isBetween(startDate, endDate, 'day', '[]')) {
          return false;
        }
      }
    }

    return true;
  });

  // Event listeners para os filtros
  $('#filtro_mes, #filtro_ano, #filtro_base').on('change', function() {
    if (table) table.draw();
  });

  $('#filtro_periodo').on('change', function() {
    if (table) table.draw();
  });

  // Limpar filtros
  $('#limpar_filtros').on('click', function() {
    $('#filtro_mes').val('');
    $('#filtro_ano').val('');
    $('#filtro_base').val('');
    $('#filtro_periodo').val('');
    if (document.querySelector('#filtro_periodo')._flatpickr) {
      document.querySelector('#filtro_periodo')._flatpickr.clear();
    }
    if (table) {
      table.search('').draw();
    }
  });

});

// Validation & Phone mask
(function () {
  const phoneMaskList = document.querySelectorAll('.phone-mask'),
    eCommerceCustomerAddForm = document.getElementById('eCommerceCustomerAddForm');

  if (phoneMaskList) {
    phoneMaskList.forEach(function (phoneMask) {
      new Cleave(phoneMask, {
        phone: true,
        phoneRegionCode: 'BR'
      });
    });
  }

  const fv = FormValidation.formValidation(eCommerceCustomerAddForm, {
    fields: {
      customerName: {
        validators: {
          notEmpty: {
            message: 'Nome da Empresa é obrigatorio '
          }
        }
      },
      customerCnpj: {
        validators: {
          notEmpty: {
            message: 'CNPJ/CPF da Empresa é obrigatorio '
          }
        }
      },
      customerEmail: {
        validators: {
          notEmpty: {
            message: 'E-mail da Empresa é obrigatorio'
          },
          emailAddress: {
            message: 'Esse E-mail não é valido'
          }
        }
      },
      customerPassword: {
        validators: {
          notEmpty: {
            message: 'Senha é obrigatorio '
          }
        }
      }
    },
    plugins: {
      trigger: new FormValidation.plugins.Trigger(),
      bootstrap5: new FormValidation.plugins.Bootstrap5({
        eleValidClass: '',
        rowSelector: function (field, ele) {
          return '.mb-5';
        }
      }),
      submitButton: new FormValidation.plugins.SubmitButton(),
      autoFocus: new FormValidation.plugins.AutoFocus()
    }
  });

  const submitButton = document.querySelector('.data-submit');
  submitButton.addEventListener('click', function (event) {
    fv.validate().then(function (status) {
      if (status === 'Valid') {
        const formData = {
          name: document.querySelector('[name="customerName"]').value,
          email: document.querySelector('[name="customerEmail"]').value,
          user_role_id: document.querySelector('[name="user_role_id"]').value,
          empresa_id: document.querySelector('[name="empresa_id"]').value,
          password: document.querySelector('[name="customerPassword"]').value
        };

        $.ajaxSetup({
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
        });

        $.ajax({
          url: 'usuarios/createUser',
          type: 'POST',
          data: formData,
          success: function (response) {
            table.ajax.reload();
            $('#offcanvasEcommerceCustomerAdd').offcanvas('hide');
            if (!response.error) {
              toastr.success(response.message, 'Sucesso');
            } else {
              toastr.error(response.message, 'Erro');
            }
          },
          error: function (error) {
            var errorMessage = error.responseJSON ? error.responseJSON.message : 'Ocorreu um erro desconhecido';

            toastr.error(error, errorMessage);
          }
        });
      }
    });
  });
})();
