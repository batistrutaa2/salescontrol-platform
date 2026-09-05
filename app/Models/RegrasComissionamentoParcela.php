<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegrasComissionamentoParcela extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use HasFactory;

    protected $table = 'regras_comissionamento_parcelas';

    protected $fillable = [
        'empresa_id',
        'regra_id',
        'parcela',
        'percentual',
        'pagador',
    ];

    // 🔗 Relacionamentos
    public function regra()
    {
        return $this->belongsTo(RegrasComissionamento::class, 'regra_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
