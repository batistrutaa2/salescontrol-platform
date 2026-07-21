<?php

/**
 * Prazos (SLA) por tipo de processo operacional, em dias corridos a partir da
 * abertura. O vencimento é calculado on-the-fly (abertura + SLA); um processo
 * PENDENTE cujo vencimento já passou aparece como "atrasado" no painel.
 *
 * Ajuste os números conforme a realidade da operação — sem migração.
 */
return [
    /*
     * Corte do painel: processos em aberto criados ANTES desta data não aparecem
     * na fila (já foram tratados fora do sistema; sem o corte seria preciso dar
     * baixa manual em todos). Ajuste ou defina como null para desligar o corte.
     */
    'corte_abertos' => '2026-05-01',

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
