<?php

namespace App\Modules\LkBeneficios\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalhesPrevidencia extends Model
{
    use HasFactory;

    protected $table = 'lk_beneficios_contratos_detalhes_previdencia';

    protected $fillable = [
        'contrato_id',
        'modalidade',
        'tabela_imposto',
        'aporte_mensal',
        'aporte_inicial',
        'rentabilidade_acumulada',
        'data_ultimo_aporte',
    ];

    protected $casts = [
        'aporte_mensal' => 'decimal:2',
        'aporte_inicial' => 'decimal:2',
        'rentabilidade_acumulada' => 'decimal:4',
        'data_ultimo_aporte' => 'date',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}
