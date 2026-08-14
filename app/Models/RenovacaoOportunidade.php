<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RenovacaoOportunidade extends Model
{
    protected $table = 'renovacao_oportunidades';
    protected $guarded = [];
    protected $casts = [
        'data_base' => 'date', 'elegivel_desde' => 'date', 'recontato_em' => 'date',
        'contatada_em' => 'datetime', 'respondida_em' => 'datetime',
        'cotacao_solicitada_em' => 'datetime', 'convertida_em' => 'datetime', 'encerrada_em' => 'datetime',
    ];

    public function vendaReferencia() { return $this->belongsTo(Vendas::class, 'venda_referencia_id'); }
    public function vendedorOriginal() { return $this->belongsTo(User::class, 'vendedor_original_id'); }
    public function responsavel() { return $this->belongsTo(User::class, 'responsavel_id'); }
    public function interacoes() { return $this->hasMany(RenovacaoInteracao::class, 'oportunidade_id')->latest(); }
}
