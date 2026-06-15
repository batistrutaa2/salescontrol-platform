<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EscolaAulaProgresso extends Model
{
    protected $table = 'escola_aula_progresso';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'escola_aula_id',
        'ultima_posicao_segundos',
        'percentual',
        'concluida',
        'concluida_em',
    ];

    protected $casts = [
        'concluida' => 'boolean',
        'concluida_em' => 'datetime',
        'percentual' => 'integer',
        'ultima_posicao_segundos' => 'integer',
    ];

    public function aula()
    {
        return $this->belongsTo(EscolaAula::class, 'escola_aula_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
