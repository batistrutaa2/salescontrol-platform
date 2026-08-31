---
name: "SalesControl — Temporada Comercial"
description: "Um placar por período que transforma resultado, posição e vendas em uma temporada legível."
colors:
  ink: "#17152a"
  muted: "#696276"
  paper: "#f7f6fa"
  surface: "#ffffff"
  line: "#e4e0ec"
  primary: "#6845df"
  primary-deep: "#3d287f"
  primary-soft: "#eee9ff"
  success: "#147d5a"
  success-soft: "#e2f4ec"
  warning: "#a76b08"
  warning-soft: "#fff0ce"
  danger: "#b74343"
  danger-soft: "#fce8e8"
  night-ink: "#f4f1fa"
  night-muted: "#bdb6d0"
  night-paper: "#171521"
  night-surface: "#211e2c"
  night-line: "#393447"
  night-primary: "#a98cff"
  night-primary-deep: "#6d50c4"
  night-primary-soft: "#30284d"
  night-success: "#66d6ae"
  night-success-soft: "#213f36"
  night-warning: "#f0bd5e"
  night-warning-soft: "#49391e"
  night-danger: "#ff9797"
  night-danger-soft: "#492929"
typography:
  display:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: "clamp(4.25rem, 7vw, 6.4rem)"
    fontWeight: 820
    lineHeight: 1
    letterSpacing: "-0.04em"
  headline:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: "clamp(1.85rem, 3vw, 2.75rem)"
    fontWeight: 780
    lineHeight: 1.05
    letterSpacing: "-0.035em"
  title:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: "1.15rem"
    fontWeight: 760
    lineHeight: 1.35
    letterSpacing: "-0.018em"
  body:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: "0.96rem"
    fontWeight: 400
    lineHeight: 1.5
    letterSpacing: "normal"
  label:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: "0.78rem"
    fontWeight: 720
    lineHeight: 1.25
    letterSpacing: "0.035em"
rounded:
  status: "7px"
  sm: "8px"
  control: "9px"
  md: "10px"
  panel: "14px"
  full: "999px"
spacing:
  xs: "0.35rem"
  sm: "0.55rem"
  md: "0.75rem"
  lg: "1rem"
  xl: "1.25rem"
  xxl: "1.75rem"
components:
  period-filter:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.md}"
    padding: "0 2.5rem 0 0.9rem"
    height: "44px"
  score-panel:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.panel}"
    padding: "clamp(1.25rem, 2vw, 1.75rem)"
  ranking-panel:
    backgroundColor: "{colors.primary-deep}"
    textColor: "{colors.surface}"
    rounded: "{rounded.panel}"
    padding: "clamp(1.25rem, 2vw, 1.75rem)"
  month-stage:
    backgroundColor: "transparent"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "0.75rem 0.65rem 0.65rem"
    height: "98px"
    width: "76px"
  month-stage-active:
    backgroundColor: "{colors.primary-soft}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "0.75rem 0.65rem 0.65rem"
  status-positive:
    backgroundColor: "{colors.success-soft}"
    textColor: "{colors.success}"
    rounded: "{rounded.status}"
    padding: "0.35rem 0.55rem"
  status-pending:
    backgroundColor: "{colors.warning-soft}"
    textColor: "{colors.warning}"
    rounded: "{rounded.status}"
    padding: "0.35rem 0.55rem"
  status-reversal:
    backgroundColor: "{colors.danger-soft}"
    textColor: "{colors.danger}"
    rounded: "{rounded.status}"
    padding: "0.35rem 0.55rem"
---

# Design System: SalesControl — Temporada Comercial

## Overview

**Creative North Star: "Temporada Comercial"**

O desempenho comercial funciona como uma temporada legível. A interface organiza ano atual, mês atual e trimestre como um placar profissional: o vendedor reconhece sua posição e entende a composição do resultado. Violeta profundo, papel frio, verde de confirmação e âmbar de marco dão caráter sem competir com os números.

A hierarquia recusa o mosaico indiferenciado de KPIs. O ranking do período é o momento de maior autoridade; total válido e maior venda dão contexto lateral, e a evolução mensal explica o horizonte selecionado. Linhas finas, superfícies claras e números tabulares mantêm a operação precisa.

**Key Characteristics:**

- Placar tipográfico com um único número dominante.
- Papel frio, superfícies brancas e violeta profundo como campo de autoridade.
- Um único filtro global para ano atual, mês atual e trimestre.
- Verde para confirmação, âmbar para atenção e vermelho reservado a estorno e falha.
- Densidade operacional que se torna um ledger vertical legível no celular.

