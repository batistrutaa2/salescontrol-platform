# Licas — Design System (v1.0)

Sistema visual completo do CRM **M&L / Licas Consultoria Seguros**. Este documento é a fonte da verdade para qualquer tela nova ou redesenhada. Leia inteiro antes de gerar UI.

Previews: `Design System.dc.html` (claro) · `Design System Dark.dc.html` (escuro).

> **Implementação neste repo:** o tema vale só para empresas com `empresas.tema = 'licas'`, que recebem a classe `theme-licas` no `<html>`. Tokens e ponte com o Bootstrap/Materio em `resources/assets/vendor/scss/licas/`. O escuro usa a classe `dark-style` já existente do Materio (equivale ao `[data-theme="dark"]` deste documento). Ícones continuam Remixicon na variante `-line` (traço fino, mesmo papel do Lucide 1.5) até a troca do set ser decidida.

---

## 1. Princípios

1. **Marca primeiro.** A identidade vem do logo: azul-marinho profundo sobre papel off-white, serifa clássica. Nada de roxo, gradientes, neon ou glow — o sistema antigo (dark roxo) está descontinuado.
2. **Suave e preciso.** Cantos arredondados (cards 14px, controles 8px, tags 6px), borda hairline de 1px. No claro, cards são contorno; no escuro, têm preenchimento `--color-card`.
3. **Um único objeto sólido de destaque.** O botão primário é o único elemento de ação preenchido por área. A sidebar acompanha o tema: clara no claro, `#04061A` no escuro (ver 7.7).
4. **Grid visível.** Conteúdo em células iguais, ritmo horizontal/vertical forte, alinhamento rígido.
5. **Cor tem função.** Cores de status (sucesso, alerta, perigo) aparecem só para comunicar estado — nunca decoração. Ícones de KPI não ganham "bolinhas" coloridas.
6. **Dados legíveis.** Números sempre com `font-variant-numeric: tabular-nums`. Sem fontes monoespaçadas para valores.
7. **Menos é mais.** Cada elemento precisa justificar sua presença. Sem badges redundantes, sem chips de "tendência" vazios.

---

## 2. Cores

### 2.1 Papéis (light — padrão)

| Token | Hex | Uso |
|---|---|---|
| `--color-bg` | `#F7F7F8` | Fundo da aplicação (papel) |
| `--color-surface` | `#EFEFF2` | Campos de input, hovers de linha |
| `--color-text` | `#0E1230` | Texto principal (marinho quase preto) |
| `--color-text-muted` | `#5B5F78` | Texto secundário, labels, metadados |
| `--color-accent` | `#141C5C` | Marca. Botão primário, links, foco, seleção |
| `--color-divider` | `rgba(14,18,48,.14)` | Bordas hairline, divisórias |
| `--color-divider-soft` | `rgba(14,18,48,.07)` | Linhas de tabela, grid de gráfico |

### 2.2 Rampa Marinho (accent)

Gerada em OKLCH; o mesmo passo tem o mesmo peso visual.

| Passo | Hex | Uso |
|---|---|---|
| 100 | `#EEF0FA` | Fundo de tag accent, linha selecionada |
| 200 | `#DCE0F2` | Hover de item de menu claro |
| 300 | `#BCC3E4` | Bordas de foco suaves, texto em sidebar (inativo) |
| 400 | `#8C96C8` | Ícones em sidebar, séries secundárias de gráfico |
| 500 | `#5A66A6` | Série 2 de gráfico |
| 600 | `#2E3A80` | Série 1 alternativa |
| 700 | `#141C5C` | **Base / marca** (`--color-accent`) |
| 800 | `#0F1650` | Hover do primário |
| 900 | `#0A0F3D` | Pressed do primário; **fundo da sidebar** |

### 2.3 Rampa Neutra

| 100 `#F7F7F8` | 200 `#EFEFF2` | 300 `#DDDEE4` | 400 `#BDBFCA` | 500 `#9A9DAE` | 600 `#787C91` | 700 `#5B5F78` | 800 `#363A55` | 900 `#0E1230` |
|---|---|---|---|---|---|---|---|---|

### 2.4 Status (funcional, nunca decorativo)

