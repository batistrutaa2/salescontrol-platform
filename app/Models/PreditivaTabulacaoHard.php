<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreditivaTabulacaoHard extends Model
{
    protected $table = 'preditiva_tabulacoes_hard';

    protected $fillable = [
        'empresa_id',
        'tabulacao',
    ];

    /**
     * Verifica se uma tabulação é HARD para a empresa.
     * Normaliza para UPPERCASE e remove espaços extras antes de comparar.
     */
    public static function isHard(int $empresaId, string $tabulacao): bool
    {
        return static::where('empresa_id', $empresaId)
            ->where('tabulacao', strtoupper(trim($tabulacao)))
            ->exists();
    }

    /**
     * Retorna array com todas as tabulações HARD da empresa (já normalizadas).
     */
    public static function listForEmpresa(int $empresaId): array
    {
        return static::where('empresa_id', $empresaId)
            ->orderBy('tabulacao')
            ->pluck('tabulacao')
            ->toArray();
    }
}
