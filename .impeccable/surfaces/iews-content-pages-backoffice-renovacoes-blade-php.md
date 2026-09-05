---
version: 1
slug: "iews-content-pages-backoffice-renovacoes-blade-php"
primary_target: "resources/views/content/pages/backoffice/renovacoes.blade.php"
related_targets: ["resources/assets/js/renovacoes.js","resources/assets/vendor/scss/pages/renovacoes.scss"]
---

## Scope and mode

- Primary target: `resources/views/content/pages/backoffice/renovacoes.blade.php`
- Related targets: `resources/assets/js/renovacoes.js`, `resources/assets/vendor/scss/pages/renovacoes.scss`
- Mode: Operate
- Visual evidence: `.impeccable/review/desktop.png`, `.impeccable/review/mobile.png`

Este brief governa apenas o mundo visual da rota de Renovações do Backoffice. O `DESIGN.md` da raiz documenta o universo estabelecido do dashboard do vendedor e continua autoritativo para aquela superfície; não deve ser mesclado nem substituído pelas regras específicas abaixo sem uma decisão explícita de produto.

## Audience and job

Administrativo, Backoffice e Developer trabalham a carteira histórica da corretora. Precisam localizar o próximo cliente, atualizar contatos, oferecer apoio de pós-venda, entender o plano atual e conduzir oportunidades independentes de Saúde e Seguro de Vida.

## Tasks and proof

- Ler cobertura, resposta, avanço e conversão no primeiro viewport.
- Filtrar por etapa, responsável, vendedor original, implantação, operadora, disponibilidade de contato e frente ativa.
- Consultar Lemit ou Assertiva pelo documento imutável da carteira.
- Registrar canal, etapa, retorno, diagnóstico, pendência e interesse por produto em uma única ficha.
- Auditar pesquisas e interações e comparar desempenho por responsável.

## Constraints

- Isolamento por empresa e autorização no servidor.
- Somente Administrativo, Backoffice e Developer acessam.
- Informações excessivas das fontes de enriquecimento não chegam ao navegador.
- Reagendamentos futuros saem da fila padrão, mas permanecem pesquisáveis.
- Desktop usa mesa dividida; tablet e celular usam ficha lateral de tela cheia.

## Direction

“Carteira em movimento”: mesa operacional de papel quente, tinta azul-marinho, ação cobre e avanço verde-petróleo. O momento memorável é o trilho contínuo do funil, que também funciona como filtro real. A ficha preserva lado a lado a verdade do contrato vendido, o relato atual do cliente e as duas frentes comerciais.

## Sistema visual entregue

**Creative North Star: “Carteira em movimento”**

A rota funciona como uma mesa de relacionamento, não como um dashboard genérico de análise. Papel quente e superfícies quase brancas sustentam informação operacional densa; o azul-marinho cria comando e hierarquia; o cobre marca a próxima ação deliberada ou a seleção atual; o verde-petróleo marca avanço saudável, contato e confirmação. Inter é a única família tipográfica, com algarismos tabulares em toda a rota para estabilizar contagens, datas, documentos e valores durante a leitura.

### Papéis de cor

- **Azul-marinho de Relacionamento** (`#15334a`): títulos, marca de comando e campo contínuo de indicadores. É a âncora visual da rota, não um acento geral a repetir em cada contêiner.
- **Cobre de Ação** (`#c56f3c`, hover `#98502a`): ações primárias, linhas e etapas selecionadas, marcos da timeline, foco e setas direcionais.
- **Verde-petróleo de Avanço** (`#16766f`): etapa do relacionamento, ações seguras de contato, pesquisa bem-sucedida e confirmação.
- **Papel Quente** (`#f4f1eb`) e **Superfície da Mesa** (`#fffdf9`): leito de filtros, hover da lista, registros agrupados e superfícies principais de trabalho.
- **Tinta Operacional** (`#182839`), **Ledger Atenuado** (`#667383`) e **Linha de Ledger** (`#ded9d0`): hierarquia de leitura e divisores finos.
- Feedback de erro usa tijolo atenuado (`#9e3c31` sobre `#f7e4e1`). Saúde e Vida mantêm identificadores próprios, respectivamente verde e violeta discretos, dentro da ficha; nenhum compete com o cobre como cor de ação.
- O tema escuro troca esses papéis pelos pares noturnos declarados na rota. Significado semântico e densidade permanecem iguais.