## Colors

A paleta combina violeta de comando com neutros frios; cores semânticas aparecem somente quando comunicam estado.

### Primary

- **Violeta de Temporada:** sinaliza seleção, links e progressão mensal.
- **Violeta de Placar:** sustenta o ranking central e concentra a maior autoridade visual.
- **Lavanda de Seleção:** marca o período ativo e o próprio vendedor sem perder contraste do conteúdo.

### Secondary

- **Verde de Confirmação:** identifica vendas e estados positivos.
- **Âmbar de Marco:** destaca recordes, pendências e atenção operacional sem assumir gravidade crítica.

### Tertiary

- **Vermelho de Estorno:** pertence a estornos, reversões pendentes e falhas recuperáveis; nunca é decoração.

### Neutral

- **Tinta Profunda:** texto principal e números de leitura decisiva.
- **Grafite Violeta:** apoio, explicação, rótulos e eixos.
- **Papel Frio:** fundo de linhas, listas e agrupamentos internos.
- **Superfície Branca:** cartões e controles elevados.
- **Linha de Névoa:** divisores, bordas de controles e estrutura discreta.
- **Noite Violeta:** o tema escuro troca cada função por seu par noturno, preservando a mesma hierarquia e os mesmos significados.

### Named Rules

**The Scoreboard Rule.** O violeta profundo forma um único campo de autoridade por primeiro viewport; os demais painéis permanecem em papel e superfície.

**The Semantic Color Rule.** Verde confirma, âmbar chama atenção e vermelho significa estorno, reversão ou erro. Não reutilize essas cores para ornamentação.

## Typography

**Display Font:** Inter (com fallbacks do sistema)
**Body Font:** Inter (com fallbacks do sistema)

**Character:** Uma única família sans-serif mantém o painel direto e contemporâneo. Contraste nasce de escala, peso, números tabulares e tracking compacto, não de uma segunda voz tipográfica.

### Hierarchy

- **Display:** posição no período; é o maior tipo da página e usa números tabulares.
- **Headline:** título da temporada, compacto e firme.
- **Title:** cabeçalhos de seção e títulos de insights; curtos, densos e orientados à leitura rápida.
- **Body:** explicações operacionais com largura máxima próxima de 68 caracteres.
- **Label:** rótulos curtos em caixa alta para categorias, colunas e metadados; dados e nomes preservam caixa natural.

### Named Rules

**The One Giant Number Rule.** Apenas a posição do período recebe escala de display. Valores monetários são fortes, mas não competem com o ranking.

**The Tabular Score Rule.** Valores, posições e contagens usam algarismos tabulares para que comparações não oscilem visualmente.

## Layout

O primeiro viewport segue uma sequência inequívoca: cabeçalho e filtro global, placar de três painéis, depois evolução mensal. Em telas largas, o ranking ocupa a coluna central mais larga, flanqueado por total válido e maior venda. Abaixo de 1200px, ele sobe e ocupa duas colunas; abaixo de 768px, todos os painéis formam uma única coluna.

O ritmo espacial usa intervalos compactos dentro dos controles e passos maiores entre blocos. Painéis principais recebem respiro fluido; estruturas internas usam divisores em vez de novos cartões. A evolução mostra somente os meses pertencentes ao período global, sem criar um quarto filtro.

No celular, o ledger deixa de imitar tabela: remove o cabeçalho, transforma cada venda em um registro de duas colunas com rótulos próprios e estende cliente/produto e status por toda a largura. O gráfico reduz a altura, oculta o eixo vertical e exibe rótulos alternados no eixo mensal para preservar clareza.

**The First Viewport Rule.** Filtro global, ranking do período central, total válido à esquerda e maior venda à direita formam a leitura canônica em desktop.

**The Horizontal Containment Rule.** Nenhuma seção pode alargar a página; somente o trilho mensal e tabelas que ainda precisem dele recebem rolagem interna.

## Elevation & Depth

O sistema combina superfícies tonais com sombras ambientais suaves. Painéis principais usam uma sombra ampla e discreta; o ranking recebe uma sombra violeta mais firme para assumir o centro do placar. Divisores finos estruturam conteúdo interno sem criar pilhas de cartões.

### Shadow Vocabulary

- **Painel principal** (`0 14px 38px rgba(36, 25, 68, 0.09)`): placar e superfícies de primeira ordem.
- **Painel de apoio** (`0 10px 30px rgba(36, 25, 68, 0.07)`): trilho, insights e ledger.
- **Placar central** (`0 18px 44px rgba(61, 40, 127, 0.25)`): somente o ranking do período.
- **Painel noturno** (`0 16px 42px rgba(0, 0, 0, 0.28)`): substitui a sombra principal no tema escuro.