| Papel | Base | Tint (100) | Texto em tint (800) | Uso |
|---|---|---|---|---|
| Sucesso | `#2F7D5B` | `#E6F2EC` | `#1C4D38` | Contrato implantado, pago |
| Alerta | `#A86B12` | `#FBF1DE` | `#5E3C08` | Boleto vencendo, lead parado (dias) |
| Perigo | `#B23A3A` | `#F8E7E7` | `#6B2121` | Vencido, excluir, lead **quente** |
| Info | `#141C5C` | `#EEF0FA` | `#0F1650` | Lead **frio**, neutro informativo |

**Temperatura de lead:** Frio = tag outline marinho · Morno = tag alerta · Quente = tag perigo. Indicador de dias parado = tag neutra; vira alerta a partir de 7d e perigo a partir de 14d.

### 2.5 Contraste

- Texto corrido: mínimo 4.5:1. Use `--color-text` ou `--color-text-muted` sobre `--color-bg`.
- Marinho 700 sobre bg = 14:1 (ok para qualquer texto).
- Na sidebar (marinho 900): texto ativo `#FFFFFF`, inativo `--color-accent-300`.

### 2.6 Tema escuro

Preview: `Design System Dark.dc.html`. Ative com `[data-theme="dark"]` no `<html>`. Layout, tipografia, espaçamento e componentes são idênticos — só os tokens de cor mudam.

| Token | Hex | Uso |
|---|---|---|
| `--color-bg` | `#080B22` | Fundo (tinta marinho, o mais escuro do conteúdo) |
| `--color-card` | `#131A4A` | **Cards e painéis têm preenchimento no escuro** + borda `rgba(241,242,248,.12)` |
| `--color-card-hover` | `#171E52` | Hover de card (kanban) |
| `--color-surface` | `#1C2360` | Inputs, trilhas de barra — sempre um passo acima do card |
| `--color-sidebar` | `#04061A` | Sidebar (mais escura que o fundo) + borda direita `rgba(241,242,248,.08)` |
| `--color-text` | `#F1F2F8` | Texto principal |
| `--color-text-muted` | `#B9BFE2` | Secundário |
| `--color-accent` | `#BCC3E4` | Primário (accent-300). Texto sobre ele: `#0A0F3D` |
| `--color-divider` | `rgba(241,242,248,.14)` | Bordas hairline |
| `--color-divider-soft` | `rgba(241,242,248,.07)` | Linhas de tabela/grid |

Status no escuro (tint = 14% alpha da base):

| Papel | Base | Tint | Texto em tint |
|---|---|---|---|
| Sucesso | `#5FB98E` | `rgba(95,185,142,.14)` | `#A8E0C4` |
| Alerta | `#E0A54A` | `rgba(224,165,74,.14)` | `#F2D29E` |
| Perigo | `#E06B6B` | `rgba(224,107,107,.14)` | `#F3B3B3` |
| Info / tag accent | `#BCC3E4` | `rgba(188,195,228,.12)` | `#DCE0F2` |
| Tag neutra | — | `rgba(241,242,248,.08)` | `#C9CCE0` |

Regras específicas do escuro:
- **Hierarquia de profundidade:** sidebar `#04061A` < fundo `#080B22` < card `#131A4A` < input `#1C2360`. Cada nível sobe ~4% de luminosidade; nunca dois níveis vizinhos com a mesma cor.
- No escuro, cards usam `--color-card` para se separar do fundo.
- **Números em destaque:** valores de KPI e contagens em `#FFFFFF`, 38/42px; no claro, `#0A0F3D`. Nunca em cor muted.
- Hover do primário: `#DCE0F2` (accent-200). Pressed: `#8C96C8` (accent-400).
- Gráficos: série 1 `#BCC3E4`, série 2 `#5A66A6`, série 3 `#787C91`.
- Avatar sem foto: bg `#2E3A80`, texto `#FFFFFF`.
- Borda do topo de coluna kanban usa `--color-text` (claro).
- Hover de card: bg `--color-card-hover` + borda `--color-accent`.
- Sombras: `--shadow-md: 0 4px 12px rgba(0,0,0,.4)`, `--shadow-lg: 0 16px 40px rgba(0,0,0,.5)`.
- Declare `color-scheme: dark` para que scrollbars e selects nativos acompanhem.

