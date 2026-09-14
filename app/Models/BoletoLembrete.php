<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoletoLembrete extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected $casts = ['competencia' => 'immutable_date', 'vencimento' => 'immutable_date', 'tratado_em' => 'immutable_datetime'];

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }

    public function tratadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tratado_por');
    }
}
