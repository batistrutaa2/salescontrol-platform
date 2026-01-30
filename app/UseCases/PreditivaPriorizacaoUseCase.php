<?php

namespace App\UseCases;

use App\Models\PreditivaRegraPriorizacao;
use App\Repositories\Contracts\PreditivaRegraRepositoryInterface;
use Illuminate\Support\Collection;

class PreditivaPriorizacaoUseCase
{
    protected PreditivaRegraRepositoryInterface $regraRepository;

    public function __construct(PreditivaRegraRepositoryInterface $regraRepository)
    {
        $this->regraRepository = $regraRepository;
    }

    /**
     * Constrói a expressão SQL para calcular o score de priorização
     * baseado nas regras ativas da empresa
     *
     * @param int $empresaId
     * @return string Expressão SQL que retorna o score (soma dos pesos das regras satisfeitas)
     */
    public function buildScoreSQL(int $empresaId): string
    {
        $regras = $this->regraRepository->getRegrasByEmpresa($empresaId, true);

        if ($regras->isEmpty()) {
            return '0';
        }

        $cases = [];

        foreach ($regras as $regra) {
            $condition = $this->buildCondition($regra);
            if ($condition) {
                $peso = (int) $regra->peso;
                $cases[] = "WHEN {$condition} THEN {$peso}";
            }
        }

        if (empty($cases)) {
            return '0';
        }

        $caseStatements = implode(' ', array_map(fn($case) => "(CASE {$case} ELSE 0 END)", $cases));

        return "({$caseStatements})";
    }

    /**
     * Constrói a condição SQL para uma regra específica
     *
     * @param PreditivaRegraPriorizacao $regra
     * @return string|null Condição SQL ou null se inválida
     */
    protected function buildCondition(PreditivaRegraPriorizacao $regra): ?string
    {
        $campo = $regra->campo;
        $operador = $regra->operador;
        $valor = $regra->valor;

        if (!array_key_exists($campo, PreditivaRegraPriorizacao::CAMPOS_PERMITIDOS)) {
            return null;
        }

        $campoConfig = PreditivaRegraPriorizacao::CAMPOS_PERMITIDOS[$campo];

        if (!in_array($operador, $campoConfig['operadores'])) {
            return null;
        }

        $campoSQL = "c.{$campo}";

        switch ($operador) {
            case '=':
            case '!=':
                if ($campoConfig['tipo'] === 'decimal' || $campoConfig['tipo'] === 'integer') {
                    return "{$campoSQL} {$operador} " . (float) $valor;
                }
                $valorEscapado = $this->escapeString($valor);
                return "{$campoSQL} {$operador} '{$valorEscapado}'";

            case '>':
            case '>=':
            case '<':
            case '<=':
                return "{$campoSQL} {$operador} " . (float) $valor;

            case 'LIKE':
                $valorEscapado = $this->escapeString($valor);
                return "{$campoSQL} LIKE '%{$valorEscapado}%'";

            case 'IN':
                $valores = json_decode($valor, true);
                if (!is_array($valores) || empty($valores)) {
                    $valores = array_map('trim', explode(',', $valor));
                }
                $valoresEscapados = array_map(fn($v) => "'" . $this->escapeString($v) . "'", $valores);
                return "{$campoSQL} IN (" . implode(', ', $valoresEscapados) . ")";

            default:
                return null;
        }
    }

    /**
     * Escapa string para uso em SQL
     *
     * @param string $value
     * @return string
     */
    protected function escapeString(string $value): string
    {
        return addslashes($value);
    }

    /**
     * Retorna o score calculado como soma dos pesos
     *
     * @param int $empresaId
     * @return string Expressão SQL completa com alias
     */
    public function getScoreExpression(int $empresaId): string
    {
        $scoreSQL = $this->buildScoreSQL($empresaId);

        if ($scoreSQL === '0') {
            return '0 as score_priorizacao';
        }

        $regras = $this->regraRepository->getRegrasByEmpresa($empresaId, true);
        $sumParts = [];

        foreach ($regras as $regra) {
            $condition = $this->buildCondition($regra);
            if ($condition) {
                $peso = (int) $regra->peso;
                $sumParts[] = "(CASE WHEN {$condition} THEN {$peso} ELSE 0 END)";
            }
        }

        if (empty($sumParts)) {
            return '0 as score_priorizacao';
        }

        return '(' . implode(' + ', $sumParts) . ') as score_priorizacao';
    }
}
