<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegrasComissionamento extends Model
{
    use HasFactory;

    protected $table = 'regras_comissionamento';

    protected $fillable = [
        'id',
        'empresa_id',
        'operadora_id',
        'categoria',
        'total_percentual',
        'descricao',
        'vitalicio',
        'percentual_vitalicio',
        'created_at',
        'updated_at',
    ];

    // 🔗 Relacionamentos
    public function operadoras()
    {
        return $this->belongsTo(Operadora::class, 'operadora_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function parcelas()
    {
        return $this->hasMany(RegrasComissionamentoParcela::class, 'regra_id');
    }
}
