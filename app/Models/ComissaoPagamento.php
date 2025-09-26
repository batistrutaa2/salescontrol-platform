<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComissaoPagamento extends Model
{
    use HasFactory;
    protected $table = "comissao_pagamentos";
    protected $fillable = [
        'id',
        'pago_em',
        'conta_pagamento_id',
        'empresa_id',
        'vendedor_id',
        'mes',
        'data_pagamento',
        'percentual_comissao',
        'percentual_imposto',
        'total_bruto',
        'total_imposto',
        'total_liquido',
        'salario',
        'total_receber',
        'created_by',
        'created_at',
        'updated_at'
    ];
}
