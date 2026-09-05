<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosVendaSolicitacaoHistorico extends Model
{
    use \App\Models\Concerns\BelongsToTenantThrough;

    protected $table = 'pos_venda_solicitacao_historico';

    protected $fillable = [
        'solicitacao_id',
        'user_id',
        'campo_alterado',
        'valor_anterior',
        'valor_novo',
        'observacao',
    ];

    public function solicitacao()
    {
        return $this->belongsTo(PosVendaSolicitacao::class, 'solicitacao_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
