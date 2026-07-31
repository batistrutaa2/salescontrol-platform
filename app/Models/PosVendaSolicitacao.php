<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosVendaSolicitacao extends Model
{
    protected $table = 'pos_venda_solicitacoes';

    public const STATUS_ABERTA = 'ABERTA';

    public const STATUS_CONCLUIDA = 'CONCLUIDA';

    public const STATUS_CANCELADA = 'CANCELADA';

    public const ORIGEM_BACKOFFICE = 'BACKOFFICE';

    public const ORIGEM_VENDEDOR = 'VENDEDOR';

    protected $fillable = [
        'venda_id',
        'empresa_id',
        'tipo',
        'etapa_id',
        'titulo',
        'descricao',
        'status',
        'prioridade',
        'data_limite',
        'origem',
        'responsavel_id',
        'created_by',
        'concluida_em',
    ];

    protected $casts = [
        'prioridade' => 'boolean',
        'data_limite' => 'date',
        'concluida_em' => 'datetime',
    ];

    public function venda()
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }

    public function etapa()
    {
        return $this->belongsTo(PosVendaFluxoEtapa::class, 'etapa_id');
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
        return $this->hasMany(PosVendaSolicitacaoHistorico::class, 'solicitacao_id')->orderByDesc('id');
    }
}
