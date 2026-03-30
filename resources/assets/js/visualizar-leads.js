/**
 * Gerenciar Leads - Hub Central
 */

'use strict';

(function () {
  const config = window.leadHubConfig || {};
  const csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content;

  let table;
  let selectedIds = new Set();
  let currentMonth = config.currentMonth || new Date().getMonth() + 1;
  let currentYear = config.currentYear || new Date().getFullYear();
  let activeImportDate = null;
  let activeImportBase = null;
  let activeSituacaoFilter = '';
  let pendingConfirmAction = null;
  let filterDateStart = '';
  let filterDateEnd = '';

  const monthNames = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

  // ============================================
  // Init
  // ============================================
  $(function () {
    initDataTable();
    initFilters();
    initDateRangeFilter();
    initKpiCards();
    initImportsSidebar();
    initBulkActions();
    initModals();
    renderImportDates(config.importDates || []);
    updateMonthLabel();
  });

  // ============================================
  // DataTable
  // ============================================
  function initDataTable() {
    table = $('#leadsHubTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '/mailing/getAllLeadsServerSide',
        type: 'GET',
        data: function (d) {
          d.situacao = activeSituacaoFilter || $('#filterSituacao').val();
          d.corretor = $('#filterCorretor').val();
          if (filterDateStart && filterDateEnd) {
            d.data_inicio = filterDateStart;
            d.data_fim = filterDateEnd;
          }
          if (activeImportBase) d.nome_base = activeImportBase;
          if (activeImportDate) {
            d.data_importacao = activeImportDate;
          }
        }
      },
      columns: [
        { data: 'id', title: '', orderable: false, searchable: false },
        { data: 'id', title: 'ID' },
        { data: 'nome_cliente', title: 'Cliente' },
        { data: 'cpf', title: 'CPF' },
        { data: 'nome_corretor', title: 'Corretor' },
        { data: 'situacao', title: 'Situacao' },
        { data: 'tabulacao', title: 'Tabulacao' },
        { data: 'nome_base', title: 'Base' },
        { data: 'created_at', title: 'Importado em' },
        { data: null, title: 'Acoes', orderable: false, searchable: false }
      ],
      columnDefs: [
        {
          targets: 0,
          render: function (data) {
            const checked = selectedIds.has(data) ? 'checked' : '';
            return '<input type="checkbox" class="dt-checkboxes form-check-input gl-row-check" data-id="' + data + '" ' + checked + '>';
          }
        },
        {
          targets: 3,
          render: function (data) {
            if (!data) return '';
            let v = data.toString().replace(/\D/g, '');
            if (v.length <= 11) {
              return v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            }
            return v.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d{1,2})$/, '$1-$2');
          }
        },
        {
          targets: 4,
          render: function (data) {
            return data || '<span class="text-muted">-</span>';
          }
        },
        {
          targets: 5,
          render: function (data) {
            const map = {
              'COM VENDEDOR': 'badge-com-vendedor',
              'PREDITIVA': 'badge-preditiva',
              'DESCARTADO': 'badge-descartado',
              'REMARKETING': 'badge-remarketing',
              'SEM ATRIBUICAO': 'badge-sem-atribuicao'
            };
            const cls = map[data] || 'badge-sem-atribuicao';
            return '<span class="gl-badge ' + cls + '"><span class="gl-badge-dot"></span>' + (data || '-') + '</span>';
          }
        },
        {
          targets: 6,
          render: function (data) {
            return data || '<span class="text-muted">-</span>';
          }
        },
        {
          targets: 7,
          render: function (data) {
            if (!data) return '<span class="text-muted">-</span>';
            if (data.length > 18) {
              return '<span title="' + data + '" style="cursor:help">' + data.substring(0, 18) + '...</span>';
            }
            return data;
          }
        },
        {
          targets: 9,
          render: function (data, type, full) {
            const sit = full.situacao;
            let items = '';

            // Edit - only for leads with broker
            if (sit === 'COM VENDEDOR' || sit === 'REMARKETING') {
              items += '<a href="/comercial/abrir-cliente/' + full.id + '" class="dropdown-item"><i class="ri-edit-box-line me-2"></i>Editar</a>';
            }

            // Transfer
            if (sit !== 'DESCARTADO') {
              items += '<a href="javascript:void(0);" class="dropdown-item js-action-transfer" data-id="' + full.id + '"><i class="ri-arrow-left-right-fill me-2"></i>Transferir</a>';
            }

            // Send to Preditiva
            if (sit === 'COM VENDEDOR' || sit === 'SEM ATRIBUICAO' || sit === 'REMARKETING') {
              items += '<a href="javascript:void(0);" class="dropdown-item js-action-preditiva" data-id="' + full.id + '"><i class="ri-phone-fill me-2"></i>Enviar Preditiva</a>';
            }

            // Send discarded to Preditiva
            if (sit === 'DESCARTADO') {
              items += '<a href="javascript:void(0);" class="dropdown-item js-action-preditiva-descartado" data-id="' + full.id + '"><i class="ri-phone-fill me-2"></i>Enviar Preditiva</a>';
              items += '<a href="javascript:void(0);" class="dropdown-item js-action-reactivate" data-id="' + full.id + '"><i class="ri-refresh-line me-2"></i>Reativar</a>';
            }

            // Discard
            if (sit !== 'DESCARTADO') {
              items += '<a href="javascript:void(0);" class="dropdown-item js-action-discard" data-id="' + full.id + '"><i class="ri-close-circle-line me-2"></i>Descartar</a>';
            }

            // Comments
            items += '<a href="javascript:void(0);" class="dropdown-item js-action-comments" data-id="' + full.id + '"><i class="ri-chat-3-line me-2"></i>Historico</a>';

            // Delete
            items += '<a href="javascript:void(0);" class="dropdown-item text-danger js-action-delete" data-id="' + full.id + '"><i class="ri-delete-bin-line me-2"></i>Excluir</a>';

            return (
              '<div class="d-flex align-items-center">' +
              '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' +
              '<i class="ri-more-2-line ri-22px"></i></button>' +
              '<div class="dropdown-menu dropdown-menu-end m-0">' + items + '</div></div>'
            );
          }
        }
      ],
      select: {
        style: 'multi',
        selector: 'td:first-child input[type="checkbox"]'
      },
      order: [[1, 'desc']],
      pageLength: 25,
      lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Buscar lead...',
        info: 'Mostrando _START_ a _END_ de _TOTAL_',
        infoFiltered: '(filtrado de _MAX_)',
        processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Carregando...',
        zeroRecords: 'Nenhum lead encontrado',
        emptyTable: 'Nenhum lead na base'
      },
      drawCallback: function () {
        restoreCheckboxes();
        updateBulkToolbar();
        updateSelectAllState();
      },
      headerCallback: function (thead) {
        var $th = $(thead).find('th').eq(0);
        if (!$th.find('.gl-select-all').length) {
          $th.html('<input type="checkbox" class="form-check-input gl-select-all">');
        }
      }
    });
  }

  // ============================================
  // Checkbox Management (cross-page)
  // ============================================
  $(document).on('change', '.gl-row-check', function () {
    const id = parseInt($(this).data('id'));
    if (this.checked) {
      selectedIds.add(id);
    } else {
      selectedIds.delete(id);
    }
    updateBulkToolbar();
    updateSelectAllState();
  });

  // Select all on current page
  $(document).on('change', '.gl-select-all', function () {
    const checked = this.checked;
    $('#leadsHubTable tbody .gl-row-check').each(function () {
      const id = parseInt($(this).data('id'));
      this.checked = checked;
      if (checked) {
        selectedIds.add(id);
      } else {
        selectedIds.delete(id);
      }
    });
    updateBulkToolbar();
  });

  function restoreCheckboxes() {
    $('#leadsHubTable tbody .gl-row-check').each(function () {
      const id = parseInt($(this).data('id'));
      this.checked = selectedIds.has(id);
    });
  }

  function updateSelectAllState() {
    const $all = $('#leadsHubTable tbody .gl-row-check');
    const $checked = $all.filter(':checked');
    const $selectAll = $('.gl-select-all');
    if ($all.length === 0) {
      $selectAll.prop('checked', false).prop('indeterminate', false);
    } else if ($checked.length === $all.length) {
      $selectAll.prop('checked', true).prop('indeterminate', false);
    } else if ($checked.length > 0) {
      $selectAll.prop('checked', false).prop('indeterminate', true);
    } else {
      $selectAll.prop('checked', false).prop('indeterminate', false);
    }
  }

  function updateBulkToolbar() {
    const count = selectedIds.size;
    $('#selectedCount').text(count);
    if (count > 0) {
      $('#bulkToolbar').addClass('visible');
    } else {
      $('#bulkToolbar').removeClass('visible');
    }
  }

  function clearSelection() {
    selectedIds.clear();
    $('#leadsHubTable tbody .gl-row-check').prop('checked', false);
    updateBulkToolbar();
  }

  // ============================================
  // Filters
  // ============================================
  function initFilters() {
    $('#filterSituacao, #filterCorretor').on('change', function () {
      // sync KPI card active state if situacao changed manually
      if (this.id === 'filterSituacao') {
        activeSituacaoFilter = '';
        $('.gl-kpi-card').removeClass('active');
        if ($(this).val()) {
          $('.gl-kpi-card[data-filter="' + $(this).val() + '"]').addClass('active');
        }
      }
      table.ajax.reload();
      refreshKPIs();
    });

    $('#btnClearFilters').on('click', function () {
      $('#filterSituacao, #filterCorretor').val('');
      activeSituacaoFilter = '';
      activeImportDate = null;
      activeImportBase = null;
      filterDateStart = '';
      filterDateEnd = '';
      if (window.dateRangePicker) window.dateRangePicker.clear();
      $('.gl-kpi-card').removeClass('active');
      $('.gl-import-item').removeClass('active');
      table.ajax.reload();
      refreshKPIs();
    });
  }

  // ============================================
  // Date Range Filter (Flatpickr)
  // ============================================
  function initDateRangeFilter() {
    window.dateRangePicker = flatpickr('#filterDateRange', {
      mode: 'range',
      dateFormat: 'd/m/Y',
      locale: 'pt',
      disableMobile: true,
      onChange: function (selectedDates) {
        if (selectedDates.length === 2) {
          var d1 = selectedDates[0];
          var d2 = selectedDates[1];
          filterDateStart = String(d1.getDate()).padStart(2, '0') + '/' + String(d1.getMonth() + 1).padStart(2, '0') + '/' + d1.getFullYear();
          filterDateEnd = String(d2.getDate()).padStart(2, '0') + '/' + String(d2.getMonth() + 1).padStart(2, '0') + '/' + d2.getFullYear();
          table.ajax.reload();
          refreshKPIs();
        }
      },
      onClose: function (selectedDates) {
        if (selectedDates.length === 0) {
          filterDateStart = '';
          filterDateEnd = '';
          table.ajax.reload();
          refreshKPIs();
        }
      }
    });
  }

  // ============================================
  // KPI Cards
  // ============================================
  function initKpiCards() {
    $('.gl-kpi-card').on('click', function () {
      const filter = $(this).data('filter');

      if ($(this).hasClass('active')) {
        // Deselect
        $(this).removeClass('active');
        activeSituacaoFilter = '';
        $('#filterSituacao').val('');
      } else {
        $('.gl-kpi-card').removeClass('active');
        $(this).addClass('active');
        activeSituacaoFilter = filter;
        $('#filterSituacao').val(filter);
      }
      table.ajax.reload();
    });
  }

  function getActiveFilters() {
    var filters = {};
    var corretor = $('#filterCorretor').val();
    var base = activeImportBase || '';
    if (corretor) filters.corretor = corretor;
    if (base) filters.nome_base = base;
    if (activeImportDate) filters.data_importacao = activeImportDate;
    if (filterDateStart && filterDateEnd) {
      filters.data_inicio = filterDateStart;
      filters.data_fim = filterDateEnd;
    }
    return filters;
  }

  function refreshKPIs() {
    var params = getActiveFilters();
    $.get('/mailing/getLeadKPIs', params, function (data) {
      animateValue('#kpi-total', data.total);
      animateValue('#kpi-vendedor', data.com_vendedor);
      animateValue('#kpi-preditiva', data.preditiva);
      animateValue('#kpi-descartados', data.descartados);
      animateValue('#kpi-remarketing', data.remarketing);
    });
  }

  function animateValue(selector, newVal) {
    $(selector).text(newVal.toLocaleString('pt-BR'));
  }

  // ============================================
  // Imports Sidebar
  // ============================================
  function initImportsSidebar() {
    $('#btnPrevMonth').on('click', function () {
      currentMonth--;
      if (currentMonth < 1) { currentMonth = 12; currentYear--; }
      fetchImportDates();
    });

    $('#btnNextMonth').on('click', function () {
      currentMonth++;
      if (currentMonth > 12) { currentMonth = 1; currentYear++; }
      fetchImportDates();
    });
  }

  function updateMonthLabel() {
    $('#monthLabel').text(monthNames[currentMonth - 1] + '/' + currentYear);
  }

  function fetchImportDates() {
    updateMonthLabel();
    $.get('/mailing/getImportDates', { month: currentMonth, year: currentYear }, function (data) {
      renderImportDates(data);
    });
  }

  function renderImportDates(imports) {
    const container = $('#importsList');
    container.empty();

    if (!imports || imports.length === 0) {
      container.html('<div class="gl-imports-empty"><svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Nenhuma importacao neste mes</div>');
      return;
    }

    imports.forEach(function (item) {
      const dateParts = item.data_importacao.split('-');
      const dateFormatted = dateParts[2] + '/' + dateParts[1];
      const baseName = item.nome_base || 'Sem nome';
      const itemKey = dateParts[2] + '/' + dateParts[1] + '/' + dateParts[0] + '|' + baseName;
      const activeKey = activeImportDate ? activeImportDate + '|' + (activeImportBase || '') : null;
      const activeCls = activeKey === itemKey ? ' active' : '';

      const html = '<div class="gl-import-item' + activeCls + '" data-date="' + dateParts[2] + '/' + dateParts[1] + '/' + dateParts[0] + '" data-base="' + baseName + '">' +
        '<span class="gl-import-date">' + dateFormatted + '</span>' +
        '<div class="gl-import-info"><span class="gl-import-base" title="' + baseName + '">' + baseName + '</span></div>' +
        '<span class="gl-import-count">' + item.quantidade + '</span>' +
        '</div>';
      container.append(html);
    });

    // Click handler
    container.find('.gl-import-item').on('click', function () {
      const date = $(this).data('date');
      const base = $(this).data('base');
      if (activeImportDate === date && activeImportBase === base) {
        activeImportDate = null;
        activeImportBase = null;
        $(this).removeClass('active');
      } else {
        $('.gl-import-item').removeClass('active');
        $(this).addClass('active');
        activeImportDate = date;
        activeImportBase = base;
      }
      table.ajax.reload();
      refreshKPIs();
    });
  }

  // ============================================
  // Bulk Actions
  // ============================================
  function initBulkActions() {
    // Transfer bulk
    $(document).on('click', '.gl-bulk-btn[data-action="transfer"]', function () {
      if (selectedIds.size === 0) return;
      $('#transferSubtitle').text(selectedIds.size + ' lead(s) selecionado(s)');
      $('#modalTransfer').data('mode', 'bulk').modal('show');
    });

    // Preditiva bulk
    $(document).on('click', '.gl-bulk-btn[data-action="preditiva"]', function () {
      if (selectedIds.size === 0) return;
      showConfirm('Enviar para Preditiva', 'Enviar ' + selectedIds.size + ' lead(s) para a fila preditiva?', function () {
        ajaxAction('/comercial/sendMultipleLeadsPredictive', { ids: Array.from(selectedIds) }, 'Enviando para preditiva...');
      });
    });

    // Discard bulk
    $(document).on('click', '.gl-bulk-btn[data-action="discard"]', function () {
      if (selectedIds.size === 0) return;
      showConfirm('Descartar Leads', 'Descartar ' + selectedIds.size + ' lead(s)? Eles poderao ser reativados depois.', function () {
        ajaxAction('/mailing/bulk-discard-leads', { ids: Array.from(selectedIds) }, 'Descartando leads...');
      });
    });

    // Reactivate bulk
    $(document).on('click', '.gl-bulk-btn[data-action="reactivate"]', function () {
      if (selectedIds.size === 0) return;
      showConfirm('Reativar Leads', 'Reativar ' + selectedIds.size + ' lead(s)?', function () {
        ajaxAction('/mailing/bulk-reactivate-leads', { ids: Array.from(selectedIds) }, 'Reativando leads...');
      });
    });

    // Delete bulk
    $(document).on('click', '.gl-bulk-btn[data-action="delete"]', function () {
      if (selectedIds.size === 0) return;
      showConfirm('Excluir Leads Permanentemente', 'Excluir ' + selectedIds.size + ' lead(s) permanentemente? Esta acao NAO pode ser desfeita.', function () {
        ajaxAction('/mailing/bulk-delete-leads', { ids: Array.from(selectedIds) }, 'Excluindo leads...');
      });
    });
  }

  // ============================================
  // Individual Actions
  // ============================================
  // Transfer single
  $(document).on('click', '.js-action-transfer', function () {
    const id = $(this).data('id');
    $('#transferSubtitle').text('Transferir 1 lead');
    $('#modalTransfer').data('mode', 'single').data('leadId', id).modal('show');
  });

  // Preditiva single (active leads)
  $(document).on('click', '.js-action-preditiva', function () {
    const id = $(this).data('id');
    showConfirm('Enviar para Preditiva', 'Enviar este lead para a fila preditiva?', function () {
      ajaxAction('/comercial/sendMultipleLeadsPredictive', { ids: [id] }, 'Enviando para preditiva...');
    });
  });

  // Preditiva single (discarded leads)
  $(document).on('click', '.js-action-preditiva-descartado', function () {
    const id = $(this).data('id');
    showConfirm('Enviar para Preditiva', 'Reativar e enviar este lead descartado para a fila preditiva?', function () {
      ajaxAction('/mailing/send-descartado-preditiva', { id: id }, 'Enviando para preditiva...');
    });
  });

  // Reactivate single
  $(document).on('click', '.js-action-reactivate', function () {
    const id = $(this).data('id');
    showConfirm('Reativar Lead', 'Reativar este lead descartado?', function () {
      ajaxAction('/mailing/reactivate-lead', { id: id }, 'Reativando...');
    });
  });

  // Discard single
  $(document).on('click', '.js-action-discard', function () {
    const id = $(this).data('id');
    showConfirm('Descartar Lead', 'Descartar este lead? Ele podera ser reativado depois.', function () {
      ajaxAction('/mailing/discard-lead', { id: id }, 'Descartando...');
    });
  });

  // Delete single
  $(document).on('click', '.js-action-delete', function () {
    const id = $(this).data('id');
    showConfirm('Excluir Lead', 'Excluir este lead permanentemente? Esta acao NAO pode ser desfeita.', function () {
      ajaxAction('/mailing/bulk-delete-leads', { ids: [id] }, 'Excluindo...');
    });
  });

  // Comments
  $(document).on('click', '.js-action-comments', function () {
    const id = $(this).data('id');
    $('#comentariosBody').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');
    $('#modalComentarios').modal('show');

    $.ajax({
      url: '/relatorios/lead-comentarios/' + id,
      method: 'GET',
      success: function (response) {
        if (response.success) {
          $('#comentariosBody').html(buildCommentsHtml(response.data));
        } else {
          $('#comentariosBody').html('<div class="alert alert-warning">' + (response.message || 'Nenhum dado encontrado') + '</div>');
        }
      },
      error: function () {
        $('#comentariosBody').html('<div class="alert alert-danger">Erro ao carregar historico</div>');
      }
    });
  });

  // ============================================
  // Modals
  // ============================================
  function initModals() {
    // Confirm transfer
    $('#btnConfirmTransfer').on('click', function () {
      const userId = $('#transferUserId').val();
      const tabulationId = $('#transferTabulationId').val();
      const mode = $('#modalTransfer').data('mode');

      if (!userId) {
        toastr.warning('Selecione um corretor');
        return;
      }

      if (mode === 'single') {
        const leadId = $('#modalTransfer').data('leadId');
        $.ajax({
          url: '/comercial/transferContact',
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
          data: { idMailing: leadId, user_id: userId, tabulation_id: tabulationId },
          success: function () {
            $('#modalTransfer').modal('hide');
            toastr.success('Lead transferido com sucesso');
            afterAction();
          },
          error: function () {
            toastr.error('Erro ao transferir lead');
          }
        });
      } else {
        // Bulk
        $.ajax({
          url: '/comercial/transferContactInNulk',
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
          data: { selectedLeadIds: Array.from(selectedIds).join(','), user_id: userId, tabulation_id: tabulationId },
          success: function () {
            $('#modalTransfer').modal('hide');
            toastr.success('Leads transferidos com sucesso');
            afterAction();
          },
          error: function () {
            toastr.error('Erro ao transferir leads');
          }
        });
      }
    });

    // Reset transfer modal on close
    $('#modalTransfer').on('hidden.bs.modal', function () {
      $('#transferUserId, #transferTabulationId').val('').trigger('change');
    });

    // Confirm action modal
    $('#btnConfirmAction').on('click', function () {
      $('#modalConfirm').modal('hide');
      if (pendingConfirmAction) {
        pendingConfirmAction();
        pendingConfirmAction = null;
      }
    });
  }

  function showConfirm(title, message, callback) {
    $('#confirmTitle').text(title);
    $('#confirmMessage').text(message);
    pendingConfirmAction = callback;
    $('#modalConfirm').modal('show');
  }

  // ============================================
  // AJAX Helpers
  // ============================================
  function ajaxAction(url, data, loadingMsg) {
    if (loadingMsg) toastr.info(loadingMsg);

    $.ajax({
      url: url,
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      data: data,
      success: function (response) {
        toastr.clear();
        toastr.success(response.message || 'Acao realizada com sucesso');
        afterAction();
      },
      error: function (xhr) {
        toastr.clear();
        const msg = xhr.responseJSON?.message || 'Erro ao realizar acao';
        toastr.error(msg);
      }
    });
  }

  function afterAction() {
    clearSelection();
    table.ajax.reload();
    refreshKPIs();
    fetchImportDates();
  }

  // ============================================
  // Comments HTML Builder
  // ============================================
  function buildCommentsHtml(data) {
    var comentarios = data.comentarios || [];
    var atividades = data.atividades || [];
    var lead = data.lead || {};

    if (comentarios.length === 0 && atividades.length === 0) {
      return '<div class="text-center py-4"><i class="ri-chat-3-line ri-48px text-muted mb-3 d-block"></i><p class="text-muted">Nenhum historico encontrado para este lead.</p></div>';
    }

    var todosItens = [];

    comentarios.forEach(function (c) {
      todosItens.push({
        tipo: 'comentario', data: c.created_at, autor: c.autor || c.usuario || 'Sistema',
        conteudo: c.anotacao, legado: c.legado, supervisao: c.supervisao
      });
    });

    atividades.forEach(function (a) {
      todosItens.push({
        tipo: 'atividade', data: a.created_at, autor: a.usuario || 'Sistema',
        conteudo: a.log_descricao, tabulacao_anterior: a.tabulacao_anterior, tabulacao_atual: a.tabulacao_atual
      });
    });

    todosItens.sort(function (a, b) { return new Date(b.data) - new Date(a.data); });

    var html = '<div class="mb-3"><h6 class="mb-1">' + (lead.nome_cliente || 'Lead') + '</h6><small class="text-muted">Historico de atividades e comentarios</small></div>';
    html += '<div style="max-height:400px;overflow-y:auto;">';

    todosItens.forEach(function (item) {
      var badgeClass, badgeText;
      if (item.tipo === 'comentario') {
        if (item.supervisao === 'Y') { badgeClass = 'bg-label-warning'; badgeText = 'SUPERVISAO'; }
        else if (item.legado === 'Y') { badgeClass = 'bg-label-info'; badgeText = 'LEGADO'; }
        else { badgeClass = 'bg-label-primary'; badgeText = 'COMENTARIO'; }
      } else {
        badgeClass = 'bg-label-success'; badgeText = 'ATIVIDADE';
      }

      var dataFormatada = new Date(item.data).toLocaleString('pt-BR');

      html += '<ul class="timeline pb-0 mb-0"><li class="timeline-item timeline-item-transparent border-primary">';
      html += '<span class="timeline-point timeline-point-primary"></span>';
      html += '<div class="timeline-event"><div class="timeline-header mb-1">';
      html += '<h6 class="mb-0">' + item.autor + ' <span class="badge ' + badgeClass + '">' + badgeText + '</span></h6>';
      html += '<small class="text-muted">' + dataFormatada + '</small></div>';
      html += '<p class="mt-1 mb-2">' + (item.conteudo || '') + '</p>';

      if (item.tipo === 'atividade' && item.tabulacao_anterior && item.tabulacao_atual) {
        html += '<small class="text-muted"><strong>Mudanca:</strong> ' + item.tabulacao_anterior + ' &rarr; ' + item.tabulacao_atual + '</small>';
      }

      html += '</div></li></ul>';
    });

    html += '</div>';
    return html;
  }

})();
