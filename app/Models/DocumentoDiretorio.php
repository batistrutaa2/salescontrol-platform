<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoDiretorio extends Model
{
    protected $fillable = ['caminho', 'nome', 'encontrado_em'];

    protected $casts = ['encontrado_em' => 'datetime'];
}
