<?php

namespace App\Modules\LkBeneficios\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalhesPatrimonial extends Model
{
    use HasFactory;

    protected $table = 'lk_beneficios_contratos_detalhes_patrimonial';

    protected $fillable = [
        'contrato_id',
        'tipo_bem',
        'descricao_bem',
        'valor_segurado',
        'franquia',
        'identificador_bem',
        'coberturas',
    ];

    protected $casts = [
        'valor_segurado' => 'decimal:2',
        'franquia' => 'decimal:2',
        'coberturas' => 'array',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}
