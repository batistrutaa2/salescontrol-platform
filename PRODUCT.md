# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Equipes comerciais de corretoras. Vendedores trabalham suas próprias carteiras; supervisores, administrativos e desenvolvedores administram bases, filas e distribuição de leads.

## Product Purpose

O SalesControl centraliza o ciclo comercial de leads, propostas e contratos. O produto deve permitir que a gestão receba bases novas, preserve a origem e o histórico de cada contato e distribua oportunidades para vendedores com controle e rastreabilidade.

## Operating Context

Leads entram por mailings, mídia paga e cadastros internos. A operação usa filas distintas para prospecção, preditiva, remarketing, descarte e pós-venda. O reservatório guarda somente oportunidades novas antes da primeira distribuição e permite liberações graduais por estratégias definidas pelo gestor.

## Capabilities and Constraints

- Todo dado é isolado por empresa.
- Elegibilidade e permissões críticas são validadas no servidor.
- A importação persistente já classifica itens novos, duplicados e inválidos por CPF/CNPJ.
- Um contato entra no reservatório no máximo uma vez; distribuído ou bloqueado não retorna.
- Estratégias filtram os campos existentes do lead. O gestor escolhe vendedores e quantidades em cada execução, e os leads compatíveis são sorteados.
- Remarketing, descarte, preditiva, negócio fechado e vendas válidas não pertencem ao reservatório.
- Uma carga inicial excepcional pode recolher, uma única vez, os leads aptos concentrados em um vendedor.

## Product Principles

- Custódia explícita: cada lead deve ter uma posição operacional inequívoca.
- Segurança antes da conveniência: filtros de tela nunca substituem validações transacionais.
- Decisões persistentes e auditáveis: importações, estratégias e distribuições sobrevivem a refresh e concorrência.
- Clareza operacional: tabelas priorizam leitura, origem, estado e próxima ação.
- Preservação do histórico: transferências de custódia não apagam evidências de trabalho.
