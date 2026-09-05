<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dependentes extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use HasFactory;

    protected $table = 'dependentes';

    protected $fillable = [
        'id',
        'empresa_id',
        'contato_id',
        'nome',
        'cpf',
        'idade',
        'parentesco',
        'telefone_1',
        'telefone_2',
        'telefone_3',
        'valor_plano',
        'created_at',
        'updated_at',
    ];
}
