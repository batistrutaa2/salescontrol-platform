# Vencimentos de boletos

Recurso local de acompanhamento mensal dos contratos implantados. Administrativos e backoffice trabalham os lembretes da empresa ativa pela tela **Vencimentos de boletos** (`/back-office/boletos`) e recebem avisos dentro do painel. O recurso não emite boletos, não confirma pagamentos e não envia e-mail ou WhatsApp.

## Uso

1. Em **Contratos implantados**, procure o cliente ou proposta. O filtro **Sem cadastro** ajuda a localizar contratos que ainda não geram lembretes.
2. Selecione **Cadastrar vencimento** e informe manualmente o dia mensal (1 a 31) e o próximo vencimento. A data de implantação não é usada como vencimento. O próximo vencimento deve ser hoje ou uma data futura, no dia mensal escolhido ou no último dia daquele mês quando esse dia não existir.
3. Mantenha **Gerar lembretes mensais** marcado e salve. Uma data de hoje já pode gerar o aviso ao salvar. Use **Editar vencimento** para ajustar ou pausar a agenda; lembretes existentes não são apagados.
4. No dia cadastrado, o lembrete aparece entre os pendentes. Abra o contrato para conferir o necessário e use **Registrar acompanhamento** → **Marcar como tratado**, com observação opcional de até 1.000 caracteres.

Marcar como tratado registra responsável, horário e observação e retira o lembrete da fila. **Não é uma baixa financeira nem prova de pagamento.** Apenas abrir ou ler uma notificação não trata o lembrete. A tela mostra os dez acompanhamentos mais recentes; os registros anteriores permanecem no banco.

## Recorrência e persistência

- A agenda usa a data civil de `America/Sao_Paulo`. No dia 31, por exemplo, fevereiro usa seu último dia e março volta ao dia 31.
- Há uma agenda por contrato e no máximo um lembrete por agenda e competência mensal. Repetir a geração não duplica o mês nem sua notificação.
- Se o agendador ficar parado, a próxima execução recupera as competências vencidas desde o próximo vencimento salvo e avança a agenda até uma data futura.
- O lembrete permanece pendente após o dia do vencimento até a equipe tratá-lo. Pausar ou editar a agenda preserva os registros já criados. A geração e a fila operacional consideram somente contratos que continuam implantados; sair desse status não apaga o histórico armazenado.
- Uma competência que já possui lembrete não pode ser escolhida novamente ao configurar o próximo vencimento. Escolha o próximo mês para preservar o histórico.

## Avisos e isolamento

Cada lembrete novo gera uma notificação de banco para usuários ativos dos perfis administrativo e backoffice pertencentes à empresa. O painel mostra a contagem de pendências e o acesso à fila. Ao tratar, as notificações daquele lembrete são marcadas como lidas para os destinatários.

Rotas, consultas, vínculos e operações usam a empresa ativa. A geração percorre agendas de várias empresas, mas executa cada uma dentro de seu contexto de tenant e seleciona destinatários da mesma empresa. O aviso de uma empresa não deve aparecer ao alternar para outra. As restrições de perfil e empresa também se aplicam no servidor.

## Operação técnica

Implementação local; este documento não comprova publicação em produção. A publicação exige aplicar a migration `2026_09_14_000001_create_boleto_lembretes_tables.php`, disponibilizar os assets compilados e manter o scheduler existente em funcionamento. A migration cria `boleto_agendas` e `boleto_lembretes`, incluindo a unicidade por agenda e competência e os campos de auditoria.

`routes/console.php` agenda `boletos:lembrar` a cada minuto, com timezone `America/Sao_Paulo`, `withoutOverlapping()` e `onOneServer()`. Use o ambiente Docker/wrapper do projeto para executar Artisan. O comando também aceita `boletos:lembrar --empresa=ID` para limitar a geração a uma empresa existente; essa execução cria lembretes e notificações reais no banco selecionado. Sem a opção, processa todas as empresas elegíveis e informa quantos lembretes criou.

Referências de implementação: `BoletoLembreteService` concentra elegibilidade, configuração, geração e tratamento; `BoletoLembreteController` valida os formulários e fornece a fila e o resumo do painel; `GerarLembretesBoletos` expõe o comando; `BoletoVencimentoNotification` usa exclusivamente o canal `database`.
