/* global $, toastr, moment, bootstrap */
(function () {
  'use strict';

  const $root = $('#pgmts-root');
  if (!$root.length) return;

  const URL_DATA = String($root.data('url') || '');
  const PDF_BASE = String($root.data('pdf-base') || '');                 // .../PAYMENT_ID
  const URL_ESTORNO_BASE = String($root.data('estornar-url') || '');    // .../PAYMENT_ID
  const URL_CONTAS_BY_USER_BASE = String($root.data('contas-base') || ''); // ...?user_id=USER_ID
  const URL_PAGAR_BASE = String($root.data('pagar-base') || '');        // .../PAYMENT_ID
  const USER_ROLE = String($root.data('role') || '');

  const CSRF = (document.querySelector('meta[name="csrf-token"]') &&
               document.querySelector('meta[name="csrf-token"]').getAttribute('content')) || '';

  const fmtBRL = (v) => (Number(v) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  const fmtPct = (v) => v == null ? '—' : `${Number(v).toFixed(2)}%`;

  const $mes = $('#filtro-mes');
  const $vend = $('#filtro-vendedor');
  const $creator = $('#filtro-criado-por');
  const $apply = $('#btn-aplicar');

  $vend.select2({ width: '100%', placeholder: 'Todos' });
  $creator.select2({ width: '100%', placeholder: 'Todos' });

  const table = $('#tabela-pagamentos').DataTable({
    processing: true,
    serverSide: false,
    searching: false,
    paging: true,
    info: true,
    responsive: true,
    order: [[3, 'desc']], // Data Pagto (pago_em)
    ajax: {
      url: URL_DATA,
      data: function (d) {
        d.mes = $mes.val();
        d.vendedor_id = $vend.val() || '';
        d.created_by = $creator.val() || '';
      }
    },
    columns: [
      { data: 'id' },
      { data: 'vendedor' },
      { data: 'mes' },
      // >>> Data Pagto agora usa 'pago_em'
      { data: 'pago_em', render: d => d ? moment(d).format('DD/MM/YYYY') : '-' },
      { data: 'percentual_comissao', className: 'text-end', render: fmtPct },
      { data: 'percentual_imposto', className: 'text-end', render: fmtPct },
      { data: 'total_bruto', className: 'text-end', render: fmtBRL },
      { data: 'total_imposto', className: 'text-end', render: fmtBRL },
      { data: 'total_liquido', className: 'text-end', render: fmtBRL },
      { data: 'total_receber', className: 'text-end', render: fmtBRL },
      { data: 'criado_por' },
      {
        data: null,
        orderable: false,
        className: 'text-nowrap',
        render: function (row) {
          const pdfUrl = PDF_BASE.replace('PAYMENT_ID', row.id);
          const estornoUrl = URL_ESTORNO_BASE ? URL_ESTORNO_BASE.replace('PAYMENT_ID', row.id) : null;

          // >>> regra correta: pago_em define se está pago
          const isPago = !!row.pago_em;
          const userId = (row.user_id != null ? row.user_id : (row.vendedor_id != null ? row.vendedor_id : ''));

          let html = `
            <div class="btn-group btn-group-sm" role="group" aria-label="Ações">
              <a class="btn btn-outline-primary"
                 href="${pdfUrl}"
                 target="_blank"
                 title="Abrir PDF"
                 data-bs-toggle="tooltip"
                 data-bs-placement="top">
                <i class="ri-file-pdf-line"></i>
                <span class="d-none d-md-inline ms-1">PDF</span>
              </a>
          `;

          if (!isPago) {
            html += `
              <button type="button"
                      class="btn btn-success js-pagar"
                      data-id="${row.id}"
                      data-user="${userId}"
                      title="Registrar pagamento"
                      data-bs-toggle="tooltip"
                      data-bs-placement="top">
                <i class="ri-bank-card-line"></i>
                <span class="d-none d-md-inline ms-1">Pagar</span>
              </button>
            `;
          } else {
            // ícone sem data; data só no tooltip
            const dt = moment(row.pago_em).format('DD/MM/YYYY');
            html += `
              <button type="button"
                      class="btn btn-outline-success disabled"
                      tabindex="-1"
                      title="Pago em ${dt}"
                      data-bs-toggle="tooltip"
                      data-bs-placement="top">
                <i class="ri-bank-card-2-fill"></i>
              </button>
            `;
          }

          if (estornoUrl && USER_ROLE !== '1') {
            html += `
              <button type="button"
                      class="btn btn-outline-danger js-estornar"
                      data-url="${estornoUrl}"
                      title="Estornar"
                      data-bs-toggle="tooltip"
                      data-bs-placement="top">
                <i class="ri-arrow-go-back-line"></i>
                <span class="d-none d-md-inline ms-1">Estornar</span>
              </button>
            `;
          }

          html += `</div>`;
          return html;
        }
      }
    ],
    language: {
      url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
    },
    drawCallback: function () {
      const data = table.rows({ search: 'applied' }).data().toArray();
      const sum = (key) => data.reduce((acc, r) => acc + (Number(r[key]) || 0), 0);

      $('#ft-bruto').text(fmtBRL(sum('total_bruto')));
      $('#ft-imp').text(fmtBRL(sum('total_imposto')));
      $('#ft-liq').text(fmtBRL(sum('total_liquido')));
      $('#ft-total').text(fmtBRL(sum('total_receber')));

      // Tooltips Bootstrap (re-init a cada draw)
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        const inst = bootstrap.Tooltip.getInstance(el);
        if (inst) inst.dispose();
        new bootstrap.Tooltip(el);
      });
    }
  });

  $apply.on('click', () => table.ajax.reload());

  // ========= Modal / Pagar =========
  const modalEl = document.getElementById('modalPagar');
  const modal = new bootstrap.Modal(modalEl);
  const selConta = document.getElementById('pg_conta');
  const inpPagoEm = document.getElementById('pg_pago_em');
  const inpPagamentoId = document.getElementById('pg_pagamento_id');
  const inpUserId = document.getElementById('pg_user_id');
  const alertNoAcc = document.getElementById('pg_alert_no_accounts');

  // hoje padrão
  const today = new Date();
  inpPagoEm.value = today.toISOString().slice(0, 10); // YYYY-MM-DD

  // Abrir modal pagar (delegado)
  $(document).on('click', '.js-pagar', async function () {
    const pagamentoId = this.dataset.id;
    const userId = this.dataset.user;

    if (!userId) {
      toastr?.warning('ID do vendedor ausente no dataset da tabela.');
      return;
    }

    inpPagamentoId.value = pagamentoId;
    inpUserId.value = userId;

    selConta.innerHTML = `<option value="" selected>Carregando contas...</option>`;
    selConta.disabled = true;
    alertNoAcc.classList.add('d-none');

    try {
      const url = URL_CONTAS_BY_USER_BASE.replace('USER_ID', encodeURIComponent(userId));
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      const contas = await res.json();

      selConta.innerHTML = `<option value="">Usar conta padrão (se houver)</option>`;

      if (Array.isArray(contas) && contas.length) {
        contas.forEach(c => {
          const rotulo = [
            c.descricao ? `[${c.descricao}]` : null,
            c.banco || null,
            (c.agencia || c.conta) ? `${c.agencia ?? ''}/${c.conta ?? ''}${c.digito ? '-' + c.digito : ''}` : null,
            c.chave_pix ? `PIX: ${c.chave_pix}` : null,
            c.is_default ? '⭐ padrão' : null
          ].filter(Boolean).join(' · ');
          const opt = document.createElement('option');
          opt.value = c.id;
          opt.textContent = rotulo;
          if (c.is_default) opt.selected = true;
          selConta.appendChild(opt);
        });
        selConta.disabled = false;
      } else {
        selConta.innerHTML = `<option value="" selected>Nenhuma conta encontrada</option>`;
        alertNoAcc.classList.remove('d-none');
      }

      modal.show();
    } catch (err) {
      console.error(err);
      selConta.innerHTML = `<option value="" selected>Erro ao carregar contas</option>`;
      alertNoAcc.classList.remove('d-none');
      modal.show();
    }
  });

  // Submit do pagamento
  document.getElementById('formPagar').addEventListener('submit', async (e) => {
    e.preventDefault();

    const pagamentoId = inpPagamentoId.value;
    const contaId = selConta.value || ''; // vazio => usa padrão no backend
    const pagoEm = inpPagoEm.value;

    const url = URL_PAGAR_BASE.replace('PAYMENT_ID', encodeURIComponent(pagamentoId));

    const btnSubmit = e.target.querySelector('button[type="submit"]');
    btnSubmit.disabled = true;

    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': CSRF
        },
        body: JSON.stringify({
          conta_pagamento_id: contaId || null,
          pago_em: pagoEm
        })
      });

      if (!res.ok) {
        const t = await res.text();
        throw new Error(t || 'Erro ao registrar pagamento');
      }

      const data = await res.json();

      // Atualiza linha do DataTable (procura por ID)
      table.rows().every(function () {
        const r = this.data();
        if (String(r.id) === String(pagamentoId)) {
          // fonte da verdade: backend devolve pago_em
          r.pago_em = data.pago_em || pagoEm;
          // não misturar ?? com || — usa ternário
          r.conta_pagamento_id = (data.conta_pagamento_id != null ? data.conta_pagamento_id : (contaId || null));
          this.data(r).draw(false);
        }
      });

      toastr?.success('Pagamento registrado com sucesso!');
      modal.hide();
    } catch (err) {
      console.error(err);
      toastr?.error('Falha ao registrar pagamento.');
    } finally {
      btnSubmit.disabled = false;
    }
  });

  // Estorno (delegado)
  $(document).on('click', '.js-estornar', async function () {
    const url = $(this).data('url');
    if (!url) return;
    if (!confirm('Confirma estornar este pagamento? As vendas vinculadas voltarão a ficar pendentes.')) return;

    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': CSRF
        }
      });
      if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || 'Falha ao estornar.');
      }
      const json = await res.json();
      toastr?.success(json.message || 'Pagamento estornado.');
      table.ajax.reload(null, false);
    } catch (e) {
      console.error(e);
      toastr?.error(e.message || 'Erro ao estornar.');
    }
  });

})();