```css
[data-theme="dark"]{
  --color-bg:#080B22; --color-card:#131A4A; --color-card-hover:#171E52; --color-surface:#1C2360; --color-sidebar:#04061A; --color-text:#F1F2F8; --color-text-muted:#B9BFE2;
  --color-accent:#BCC3E4; --color-on-accent:#0A0F3D; --color-divider:rgba(241,242,248,.14); --color-divider-soft:rgba(241,242,248,.07);
  --color-success:#5FB98E; --color-success-100:rgba(95,185,142,.14); --color-success-800:#A8E0C4;
  --color-warning:#E0A54A; --color-warning-100:rgba(224,165,74,.14); --color-warning-800:#F2D29E;
  --color-danger:#E06B6B;  --color-danger-100:rgba(224,107,107,.14);  --color-danger-800:#F3B3B3;
  --shadow-sm:0 1px 2px rgba(0,0,0,.3); --shadow-md:0 4px 12px rgba(0,0,0,.4); --shadow-lg:0 16px 40px rgba(0,0,0,.5);
  color-scheme:dark;
}
```

---

## 3. Tipografia

| Papel | Família | Peso | Observação |
|---|---|---|---|
| Wordmark / marca | **Cinzel** | 500 | Só no logo, login e cabeçalhos de documento. Caixa alta, tracking `0.32em` |
| Títulos / UI labels | **Barlow Condensed** | 600 | Títulos, botões, KPIs, cabeçalhos de coluna |
| Corpo | **Barlow** | 400 / 500 | Texto, tabelas, inputs |

```css
@import url('https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600&family=Barlow+Condensed:wght@500;600&family=Cinzel:wght@500&display=swap');
--font-brand: "Cinzel", Georgia, serif;
--font-heading: "Barlow Condensed", system-ui, sans-serif;
--font-body: "Barlow", system-ui, sans-serif;
```

### Escala

| Token | Tamanho / altura | Fonte | Uso |
|---|---|---|---|
| `display` | 40 / 44 | Heading 600 | Título de página |
| `kpi` | 38 / 42 | Heading 600, tracking -0.01em, tabular | Valor de KPI (claro `#0A0F3D`, escuro `#FFFFFF`) |
| `h1` | 32 / 36 | Heading 600 | Título de seção principal |
| `h2` | 24 / 28 | Heading 600 | Título de seção |
| `h3` | 18 / 22 | Heading 600 | Título de card / painel |
| `body` | 15 / 23 | Body 400 | Texto padrão |
| `body-sm` | 13 / 19 | Body 400 | Tabelas, cards de kanban |
| `caption` | 12 / 16 | Body 500 | Metadados, datas, telefones |
| `overline` | 11 / 14 | Body 600, uppercase, tracking `0.12em` | Rótulos de seção, cabeçalho de tabela, grupo de menu |

Regras: letter-spacing `-0.01em` em headings ≥ 24px. Nomes de cliente no kanban em **Title Case** (não CAIXA ALTA). Valores monetários: `R$ 1.759,00` com `tabular-nums`.

---

## 4. Espaçamento, grid e forma

### Espaçamento (base 4px)

| `--space-1` 4 | `--space-2` 8 | `--space-3` 12 | `--space-4` 16 | `--space-5` 20 | `--space-6` 24 | `--space-8` 32 | `--space-10` 40 | `--space-12` 48 |
|---|---|---|---|---|---|---|---|---|

- Padding de card: 20px. Gap entre cards: 16px. Padding de página: 32px horizontal, 28px topo.
- Altura de controles: 36px (padrão), 32px (compacto/kanban), 44px (login, mobile).

### Grid
- App shell: sidebar fixa **248px** + conteúdo fluido (max-width 1600px).
- Conteúdo: 12 colunas, gutter 16px. KPIs = 4 × 3 colunas. Gráfico principal 8 col + lateral 4 col.
- Kanban: colunas de **300px** fixas, gap 16px, scroll horizontal.

### Forma
- Raios: `--radius-sm: 6px` (tags, contadores, barras de gráfico no topo) · `--radius-md: 8px` (botões, inputs, selects, itens de menu, botões-ícone) · `--radius-lg: 10px` (alertas, segmented) · `--radius-xl: 14px` (cards, painéis, dialogs) · `--radius-2xl: 16px` (frames de tela). Avatares: círculo.
- Borda: 1px `--color-divider`. Nunca 2px+ exceto foco.
- Marcas de registro `+` foram **removidas** (não combinam com cantos arredondados).
- Sombras: `--shadow-sm` só em elementos flutuantes (dropdown, card sendo arrastado); `--shadow-lg` em dialogs. Cards em repouso **não** têm sombra.

