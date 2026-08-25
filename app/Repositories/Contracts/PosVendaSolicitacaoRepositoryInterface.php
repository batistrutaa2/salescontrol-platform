<?php

namespace App\Repositories\Contracts;

use App\Models\PosVendaFluxoEtapa;
use App\Models\PosVendaSolicitacao;

interface PosVendaSolicitacaoRepositoryInterface
{
    /** @param array{tipo?: string, responsavel_id?: int, etapa_id?: int, status?: string, busca?: string} $filtros */
    public function listar(int $empresaId, array $filtros = []): array;

    public function kpis(int $empresaId): array;

    public function criar(int $empresaId, array $dados, int $userId): ?PosVendaSolicitacao;

    public function detalhe(int $id, int $empresaId): ?array;

    public function atualizar(int $id, int $empresaId, array $dados, int $userId): bool;

    /** @return bool|null novo valor da prioridade; null se não encontrada na empresa */
    public function alternarPrioridade(int $id, int $empresaId, int $userId): ?bool;

    /** Agenda o retorno à fila; null antecipa o retorno para agora. */
    public function programarRetorno(int $id, int $empresaId, ?string $dataRetorno, int $userId): bool;

    /**
     * Registra uma atualização de andamento na timeline da solicitação.
     *
     * @return PosVendaSolicitacao|null a solicitação (com venda carregada); null se não encontrada na empresa
     */
    public function registrarAtualizacao(int $id, int $empresaId, int $userId, string $texto): ?PosVendaSolicitacao;

    /**
     * @return string|null null em caso de sucesso; código do impedimento nos demais casos
     */
    public function excluir(int $id, int $empresaId): ?string;

    /** @return string|null null = sucesso; 'NAO_ENCONTRADA' | 'ETAPA_INVALIDA' em erro */
    public function moverEtapa(int $id, int $empresaId, int $etapaId, int $userId, ?string $observacao = null): ?string;

    public function buscarContratos(string $termo, int $empresaId, int $limite = 20): array;

    /** Etapas da empresa agrupadas por tipo, ordenadas. */
    public function etapas(int $empresaId): array;

    /** Primeira etapa EM_ANDAMENTO do tipo; semeia os defaults da empresa se preciso. */
    public function etapaInicial(int $empresaId, string $tipo): PosVendaFluxoEtapa;

    public function criarEtapa(int $empresaId, array $dados): PosVendaFluxoEtapa;

    public function atualizarEtapa(int $id, int $empresaId, array $dados): bool;

    /** @return string|null null = sucesso; 'NAO_ENCONTRADA' | 'COM_SOLICITACOES' | 'ULTIMA_CONCLUIDA' */
    public function excluirEtapa(int $id, int $empresaId): ?string;

    /** @param array<int, int> $ordemIds ids das etapas do tipo, na nova ordem */
    public function reordenarEtapas(int $empresaId, string $tipo, array $ordemIds): bool;
}
