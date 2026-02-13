'use strict';

/**
 * Backoffice Performance — Individual Analytics
 */
(function () {
  // Theme
  let labelColor, headingColor, borderColor;
  if (isDarkStyle) {
    labelColor = config.colors_dark.textMuted;
    headingColor = config.colors_dark.headingColor;
    borderColor = config.colors_dark.borderColor;
  } else {
    labelColor = config.colors.textMuted;
    headingColor = config.colors.headingColor;
    borderColor = config.colors.borderColor;
  }

  const palette = {
    primary: '#7C3AED',
    primaryLight: '#A78BFA',
    success: '#10B981',
    successLight: '#34D399',
    warning: '#F59E0B',
    warningLight: '#FBBF24',
    danger: '#EF4444',
    dangerLight: '#F87171',
    info: '#06B6D4',
    infoLight: '#22D3EE'
  };

  // State
  let fpInicio, fpFim;
  let chartBackoffice, chartOperadora, chartPipeline;
  let currentData = null;

  document.addEventListener('DOMContentLoaded', function () {
    initDatePickers();
    initCharts();
    bindEvents();
    loadData();
  });

  // ─── Date Pickers ────────────────────────────
  function initDatePickers() {
    const now = new Date();
    const yearStart = new Date(now.getFullYear(), 0, 1);

    fpInicio = flatpickr('#filtro-data-inicio', {
      dateFormat: 'd/m/Y',
      defaultDate: yearStart,
      locale: 'pt',
      allowInput: true
    });

    fpFim = flatpickr('#filtro-data-fim', {
      dateFormat: 'd/m/Y',
      defaultDate: now,
      locale: 'pt',
      allowInput: true
    });
  }

  // ─── Events ──────────────────────────────────
  function bindEvents() {
    document.getElementById('btn-filtrar')?.addEventListener('click', loadData);

    document.getElementById('ranking-sort')?.addEventListener('change', function () {
      if (currentData?.por_backoffice) {
        renderRanking(sortBackoffice(currentData.por_backoffice, this.value));
      }
    });
  }

  // ─── Data Loading ────────────────────────────
  function loadData() {
    const start = dateToBackend(document.getElementById('filtro-data-inicio')?.value);
    const end = dateToBackend(document.getElementById('filtro-data-fim')?.value);
    const boId = document.getElementById('filtro-backoffice')?.value || '';

    showLoading();

    fetch(`/back-office/relatorio-performance/data?start_date=${start}&end_date=${end}&backoffice_id=${boId}`)
      .then(r => r.json())
      .then(data => {
        hideLoading();
        if (!data.success) throw new Error(data.message || 'Erro');
        currentData = data;
        updateKPIs(data.metricas);
        const sortBy = document.getElementById('ranking-sort')?.value || 'implantados';
        renderRanking(sortBackoffice(data.por_backoffice || [], sortBy));
        updateCharts(data);
        updatePendencias(data.pendencias_frequentes);
      })
      .catch(err => {
        hideLoading();
        console.error(err);
        Swal.fire({ icon: 'error', title: 'Erro', text: 'Nao foi possivel carregar os dados.', confirmButtonColor: palette.primary });
      });
  }

  // ─── KPIs ────────────────────────────────────
  function updateKPIs(m) {
    if (!m) return;
    animateNum('.js-kpi-total', m.total_vendas || 0);
    animateNum('.js-kpi-implantados', m.implantadas || 0);
    animateNum('.js-kpi-andamento', m.em_andamento || 0);
    animateNum('.js-kpi-perdidos', m.perdidas || 0);

    setText('.js-kpi-total-valor', formatCurrency(m.valor_total || 0));
    setText('.js-kpi-taxa-conversao', `${(m.taxa_conversao || 0).toFixed(1)}% de conversao`);
    setText('.js-kpi-valor-perdido', formatCurrency(m.valor_perdido || 0));
  }

  // ─── Ranking ─────────────────────────────────
  function sortBackoffice(arr, key) {
    const sorted = [...arr];
    if (key === 'tempo_medio') {
      sorted.sort((a, b) => (a.tempo_medio || 999) - (b.tempo_medio || 999));
    } else {
      sorted.sort((a, b) => (b[key] || 0) - (a[key] || 0));
    }
    return sorted;
  }

  function renderRanking(list) {
    renderPodium(list.slice(0, 3));
    renderRankingTable(list);
  }

  function renderPodium(top3) {
    const container = document.getElementById('podium-container');
    if (!container) return;

    if (!top3.length) {
      container.innerHTML = `<div class="bp-podium__empty">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span>Nenhum backoffice com contratos no periodo</span>
      </div>`;
      return;
    }

    container.innerHTML = top3.map((bo, i) => {
      const pos = i + 1;
      const initials = getInitials(bo.backoffice_nome);
      const sortKey = document.getElementById('ranking-sort')?.value || 'implantados';
      let mainVal, mainLabel;

      if (sortKey === 'taxa_conversao') {
        mainVal = `${(bo.taxa_conversao || 0).toFixed(1)}%`;
        mainLabel = 'Conversao';
      } else if (sortKey === 'total') {
        mainVal = bo.total;
        mainLabel = 'Contratos';
      } else if (sortKey === 'tempo_medio') {
        mainVal = bo.tempo_medio !== null ? `${bo.tempo_medio}d` : 'N/A';
        mainLabel = 'Tempo Medio';
      } else if (sortKey === 'valor_implantado') {
        mainVal = formatCurrencyShort(bo.valor_implantado || 0);
        mainLabel = 'Valor Implant.';
      } else {
        mainVal = bo.implantados;
        mainLabel = 'Implantados';
      }

      return `
        <div class="bp-podium-card bp-podium-card--${pos}">
          <div class="bp-podium-card__medal">${pos}</div>
          <div class="bp-podium-card__avatar">${initials}</div>
          <div class="bp-podium-card__name" title="${bo.backoffice_nome}">${bo.backoffice_nome}</div>
          <div class="bp-podium-card__stat-main">${mainVal}</div>
          <div class="bp-podium-card__stat-label">${mainLabel}</div>
          <div class="bp-podium-card__stats-row">
            <div class="bp-podium-card__mini-stat"><span>${bo.total}</span><span>Total</span></div>
            <div class="bp-podium-card__mini-stat"><span>${(bo.taxa_conversao || 0).toFixed(1)}%</span><span>Conv.</span></div>
            <div class="bp-podium-card__mini-stat"><span>${bo.pendencias || 0}</span><span>Pend.</span></div>
          </div>
        </div>`;
    }).join('');
  }

  function renderRankingTable(list) {
    const tbody = document.getElementById('ranking-tbody');
    if (!tbody) return;

    if (!list.length) {
      tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--bp-text-muted);">Nenhum dado disponivel</td></tr>`;
      return;
    }

    tbody.innerHTML = list.map((bo, i) => {
      const pos = i + 1;
      const initials = getInitials(bo.backoffice_nome);
      const rate = bo.taxa_conversao || 0;
      const rateClass = rate >= 60 ? 'high' : (rate >= 30 ? 'mid' : 'low');

      return `<tr>
        <td><span class="rank-pos rank-pos--${pos <= 3 ? pos : 'x'}">${pos}</span></td>
        <td><div class="rank-user"><div class="rank-user__avatar">${initials}</div><span class="rank-user__name">${bo.backoffice_nome}</span></div></td>
        <td class="rank-num">${bo.total}</td>
        <td class="rank-num" style="color:var(--bp-success);font-weight:700;">${bo.implantados}</td>
        <td class="rank-num">${bo.em_andamento}</td>
        <td class="rank-num" style="color:var(--bp-danger);">${bo.perdidos}</td>
        <td><div class="rank-bar">
          <div class="rank-bar__track"><div class="rank-bar__fill rank-bar__fill--${rateClass}" style="width:${rate}%"></div></div>
          <span class="rank-bar__value rank-bar__value--${rateClass}">${rate.toFixed(1)}%</span>
        </div></td>
        <td class="rank-time">${bo.tempo_medio !== null ? bo.tempo_medio + ' <span>dias</span>' : '<span>N/A</span>'}</td>
        <td class="rank-currency">${formatCurrency(bo.valor_implantado || 0)}</td>
      </tr>`;
    }).join('');
  }

  // ─── Charts ──────────────────────────────────
  function initCharts() {
    const tooltipTheme = isDarkStyle ? 'dark' : 'light';
    const fontFamily = 'Plus Jakarta Sans, sans-serif';

    // Backoffice Compare
    const el1 = document.querySelector('#chart-backoffice-compare');
    if (el1) {
      chartBackoffice = new ApexCharts(el1, {
        chart: { type: 'bar', height: 320, toolbar: { show: false }, fontFamily },
        series: [{ name: 'Total', data: [] }, { name: 'Implantados', data: [] }],
        colors: [palette.primary, palette.success],
        plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 6 } },
        dataLabels: { enabled: false },
        xaxis: { categories: [], labels: { style: { colors: labelColor, fontSize: '11px' }, rotate: -45, rotateAlways: false } },
        yaxis: { labels: { style: { colors: labelColor } } },
        grid: { borderColor, strokeDashArray: 4 },
        legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px', labels: { colors: labelColor }, markers: { width: 10, height: 10, radius: 3 } },
        tooltip: { theme: tooltipTheme, shared: true, intersect: false }
      });
      chartBackoffice.render();
    }

    // Operadora
    const el2 = document.querySelector('#chart-operadora');
    if (el2) {
      chartOperadora = new ApexCharts(el2, {
        chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily },
        series: [{ name: 'Total', data: [] }, { name: 'Implantados', data: [] }],
        colors: [palette.info, palette.success],
        plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 6 } },
        dataLabels: { enabled: false },
        xaxis: { categories: [], labels: { style: { colors: labelColor, fontSize: '11px' }, rotate: -45, rotateAlways: false } },
        yaxis: { labels: { style: { colors: labelColor } } },
        grid: { borderColor, strokeDashArray: 4 },
        legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px', labels: { colors: labelColor }, markers: { width: 10, height: 10, radius: 3 } },
        tooltip: { theme: tooltipTheme, shared: true, intersect: false }
      });
      chartOperadora.render();
    }

    // Pipeline
    const el3 = document.querySelector('#chart-pipeline');
    if (el3) {
      chartPipeline = new ApexCharts(el3, {
        chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily },
        series: [{ name: 'Contratos', data: [] }],
        colors: [palette.primary, palette.info, palette.primaryLight, palette.warning, palette.infoLight, palette.success, palette.successLight, palette.danger, palette.dangerLight],
        plotOptions: { bar: { borderRadius: 6, horizontal: true, distributed: true, barHeight: '65%' } },
        dataLabels: { enabled: true, style: { colors: ['#fff'], fontSize: '12px', fontWeight: 600 }, formatter: (val, opt) => opt.w.globals.labels[opt.dataPointIndex] + ': ' + val, offsetX: 8, textAnchor: 'start' },
        xaxis: { labels: { style: { colors: labelColor, fontSize: '11px' } } },
        yaxis: { labels: { show: false } },
        grid: { borderColor, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
        legend: { show: false },
        tooltip: { theme: tooltipTheme, x: { show: false } }
      });
      chartPipeline.render();
    }
  }

  function updateCharts(data) {
    // Backoffice Compare
    if (chartBackoffice && data.por_backoffice) {
      const bo = data.por_backoffice.slice(0, 12);
      chartBackoffice.updateOptions({ xaxis: { categories: bo.map(b => truncate(b.backoffice_nome, 15)) } });
      chartBackoffice.updateSeries([
        { name: 'Total', data: bo.map(b => b.total) },
        { name: 'Implantados', data: bo.map(b => b.implantados) }
      ]);
    }

    // Operadora
    if (chartOperadora && data.por_operadora) {
      const op = data.por_operadora.slice(0, 8);
      chartOperadora.updateOptions({ xaxis: { categories: op.map(o => o.operadora || 'N/A') } });
      chartOperadora.updateSeries([
        { name: 'Total', data: op.map(o => o.total) },
        { name: 'Implantados', data: op.map(o => o.implantados) }
      ]);
    }

    // Pipeline
    if (chartPipeline && data.pipeline_funil) {
      const pf = data.pipeline_funil;
      chartPipeline.updateOptions({ xaxis: { categories: pf.map(p => p.etapa) } });
      chartPipeline.updateSeries([{ name: 'Contratos', data: pf.map(p => p.quantidade) }]);
    }
  }

  // ─── Pendencias ──────────────────────────────
  function updatePendencias(list) {
    const grid = document.getElementById('pendencias-grid');
    if (!grid) return;

    if (!list?.length) {
      grid.innerHTML = `<div class="bp-empty" style="grid-column:1/-1;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
        <div class="bp-empty__title">Nenhuma pendencia frequente</div>
        <div class="bp-empty__text">Nao ha motivos de pendencia recorrentes no periodo.</div>
      </div>`;
      return;
    }

    const max = Math.max(...list.map(p => p.quantidade));

    grid.innerHTML = list.map((item, i) => {
      const pct = (item.quantidade / max) * 100;
      const rankClass = i === 0 ? '1' : (i === 1 ? '2' : (i === 2 ? '3' : 'default'));
      return `<div class="bp-pend-card">
        <div class="bp-pend-card__rank bp-pend-card__rank--${rankClass}">${i + 1}</div>
        <div class="bp-pend-card__body">
          <div class="bp-pend-card__motivo" title="${item.motivo}">${item.motivo}</div>
          <div class="bp-pend-card__bar"><div class="bp-pend-card__bar-fill" style="width:${pct}%"></div></div>
        </div>
        <div class="bp-pend-card__count">${item.quantidade}</div>
      </div>`;
    }).join('');
  }

  // ─── Helpers ─────────────────────────────────
  function dateToBackend(val) {
    if (!val) return '';
    const p = val.split('/');
    return p.length === 3 ? `${p[2]}-${p[1].padStart(2, '0')}-${p[0].padStart(2, '0')}` : '';
  }

  function formatCurrency(v) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v || 0);
  }

  function formatCurrencyShort(v) {
    if (v >= 1000000) return `R$ ${(v / 1000000).toFixed(1)}M`;
    if (v >= 1000) return `R$ ${(v / 1000).toFixed(1)}K`;
    return formatCurrency(v);
  }

  function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
  }

  function truncate(str, len) {
    if (!str) return 'N/A';
    return str.length > len ? str.substring(0, len) + '...' : str;
  }

  function setText(sel, val) {
    const el = document.querySelector(sel);
    if (el) el.textContent = val;
  }

  function animateNum(sel, end) {
    const el = document.querySelector(sel);
    if (!el) return;
    const start = parseInt(el.textContent) || 0;
    const dur = 450;
    const t0 = performance.now();

    function tick(now) {
      const p = Math.min((now - t0) / dur, 1);
      const ease = 1 - Math.pow(1 - p, 4);
      el.textContent = Math.round(start + (end - start) * ease);
      if (p < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  function showLoading() {
    let ol = document.querySelector('.bp-loading');
    if (!ol) {
      ol = document.createElement('div');
      ol.className = 'bp-loading';
      ol.innerHTML = '<div class="bp-loading__spinner"></div>';
      document.body.appendChild(ol);
    }
    ol.style.display = 'flex';
  }

  function hideLoading() {
    const ol = document.querySelector('.bp-loading');
    if (ol) ol.style.display = 'none';
  }
})();
