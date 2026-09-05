<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreditivaConfiguracao extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $table = 'preditiva_configuracoes';

    protected $fillable = [
        'empresa_id',
        'cooldown_horas',
        'exclusao_usuario_dias',
        'limite_descartes_soft',
        'limite_descartes_hard',
        'dias_sem_contato_reenvio',
        'envio_automatico_ativo',
        'limite_envio_diario',
        'mascote_dias_sem_atividade',
        'mascote_limite_sugestoes',
        'lock_expiracao_horas',
        'indicadores_janela_dias',
        'kanban_inatividade_alerta_dias',
        'kanban_inatividade_urgente_dias',
        'kanban_inatividade_critica_dias',
    ];

    protected $casts = [
        'cooldown_horas' => 'integer',
        'exclusao_usuario_dias' => 'integer',
        'limite_descartes_soft' => 'integer',
        'limite_descartes_hard' => 'integer',
        'dias_sem_contato_reenvio' => 'integer',
        'envio_automatico_ativo' => 'boolean',
        'limite_envio_diario' => 'integer',
        'mascote_dias_sem_atividade' => 'integer',
        'mascote_limite_sugestoes' => 'integer',
        'lock_expiracao_horas' => 'integer',
        'indicadores_janela_dias' => 'integer',
        'kanban_inatividade_alerta_dias' => 'integer',
        'kanban_inatividade_urgente_dias' => 'integer',
        'kanban_inatividade_critica_dias' => 'integer',
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
                'cooldown_horas' => 0,
                'exclusao_usuario_dias' => null,
                'limite_descartes_soft' => 5,
                'limite_descartes_hard' => 1,
                'dias_sem_contato_reenvio' => 90,
                'envio_automatico_ativo' => false,
                'limite_envio_diario' => 500,
                'mascote_dias_sem_atividade' => 5,
                'mascote_limite_sugestoes' => 10,
                'lock_expiracao_horas' => 2,
                'indicadores_janela_dias' => 30,
                'kanban_inatividade_alerta_dias' => 7,
                'kanban_inatividade_urgente_dias' => 14,
                'kanban_inatividade_critica_dias' => 20,
            ]
        );
    }
}
