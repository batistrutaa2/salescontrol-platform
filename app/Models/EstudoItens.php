<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstudoItens extends Model
{
    use HasFactory;

    protected $table = "estudo_itens";

    protected $fillable = [
        'id',
        'estudo_id',
        'operadora_plano',
        'coparticipacao',
        'reembolso_consulta',
        'created_at',
        'updated_at'
    ];
}