**Regra da Única Cor de Comando.** O cobre identifica o que a pessoa pode fazer em seguida ou o que selecionou. Não o use como decoração ampla.

**Regra do Avanço Seguro.** O verde-petróleo significa que o relacionamento avançou, que há um caminho de contato ou que uma operação funcionou; nunca substitui semânticas de erro ou alerta.

### Tipografia, forma e profundidade

- Inter com fallbacks do sistema cobre todos os papéis. O título da página é compacto e pesado (`clamp(1.7rem, 2.4vw, 2.35rem)`); rótulos operacionais e linhas repetidas permanecem deliberadamente pequenos (`.7rem` a `.82rem`), com peso, cor e espaçamento formando a hierarquia.
- Títulos usam tracking negativo justo; números usam algarismos tabulares. Caixa alta é reservada a categorias curtas, como rótulos de indicadores e metadados de etapa, nunca a parágrafos ou nomes de clientes.
- Superfícies principais usam cantos suavemente curvos (`13px` a `15px`); controles e registros aninhados reduzem o raio (`9px` a `11px`); pontos e indicadores compactos podem ser circulares.
- Sombras amplas e de baixo contraste separam indicadores, funil e área de trabalho do canvas da aplicação. A marca azul-marinho e a ação primária cobre recebem sombras coloridas mais firmes. Linhas finas de ledger estruturam informação repetida em vez de cercar cada dado com um cartão.

**Regra do Ledger Antes dos Cartões.** Clientes, indicadores e itens de histórico repetidos se organizam por campos, ritmo e divisores compartilhados. Não transforme cada item repetido em um cartão flutuante isolado.

## Composição e comportamento responsivo

A sequência canônica no desktop é cabeçalho de comando, faixa contínua de indicadores, funil de largura total, desempenho da equipe recolhível e então a área operacional. A área é uma única mesa unida: fila mais larga à esquerda e ficha sticky à direita (`minmax(400px, 42%)`). A aresta reta de encontro e a borda compartilhada são intencionais; devem parecer duas metades da mesma tarefa.

Em larguras abaixo de `1400px`, o indicador de retornos vencidos vira uma linha inteira e a fila remove a coluna de responsável antes de comprimir identidade ou próxima ação. Abaixo de `1200px`, a área de trabalho vira uma coluna e a ficha se torna um overlay lateral de até `610px`. Abaixo de `768px`, a ação de comando ocupa toda a largura, indicadores e funil se tornam trilhos horizontais com snap e instrução, filtros recolhem sob um controle com contagem, linhas se achatam para identidade/etapa e contato/próxima ação, e a ficha vira uma folha de tela cheia com safe areas.

Overflow horizontal é deliberado somente nos trilhos de indicadores e funil e no histórico de contratos realmente tabular. A página em si deve permanecer contida. “Deslize” é uma affordance móvel intencional dos trilhos horizontais, não um texto auxiliar genérico.

## Componentes de assinatura e comportamento

### Comando e faixa de indicadores

O cabeçalho combina marca de atendimento e promessa operacional concisa com contexto de sincronização e uma única ação cobre, “Trabalhar próximo cliente”. A faixa azul-marinho é um objeto contínuo: tamanho da carteira lidera, resposta/negociação/conversão compõem o meio e retornos vencidos permanecem uma exceção acionável. Não a divida em cinco cartões de KPI sem relação.

### Trilho do funil

Sete etapas adjacentes formam um caminho contínuo de relacionamento. Cada etapa combina ícone, rótulo direto, explicação curta e contagem, e funciona como filtro real da fila com `aria-pressed`. O hover repousa em papel quente; a seleção usa cobre suave com ícone cobre sólido. “Ver funil completo” limpa a etapa sem introduzir um segundo modelo de filtro.

