<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoletoAgenda extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected $casts = ['ativo' => 'boolean', 'proximo_vencimento' => 'immutable_date'];

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }
}
