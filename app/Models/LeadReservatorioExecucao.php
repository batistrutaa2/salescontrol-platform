<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadReservatorioExecucao extends Model
{
    protected $table = 'lead_reservatorio_execucoes';

    protected $fillable = [
        'empresa_id', 'estrategia_id', 'tipo', 'status', 'filtros_snapshot',
        'distribuicoes', 'semente', 'total_solicitado', 'total_executado',
        'total_ignorado', 'vendedor_origem_id', 'created_by',
        'chave_idempotencia', 'executada_em',
    ];

    protected $casts = [
        'filtros_snapshot' => 'array',
        'distribuicoes' => 'array',
        'executada_em' => 'datetime',
    ];
}
