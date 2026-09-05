<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadReservatorioEstrategia extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $table = 'lead_reservatorio_estrategias';

    protected $fillable = ['empresa_id', 'nome', 'condicoes', 'ativo', 'created_by', 'updated_by'];

    protected $casts = [
        'condicoes' => 'array',
        'ativo' => 'boolean',
    ];
}
