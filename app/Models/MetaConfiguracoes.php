<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaConfiguracoes extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use HasFactory;

    protected $table = 'meta_configuracoes';

    protected $fillable = [
        'id',
        'empresa_id',
        'nome',
        'tipo_calculo',
        'periodo',
        'data_inicio',
        'data_fim',
        'meta_individual',
        'ativa',
        'valor_meta',
        'quantidade_meta',
        'descricao',
        'created_at',
        'updated_at',
    ];
}
