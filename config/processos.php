<?php

/**
 * Prazos (SLA) por tipo de processo operacional, em dias corridos. O relógio do
 * prazo começa na IMPLANTAÇÃO do contrato atual (vendas.data_implantacao), pois a
 * operação só age depois disso (cancelamento na operadora anterior e portabilidade
 * dependem da apólice implantada). Antes da implantação o processo fica "aguardando
 * implantação" e NÃO conta atraso. O vencimento é calculado on-the-fly.
 *
 * Ajuste os números conforme a realidade da operação — sem migração.
 */
return [
    /*
     * Antecedência (em dias) para marcar um processo como "vencendo": dentro do
     * prazo, mas faltando <= alerta_dias para o vencimento. Vira o balde amarelo.
     */
    'alerta_dias' => 7,

    /*
     * Corte do painel: processos em aberto criados ANTES desta data não aparecem
     * na fila (já foram tratados fora do sistema; sem o corte seria preciso dar
     * baixa manual em todos). Ajuste ou defina como null para desligar o corte.
     */
    'corte_abertos' => '2026-05-01',

    /*
     * Grupos de processo exibidos no Painel de Processos (visão do gestor).
     * O foco da operação é cancelamento e portabilidade; os demais tipos
     * (boas-vindas, boleto, acessos...) seguem no checklist do contrato,
     * fora do painel. Grupos possíveis: ver TipoDemandaContrato::grupos().
     */
    'grupos_painel' => ['cancelamentos', 'portabilidade'],

    'sla_dias' => [
        'CANCELAMENTO_OPERADORA_ANTERIOR' => 30,
        'CANCELAMENTO' => 30,
        'CANCELAMENTO_QUALICORP' => 30,
        'CANCELAMENTO_LIMITAR' => 30,
        'CARTA_PERMANENCIA' => 20,
        'PORTABILIDADE' => 60,
        'DS' => 15,
        'EMAIL_IMPLANTACAO' => 5,
        'ACESSO_EMPRESA' => 7,
        'ACESSO_BENEFICIARIO' => 7,
        'LOGIN_APPS' => 7,
        'TROCA_EMAIL' => 7,
        'ENVIO_BOLETO' => 5,
        'BOAS_VINDAS' => 5,
        'default' => 15,
    ],
];
