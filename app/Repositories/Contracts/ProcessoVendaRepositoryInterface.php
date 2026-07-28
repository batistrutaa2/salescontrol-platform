<?php

namespace App\Repositories\Contracts;

use App\Models\VendaDemanda;
use Illuminate\Support\Collection;

/**
 * Ponto único de acesso a venda_demandas para a tela de acompanhamento de
 * processos operacionais de uma venda (cancelamentos, DS, e-mails, acessos...).
 * Toda query é escopada por empresa_id (multi-tenancy).
 */
interface ProcessoVendaRepositoryInterface
{
    /** Processos (venda_demandas) de uma venda, com titular/operadora/autores carregados. */
    public function daVenda(int $vendaId, int $empresaId): Collection;

    /** Contadores agregados: total, pendentes, concluidas, progresso (%). */
    public function resumo(int $vendaId, int $empresaId): array;

    /**
     * Atualiza modalidade/fase/protocolo de um processo de cancelamento (guardados em meta).
     * Quando a fase é a final (CONCLUIDO), a demanda vira CONCLUIDA. Null se não achar/for de outra empresa.
     */
    public function atualizarCancelamento(int $id, int $empresaId, array $dados, ?int $userId): ?VendaDemanda;

    /** Avança a fase da portabilidade (REUNINDO_DOCUMENTOS → ENVIADA_ANALISE → CONCLUIDA|NEGADA); fase final fecha o processo. */
    public function atualizarFasePortabilidade(int $id, int $empresaId, string $fase, int $userId): bool;

    // ---- E-mails criados para o cliente ----

    /** E-mails criados pelo backoffice para o cliente deste contrato. */
    public function emailsCriadosDaVenda(int $vendaId, int $empresaId): array;

    public function criarEmailCriado(int $vendaId, int $empresaId, array $dados, int $userId): array;

    /** Null se não achar / for de outra empresa. */
    public function atualizarEmailCriado(int $id, int $empresaId, array $dados): ?array;

    public function excluirEmailCriado(int $id, int $empresaId): bool;

    /** Busca contratos da empresa por nome, CNPJ/CPF ou nº de proposta (para o "abrir contrato" global). */
    public function buscarContratos(string $termo, int $empresaId, int $limite = 20): array;

    // ---- Contexto do cliente (mesmo CNPJ) ----

    /** Outros contratos da empresa com o mesmo CNPJ (histórico do cliente / renovações). */
    public function contratosDoCnpj(string $cnpj, int $empresaId, int $exceptVendaId): array;

    /** Acessos/senhas que casam com o CNPJ do contrato (por venda_id, coluna cnpj ou dígitos do login). */
    public function acessosPorCnpj(string $cnpj, int $empresaId, int $vendaId): array;
}
