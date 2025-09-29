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
  // Filtros
  // ---------------------------
  const $fOperadora = $('#filtroOperadora');
  const $fModalidade = $('#filtroModalidade');
  $('#btnLimparFiltros').on('click', function () {
    $fOperadora.val('');
    $fModalidade.val('');
    rulesDT.ajax.reload(null, false);
  });
  $('#btnAplicarFiltros').on('click', function () {
    rulesDT.ajax.reload();
  });

  // ---------------------------
  // DataTable de Regras
  // ---------------------------
  const $table = $('#rulesTable');
  const rulesDT = $table.DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: ROUTES.index,
      data: function (d) {
        d.operadora_id = $fOperadora.val() || '';
        d.modalidade = $fModalidade.val() || '';
      }
    },
    order: [[1, 'asc']],
    columns: [
      { data: 'id', name: 'id', width: 40 },
      { data: 'operadora_nome', name: 'operadoras.nome' },
      { data: 'modalidade', name: 'modalidade', render: (val) => {
          if (!val) return '';
          const label = val.toUpperCase() === 'PME' ? 'PME' : 'ADESÃO';
          const cls = val.toUpperCase() === 'PME' ? 'badge-pme' : 'badge-adesao';
          return `<span class="badge ${cls}">${label}</span>`;
        }
      },
      { data: null, orderable: false, render: (row) => {
          if (!row.vitalicio_active) return '<span class="badge bg-secondary">Inativo</span>';
          const pct = parseFloat(row.vitalicio_percent || 0).toFixed(2).replace('.', ',');
          const start = row.vitalicio_starts_at_installment || '-';
          return `<span class="badge bg-success">Ativo</span> <small>${pct}% a partir da ${start}ª</small>`;
        }
      },
      { data: 'observacao', name: 'observacao', defaultContent: '' },
      { data: null, orderable: false, width: 140, render: (row) => {
          const dataAttr = [
            `data-id="${row.id}"`,
            `data-operadora_id="${row.operadora_id}"`,
            `data-operadora_nome="${(row.operadora_nome || '').replace(/"/g, '&quot;')}"`,
            `data-modalidade="${row.modalidade}"`,
            `data-vitalicio_active="${row.vitalicio_active ? 1 : 0}"`,
            `data-vitalicio_percent="${row.vitalicio_percent || ''}"`,
            `data-vitalicio_starts="${row.vitalicio_starts_at_installment || ''}"`,
            `data-observacao="${(row.observacao || '').replace(/"/g, '&quot;')}"`
          ].join(' ');

          return `
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-primary btn-edit" ${dataAttr}>
                <i class="ri-edit-2-line"></i>
              </button>
              <button type="button" class="btn btn-outline-danger btn-del" data-id="${row.id}">
                <i class="ri-delete-bin-6-line"></i>
              </button>
              <button type="button" class="btn btn-outline-dark btn-parcelas" data-id="${row.id}" data-title="${(row.operadora_nome || '').replace(/"/g,'&quot;')} - ${row.modalidade}">
                <i class="ri-list-ordered-2"></i>
              </button>
            </div>
          `;
        }
      }
    ],
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
    }
  });

  $('#btnRecarregar').on('click', function () {
    rulesDT.ajax.reload(null, false);
  });

  // ---------------------------
  // Nova Regra / Editar Regra
  // ---------------------------
  const $modalRegra = $('#modalRegra');
  const $formRegra = $('#formRegra');

  function openCreateRule() {
    $('#tituloModalRegra').text('Nova Regra');
    $('#regraId').val('');
    $('#regraOperadora').val('');
    $('#regraModalidade').val('');
    $('#regraVitalicioAtivo').val('0');
    $('#regraVitalicioPercent').val('');
    $('#regraVitalicioStart').val('');
    $('#regraObs').val('');
    $('#btnExcluirRegra').hide();
    $formRegra.attr('action', ROUTES.store);
    $modalRegra.modal('show');
  }

  function openEditRule(btn) {
    const $b = $(btn);
    $('#tituloModalRegra').text('Editar Regra');
    $('#regraId').val($b.data('id'));
    $('#regraOperadora').val($b.data('operadora_id'));
    $('#regraModalidade').val($b.data('modalidade'));
    $('#regraVitalicioAtivo').val(String($b.data('vitalicio_active') || 0));
    $('#regraVitalicioPercent').val($b.data('vitalicio_percent') || '');
    $('#regraVitalicioStart').val($b.data('vitalicio_starts') || '');
    $('#regraObs').val($b.data('observacao') || '');

    const destroyUrl = subst(ROUTES.destroy, { '__ID__': String($b.data('id')) });
    $('#btnExcluirRegra').data('href', destroyUrl).show();

    const updateUrl = subst(ROUTES.update, { '__ID__': String($b.data('id')) });
    $formRegra.attr('action', updateUrl);
    $modalRegra.modal('show');
  }

  $('#btnNovaRegra').on('click', openCreateRule);

  $table.on('click', '.btn-edit', function () {
    openEditRule(this);
  });

  // Salvar (create/update)
  $formRegra.on('submit', async function (e) {
    e.preventDefault();
    const form = e.currentTarget;
    const action = form.getAttribute('action');
    const id = $('#regraId').val();
    const method = id ? 'PUT' : 'POST';

    const fd = new FormData(form);
    // Laravel espera _method para PUT via form-data
    if (method === 'PUT') fd.append('_method', 'PUT');

    try {
      await http('POST', action, fd);
      $modalRegra.modal('hide');
      swal({ icon: 'success', title: 'Salvo!', timer: 1500, showConfirmButton: false });
      rulesDT.ajax.reload(null, false);
    } catch (err) {
      console.error(err);
      swal({ icon: 'error', title: 'Erro ao salvar', html: `<pre class="text-start">${(err.message || '').substring(0, 1000)}</pre>` });
    }
  });

  // Excluir Regra (dentro do modal)
  $('#btnExcluirRegra').on('click', async function () {
    const href = $(this).data('href');
    if (!href) return;

    const ok = await swalConfirm('Excluir regra?', 'Essa ação não pode ser desfeita.');
    if (!ok.isConfirmed) return;

    try {
      const fd = new FormData();
      fd.append('_method', 'DELETE');
      await http('POST', href, fd);
      $modalRegra.modal('hide');
      swal({ icon: 'success', title: 'Excluída!', timer: 1200, showConfirmButton: false });
      rulesDT.ajax.reload(null, false);
    } catch (err) {
      console.error(err);
      swal({ icon: 'error', title: 'Erro ao excluir', text: err.message || 'Falha ao excluir' });
    }
  });

  // Excluir via botão da linha
  $table.on('click', '.btn-del', async function () {
    const id = $(this).data('id');
    const href = subst(ROUTES.destroy, { '__ID__': String(id) });

    const ok = await swalConfirm('Excluir regra?', 'Essa ação não pode ser desfeita.');
    if (!ok.isConfirmed) return;

    try {
      const fd = new FormData();
      fd.append('_method', 'DELETE');
      await http('POST', href, fd);
      swal({ icon: 'success', title: 'Excluída!', timer: 1200, showConfirmButton: false });
      rulesDT.ajax.reload(null, false);
    } catch (err) {
      console.error(err);
      swal({ icon: 'error', title: 'Erro ao excluir', text: err.message || 'Falha ao excluir' });
    }
  });

  // ---------------------------
  // Parcelas (lista + CRUD)
  // ---------------------------
  const $modalParcelas = $('#modalParcelas');
  const $parcelasTbody = $('#parcelasTable tbody');
  const $parcelasLegenda = $('#parcelasLegenda');
  let currentRuleId = null;

  async function carregarParcelas(ruleId) {
    const url = subst(ROUTES.instIndex, { '__RULE_ID__': String(ruleId) });
    const data = await http('GET', url);
    // Espera-se um array [{id, installment_number, percent, payer, month_offset, note}]
    $parcelasTbody.empty();
    if (!Array.isArray(data) || data.length === 0) {
      $parcelasTbody.append(`<tr><td colspan="6" class="text-center text-muted">Sem parcelas cadastradas.</td></tr>`);
      return;
    }
    data.sort((a, b) => (a.installment_number ?? 999) - (b.installment_number ?? 999));

    for (const it of data) {
      const pct = Number(it.percent || 0).toFixed(2).replace('.', ',');
      const num = it.installment_number ?? '—';
      const note = it.note ? String(it.note) : '';
      $parcelasTbody.append(`
        <tr data-id="${it.id}">
          <td>${num}</td>
          <td>${pct}</td>
          <td>${it.payer || ''}</td>
          <td>${it.month_offset ?? 0}</td>
          <td>${note.replace(/</g,'&lt;')}</td>
          <td>
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-primary btn-parcela-edit"
                data-id="${it.id}"
                data-num="${it.installment_number || ''}"
                data-percent="${it.percent || ''}"
                data-payer="${it.payer || ''}"
                data-offset="${it.month_offset || 0}"
                data-note="${(it.note || '').replace(/"/g,'&quot;')}">
                <i class="ri-edit-2-line"></i>
              </button>
              <button class="btn btn-outline-danger btn-parcela-del" data-id="${it.id}">
                <i class="ri-delete-bin-6-line"></i>
              </button>
            </div>
          </td>
        </tr>
      `);
    }
  }

  // Abrir modal Parcelas
  $table.on('click', '.btn-parcelas', async function () {
    currentRuleId = $(this).data('id');
    const titulo = $(this).data('title') || '';
    $parcelasLegenda.text(titulo);
    try {
      await carregarParcelas(currentRuleId);
      $modalParcelas.modal('show');
    } catch (err) {
      console.error(err);
      swal({ icon: 'error', title: 'Erro ao carregar parcelas', text: err.message || '' });
    }
  });

  // ---------------------------
  // Form de Parcela (add/edit)
  // ---------------------------
  const $modalParcelaForm = $('#modalParcelaForm');
  const $formParcela = $('#formParcela');

  function openParcelaCreate() {
    $('#tituloModalParcela').text('Adicionar Parcela');
    $('#parcelaRuleId').val(currentRuleId);
    $('#parcelaId').val('');
    $('#parcelaNumero').val('');
    $('#parcelaPercent').val('');
    $('#parcelaOffset').val('0');
    $('#parcelaPagador').val('');
    $('#parcelaObs').val('');
    $('#btnExcluirParcela').hide().data('href', '');
    $formParcela.attr('action', ROUTES.instStore);
    $modalParcelaForm.modal('show');
  }

  function openParcelaEdit(btn) {
    const $b = $(btn);
    $('#tituloModalParcela').text('Editar Parcela');
    $('#parcelaRuleId').val(currentRuleId);
    $('#parcelaId').val($b.data('id'));
    $('#parcelaNumero').val($b.data('num') || '');
    $('#parcelaPercent').val($b.data('percent') || '');
    $('#parcelaOffset').val($b.data('offset') || 0);
    $('#parcelaPagador').val($b.data('payer') || '');
    $('#parcelaObs').val($b.data('note') || '');

    const hrefDel = subst(ROUTES.instDestroy, { '__ID__': String($b.data('id')) });
    $('#btnExcluirParcela').show().data('href', hrefDel);

    const hrefUpd = subst(ROUTES.instUpdate, { '__ID__': String($b.data('id')) });
    $formParcela.attr('action', hrefUpd);
    $modalParcelaForm.modal('show');
  }

  $('#btnAddParcela').on('click', function () {
    if (!currentRuleId) {
      swal({ icon: 'info', title: 'Selecione uma regra primeiro.' });
      return;
    }
    openParcelaCreate();
  });

  $parcelasTbody.on('click', '.btn-parcela-edit', function () {
    openParcelaEdit(this);
  });

  // Salvar parcela (create/update)
  $formParcela.on('submit', async function (e) {
    e.preventDefault();
    const form = e.currentTarget;
    const action = form.getAttribute('action');
    const id = $('#parcelaId').val();
    const method = id ? 'PUT' : 'POST';

    const fd = new FormData(form);
    if (method === 'PUT') fd.append('_method', 'PUT');

    try {
      await http('POST', action, fd);
      $modalParcelaForm.modal('hide');
      await carregarParcelas(currentRuleId);
      swal({ icon: 'success', title: 'Parcela salva!', timer: 1200, showConfirmButton: false });
    } catch (err) {
      console.error(err);
      swal({ icon: 'error', title: 'Erro ao salvar parcela', html: `<pre class="text-start">${(err.message || '').substring(0, 1000)}</pre>` });
    }
  });

  // Excluir parcela
  $('#btnExcluirParcela').on('click', async function () {
    const href = $(this).data('href');
    if (!href) return;
    const ok = await swalConfirm('Excluir parcela?', 'Essa ação não pode ser desfeita.');
    if (!ok.isConfirmed) return;

    try {
      const fd = new FormData();
      fd.append('_method', 'DELETE');
      await http('POST', href, fd);
      $modalParcelaForm.modal('hide');
      await carregarParcelas(currentRuleId);
      swal({ icon: 'success', title: 'Parcela excluída!', timer: 1200, showConfirmButton: false });
    } catch (err) {
      console.error(err);
      swal({ icon: 'error', title: 'Erro ao excluir parcela', text: err.message || '' });
    }
  });

  // Excluir parcela direto na linha
  $parcelasTbody.on('click', '.btn-parcela-del', async function () {
    const id = $(this).data('id');
    const href = subst(ROUTES.instDestroy, { '__ID__': String(id) });
    const ok = await swalConfirm('Excluir parcela?', 'Essa ação não pode ser desfeita.');
    if (!ok.isConfirmed) return;

    try {
      const fd = new FormData();
      fd.append('_method', 'DELETE');
      await http('POST', href, fd);
      await carregarParcelas(currentRuleId);
      swal({ icon: 'success', title: 'Parcela excluída!', timer: 1200, showConfirmButton: false });
    } catch (err) {
      console.error(err);
      swal({ icon: 'error', title: 'Erro ao excluir parcela', text: err.message || '' });
    }
  });

});
