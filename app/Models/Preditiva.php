<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preditiva extends Model
{
    protected $table = 'preditiva';

    protected $fillable = [
        'empresa_id',
        'contato_id',
        'user_id',
        'data_atribuicao',
        'status'
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