---

## 5. Iconografia
- **Lucide**, stroke-width **1.5**, tamanho 16px (inline/menu), 18px (botões ícone), 20px (KPI).
- Cor herda o texto (`currentColor`). Sem fundos coloridos atrás de ícones.

---

## 6. Estados de interação

| Estado | Regra |
|---|---|
| Hover (primário) | bg `accent-800` |
| Pressed (primário) | bg `accent-900` |
| Hover (secundário/ghost/linha) | bg `color-mix(accent 6%, transparent)` |
| Selecionado (menu, tab) | sidebar: bg `rgba(255,255,255,.08)` + barra 2px branca à esquerda; tabs: sublinhado 2px marinho |
| Foco | `outline: 2px solid var(--color-accent); outline-offset: 2px` |
| Desabilitado | `opacity: .45; cursor: not-allowed` |
| Arrastando (kanban) | `--shadow-md`, rotação 1deg, borda accent |
| Transições | 120ms `ease-out` em cor/bg; 200ms em layout. Sem bounces. |

---

## 7. Componentes

### 7.1 Botões
- **Primário**: bg `--color-accent`, texto `#fff`, Barlow Condensed 600 14px, altura 36px, padding 0 16px, radius 8px. No escuro: bg `#BCC3E4`, texto `#0A0F3D`, hover `#DCE0F2`, pressed `#8C96C8`.
- **Secundário**: transparente, borda divider, texto `--color-text`.
- **Ghost**: sem borda, texto accent.
- **Perigo**: transparente, borda e texto `danger`. Hover bg `danger-100`.
- **Ícone**: 32×32 (kanban) / 36×36, borda divider, ícone 16px.
- Máximo **um** primário por área visível.

### 7.2 Tags
Altura 22px, padding 0 8px, Body 600 11px uppercase tracking .06em, radius 6px.
Variantes: `accent` (tint marinho), `outline` (borda marinho — lead frio), `warning`, `danger`, `success`, `neutral`.

### 7.3 Inputs
Label overline acima (não flutuante). Input 36px, bg `--color-surface`, borda divider, hover borda `text 45%`, foco borda accent. Placeholder `text-muted`. Ícone de busca à esquerda 16px.
Select segue o mesmo visual com chevron Lucide.

### 7.4 Card / Painel
Radius 14px, borda hairline, padding 20px. Claro: transparente. Escuro: bg `--color-card` + borda `rgba(241,242,248,.12)`. Cabeçalho: overline + título h3 + ação opcional à direita. Sem divisória interna — use espaço.

### 7.5 KPI
Card com: overline (rótulo) · valor `kpi` 38px tabular (cor de destaque, nunca muted) · linha de contexto caption muted (ex.: "1 vendedor · out/2026"). Variação positiva/negativa como texto colorido (success/danger) com seta Lucide, nunca pill.

### 7.6 Alerta (banner)
Borda-esquerda **não**. Usa: borda 1px `warning`, bg `warning-100`, ícone `alert-triangle` warning, título Body 600 + descrição, ação secundária à direita. Dispensável (×).

### 7.7 Sidebar
> **Alterado pelo dono em 22/10/2026:** no tema claro a sidebar é clara (`#FFFFFF`, borda direita divider, texto `neutral-800`, rótulos e ícones `neutral-700`, ativo `accent-100` + texto e barra `accent-700`). No escuro continua `#04061A`. O texto abaixo descreve a versão original.

Bg `accent-900`. Topo: monograma M (Cinzel) + "LICAS" wordmark. Grupos com overline `accent-400`. Itens 34px radius 8px, ícone 16px `accent-300`, texto 14px `accent-200`; ativo branco com barra 2px. Rodapé: avatar + nome + papel.

### 7.8 Topbar
Altura 56px, borda inferior hairline, breadcrumb à esquerda, busca global (⌘K), notificações e avatar à direita.

### 7.9 Tabs
Texto Heading 600 15px, `text-muted`; ativo `text` + sublinhado 2px accent. Contador em tag neutra.

