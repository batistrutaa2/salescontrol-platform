<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosVendaAnotacao extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $table = 'pos_venda_anotacoes';

    protected $fillable = [
        'empresa_id',
        'venda_id',
        'user_id',
        'descricao',
    ];

    /**
     * Relacionamento com a venda
     */
    public function venda(): BelongsTo
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }

    /**
     * Relacionamento com o usuário que criou a anotação
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relacionamento com a empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
