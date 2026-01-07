'use strict';

$(function () {
  // ---------------------------
  // Helpers
  // ---------------------------
  const $routes = $('#regrasRecebimentosRoutes');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || $('input[name="_token"]').first().val();

  const ROUTES = {
    index: $routes.data('index'),
    store: $routes.data('store'),
    update: $routes.data('update'), // tem __ID__
    destroy: $routes.data('destroy'), // tem __ID__
    instIndex: $routes.data('installments-index'), // tem __RULE_ID__
    instStore: $routes.data('installments-store'),
    instUpdate: $routes.data('installments-update'), // tem __ID__
    instDestroy: $routes.data('installments-destroy') // tem __ID__
  };

  const subst = (tpl, map) => {
    let out = tpl;
    Object.entries(map).forEach(([k, v]) => { out = out.replace(k, v); });
    return out;
  };

  const swal = (opts) => Swal.fire(Object.assign({
    confirmButtonText: 'OK',
    cancelButtonText: 'Cancelar',
    focusConfirm: true
  }, opts || {}));

  const swalConfirm = (title = 'Tem certeza?', text = 'Essa ação não pode ser desfeita.') => Swal.fire({
    title, text, icon: 'warning', showCancelButton: true,
    confirmButtonText: 'Sim, confirmar', cancelButtonText: 'Cancelar'
  });

  async function http(method, url, bodyObj) {
    const headers = { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf };
    if (bodyObj && !(bodyObj instanceof FormData)) headers['Content-Type'] = 'application/json';
    const res = await fetch(url, {
      method,
      headers,
      body: bodyObj ? (bodyObj instanceof FormData ? bodyObj : JSON.stringify(bodyObj)) : undefined
    });
    if (!res.ok) {
      const msg = await res.text();
      throw new Error(msg || `HTTP ${res.status}`);
    }
    const ct = res.headers.get('content-type') || '';
    return ct.includes('application/json') ? res.json() : res.text();
  }

  // ---------------------------
  // KPI Update
  // ---------------------------
  function updateKPIs(data) {
    let total = 0, pme = 0, adesao = 0, vitalicias = 0;

    if (data && data.data && Array.isArray(data.data)) {
      total = data.recordsTotal || data.data.length;

      data.data.forEach(row => {
        if (row.categoria && row.categoria.toUpperCase() === 'PME') pme++;
        if (row.categoria && row.categoria.toUpperCase() === 'ADESAO') adesao++;
        if (row.vitalicio == 1 || row.vitalicio === true) vitalicias++;
      });
    }

    $('#kpiTotalRegras').text(total);
    $('#kpiPME').text(pme);
    $('#kpiAdesao').text(adesao);
    $('#kpiVitalicias').text(vitalicias);
    $('#tableCount').text(total);
  }

  // ---------------------------
  // DataTable de Regras
  // ---------------------------
  const $table = $('#rulesTable');
  const rulesDT = $table.DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: ROUTES.index,
      dataSrc: function(json) {
        updateKPIs(json);
        return json.data;
      }
    },
    order: [[1, 'asc']],
    columns: [
      { data: 'id', name: 'id', width: 50 },
      { data: 'operadora_nome', name: 'operadoras.nome' },
      {
        data: 'categoria', name: 'categoria', render: (val) => {
          if (!val) return '';
          const cls = val.toUpperCase() === 'PME' ? 'badge-pme' : 'badge-adesao';
          return `<span class="${cls}">${val.toUpperCase()}</span>`;
        }
      },
      {
        data: 'total_percentual', name: 'total_percentual',
        render: (v) => v ? `<span class="percentual-value">${parseFloat(v).toFixed(2)}%</span>` : '-'
      },
      {
        data: 'vitalicio', name: 'vitalicio', render: (v) =>
          v ? '<span class="badge-vitalicio">Sim</span>' : '<span class="badge-nao-vitalicio">Não</span>'
      },
      { data: 'descricao', name: 'descricao', defaultContent: '-' },
      {
        data: null, orderable: false, width: 140, render: (row) => {
          const dataAttr = [
            `data-id="${row.id}"`,
            `data-operadora_id="${row.operadora_id}"`,
            `data-operadora_nome="${(row.operadora_nome || '').replace(/"/g, '&quot;')}"`,
            `data-categoria="${row.categoria}"`,
            `data-total_percentual="${row.total_percentual || ''}"`,
            `data-vitalicio="${row.vitalicio}"`,
            `data-percentual_vitalicio="${row.percentual_vitalicio || ''}"`,
            `data-descricao="${(row.descricao || '').replace(/"/g, '&quot;')}"`
          ].join(' ');

          return `
            <div class="actions-group">
              <button type="button" class="btn-action btn-edit" ${dataAttr} title="Editar">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
              </button>
              <button type="button" class="btn-action btn-parcelas" data-id="${row.id}" data-title="${(row.operadora_nome || '').replace(/"/g, '&quot;')} - ${row.categoria}" title="Ver Parcelas">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="8" y1="6" x2="21" y2="6"/>
                  <line x1="8" y1="12" x2="21" y2="12"/>
                  <line x1="8" y1="18" x2="21" y2="18"/>
                  <line x1="3" y1="6" x2="3.01" y2="6"/>
                  <line x1="3" y1="12" x2="3.01" y2="12"/>
                  <line x1="3" y1="18" x2="3.01" y2="18"/>
                </svg>
              </button>
              <button type="button" class="btn-action btn-del" data-id="${row.id}" title="Excluir">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="3 6 5 6 21 6"/>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
              </button>
            </div>
          `;
        }
      }
    ],
    language: {
      decimal: ",",
      thousands: ".",
      processing: "Processando...",
      search: "Buscar:",
      lengthMenu: "Mostrar _MENU_ registros",
      info: "Mostrando de _START_ até _END_ de _TOTAL_ registros",
      infoEmpty: "Mostrando 0 até 0 de 0 registros",
      infoFiltered: "(filtrado de _MAX_ registros no total)",
      infoPostFix: "",
      loadingRecords: "Carregando...",
      zeroRecords: "Nenhum registro encontrado",
      emptyTable: "Nenhum dado disponível nesta tabela",
      paginate: {
        first: "Primeiro",
        previous: "Anterior",
        next: "Próximo",
        last: "Último"
      },
      aria: {
        sortAscending: ": ativar para ordenar a coluna de forma ascendente",
        sortDescending: ": ativar para ordenar a coluna de forma descendente"
      }
    }

  });

  // ---------------------------
  // Nova Regra / Editar Regra
  // ---------------------------
  const $modalRegra = $('#modalRegra');
  const $formRegra = $('#formRegra');

  // Função para mostrar/ocultar campo percentual vitalício
  function togglePercentualVitalicio() {
    if ($('#regraVitalicio').val() == '1') {
      $('#percentualVitalicioWrapper').show();
    } else {
      $('#percentualVitalicioWrapper').hide();
      $('#regraPercentualVitalicio').val('');
    }
  }

  // Evento change no select vitalício
  $('#regraVitalicio').on('change', togglePercentualVitalicio);

  $('#btnNovaRegra').on('click', function () {
    $('#tituloModalRegra').text('Nova Regra');
    $('#regraId').val('');
    $('#regraOperadora').val('');
    $('#regraCategoria').val('PME');
    $('#regraTotalPercentual').val('');
    $('#regraVitalicio').val('0');
    $('#regraPercentualVitalicio').val('');
    $('#percentualVitalicioWrapper').hide();
    $('#regraDescricao').val('');
    $('#btnExcluirRegra').hide();
    $formRegra.attr('action', ROUTES.store);
    $modalRegra.modal('show');
  });

  $table.on('click', '.btn-edit', function () {
    const $b = $(this);
    $('#tituloModalRegra').text('Editar Regra');
    $('#regraId').val($b.data('id'));
    $('#regraOperadora').val($b.data('operadora_id'));
    $('#regraCategoria').val($b.data('categoria'));
    $('#regraTotalPercentual').val($b.data('total_percentual'));
    $('#regraVitalicio').val($b.data('vitalicio'));
    $('#regraPercentualVitalicio').val($b.data('percentual_vitalicio') || '');
    $('#regraDescricao').val($b.data('descricao'));

    // Mostrar/ocultar campo percentual vitalício com base no valor atual
    if ($b.data('vitalicio') == 1) {
      $('#percentualVitalicioWrapper').show();
    } else {
      $('#percentualVitalicioWrapper').hide();
    }

    const updateUrl = subst(ROUTES.update, { '__ID__': String($b.data('id')) });
    $formRegra.attr('action', updateUrl);
    $('#btnExcluirRegra').data('href', subst(ROUTES.destroy, { '__ID__': String($b.data('id')) })).show();
    $modalRegra.modal('show');
  });

  $formRegra.on('submit', async function (e) {
    e.preventDefault();
    const action = this.getAttribute('action');
    const id = $('#regraId').val();
    const method = id ? 'PUT' : 'POST';
    const fd = new FormData(this);
    if (method === 'PUT') fd.append('_method', 'PUT');

    try {
      await http('POST', action, fd);
      $modalRegra.modal('hide');
      swal({ icon: 'success', title: 'Salvo!', timer: 1500, showConfirmButton: false });
      rulesDT.ajax.reload(null, false);
    } catch (err) {
      swal({ icon: 'error', title: 'Erro ao salvar', text: err.message || '' });
    }
  });

  $('#btnExcluirRegra').on('click', async function () {
    const href = $(this).data('href');
    if (!href) return;
    const ok = await swalConfirm('Excluir regra?', 'Essa ação não pode ser desfeita.');
    if (!ok.isConfirmed) return;
    try {
      const fd = new FormData(); fd.append('_method', 'DELETE');
      await http('POST', href, fd);
      $modalRegra.modal('hide');
      swal({ icon: 'success', title: 'Excluída!', timer: 1200, showConfirmButton: false });
      rulesDT.ajax.reload(null, false);
    } catch (err) {
      swal({ icon: 'error', title: 'Erro ao excluir', text: err.message || '' });
    }
  });

  $table.on('click', '.btn-del', async function () {
    const id = $(this).data('id');
    const href = subst(ROUTES.destroy, { '__ID__': String(id) });
    const ok = await swalConfirm('Excluir regra?', 'Essa ação não pode ser desfeita.');
    if (!ok.isConfirmed) return;
    try {
      const fd = new FormData(); fd.append('_method', 'DELETE');
      await http('POST', href, fd);
      swal({ icon: 'success', title: 'Excluída!', timer: 1200, showConfirmButton: false });
      rulesDT.ajax.reload(null, false);
    } catch (err) {
      swal({ icon: 'error', title: 'Erro ao excluir', text: err.message || '' });
    }
  });

  // ---------------------------
  // Parcelas
  // ---------------------------
  const $modalParcelas = $('#modalParcelas');
  const $parcelasTbody = $('#parcelasTable tbody');
  const $modalParcelaForm = $('#modalParcelaForm');
  const $formParcela = $('#formParcela');
  let currentRuleId = null;

  async function carregarParcelas(ruleId) {
    const url = subst(ROUTES.instIndex, { '__RULE_ID__': String(ruleId) });
    const data = await http('GET', url);
    $parcelasTbody.empty();
    if (!Array.isArray(data) || !data.length) {
      $parcelasTbody.append('<tr><td colspan="4" class="text-center text-muted py-4">Nenhuma parcela cadastrada para esta regra.</td></tr>');
      return;
    }
    data.sort((a, b) => (a.parcela ?? 0) - (b.parcela ?? 0));
    for (const it of data) {
      $parcelasTbody.append(`
        <tr data-id="${it.id}">
          <td><span class="parcela-num">${it.parcela}</span></td>
          <td><span class="percentual-value">${Number(it.percentual).toFixed(2)}%</span></td>
          <td><span class="pagador-badge">${it.pagador || '-'}</span></td>
          <td>
            <div class="actions-group">
              <button class="btn-action btn-edit btn-parcela-edit"
                data-id="${it.id}"
                data-num="${it.parcela}"
                data-percent="${it.percentual}"
                data-payer="${it.pagador || ''}"
                title="Editar">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
              </button>
              <button class="btn-action btn-del btn-parcela-del" data-id="${it.id}" title="Excluir">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="3 6 5 6 21 6"/>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
              </button>
            </div>
          </td>
        </tr>
      `);
    }
  }

  $table.on('click', '.btn-parcelas', async function () {
    currentRuleId = $(this).data('id');
    await carregarParcelas(currentRuleId);
    $modalParcelas.modal('show');
  });

  $('#btnAddParcela').on('click', function () {
    if (!currentRuleId) {
      swal({ icon: 'info', title: 'Selecione uma regra primeiro.' });
      return;
    }
    $('#tituloModalParcela').text('Adicionar Parcela');
    $('#parcelaRuleId').val(currentRuleId);
    $('#parcelaId').val('');
    $('#parcelaNumero').val('');
    $('#parcelaPercent').val('');
    $('#parcelaPagador').val('');
    $('#btnExcluirParcela').hide();
    $formParcela.attr('action', ROUTES.instStore);
    $modalParcelaForm.modal('show');
  });

  $parcelasTbody.on('click', '.btn-parcela-edit', function () {
    const $b = $(this);
    $('#tituloModalParcela').text('Editar Parcela');
    $('#parcelaRuleId').val(currentRuleId);
    $('#parcelaId').val($b.data('id'));
    $('#parcelaNumero').val($b.data('num'));
    $('#parcelaPercent').val($b.data('percent'));
    $('#parcelaPagador').val($b.data('payer'));
    $formParcela.attr('action', subst(ROUTES.instUpdate, { '__ID__': String($b.data('id')) }));
    $('#btnExcluirParcela').data('href', subst(ROUTES.instDestroy, { '__ID__': String($b.data('id')) })).show();
    $modalParcelaForm.modal('show');
  });

  $formParcela.on('submit', async function (e) {
    e.preventDefault();
    const action = this.getAttribute('action');
    const id = $('#parcelaId').val();
    const method = id ? 'PUT' : 'POST';
    const fd = new FormData(this);
    if (method === 'PUT') fd.append('_method', 'PUT');
    try {
      await http('POST', action, fd);
      $modalParcelaForm.modal('hide');
      await carregarParcelas(currentRuleId);
      swal({ icon: 'success', title: 'Parcela salva!', timer: 1200, showConfirmButton: false });
    } catch (err) {
      swal({ icon: 'error', title: 'Erro ao salvar parcela', text: err.message || '' });
    }
  });

  $('#btnExcluirParcela').on('click', async function () {
    const href = $(this).data('href');
    if (!href) return;
    const ok = await swalConfirm('Excluir parcela?', 'Essa ação não pode ser desfeita.');
    if (!ok.isConfirmed) return;
    try {
      const fd = new FormData(); fd.append('_method', 'DELETE');
      await http('POST', href, fd);
      $modalParcelaForm.modal('hide');
      await carregarParcelas(currentRuleId);
      swal({ icon: 'success', title: 'Parcela excluída!', timer: 1200, showConfirmButton: false });
    } catch (err) {
      swal({ icon: 'error', title: 'Erro ao excluir parcela', text: err.message || '' });
    }
  });

  $parcelasTbody.on('click', '.btn-parcela-del', async function () {
    const id = $(this).data('id');
    const href = subst(ROUTES.instDestroy, { '__ID__': String(id) });
    const ok = await swalConfirm('Excluir parcela?', 'Essa ação não pode ser desfeita.');
    if (!ok.isConfirmed) return;
    try {
      const fd = new FormData(); fd.append('_method', 'DELETE');
      await http('POST', href, fd);
      await carregarParcelas(currentRuleId);
      swal({ icon: 'success', title: 'Parcela excluída!', timer: 1200, showConfirmButton: false });
    } catch (err) {
      swal({ icon: 'error', title: 'Erro ao excluir parcela', text: err.message || '' });
    }
  });

});
