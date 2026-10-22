#!/usr/bin/env node
/**
 * Gera resources/assets/vendor/scss/licas/_hardcoded.scss.
 *
 * O tools/licas-remap.py troca os tokens (--dash-primary...) das telas antigas, mas
 * várias regras escrevem a cor direto: `linear-gradient(135deg, #7C3AED, #A78BFA)`,
 * `box-shadow: 0 4px 12px rgba(124, 58, 237, .3)`. Este script compila o SCSS de cada
 * tela (e os <style> inline dos Blades), acha as declarações com cor de marca antiga
 * (roxo, violeta, índigo, ciano, rosa) e emite a sobrescrita em html.theme-licas:
 *   - fundo com cor clara/transparente -> tom claro do marinho; cheia -> marinho;
 *   - texto, borda, fill, stroke -> marinho;
 *   - sombra e brilho coloridos -> nenhum.
 * Verde, âmbar e vermelho ficam: são status.
 *
 *   ./dev npm exec -- node tools/licas-hardcoded.mjs   (ou docker exec no container vite)
 */
import { readFileSync, writeFileSync, readdirSync } from 'node:fs';
import { join, relative } from 'node:path';
import * as sass from 'sass';
import postcss from 'postcss';

const ROOT = new URL('..', import.meta.url).pathname;
const PAGES = join(ROOT, 'resources/assets/vendor/scss/pages');
const VIEWS = join(ROOT, 'resources/views');
const SAIDA = join(ROOT, 'resources/assets/vendor/scss/licas/_hardcoded.scss');
// O tema já trata essas telas à mão (casca do app e login).
const IGNORAR = new Set(['sidebar-custom.scss', 'page-auth.scss', 'page-auth-modern.scss']);

const COR = /#(?:[0-9a-fA-F]{3}){1,2}\b|rgba?\([^)]*\)/g;

function rgb(cor) {
  if (cor.startsWith('#')) {
    let h = cor.slice(1);
    if (h.length === 3) h = [...h].map(c => c + c).join('');
    return { r: parseInt(h.slice(0, 2), 16), g: parseInt(h.slice(2, 4), 16), b: parseInt(h.slice(4, 6), 16), a: 1 };
  }
  const p = cor.replace(/rgba?\(|\)/g, '').split(/[\s,/]+/).filter(Boolean).map(Number);
  if (p.length < 3 || p.some(Number.isNaN)) return null;
  return { r: p[0], g: p[1], b: p[2], a: p.length > 3 ? p[3] : 1 };
}

function hsl({ r, g, b }) {
  r /= 255; g /= 255; b /= 255;
  const max = Math.max(r, g, b), min = Math.min(r, g, b), l = (max + min) / 2;
  if (max === min) return { h: 0, s: 0, l };
  const d = max - min;
  const s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
  let h = max === r ? (g - b) / d + (g < b ? 6 : 0) : max === g ? (b - r) / d + 2 : (r - g) / d + 4;
  return { h: h * 60, s, l };
}

// Cor da identidade antiga: ciano (175-200) e azul/índigo/roxo/rosa (215-335).
function ehMarcaAntiga(cor) {
  const c = rgb(cor);
  if (!c) return false;
  const { h, s, l } = hsl(c);
  if (s < 0.35 || l < 0.12) return false;
  return (h >= 175 && h <= 200) || (h >= 215 && h <= 335);
}

function ehClara(cor) {
  const c = rgb(cor);
  return !!c && (c.a <= 0.25 || hsl(c).l > 0.85);
}

const PROPS_FUNDO = /^background(-color|-image)?$/;
const PROPS_SOMBRA = /^(box-shadow|text-shadow|filter|-webkit-box-shadow)$/;
const PROPS_COR = /^(color|fill|stroke|caret-color|accent-color|-webkit-text-fill-color|text-decoration-color|column-rule-color)$/;
const PROPS_BORDA = /^(border(-top|-right|-bottom|-left)?(-color)?|outline(-color)?)$/;

function sobrescrever(prop, valor) {
  const cores = valor.match(COR) || [];
  const marca = cores.filter(ehMarcaAntiga);
  if (!marca.length) return null;

  if (PROPS_SOMBRA.test(prop)) return 'none';
  if (PROPS_FUNDO.test(prop)) {
    // Gradiente ou cor sólida: todas claras = fundo de destaque suave; senão marinho cheio.
    return marca.every(ehClara) ? 'var(--color-accent-tint)' : 'var(--color-accent)';
  }
  if (prop === '-webkit-text-fill-color') return 'currentColor';
  if (PROPS_COR.test(prop)) return ehClara(marca[0]) ? 'var(--color-accent-tint-text)' : 'var(--color-accent)';
  if (PROPS_BORDA.test(prop)) {
    return valor.replace(COR, c => (ehMarcaAntiga(c) ? (ehClara(c) ? 'var(--color-divider)' : 'var(--color-accent)') : c));
  }
  return null;
}

