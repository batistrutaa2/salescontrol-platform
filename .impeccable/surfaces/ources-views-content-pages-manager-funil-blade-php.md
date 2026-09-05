---
version: 1
slug: "ources-views-content-pages-manager-funil-blade-php"
primary_target: "resources/views/content/pages/manager/funil.blade.php"
related_targets: ["resources/assets/vendor/scss/pages/page-funnel-manager.scss","app/Http/Controllers/pages/manager/FunilController.php","resources/views/layouts/commonMaster.blade.php","resources/views/layouts/sections/navbar/navbar.blade.php"]
---

# Configuração de funis

## Contrato da superfície

- Escopo: `/configuracoes/funis`, modo Operate.
- Público: desenvolvedor da plataforma e administrativo da empresa ativa.
- Trabalho: criar, renomear, arquivar e ordenar etapas do fluxo comercial e do pós-venda sem conhecer IDs técnicos.
- Ação principal: confirmar a empresa ativa e criar ou ajustar uma etapa daquele tenant.
- Tese: cada corretora governa um fluxo operacional próprio; a página recusa configuração abstrata que esconda o tenant ou os efeitos da mudança.
- Direção: ledger operacional no mundo visual estabelecido, com papel frio, tinta violeta, linhas precisas e um único campo violeta de autoridade. Contrato FORM registrado sob a seed `f4c9382a`.

## Resultado construído

O primeiro viewport abre com o hero “Desenhe o caminho da operação”. A empresa ativa aparece nominalmente ao lado de um marcador explícito; o campo violeta adjacente explica três guardrails antes de qualquer controle: códigos técnicos não entram no formulário, etapas estruturais preservam regras internas e dados de outra empresa não podem ser consultados ou editados.

A criação vem imediatamente abaixo em uma seção expansível. O formulário pede somente nome, fluxo, efetividade e prazo; permanece aberto quando uma tentativa de criação volta com erro. Alertas de sucesso e falha antecedem o hero e mantêm a estrutura da página no retorno do servidor.

Os dois domínios operacionais aparecem depois como ledgers independentes e ordenados:

- **Funil comercial:** jornada do lead até o fechamento.
- **Pós-venda e administrativo:** contratos, pendências e implantação.

Cada linha mostra posição sequencial, nome, código ou identidade de etapa personalizada, classificação estrutural/própria, disponibilidade e prazo. A expansão da linha revela movimento para cima ou para baixo e edição contextual, sem transformar as etapas em mosaico de cartões. Estados vazios preservam o título e a contagem do fluxo.

## Regras de configuração e tenant

- `empresa_id` nunca é editável nem enviado pela superfície; listagem, criação, edição e ordenação derivam a empresa do contexto ativo no servidor.
- O nome da empresa no hero confirma o escopo antes da ação. Para administradores da plataforma, o seletor da navbar troca explicitamente a empresa ativa e a página volta a refletir esse contexto.
- A consulta de uma etapa de outro tenant termina em 404. A ordenação considera apenas etapas do mesmo tenant e do mesmo fluxo e é recalculada dentro de transação.
- O nome é único dentro da empresa e persistido em caixa alta. Uma nova etapa entra no fim do fluxo escolhido e nasce ativa.
- Etapas estruturais podem receber novo nome e prazo, mas conservam código, efetividade e disponibilidade; o editor as identifica como “Etapa estrutural protegida”.
- Etapas próprias podem alterar nome, efetividade, disponibilidade e prazo. Arquivar substitui exclusão: a superfície não oferece remoção destrutiva.
- Os controles de movimento ficam indisponíveis nas extremidades do ledger, tornando o limite da sequência visível antes do envio.

## Comportamento responsivo

- **Acima de 1100px:** hero em duas colunas, com narrativa e empresa à esquerda e guardrails no campo violeta à direita; criação e edição usam uma grade horizontal; cada etapa usa cinco faixas para posição, identidade, metadados, prazo e acionador.
- **Até 1100px:** formulários passam a duas colunas e o botão ocupa a largura disponível; o prazo sai do resumo do ledger, mas continua acessível dentro do editor; a linha reduz sua grade para quatro faixas.
- **Até 767,98px:** hero, cabeçalho dos fluxos e formulários empilham em uma coluna. A linha vira um registro compacto com posição, identidade e acionador na primeira faixa e chips na faixa seguinte; nomes deixam de truncar, o editor reduz padding e o prazo permanece no formulário expandido.
- No mesmo breakpoint móvel, a navbar cede espaço ao contexto operacional: o rótulo do seletor de empresa fica apenas para tecnologias assistivas, a seleção usa largura compacta com elipse, os rótulos do seletor de módulo cedem lugar aos ícones e avatar e espaçamentos diminuem.
- Nenhum breakpoint transforma o ledger em tabela larga com rolagem horizontal; a hierarquia e a edição permanecem legíveis dentro da largura da tela.

## Linguagem visual e estados

- Superfícies claras e papel frio organizam a sequência; violeta profundo fica concentrado no campo de proteção, lavanda marca etapas estruturais e verde confirma etapas ativas.
- Tema escuro troca tinta, papel, superfície, linhas e pares semânticos por equivalentes noturnos sem alterar a hierarquia.
- Contagens e posições usam algarismos tabulares. Linhas abertas recebem papel tonal, divisores preservam a leitura sequencial e o ícone de edição responde com movimento curto.
- Campos mantêm altura mínima de 44px. Foco visível usa halo violeta; labels, `aria-label`, regiões de alerta e os elementos nativos `details`/`summary` sustentam navegação por teclado e leitura assistiva.

## Guardrails duráveis

- **Faça** manter empresa ativa e consequências da configuração visíveis antes dos controles.
- **Faça** distinguir etapa estrutural de etapa própria tanto no resumo quanto nas permissões do editor.
- **Faça** preservar os dois fluxos como sequências ordenadas com próxima ação clara.
- **Não faça** expor `empresa_id`, IDs técnicos ou códigos estruturais como configuração de usuário.
- **Não faça** permitir que uma mudança visual contorne escopo de tenant, imutabilidade estrutural ou validação no servidor.
- **Não faça** substituir arquivamento por exclusão destrutiva ou esconder prazo/edição no celular.

## Continuidade

- Pendente: ampliar gradualmente o catálogo semântico aos módulos legados que ainda usam IDs numéricos.
