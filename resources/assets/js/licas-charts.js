/**
 * Tema Licas para ApexCharts (DESIGN-LICAS.md, 7.12).
 *
 * Os ~10 scripts de gráfico fixam roxo, ciano e afins em hex. Em vez de editar cada
 * um, este módulo envolve o construtor do ApexCharts e reescreve as opções antes do
 * render, só quando o <html> tem theme-licas:
 *   - cores de marca (azuis, roxos, cianos, rosas) viram a série 1, 2, 3 do DS na
 *     ordem em que aparecem;
 *   - verde, âmbar e vermelho viram sucesso, alerta e perigo (status continua status);
 *   - cinzas viram texto muted ou divisória;
 *   - gradiente vira cor sólida, sombra some, barras ganham topo de 6px.
 * Carregado em layouts/sections/scripts.blade.php logo depois do vendor-script.
 */

const root = document.documentElement;

function tokens() {
  const css = getComputedStyle(root);
  const v = name => css.getPropertyValue(name).trim();
  return {
    series: [v('--chart-1'), v('--chart-2'), v('--chart-3'), v('--color-accent-500'), v('--color-neutral-400')],
    success: v('--color-success'),
    warning: v('--color-warning'),
    danger: v('--color-danger'),
    text: v('--color-text'),
    muted: v('--color-text-muted'),
    divider: v('--color-divider'),
    dividerSoft: v('--color-divider-soft'),
    card: v('--color-card'),
    font: v('--font-body')
  };
}

function hsl(hex) {
  let h = hex.replace('#', '');
  if (h.length === 3) h = h.split('').map(c => c + c).join('');
  if (h.length !== 6) return null;
  const r = parseInt(h.slice(0, 2), 16) / 255;
  const g = parseInt(h.slice(2, 4), 16) / 255;
  const b = parseInt(h.slice(4, 6), 16) / 255;
  const max = Math.max(r, g, b);
  const min = Math.min(r, g, b);
  const l = (max + min) / 2;
  if (max === min) return { h: 0, s: 0, l };
  const d = max - min;
  const s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
  let hue;
  if (max === r) hue = (g - b) / d + (g < b ? 6 : 0);
  else if (max === g) hue = (b - r) / d + 2;
  else hue = (r - g) / d + 4;
  return { h: hue * 60, s, l };
}

function criarMapeador(t, forcarMarca = false) {
  const marca = new Map();
  return function mapear(cor) {
    if (typeof cor !== 'string' || !/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(cor.trim())) return cor;
    const c = hsl(cor.trim());
    if (!c) return cor;
    if (c.s < 0.18) return c.l > 0.8 ? t.divider : t.muted;
    // data-licas-series="marca" no elemento: as séries não são status, todas viram marinho.
    if (forcarMarca) return serieDaMarca(marca, t, cor);
    if (c.h >= 85 && c.h < 175) return t.success;
    if (c.h >= 25 && c.h < 65) return t.warning;
    if (c.h < 15 || c.h >= 345) return t.danger;
    return serieDaMarca(marca, t, cor);
  };
}

function serieDaMarca(marca, t, cor) {
  const chave = cor.trim().toLowerCase();
  if (!marca.has(chave)) marca.set(chave, t.series[Math.min(marca.size, t.series.length - 1)]);
  return marca.get(chave);
}

function trocarCores(valor, mapear) {
  if (Array.isArray(valor)) return valor.map(v => trocarCores(v, mapear));
  if (valor && typeof valor === 'object') {
    for (const k of Object.keys(valor)) valor[k] = trocarCores(valor[k], mapear);
    return valor;
  }
  return typeof valor === 'string' ? mapear(valor) : valor;
}

function soMarca(el) {
  const alvo = typeof el === 'string' ? document.querySelector(el) : el;
  return !!(alvo && alvo.dataset && alvo.dataset.licasSeries === 'marca');
}

function aplicarTema(opcoes, el) {
  const t = tokens();
  trocarCores(opcoes, criarMapeador(t, soMarca(el)));

  opcoes.chart = opcoes.chart || {};
  opcoes.chart.fontFamily = t.font;
  opcoes.chart.dropShadow = { enabled: false };
  opcoes.chart.background = 'transparent';

  if (opcoes.fill && opcoes.fill.type === 'gradient') {
    opcoes.fill.type = 'solid';
    opcoes.fill.opacity = opcoes.chart.type === 'area' ? 0.12 : 1;
  }

  opcoes.grid = Object.assign({}, opcoes.grid, { borderColor: t.dividerSoft, strokeDashArray: 0 });

  if (['bar', 'rangeBar'].includes(opcoes.chart.type)) {
    opcoes.plotOptions = opcoes.plotOptions || {};
    opcoes.plotOptions.bar = Object.assign({}, opcoes.plotOptions.bar, {
      borderRadius: 6,
      borderRadiusApplication: 'end'
    });
  }

  if (opcoes.stroke && opcoes.chart.type !== 'bar') {
    opcoes.stroke.width = opcoes.stroke.width ?? 2;
  }

  opcoes.tooltip = Object.assign({}, opcoes.tooltip, { theme: root.classList.contains('dark-style') ? 'dark' : 'light' });
  return opcoes;
}

if (root.classList.contains('theme-licas') && window.ApexCharts && !window.ApexCharts.__licas) {
  const Original = window.ApexCharts;
  class ApexLicas extends Original {
    constructor(el, opcoes) {
      super(el, aplicarTema(opcoes || {}, el));
      this.__licasEl = el;
    }

    updateOptions(opcoes, ...resto) {
      // Atualização parcial: só as cores, para não inventar chart/grid que a tela não mandou.
      return super.updateOptions(trocarCores(opcoes || {}, criarMapeador(tokens(), soMarca(this.__licasEl))), ...resto);
    }
  }
  Object.getOwnPropertyNames(Original).forEach(k => {
    if (!(k in ApexLicas)) {
      try {
        ApexLicas[k] = Original[k];
      } catch (e) {}
    }
  });
  ApexLicas.__licas = true;
  window.ApexCharts = ApexLicas;
}
