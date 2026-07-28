<?php

namespace App\Repositories\Contracts;

use App\Models\CancelamentoPosVenda;

/**
 * Painel de Cancelamentos do pós-venda (cancelamento do plano anterior na
 * operadora antiga). Um único payload alimenta kanban, tabela e KPIs.
 */
interface CancelamentoPosVendaRepositoryInterface
{
    /**
     * Todos os registros da empresa, mapeados para o front (kanban + tabela).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $empresaId): array;

    /**
     * Contagem por etapa (todas, incluindo DESISTIDO), para os KPIs.
     *
     * @return array<string, int> etapa => quantidade
     */
    public function kpis(int $empresaId): array;

    /**
     * Cria vinculado à venda (valida a posse pela empresa). Nasce em
     * PRONTO_PARA_SOLICITAR se a venda já implantou, senão AGUARDANDO_IMPLANTACAO.
     * Grava histórico de criação.
     *
     * @param  array<string, mixed>  $dados  venda_id, escopo, beneficiario_nome, via,
     *                                       operadora_anterior, responsavel_id, observacoes
     */
    public function criar(int $empresaId, array $dados, int $userId): ?CancelamentoPosVenda;

    /**
     * Muda a etapa + histórico. SOLICITADO seta solicitado_em (e protocolo, se
     * vier); CONCLUIDO seta concluido_em. False se não achar / outra empresa.
     */
    public function moverEtapa(int $id, int $empresaId, string $etapa, int $userId, ?string $protocolo = null, ?string $observacao = null): bool;

    /** Atribui/limpa responsável + histórico. */
    public function atribuirResponsavel(int $id, int $empresaId, ?int $responsavelId, int $userId): bool;

    /** Etapa DESISTIDO + histórico com o motivo. */
    public function desistir(int $id, int $empresaId, int $userId, ?string $motivo): bool;

    /**
     * Atualiza protocolo/observações (modal de detalhe) + histórico por campo.
     *
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(int $id, int $empresaId, array $dados, int $userId): bool;

    /**
     * Detalhe completo + timeline para o modal. Null se não achar / outra empresa.
     *
     * @return array<string, mixed>|null
     */
    public function detalhe(int $id, int $empresaId): ?array;

    /**
     * Autocomplete de contratos por nome/proposta/CPF-CNPJ, com data de
     * implantação e flag implantado (decide a etapa inicial no modal).
     *
     * @return array<int, array<string, mixed>>
     */
    public function buscarContratos(string $termo, int $empresaId, int $limite = 20): array;
}
