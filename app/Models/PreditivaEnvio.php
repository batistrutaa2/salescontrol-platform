<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreditivaEnvio extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $table = 'preditiva_envios';

    protected $fillable = [
        'empresa_id',
        'contato_id',
        'enviado_em',
        'origem',
        'enviado_por',
        'situacao_origem',
        'dias_inativo',
    ];

    protected $casts = [
        'enviado_em' => 'datetime',
        'dias_inativo' => 'integer',
    ];

    public function contato()
    {
        return $this->belongsTo(Contatos::class, 'contato_id');
    }

    public function enviadoPor()
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }
}
