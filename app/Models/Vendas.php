<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Vendas extends Model
{
  use HasFactory;

  protected $table = "vendas";

  protected $fillable = [
    'id',
    'numero_proposta',
    'empresa_id',
    'user_id',
    'contato_id',
    'nome_contrato',
    'cpf_cnpj',
    'email',
    'data_vigencia',
    'data_implantacao',
    'telefone1',
    'telefone2',
    'operadora',
    'nome_plano',
    'valor_contrato',
    'vidas',
    'obs_contrato',
    'coparticipacao',
    'plano_id',
    'path_boleto_disponivel',
    'motivo_pendencia',
    'comissao_paga',
    'data_pagamento_comissao',
    'comissao_estornada',
    'data_estorno_comissao',
    'created_at',
    'updated_at',
    'angariacao_valor',
    'angariacao_status',
    'boas_vindas_enviado_em',
    'boas_vindas_enviado_por'
  ];

  protected $casts = [
    'data_vigencia' => 'date',
    'valor_contrato' => 'decimal:2',
    'vidas' => 'integer',
    'data_implantacao' => 'date',
    'boas_vindas_enviado_em' => 'datetime',
  ];

  public function titulares()
  {
    return $this->hasMany(\App\Models\VendaTitular::class, 'venda_id');
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function contatoCorretor()
  {
    return $this->hasOne(ContatosCorretores::class, 'contato_id', 'contato_id');
  }

  public function acessosEmpresa()
  {
    return $this->hasMany(AcessoEmpresa::class, 'venda_id');
  }

  public function usuarioBoasVindas()
  {
    return $this->belongsTo(User::class, 'boas_vindas_enviado_por');
  }


  public function getCreatedAtAttribute($value)
  {
    return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
  }

  public function getUpdatedAtAttribute($value)
  {
    return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
  }

}
