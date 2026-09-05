<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tabulacoes extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'tabulacoes';

    protected $fillable = [
        'id',
        'empresa_id',
        'codigo',
        'descricao',
        'tipo_tabulacao',
        'efetivo',
        'ordem_kanban',
        'status',
        'sub_tabulacao',
        'prazo',
        'created_at',
        'updated_at',
    ];
}
