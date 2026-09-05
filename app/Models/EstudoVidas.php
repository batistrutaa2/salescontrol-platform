<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstudoVidas extends Model
{
    use \App\Models\Concerns\BelongsToTenantThrough;
    use HasFactory;

    protected $table = 'estudo_vidas';

    protected $fillable = [
        'id',
        'estudo_item_id',
        'faixa',
        'qtde',
        'valor_unitario',
        'total',
        'created_at',
        'updated_at',
    ];

    public function item()
    {
        return $this->belongsTo(EstudoItens::class, 'estudo_item_id');
    }
}
