<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CancelamentoPosVenda extends Model
{
    protected $table = 'cancelamentos_pos_venda';

    protected $fillable = [
        'venda_id',
        'empresa_id',
        'escopo',
        'beneficiario_nome',
        'via',
        'operadora_anterior',
        'etapa',
        'protocolo',
        'observacoes',
        'responsavel_id',
        'created_by',
        'solicitado_em',
        'concluido_em',
    ];

    protected $casts = [
        'solicitado_em' => 'date',
        'concluido_em' => 'datetime',
    ];

    public function venda()
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function criador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function historico()
    {
        return $this->hasMany(CancelamentoPosVendaHistorico::class, 'cancelamento_pos_venda_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
