<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogPreditiva extends Model
{
    protected $table = 'log_preditiva';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'contato_id',
        'tabulacao',
        'acao',
        'tipo_descarte',
    ];

    public function contato()
    {
        return $this->belongsTo(Contatos::class, 'contato_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
