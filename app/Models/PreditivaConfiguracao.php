<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreditivaConfiguracao extends Model
{
    protected $table = 'preditiva_configuracoes';

    protected $fillable = [
        'empresa_id',
        'cooldown_horas',
        'exclusao_usuario_dias',
        'limite_descartes_soft',
        'limite_descartes_hard',
    ];

    protected $casts = [
        'cooldown_horas'        => 'integer',
        'exclusao_usuario_dias' => 'integer',
        'limite_descartes_soft' => 'integer',
        'limite_descartes_hard' => 'integer',
    ];

    /**
     * Retorna a configuração da empresa ou um objeto com os valores padrão,
     * sem persistir — evita null checks espalhados pelo código.
     */
    public static function getOrDefault(int $empresaId): self
    {
        return static::firstOrNew(
            ['empresa_id' => $empresaId],
            [
                'cooldown_horas'        => 0,
                'exclusao_usuario_dias' => null,
                'limite_descartes_soft' => 5,
                'limite_descartes_hard' => 1,
            ]
        );
    }
}
