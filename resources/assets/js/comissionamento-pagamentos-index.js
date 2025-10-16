/* global $, toastr, moment, bootstrap */
(function () {
  'use strict';

  // Configura moment.js para português brasileiro
  if (!moment.locales().includes('pt-br')) {
    moment.defineLocale('pt-br', {
      months: 'janeiro_fevereiro_março_abril_maio_junho_julho_agosto_setembro_outubro_novembro_dezembro'.split('_'),
      monthsShort: 'jan_fev_mar_abr_mai_jun_jul_ago_set_out_nov_dez'.split('_'),
      weekdays: 'domingo_segunda-feira_terça-feira_quarta-feira_quinta-feira_sexta-feira_sábado'.split('_'),
      weekdaysShort: 'dom_seg_ter_qua_qui_sex_sáb'.split('_'),
      weekdaysMin: 'dom_2ª_3ª_4ª_5ª_6ª_sáb'.split('_'),
      longDateFormat: {
        LT: 'HH:mm',
        LTS: 'HH:mm:ss',
        L: 'DD/MM/YYYY',
        LL: 'D [de] MMMM [de] YYYY',
        LLL: 'D [de] MMMM [de] YYYY [às] HH:mm',
        LLLL: 'dddd, D [de] MMMM [de] YYYY [às] HH:mm'
      },
      calendar: {
        sameDay: '[Hoje às] LT',
        nextDay: '[Amanhã às] LT',
        nextWeek: 'dddd [às] LT',
        lastDay: '[Ontem às] LT',
        lastWeek: function () {
          return (this.day() === 0 || this.day() === 6) ?
            '[Último] dddd [às] LT' :
            '[Última] dddd [às] LT';
        },
        sameElse: 'L'
      },
      relativeTime: {
        future: 'em %s',
        past: '%s atrás',
        s: 'poucos segundos',
        ss: '%d segundos',
        m: 'um minuto',
        mm: '%d minutos',
        h: 'uma hora',
        hh: '%d horas',
        d: 'um dia',
        dd: '%d dias',
        M: 'um mês',
        MM: '%d meses',
        y: 'um ano',
        yy: '%d anos'
      },
      dayOfMonthOrdinalParse: /\d{1,2}º/,
      ordinal: '%dº'
    });
  }
  moment.locale('pt-br');

  const $root = $('#pgmts-root');
  if (!$root.length) return;

  const URL_DATA = String($root.data('url') || '');
  const PDF_BASE = String($root.data('pdf-base') || '');
  const URL_ESTORNO_BASE = String($root.data('estornar-url') || '');
  const URL_CONTAS_BY_USER_BASE = String($root.data('contas-base') || '');
  const URL_PAGAR_BASE = String($root.data('pagar-base') || '');
  const USER_ROLE = String($root.data('role') || '');

  const CSRF = (document.querySelector('meta[name="csrf-token"]') &&
               document.querySelector('meta[name="csrf-token"]').getAttribute('content')) || '';

  const fmtBRL = (v) => (Number(v) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  const fmtPct = (v) => v == null ? '—' : `${Number(v).toFixed(2)}%`;

  const $mes = $('#filtro-mes');
  const $vend = $('#filtro-vendedor');
  const $creator = $('#filtro-criado-por');
  const $status = $('#filtro-status');
  const $apply = $('#btn-aplicar');
  const $container = $('#pagamentos-container');

  $vend.select2({ width: '100%', placeholder: 'Todos' });
  $creator.select2({ width: '100%', placeholder: 'Todos' });

  // ======== Carregar e Renderizar Pagamentos ========
  async function carregarPagamentos() {
    const params = new URLSearchParams({
      mes: $mes.val() || '',
      vendedor_id: $vend.val() || '',
      created_by: $creator.val() || '',
      status: $status.val() || ''
    });

    $container.html(`
      <div class="loading-container">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Carregando...</span>
        </div>
      </div>
    `);

    try {
      const res = await fetch(`${URL_DATA}?${params.toString()}`, {
        headers: { 'Accept': 'application/json' }
      });

      if (!res.ok) throw new Error('Erro ao carregar pagamentos');

      const json = await res.json();
      const pagamentos = json.data || [];

      renderizarPagamentos(pagamentos);
      atualizarMetricas(pagamentos);

    } catch (err) {
      console.error(err);
      $container.html(`
        <div class="empty-state">
          <i class="ri-error-warning-line"></i>
          <h5>Erro ao carregar pagamentos</h5>
          <p>Tente novamente mais tarde</p>
        </div>
      `);
      toastr?.error('Erro ao carregar pagamentos');
    }
  }

  // ======== Agrupar por Data de Pagamento ========
  function renderizarPagamentos(pagamentos) {
    if (!pagamentos.length) {
      $container.html(`
        <div class="empty-state">
          <i class="ri-file-list-3-line"></i>
          <h5>Nenhum pagamento encontrado</h5>
          <p>Ajuste os filtros para ver mais resultados</p>
        </div>
      `);
      return;
    }

    // Agrupa por data_pagamento
    const grupos = {};
    pagamentos.forEach(p => {
      const data = p.data_pagamento || 'Sem data';
      if (!grupos[data]) grupos[data] = [];
      grupos[data].push(p);
    });

    // Ordena datas (mais recentes primeiro)
    const datasOrdenadas = Object.keys(grupos).sort((a, b) => {
      if (a === 'Sem data') return 1;
      if (b === 'Sem data') return -1;
      return new Date(b) - new Date(a);
    });

    let html = '';

    datasOrdenadas.forEach((data, index) => {
      const lista = grupos[data];
      const dataFormatada = data === 'Sem data' ? 'Sem data definida' : moment(data).format('DD/MM/YYYY');
      const diaSemana = data === 'Sem data' ? '' : moment(data).format('dddd');
      const groupId = `group-${index}`;

      // Calcula totais do grupo
      const totalBruto = lista.reduce((acc, p) => acc + (Number(p.total_bruto) || 0), 0);
      const totalLiquido = lista.reduce((acc, p) => acc + (Number(p.total_liquido) || 0), 0);

      html += `
        <div class="date-group">
          <div class="date-group-header card collapsed" data-group="${groupId}">
            <h4>
              <div class="date-info">
                <i class="ri-calendar-line"></i>
                ${dataFormatada}
                ${diaSemana ? `<span class="badge">${diaSemana}</span>` : ''}
                <span class="badge">${lista.length} pagamento(s)</span>
                <span class="badge">Total: ${fmtBRL(totalLiquido)}</span>
              </div>
              <i class="ri-arrow-up-s-line toggle-icon"></i>
            </h4>
          </div>
          <div class="date-group-content collapsed" id="${groupId}" style="max-height: 0;">
      `;

      lista.forEach(p => {
        html += renderizarCard(p);
      });

      html += `
          </div>
        </div>
      `;
    });

    $container.html(html);

    // Inicializa comportamento de collapse
    inicializarCollapses();
  }

  // ======== Inicializar Collapses ========
  function inicializarCollapses() {
    document.querySelectorAll('.date-group-header').forEach(header => {
      header.addEventListener('click', function() {
        const groupId = this.dataset.group;
        const content = document.getElementById(groupId);
        const toggleIcon = this.querySelector('.toggle-icon');

        // Toggle classes
        this.classList.toggle('collapsed');
        content.classList.toggle('collapsed');

        // Smooth height transition
        if (!content.classList.contains('collapsed')) {
          content.style.maxHeight = content.scrollHeight + 'px';
        } else {
          content.style.maxHeight = '0';
        }
      });
    });
  }

  // ======== Renderizar Card Individual ========
  function renderizarCard(p) {
    const isPago = !!p.pago_em;
    const userId = p.user_id || p.vendedor_id || '';
    const pdfUrl = PDF_BASE.replace('PAYMENT_ID', p.id);
    const estornoUrl = URL_ESTORNO_BASE ? URL_ESTORNO_BASE.replace('PAYMENT_ID', p.id) : null;

    // Iniciais do vendedor
    const iniciais = (p.vendedor || 'V').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();

    // Status badge
    const statusBadge = isPago
      ? `<span class="badge badge-payment bg-success">
           <i class="ri-checkbox-circle-line me-1"></i> Pago em ${moment(p.pago_em).format('DD/MM/YYYY')}
         </span>`
      : `<span class="badge badge-payment bg-warning">
           <i class="ri-time-line me-1"></i> Pendente
         </span>`;

    // Botões de ação
    let btnPagar = '';
    if (!isPago && estornoUrl && USER_ROLE !== '1') {
      btnPagar = `
        <button type="button" class="btn btn-success btn-sm js-pagar"
                data-id="${p.id}" data-user="${userId}">
          <i class="ri-bank-card-line me-1"></i> Pagar
        </button>
      `;
    }

    let btnEstornar = '';
    if (estornoUrl && USER_ROLE !== '1') {
      btnEstornar = `
        <button type="button" class="btn btn-outline-danger btn-sm js-estornar"
                data-url="${estornoUrl}">
          <i class="ri-arrow-go-back-line me-1"></i> Estornar
        </button>
      `;
    }

    return `
      <div class="card payment-card" data-id="${p.id}">
        <div class="payment-card-header">
          <div class="payment-info">
            <div class="payment-avatar">${iniciais}</div>
            <div class="payment-details">
              <h5>${escapeHtml(p.vendedor)}</h5>
              <small>
                <i class="ri-calendar-2-line me-1"></i> Mês ref: ${p.mes}
                <span class="mx-2">|</span>
                <i class="ri-user-line me-1"></i> Lançado por: ${escapeHtml(p.criado_por || 'N/A')}
              </small>
            </div>
          </div>
          <div>
            ${statusBadge}
          </div>
        </div>

        <div class="payment-card-body">
          <div class="payment-values mb-3">
            <div class="payment-value-item primary">
              <label>Bruto</label>
              <div class="value">${fmtBRL(p.total_bruto)}</div>
            </div>
            <div class="payment-value-item warning">
              <label>Imposto (${fmtPct(p.percentual_imposto)})</label>
              <div class="value">${fmtBRL(p.total_imposto)}</div>
            </div>
            <div class="payment-value-item success">
              <label>Líquido</label>
              <div class="value">${fmtBRL(p.total_liquido)}</div>
            </div>
            <div class="payment-value-item info">
              <label>Total a Receber</label>
              <div class="value">${fmtBRL(p.total_receber)}</div>
            </div>
          </div>

          <div class="payment-actions">
            <a class="btn btn-outline-primary btn-sm" href="${pdfUrl}" target="_blank">
              <i class="ri-file-pdf-line me-1"></i> Ver PDF
            </a>
            ${btnPagar}
            ${btnEstornar}
          </div>
        </div>
      </div>
    `;
  }

  function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text || '').replace(/[&<>"']/g, m => map[m]);
  }

  // ======== Atualizar Métricas ========
  function atualizarMetricas(pagamentos) {
    const sum = (key) => pagamentos.reduce((acc, p) => acc + (Number(p[key]) || 0), 0);

    $('#total-bruto').text(fmtBRL(sum('total_bruto')));
    $('#total-imposto').text(fmtBRL(sum('total_imposto')));
    $('#total-liquido').text(fmtBRL(sum('total_liquido')));
    $('#total-receber').text(fmtBRL(sum('total_receber')));
  }

  // ======== Event Listeners ========
  $apply.on('click', carregarPagamentos);

  // Pagar (delegado)
  $(document).on('click', '.js-pagar', async function () {
    const pagamentoId = this.dataset.id;
    const userId = this.dataset.user;

    if (!userId) {
      toastr?.warning('ID do vendedor ausente.');
      return;
    }

    abrirModalPagar(pagamentoId, userId);
  });

  // Estornar (delegado)
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
      carregarPagamentos();
    } catch (e) {
      console.error(e);
      toastr?.error(e.message || 'Erro ao estornar.');
    }
  });

  // ======== Modal Pagar ========
  const modalEl = document.getElementById('modalPagar');
  const modal = new bootstrap.Modal(modalEl);
  const selConta = document.getElementById('pg_conta');
  const inpPagoEm = document.getElementById('pg_pago_em');
  const inpPagamentoId = document.getElementById('pg_pagamento_id');
  const inpUserId = document.getElementById('pg_user_id');
  const alertNoAcc = document.getElementById('pg_alert_no_accounts');

  const today = new Date();
  inpPagoEm.value = today.toISOString().slice(0, 10);

  async function abrirModalPagar(pagamentoId, userId) {
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
  }

  document.getElementById('formPagar').addEventListener('submit', async (e) => {
    e.preventDefault();

    const pagamentoId = inpPagamentoId.value;
    const contaId = selConta.value || '';
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

      toastr?.success('Pagamento registrado com sucesso!');
      modal.hide();
      carregarPagamentos();

    } catch (err) {
      console.error(err);
      toastr?.error('Falha ao registrar pagamento.');
    } finally {
      btnSubmit.disabled = false;
    }
  });

  // Carrega ao iniciar
  carregarPagamentos();

})();
