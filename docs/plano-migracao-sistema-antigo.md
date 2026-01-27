# Plano de Migração - Sistema Antigo para SalesControl CRM

## Status: PENDENTE - Aguardando informações

## Objetivo

Migrar todas as vendas de 2021 até o presente do sistema antigo para o SalesControl CRM, descontinuando o sistema legado e consolidando toda a carteira num único lugar.

## Análise de Viabilidade

**Resultado: VIÁVEL**

O banco de dados do SalesControl consegue receber todas as vendas do sistema antigo. A maioria dos campos da tabela `vendas` é nullable, o que facilita a importação mesmo quando nem todos os dados estão disponíveis.

## Campos obrigatórios na tabela `vendas`

| Campo | Origem |
|-------|--------|
| `empresa_id` | Fixo (empresa do CRM) |
| `user_id` | Match pelo **nome do vendedor** |
| `operadora` | Nome da operadora (texto) |
| `valor_contrato` | Valor do contrato |

Todos os outros campos (`nome_contrato`, `cpf_cnpj`, `data_vigencia`, `data_implantacao`, `vidas`, `nome_plano`, etc.) são opcionais — se existirem no sistema antigo, importamos; se não, ficam null.

## Cadeia de Importação

A ordem de inserção deve ser:

1. **Contatos** — Criar registro em `contatos` (se não existir pelo CPF/nome)
2. **Contatos_Corretores** — Vincular o contato ao vendedor
3. **Vendas** — Criar a venda com `user_id` encontrado pelo nome
4. **Titulares/Dependentes/Portabilidades** — Se existirem no sistema antigo

## Match de Vendedores por Nome

```
Nome no sistema antigo → SELECT id FROM users WHERE name = ? AND empresa_id = ?
```

- Se encontrar: atribui o `user_id`
- Se não encontrar: coloca num log de "vendas órfãs" para tratamento manual (vendedor pode ter saído da empresa ou nome estar diferente)

## Informações Necessárias (antes de implementar)

1. **Em que formato estão os dados do sistema antigo?** (CSV/Excel exportado, acesso direto ao banco MySQL/Postgres, API?)
2. **Quais campos existem lá?** (nome do cliente, CPF, operadora, plano, valor, data de vigência, data de implantação, vidas, titulares, dependentes?)
3. **Quantas vendas aproximadamente?** (para dimensionar se roda direto ou em chunks)

## Implementação Prevista

- Comando Artisan: `vendas:importar-legado`
- Importação segura sem duplicar registros existentes
- Log detalhado de vendas importadas, ignoradas e órfãs
- Modo `--dry-run` para validação prévia

## Riscos e Cuidados

- Vendedores com nomes diferentes entre sistemas precisam de mapeamento manual
- Deduplicação por CPF/CNPJ + operadora + data_vigencia para evitar duplicatas
- Transações por chunk para segurança em caso de falha
- Recebiveis NÃO devem ser gerados automaticamente na importação (rodar `recebiveis:retroativos` depois, manualmente)
