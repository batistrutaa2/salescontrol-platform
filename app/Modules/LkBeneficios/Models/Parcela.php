<?php

namespace App\Modules\LkBeneficios\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parcela extends Model
{
    use HasFactory;

    protected $table = 'lk_beneficios_parcelas';

    protected $fillable = [
        'contrato_id',
        'numero_parcela',
        'competencia',
        'valor',
        'data_vencimento',
        'data_pagamento',
        'status',
        'path_boleto',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_vencimento' => 'date',
        'data_pagamento' => 'date',
        'numero_parcela' => 'integer',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}