**The Structural Shadow Rule.** Sombra indica nível de informação, não hover genérico. O ranking pode ser mais elevado porque orienta toda a leitura.

## Shapes

Painéis usam cantos suavemente curvos; controles, linhas do ledger e seletores usam raios menores. Círculos ficam restritos a informações compactas como posição, contagem e botão de ajuda. O fundo violeta do ranking recebe um único círculo difuso parcialmente recortado, eco de uma arena sem virar ilustração decorativa.

**The Nested Radius Rule.** Superfícies grandes usam o raio de painel; elementos internos reduzem progressivamente para controle, status e círculo.

## Components

### Score Panels

- **Shape:** cartões amplos de cantos suaves, com padding fluido e conteúdo alinhado ao mesmo eixo.
- **Default:** superfície clara, tinta profunda e sombra ambiental.
- **Ranking:** campo violeta profundo, texto branco, posição central e halo circular recortado.
- **Behavior:** durante carregamento, o conjunto reduz opacidade sem desmontar a estrutura.

### Period Evolution

- **Style:** meses do período com valor, contagem e uma barra inferior proporcional.
- **Behavior:** é uma leitura do filtro global, sem interação concorrente.

### Status Chips

- **Positive:** verde sobre verde suave.
- **Pending:** âmbar sobre âmbar suave.
- **Reversal:** vermelho sobre vermelho suave, inclusive quando ESTORNO continua compondo o total válido.

### Cards / Containers

- **Corner Style:** raio de painel nas superfícies principais e raio médio nas linhas internas.
- **Background:** superfície para cartões; papel frio para listas e agrupamentos.
- **Shadow Strategy:** profundidade ambiental por nível, nunca uma sombra diferente por cartão.
- **Border:** divisores finos separam composição, estatísticas e linhas.
- **Internal Padding:** respiro fluido nos painéis; compactação somente em itens repetidos.

### Inputs / Fields

- **Style:** superfície branca, borda fina, altura mínima confortável e texto forte.
- **Focus:** halo violeta de três pixels com offset externo.
- **Dark Theme:** troca para os pares noturnos sem alterar semântica ou densidade.

### Navigation and Links

- **Style:** links operacionais usam violeta e peso firme; sublinhados, quando presentes, têm afastamento legível.
- **Active:** seleção de período usa preenchimento lavanda, não apenas mudança de texto.
- **Keyboard:** todo botão, link e select deve manter `focus-visible` inequívoco.

### Sales Ledger

- **Desktop:** tabela plana, cabeçalhos compactos em caixa alta, divisores e hover tonal.
- **Mobile:** registros achatados em duas colunas com `data-label`; cliente/produto e status ocupam a largura completa.
- **Empty / Error:** mensagens preservam o espaço de leitura; falha externa aparece em uma faixa vermelha com ação de retry.

### Motion

- **State transitions:** borda, fundo, transformação e opacidade usam respostas curtas entre 160ms e 180ms com saída suave.
- **Chart:** barras animam em 450ms ao trocar o período global.
- **Ranking movement:** subida recebe celebração breve; queda recebe aviso contido. A posição anterior é local ao usuário, ano, mês ou trimestre, e movimento reduzido preserva a mensagem sem deslocamento.
- **Entrance:** o trilho mensal revela da esquerda para a direita em 520ms apenas quando o usuário não pediu movimento reduzido.

## Do's and Don'ts

### Do:

- **Do** conduzir a leitura pela posição, depois pela composição do valor e finalmente pelas vendas que explicam o resultado.
- **Do** aplicar o mesmo período a todos os indicadores, líderes e vendas detalhadas.
- **Do** preservar nomes de plano normalizados como unidade de agregação e mostrar operadora apenas como contexto.
- **Do** manter ESTORNO no total válido quando essa for a regra de negócio, mas sempre com semântica visual vermelha.
- **Do** adaptar dados tabulares para registros rotulados no celular e simplificar eixos antes que rótulos se choquem.

### Don't:

- **Don't** transformar o dashboard em um mosaico de KPIs de peso idêntico.
- **Don't** promover cada insight a um cartão de destaque ou repetir o violeta profundo em múltiplos campos concorrentes.
- **Don't** usar verde, âmbar ou vermelho como cor decorativa.
- **Don't** agrupar produto por combinação de plano e operadora.
- **Don't** resolver mobile apenas com uma tabela larga rolável ou um gráfico cheio de eixos comprimidos.
