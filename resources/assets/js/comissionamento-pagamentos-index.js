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
      <div class="loading-state">
        <div class="spinner"></div>
        <span class="loading-text">Carregando pagamentos...</span>
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
          <div class="empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          </div>
          <h5 class="empty-title">Erro ao carregar pagamentos</h5>
          <p class="empty-description">Tente novamente mais tarde</p>
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
          <div class="empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
              <line x1="16" y1="13" x2="8" y2="13"/>
              <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
          </div>
          <h5 class="empty-title">Nenhum pagamento encontrado</h5>
          <p class="empty-description">Ajuste os filtros para ver mais resultados</p>
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
          <div class="date-group-header collapsed" data-group="${groupId}">
            <h4>
              <div class="date-info">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                  <line x1="16" y1="2" x2="16" y2="6"/>
                  <line x1="8" y1="2" x2="8" y2="6"/>
                  <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                ${dataFormatada}
                ${diaSemana ? `<span class="badge">${diaSemana}</span>` : ''}
                <span class="badge">${lista.length} pagamento(s)</span>
                <span class="badge">Total: ${fmtBRL(totalLiquido)}</span>
              </div>
              <svg class="toggle-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="18 15 12 9 6 15"/>
              </svg>
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
      ? `<span class="status-badge badge-success">
           <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
             <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
             <polyline points="22 4 12 14.01 9 11.01"/>
           </svg>
           Pago em ${moment(p.pago_em).format('DD/MM/YYYY')}
         </span>`
      : `<span class="status-badge badge-warning">
           <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
             <circle cx="12" cy="12" r="10"/>
             <polyline points="12 6 12 12 16 14"/>
           </svg>
           Pendente
         </span>`;

    // Botoes de acao
    let btnPagar = '';
    if (!isPago && estornoUrl && USER_ROLE !== '1') {
      btnPagar = `
        <button type="button" class="btn-dash btn-success js-pagar"
                data-id="${p.id}" data-user="${userId}">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
            <line x1="1" y1="10" x2="23" y2="10"/>
          </svg>
          Pagar
        </button>
      `;
    }

    let btnEstornar = '';
    if (estornoUrl && USER_ROLE !== '1') {
      btnEstornar = `
        <button type="button" class="btn-dash btn-outline-danger js-estornar"
                data-url="${estornoUrl}">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="1 4 1 10 7 10"/>
            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
          </svg>
          Estornar
        </button>
      `;
    }

    return `
      <div class="payment-card" data-id="${p.id}">
        <div class="payment-card-header">
          <div class="payment-info">
            <div class="payment-avatar">${iniciais}</div>
            <div class="payment-details">
              <h5>${escapeHtml(p.vendedor)}</h5>
              <small>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                  <line x1="16" y1="2" x2="16" y2="6"/>
                  <line x1="8" y1="2" x2="8" y2="6"/>
                  <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                Mes ref: ${p.mes}
                <span style="margin: 0 0.5rem; opacity: 0.5;">|</span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                  <circle cx="12" cy="7" r="4"/>
                </svg>
                Lancado por: ${escapeHtml(p.criado_por || 'N/A')}
              </small>
            </div>
          </div>
          <div>
            ${statusBadge}
          </div>
        </div>

        <div class="payment-card-body">
          <div class="payment-values">
            <div class="payment-value-item primary">
              <label>Bruto</label>
              <div class="value">${fmtBRL(p.total_bruto)}</div>
            </div>
            <div class="payment-value-item warning">
              <label>Imposto (${fmtPct(p.percentual_imposto)})</label>
              <div class="value">${fmtBRL(p.total_imposto)}</div>
            </div>
            <div class="payment-value-item success">
              <label>Liquido</label>
              <div class="value">${fmtBRL(p.total_liquido)}</div>
            </div>
            <div class="payment-value-item info">
              <label>Total a Receber</label>
              <div class="value">${fmtBRL(p.total_receber)}</div>
            </div>
          </div>

          <div class="payment-actions">
            <a class="btn-dash btn-outline-primary" href="${pdfUrl}" target="_blank">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
              </svg>
              Ver PDF
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

  // Inicializar Flatpickr no campo de data do modal
  const fpPagoEm = flatpickr(inpPagoEm, {
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'd/m/Y',
    locale: 'pt',
    allowInput: true,
    defaultDate: new Date()
  });

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