// :where() zera a especificidade do seletor da tela: a regra gerada vence o roxo antigo
// (pelo !important) mas perde para qualquer regra escrita à mão em licas/*.scss.
function envolver(base, resto) {
  if (!resto) return base;
  const m = resto.match(/^(.*?)(::?(?:before|after|placeholder|selection|-webkit-[a-z-]+))$/);
  const [corpo, pseudo] = m ? [m[1], m[2]] : [resto, ''];
  return corpo.trim() ? `${base} :where(${corpo.trim()})${pseudo}` : `${base}${pseudo}`;
}

function prefixar(sel) {
  sel = sel.trim();
  if (/^:root\b/.test(sel)) return null;
  const m = sel.match(/^(?:html)?\.(dark|light)-style\b\s*(.*)$/);
  if (m) return envolver(`html.theme-licas.${m[1]}-style`, m[2]);
  if (/^html\b/.test(sel)) return envolver('html.theme-licas', sel.replace(/^html\S*\s*/, ''));
  return envolver('html.theme-licas', sel);
}

function processar(css, origem, saida) {
  let raiz;
  try {
    raiz = postcss.parse(css);
  } catch (e) {
    console.warn(`  ignorado (CSS inválido): ${origem}`);
    return 0;
  }
  let total = 0;
  raiz.walkRules(regra => {
    if (regra.parent?.type === 'atrule' && /keyframes$/.test(regra.parent.name)) return;
    const decls = [];
    let fundoCheio = false;
    let textoBranco = false;
    regra.walkDecls(d => {
      if (d.prop.startsWith('--')) return;
      const prop = d.prop.toLowerCase();
      if (prop === 'color' && /^(#fff(fff)?|white)$/i.test(d.value.trim())) textoBranco = true;
      const novo = sobrescrever(prop, d.value);
      if (novo === 'var(--color-accent)' && PROPS_FUNDO.test(prop)) fundoCheio = true;
      if (novo) decls.push(`  ${d.prop}: ${novo} !important;`);
    });
    // No escuro o marinho vira claro (#BCC3E4): texto branco em cima some.
    if (fundoCheio && textoBranco) decls.push('  color: var(--color-on-accent) !important;');
    if (!decls.length) return;
    const seletores = regra.selectors.map(prefixar).filter(Boolean);
    if (!seletores.length) return;
    let bloco = `${seletores.join(',\n')} {\n${[...new Set(decls)].join('\n')}\n}`;
    for (let p = regra.parent; p && p.type === 'atrule'; p = p.parent) {
      if (p.name === 'media' || p.name === 'supports') bloco = `@${p.name} ${p.params} {\n${bloco}\n}`;
    }
    saida.push(`/* ${origem} */\n${bloco}`);
    total += decls.length;
  });
  return total;
}

const saida = [
  '// GERADO por tools/licas-hardcoded.mjs. Não editar à mão: rode o script de novo.',
  '// Sobrescreve as cores da identidade antiga escritas direto no CSS das telas.',
  '',
];
let total = 0;

for (const arq of readdirSync(PAGES).filter(f => f.endsWith('.scss') && !f.startsWith('_') && !IGNORAR.has(f)).sort()) {
  try {
    const { css } = sass.compile(join(PAGES, arq), {
      loadPaths: [join(ROOT, 'resources/assets/vendor/scss'), join(ROOT, 'node_modules')],
      quietDeps: true,
      silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'mixed-decls', 'slash-div'],
      logger: sass.Logger.silent,
    });
    total += processar(css, `pages/${arq}`, saida);
  } catch (e) {
    console.warn(`  não compilou: pages/${arq} (${String(e.message).split('\n')[0]})`);
  }
}

function blades(dir) {
  return readdirSync(dir, { withFileTypes: true }).flatMap(e =>
    e.isDirectory() ? blades(join(dir, e.name)) : e.name.endsWith('.blade.php') ? [join(dir, e.name)] : []
  );
}

for (const arq of blades(VIEWS).sort()) {
  const html = readFileSync(arq, 'utf8');
  for (const [, css] of html.matchAll(/<style[^>]*>([\s\S]*?)<\/style>/g)) {
    // Blocos com sintaxe Blade dentro do CSS ficam de fora.
    if (/\{\{|@(if|php|foreach)/.test(css)) continue;
    total += processar(css, relative(ROOT, arq), saida);
  }
}

writeFileSync(SAIDA, saida.join('\n\n') + '\n');
console.log(`${total} declarações sobrescritas -> ${relative(ROOT, SAIDA)}`);