### Ledger de clientes

As linhas priorizam identidade do cliente e contexto do documento imutável, depois etapa, contato, responsável e próxima ação. A seleção usa um banho de cobre quente; a etapa usa ponto e texto verde-petróleo; o chevron cobre sinaliza entrada na ficha. No celular, a linha vira um registro compacto de dois andares, preservando identidade, etapa, contato e próxima ação enquanto omite responsável.

### Ficha do cliente

A ficha mantém cabeçalho sticky e corpo rolável próprios. Abre com etapa atual, identidade do cliente e documento imutável, seguidos por responsabilidade e fotografia do contrato em três partes. A ordem restante segue o caminho de decisão da operação: contato seguro e enriquecimento, evolução do relacionamento, verdade do plano atual/pós-venda, pipelines independentes de Saúde e Vida, resumo e salvamento da conversa, contratos recolhíveis e timeline auditável.

Lemit e Assertiva são controles secundários de pesquisa em par, não chamadas primárias. Links de WhatsApp ficam junto aos números verificados. A ação cobre de largura total conclui a etapa corrente. Resultados de pesquisa usam campo tonal verde-petróleo; falhas de validação e carregamento usam o campo tijolo de erro.

### Estados operacionais

- Carregamentos de fila e ficha preservam a geometria do contêiner e usam três pontos cobre pulsantes com status em linguagem direta.
- Estados vazio e de erro ocupam a área de leitura da fila; o erro oferece uma única ação de nova tentativa.
- Parâmetros de query na URL espelham filtros ativos, e o controle de filtros informa sua contagem no celular.
- Notificações de sucesso e erro usam toast inferior e anúncio em `aria-live`. O toast sobe somente `12px` e permanece visível por um intervalo limitado.
- Todo elemento focável recebe anel externo cobre. `prefers-reduced-motion` remove animações, transições e rolagem suave sem esconder mudanças de estado.

## Guardrails visuais

### Faça

- Preserve a ordem de leitura do primeiro viewport: comando, indicadores, funil, desempenho recolhível, fila e ficha.
- Use linhas compartilhadas e agrupamento tonal para acelerar a leitura de dados operacionais densos.
- Mantenha verdade do contrato, relato atual do cliente e oportunidades de Saúde/Vida visualmente distintos dentro de uma ficha contínua.
- Preserve a paridade de papéis no tema escuro e as safe areas móveis.
- Trate `.impeccable/review/desktop.png` e `.impeccable/review/mobile.png` como referência entregue de densidade, hierarquia e composição responsiva.

### Não faça

- Não importe para esta rota a linguagem violeta de placar “Temporada Comercial” do `DESIGN.md` da raiz.
- Não fragmente faixa de indicadores, funil, ledger ou ficha em um mosaico de cartões independentes.
- Não trate o funil como visualização decorativa; cada etapa permanece um filtro operável.
- Não force a mesa dividida do desktop em tablet ou celular e não resolva o ledger móvel com rolagem horizontal da página.
- Não exponha detalhes do payload de provedores nem entrada mutável de documento na superfície visual.

## Evidência entregue

- `resources/views/content/pages/backoffice/renovacoes.blade.php` contém tese, história, contrato do primeiro viewport e esqueleto semântico da aplicação.
- `resources/assets/vendor/scss/pages/renovacoes.scss` é a fonte normativa dos tokens da rota, estilo dos componentes, tema escuro, breakpoints, movimento e comportamento de movimento reduzido.
- `resources/assets/js/renovacoes.js` é a fonte normativa dos estados dinâmicos, filtros, renderização de fila/ficha, feedback de enriquecimento e anúncios acessíveis.
- `.impeccable/review/desktop.png` registra a mesa unida de fila e ficha na densidade desktop.
- `.impeccable/review/mobile.png` registra ledger compacto, filtros recolhidos e trilhos de indicadores/funil horizontalmente descobríveis. O tratamento móvel da ficha está definido na implementação, embora ela não esteja aberta nesse raster.

## Unresolved

- Validar com a operação os textos finais de abordagem no WhatsApp.
