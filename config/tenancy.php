<?php

return [
    'bootstrap' => [
        'company_name' => env('PLATFORM_COMPANY_NAME', 'SalesControl Desenvolvimento'),
        'company_document' => env('PLATFORM_COMPANY_DOCUMENT', '00000000000000'),
        'company_phone' => env('PLATFORM_COMPANY_PHONE', '00000000000'),
        'company_email' => env('PLATFORM_COMPANY_EMAIL', 'platform@salescontrol.local'),
        'admin_name' => env('PLATFORM_ADMIN_NAME', 'Administrador da Plataforma'),
        'admin_email' => env('PLATFORM_ADMIN_EMAIL', 'admin@salescontrol.local'),
        'admin_password' => env('PLATFORM_ADMIN_PASSWORD'),
    ],

    // Credenciais operadas e faturadas pela própria plataforma. Elas podem ser
    // globais, mas todo dado persistido e todo recurso enviado ao provedor deve
    // continuar pertencendo à empresa ativa. Serviços trazidos pelo cliente
    // devem usar tenant_service_credentials em vez desta lista.
    'platform_managed_services' => [
        'anthropic',
        'assertiva',
        'evolution',
        'lemit',
        'postmark',
        'resend',
        'ses',
        'slack',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tabelas com tenant direto
    |--------------------------------------------------------------------------
    |
    | Todo acesso a estas tabelas deve incluir empresa_id. A lista também é um
    | contrato de arquitetura: o teste de schema impede que uma tabela com
    | tenant explícito seja criada sem classificação.
    |
    */
    'direct' => [
        'agendamentos',
        'assertiva_emails',
        'assertiva_empresas',
        'assertiva_enderecos',
        'assertiva_pessoas',
        'assertiva_telefones',
        'cancelamentos_liminares',
        'cancelamentos_liminares_documentos',
        'comentarios',
        'comercial_reunioes',
        'comissao_pagamentos',
        'comissionamento_configuracao',
        'contatos',
        'contatos_corretores',
        'credenciais_acesso',
        'credenciais_acesso_historico',
        'demandas',
        'dependentes',
        'escola_aula_materiais',
        'escola_aula_progresso',
        'escola_aulas',
        'escola_modulos',
        'estudos',
        'faqs',
        'lancamentos_debito_credito',
        'lead_atividades',
        'lead_reservatorio_estrategias',
        'lead_reservatorio_execucoes',
        'lead_reservatorio_itens',
        'ligacoes',
        'log_preditiva',
        'mailing_importacoes',
        'meta_configuracoes',
        'metas_diarias',
        'operadoras',
        'planos',
        'pos_venda_anotacoes',
        'pos_venda_demanda_templates',
        'pos_venda_fluxo_etapas',
        'pos_venda_solicitacoes',
        'preditiva',
        'preditiva_configuracoes',
        'preditiva_envios',
        'preditiva_regras_priorizacao',
        'preditiva_tabulacoes_hard',
        'ramais',
        'ranking_de_vendas',
        'recebiveis',
        'regras_comissionamento',
        'regras_comissionamento_parcelas',
        'renovacao_oportunidades',
        'tabulacoes',
        'tenant_integration_credentials',
        'tenant_service_credentials',
        'transferencia_contatos',
        'tv_comercial_access_tokens',
        'users',
        'venda_demandas',
        'venda_documentos',
        'venda_emails_criados',
        'vendas',
        'vendas_historico',
        'whatsapp_conversas',
        'whatsapp_instancias',
        'whatsapp_mensagens',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tabelas com tenant herdado
    |--------------------------------------------------------------------------
    |
    | Estas tabelas não devem ser consultadas por seu ID isolado. O tenant é
    | obrigatoriamente comprovado alcançando o pai indicado (transitivamente,
    | quando o próprio pai também estiver nesta lista).
    |
    */
    'inherited' => [
        'acesso_empresas' => ['parent' => 'vendas', 'foreign_key' => 'venda_id'],
        'cancelamentos_liminares_historico' => ['parent' => 'cancelamentos_liminares', 'foreign_key' => 'cancelamento_liminar_id'],
        'comissao_pagamento_itens' => ['parent' => 'comissao_pagamentos', 'foreign_key' => 'comissao_pagamento_id'],
        'contas_pagamento' => ['parent' => 'users', 'foreign_key' => 'user_id'],
        'estudo_itens' => ['parent' => 'estudos', 'foreign_key' => 'estudo_id'],
        'estudo_vidas' => ['parent' => 'estudo_itens', 'foreign_key' => 'estudo_item_id'],
        'lead_reservatorio_execucao_itens' => ['parent' => 'lead_reservatorio_execucoes', 'foreign_key' => 'execucao_id'],
        'mailing_importacao_itens' => ['parent' => 'mailing_importacoes', 'foreign_key' => 'mailing_importacao_id'],
        'pos_venda_solicitacao_historico' => ['parent' => 'pos_venda_solicitacoes', 'foreign_key' => 'solicitacao_id'],
        'renovacao_interacoes' => ['parent' => 'renovacao_oportunidades', 'foreign_key' => 'oportunidade_id'],
        'vendas_dependentes' => ['parent' => 'vendas_titulares', 'foreign_key' => 'titular_id'],
        'vendas_portabilidades' => ['parent' => 'vendas', 'foreign_key' => 'venda_id'],
        'vendas_titulares' => ['parent' => 'vendas', 'foreign_key' => 'venda_id'],
    ],

    // Dados de referência realmente compartilhados, sem payload de clientes.
    'shared_reference' => [
        'documento_diretorios',
    ],

    // Tabelas externas sem tenant não são aceitas no runtime da plataforma.
    'external_reference' => [],

    // Infraestrutura do framework ou registros cujo dono é resolvido por chave
    // própria (usuário, token, job), não por uma empresa selecionável.
    'infrastructure' => [
        'cache',
        'cache_locks',
        'empresas',
        'failed_jobs',
        'job_batches',
        'jobs',
        'migrations',
        'notifications',
        'password_reset_tokens',
        'personal_access_tokens',
        'sessions',
        'tenant_context_switches',
        'user_roles',
    ],

    // Mantida somente porque a migration histórica já existe. Não pode voltar
    // a ser registrada em rotas, controllers, models ou serviços da plataforma.
    'deprecated' => [
        'comentarios_legado',
    ],

    /*
    |--------------------------------------------------------------------------
    | Unicidades globais deliberadas em tabelas de tenant
    |--------------------------------------------------------------------------
    |
    | Qualquer índice UNIQUE de uma tabela direta que não contenha empresa_id
    | precisa estar explicitamente justificado aqui. Isso evita que uma nova
    | regra local a uma corretora seja acidentalmente criada como global.
    |
    */
    'global_unique_indexes' => [
        'escola_aula_progresso.uq_progresso_user_aula' => 'Os IDs de usuário e aula são globais e ambos carregam o tenant.',
        'estudos.estudos_link_unico_unique' => 'Token público opaco deve ser único em toda a plataforma.',
        'lead_reservatorio_execucoes.lead_reservatorio_execucoes_chave_idempotencia_unique' => 'Chave de idempotência é gerada globalmente.',
        'ranking_de_vendas.ranking_de_vendas__id_unique' => 'Identificador externo global do documento de ranking.',
        'recebiveis.unique_venda_parcela' => 'Venda possui ID global e determina o tenant do recebível.',
        'tenant_integration_credentials.tenant_integration_credentials_token_hash_unique' => 'Hash de credencial pública não pode colidir entre empresas.',
        'tv_comercial_access_tokens.tv_comercial_access_tokens_token_hash_unique' => 'Token público da TV não pode colidir entre empresas.',
        'users.users_email_unique' => 'E-mail é a identidade global usada no login.',
        'venda_documentos.venda_documentos_venda_id_client_upload_id_unique' => 'Venda possui ID global e o par garante idempotência do upload.',
        'whatsapp_conversas.whatsapp_conversas_instancia_id_remote_jid_unique' => 'A instância possui ID global e determina o tenant da conversa.',
        'whatsapp_instancias.whatsapp_instancias_instance_name_unique' => 'Nome identifica globalmente a instância no provedor.',
        'whatsapp_mensagens.whatsapp_mensagens_conversa_id_message_id_unique' => 'Conversa possui ID global e determina o tenant da mensagem.',
    ],

    // Exceções ao escopo Eloquent automático. Devem permanecer mínimas e ter
    // isolamento equivalente comprovado em testes próprios.
    'model_scope_exceptions' => [
        'users' => 'O usuário precisa ser localizado antes da resolução do tenant no login; gestão e relações são escopadas explicitamente.',
    ],
];