### 7.10 Kanban
- Coluna: cabeçalho overline + contador (tag neutra) + menu kebab. Sem fundo; só borda superior 2px `text` para marcar a coluna.
- Card: radius 14px (mesmo do card), padding 12px, gap 8px. Linha 1: tags (temperatura + dias). Linha 2: nome do cliente Body 600 14px Title Case. Linha 3: telefone + data (caption muted, ícones 12px). Rodapé: avatar 20px + responsável, ações ícone (agendar, descartar) à direita aparecendo no hover.

### 7.11 Tabela
Cabeçalho overline muted, borda inferior divider. Linhas 44px, divisória `divider-soft`, hover `accent 4%`. Números alinhados à direita, tabulares.

### 7.12 Gráficos
- Série 1: `accent-700`. Série 2: `accent-400`. Série 3: `neutral-500`. Status só quando o dado é status.
- Grid: linhas `divider-soft` sólidas, eixos Body 12px muted. Barras com topo arredondado 6px (base reta), largura máx 48px. Trilha do funil 8px, radius 4px.
- Funil: barras horizontais decrescentes com % e contagem — não gauge circular.

### 7.13 Dialog
Largura 480px, radius 14px, borda hairline, bg `--color-bg` (escuro: `--color-card`), `--shadow-lg`, backdrop `rgba(10,15,61,.45)`. Título h3, ações à direita (secundário + primário).

### 7.14 Avatar
Círculo 20/28/36px. Sem foto: iniciais Body 600 em `accent-100` / texto `accent-800`.

---

## 8. Login (referência de marca)
Fundo `--color-bg`. Centro: monograma M Cinzel 120px marinho com linha horizontal cruzando a base (hairline 1.5px), wordmark "LICAS CONSULTORIA" Cinzel tracking .32em, "SEGUROS" 11px tracking .5em. Formulário 360px abaixo: e-mail, senha, botão primário bloco 44px.

---

## 9. Tokens CSS (copiar)

```css
:root{
  --color-bg:#F7F7F8; --color-surface:#EFEFF2; --color-text:#0E1230; --color-text-muted:#5B5F78;
  --color-accent:#141C5C; --color-divider:rgba(14,18,48,.14); --color-divider-soft:rgba(14,18,48,.07);
  --color-accent-100:#EEF0FA; --color-accent-200:#DCE0F2; --color-accent-300:#BCC3E4; --color-accent-400:#8C96C8;
  --color-accent-500:#5A66A6; --color-accent-600:#2E3A80; --color-accent-700:#141C5C; --color-accent-800:#0F1650; --color-accent-900:#0A0F3D;
  --color-neutral-100:#F7F7F8; --color-neutral-200:#EFEFF2; --color-neutral-300:#DDDEE4; --color-neutral-400:#BDBFCA;
  --color-neutral-500:#9A9DAE; --color-neutral-600:#787C91; --color-neutral-700:#5B5F78; --color-neutral-800:#363A55; --color-neutral-900:#0E1230;
  --color-success:#2F7D5B; --color-success-100:#E6F2EC; --color-success-800:#1C4D38;
  --color-warning:#A86B12; --color-warning-100:#FBF1DE; --color-warning-800:#5E3C08;
  --color-danger:#B23A3A;  --color-danger-100:#F8E7E7;  --color-danger-800:#6B2121;
  --font-brand:"Cinzel",Georgia,serif; --font-heading:"Barlow Condensed",system-ui,sans-serif; --font-body:"Barlow",system-ui,sans-serif;
  --space-1:4px; --space-2:8px; --space-3:12px; --space-4:16px; --space-5:20px; --space-6:24px; --space-8:32px; --space-10:40px; --space-12:48px;
  --radius-sm:6px; --radius-md:8px; --radius-lg:10px; --radius-xl:14px; --radius-2xl:16px; --sidebar-w:248px; --control-h:36px;
  --shadow-sm:0 1px 2px rgba(14,18,48,.10); --shadow-md:0 4px 12px rgba(14,18,48,.12); --shadow-lg:0 16px 40px rgba(14,18,48,.18);
}
```

---

## 10. Não fazer
- Roxo, ciano, gradientes, glow, fundos escuros roxos do sistema antigo.
- Cantos retos ou raios fora da escala (ex.: pills 999px em botões).
- Ícones dentro de quadrados coloridos.
- Texto de cliente todo em CAIXA ALTA.
- Fonte mono para dinheiro.
- Borda-esquerda colorida como acento de card.
- Gauge circular para funil.
- Mais de um botão primário por área.
