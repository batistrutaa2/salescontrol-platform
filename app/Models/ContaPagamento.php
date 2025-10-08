<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContaPagamento extends Model
{
        use HasFactory;
    protected $table = "contas_pagamento";
    protected $fillable = [
        'id',
        'user_id',
        'banco',
        'agencia',
        'conta',
        'digito',
        'chave_pix',
        'descricao',
        'is_default',
        'created_at',
        'updated_at',
    ];
}
