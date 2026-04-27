<?php

namespace App\Modules\LkBeneficios\Models;

use App\Models\People\Empresa as EmpresaLemit;
use App\Models\People\Pessoa;
use App\Models\User;
use App\Models\Vendas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contrato extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lk_beneficios_contratos';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'produto_id',
        'venda_origem_id',
        'lead_id',
        'pessoa_id',
        'empresa_lemit_id',
        'numero_apolice',
        'numero_proposta',
        'cliente_tipo',
        'cpf_cnpj',
        'nome_cliente',
        'status',
        'data_proposta',
        'data_vigencia_inicio',
        'data_vigencia_fim',
        'data_aniversario_reajuste',
        'valor_mensal',
        'valor_anual',
        'forma_pagamento',
        'dia_vencimento',
        'vidas_total',
        'vidas_titulares',
        'vidas_dependentes',
        'comissao_percentual_primeira',
        'comissao_percentual_recorrente',
        'observacoes',
    ];

    protected $casts = [
        'data_proposta' => 'date',
        'data_vigencia_inicio' => 'date',
        'data_vigencia_fim' => 'date',
        'data_aniversario_reajuste' => 'date',
        'valor_mensal' => 'decimal:2',
        'valor_anual' => 'decimal:2',
        'comissao_percentual_primeira' => 'decimal:2',
        'comissao_percentual_recorrente' => 'decimal:2',
        'vidas_total' => 'integer',
        'vidas_titulares' => 'integer',
        'vidas_dependentes' => 'integer',
        'dia_vencimento' => 'integer',
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vendaOrigem()
    {
        return $this->belongsTo(Vendas::class, 'venda_origem_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'pessoa_id');
    }

    public function empresaLemit()
    {
        return $this->belongsTo(EmpresaLemit::class, 'empresa_lemit_id');
    }

    public function beneficiarios()
    {
        return $this->hasMany(Beneficiario::class, 'contrato_id');
    }

    public function apolices()
    {
        return $this->hasMany(Apolice::class, 'contrato_id');
    }

    public function parcelas()
    {
        return $this->hasMany(Parcela::class, 'contrato_id');
    }

    public function movimentacoes()
    {
        return $this->hasMany(Movimentacao::class, 'contrato_id');
    }

    public function detalhesVida()
    {
        return $this->hasOne(DetalhesVida::class, 'contrato_id');
    }

    public function detalhesPrevidencia()
    {
        return $this->hasOne(DetalhesPrevidencia::class, 'contrato_id');
    }

    public function detalhesPatrimonial()
    {
        return $this->hasOne(DetalhesPatrimonial::class, 'contrato_id');
    }
}
