<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoletoAgenda extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = ['proxima_notificacao' => 'immutable_date', 'ativo' => 'boolean', 'proximo_vencimento' => 'immutable_date'];

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }
}
