# Vencimentos de boletos

Recurso de acompanhamento mensal de contratos implantados e de clientes sem contrato na carteira. Administrativos e backoffice trabalham os lembretes da empresa ativa pela tela **Vencimentos de boletos** (`/back-office/boletos`) e recebem avisos dentro do painel. O recurso não emite boletos, não confirma pagamentos e não envia e-mail ou WhatsApp.

## Uso

1. Em **Contratos implantados**, procure o cliente ou proposta. O filtro **Sem cadastro** ajuda a localizar contratos que ainda não geram lembretes.
2. Selecione **Cadastrar vencimento** e informe manualmente o dia mensal (1 a 31) e o próximo vencimento. A data de implantação não é usada como vencimento. O próximo vencimento deve ser hoje ou uma data futura, no dia mensal escolhido ou no último dia daquele mês quando esse dia não existir.
3. Mantenha **Gerar lembretes mensais** marcado e salve. Vencimentos a até 10 dias de hoje geram o aviso ao salvar. Use **Editar cadastro** para ajustar ou pausar a agenda; lembretes existentes não são apagados.
4. Dez dias antes do vencimento, o lembrete aparece entre os pendentes. Abra o contrato para conferir o necessário e use **Registrar acompanhamento** → **Marcar como tratado**, com observação opcional de até 1.000 caracteres.

Marcar como tratado registra responsável, horário e observação e retira o lembrete da fila. **Não é uma baixa financeira nem prova de pagamento.** Apenas abrir ou ler uma notificação não trata o lembrete. A tela mostra os dez acompanhamentos mais recentes; os registros anteriores permanecem no banco.

## Clientes sem contrato

Use **Novo cliente sem contrato** no cabeçalho da tela. Informe nome do cliente, uma referência opcional (operadora, plano ou identificação do boleto), dia mensal e próximo vencimento. O cadastro pertence à empresa ativa e não cria venda nem contrato fictício.

A seção **Clientes sem contrato** permite editar os dados e pausar os próximos lembretes. Os filtros de nome/referência e situação também se aplicam a essa lista. O filtro **Sem cadastro** mostra apenas contratos da carteira sem agenda, pois todo cliente independente já possui vencimento.

Os avisos e o acompanhamento são iguais aos da carteira. Nome e referência são copiados para cada lembrete gerado: editar o cadastro não altera o histórico das competências anteriores.

## Recorrência e persistência

- A agenda usa a data civil de `America/Sao_Paulo`. No dia 31, por exemplo, fevereiro usa seu último dia e março volta ao dia 31.
- Há uma agenda por contrato e no máximo um lembrete por agenda e competência mensal. Repetir a geração não duplica o mês nem sua notificação.
- Se o agendador ficar parado, a próxima execução recupera as competências vencidas desde a próxima notificação salva e avança a agenda até uma data futura.
- O lembrete permanece pendente após o dia do vencimento até a equipe tratá-lo. Pausar ou editar a agenda preserva os registros já criados. Para agendas vinculadas à carteira, a geração e a fila operacional consideram somente contratos que continuam implantados; sair desse status não apaga o histórico armazenado.
- Uma competência que já possui lembrete não pode ser escolhida novamente ao configurar o próximo vencimento. Escolha o próximo mês para preservar o histórico.

## Avisos e isolamento

Cada lembrete novo gera uma notificação de banco para usuários ativos dos perfis administrativo e backoffice pertencentes à empresa. O painel mostra a contagem de pendências e o acesso à fila. Ao tratar, as notificações daquele lembrete são marcadas como lidas para os destinatários.

Rotas, consultas, vínculos e operações usam a empresa ativa. A geração percorre agendas de várias empresas, mas executa cada uma dentro de seu contexto de tenant e seleciona destinatários da mesma empresa. O aviso de uma empresa não deve aparecer ao alternar para outra. As restrições de perfil e empresa também se aplicam no servidor.

## Operação técnica

A publicação exige aplicar as migrations pendentes, incluindo `2026_09_14_000002_allow_manual_boleto_agendas.php` para clientes independentes e a migration inicial `2026_09_14_000001_create_boleto_lembretes_tables.php`, disponibilizar os assets compilados e manter o scheduler existente em funcionamento. A migration cria `boleto_agendas` e `boleto_lembretes`, incluindo a unicidade por agenda e competência e os campos de auditoria.

`routes/console.php` agenda `boletos:lembrar` a cada minuto, com timezone `America/Sao_Paulo`, `withoutOverlapping()` e `onOneServer()`. Use o ambiente Docker/wrapper do projeto para executar Artisan. O comando também aceita `boletos:lembrar --empresa=ID` para limitar a geração a uma empresa existente; essa execução cria lembretes e notificações reais no banco selecionado. Sem a opção, processa todas as empresas elegíveis e informa quantos lembretes criou.

Referências de implementação: `BoletoLembreteService` concentra elegibilidade, configuração, geração e tratamento; `BoletoLembreteController` valida os formulários e fornece a fila e o resumo do painel; `GerarLembretesBoletos` expõe o comando; `BoletoVencimentoNotification` usa exclusivamente o canal `database`.

## Atualização: antecedência e filtros

Os alertas são calculados pelo servidor **10 dias corridos antes do vencimento**, a cada competência. O cálculo considera virada de mês, ano bissexto e o último dia do mês. A data do alerta é exibida automaticamente no formulário; cadastros feitos com menos de dez dias de antecedência geram o aviso ao salvar. Agendas existentes adotam essa regra pela migration `2026_09_15_000002_schedule_boleto_alerts_ten_days_before`; avisos já emitidos preservam suas datas históricas.

Os filtros **Mês do vencimento** e **Quinzena do vencimento** aparecem antes da fila: primeira quinzena corresponde aos dias 1–15, segunda aos dias 16–31 (até o último dia do mês). A fila usa o vencimento do boleto; os cadastros usam o próximo vencimento programado. Mês em branco considera todos os meses. A paginação mantém os filtros e “Limpar filtros” restaura a visão completa.

É possível editar ou excluir uma ocorrência pendente e excluir um cadastro mensal. A exclusão do cadastro cancela seus avisos pendentes e futuros e preserva os acompanhamentos tratados.
