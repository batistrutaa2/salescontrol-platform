<?php

namespace App\Modules\LkBeneficios\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalhesVida extends Model
{
    use HasFactory;

    protected $table = 'lk_beneficios_contratos_detalhes_vida';

    protected $fillable = [
        'contrato_id',
        'capital_segurado',
        'cobertura_morte',
        'cobertura_invalidez',
        'cobertura_doencas_graves',
        'possui_assistencia_funeral',
        'beneficiarios_designados',
    ];

    protected $casts = [
        'capital_segurado' => 'decimal:2',
        'cobertura_morte' => 'decimal:2',
        'cobertura_invalidez' => 'decimal:2',
        'cobertura_doencas_graves' => 'decimal:2',
        'possui_assistencia_funeral' => 'boolean',
        'beneficiarios_designados' => 'array',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}
