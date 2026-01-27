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
    'backoffice_id',
    'contato_id',
    'nome_contrato',
    'cpf_cnpj',
    'email',
    'data_vigencia',
    'data_implantacao',
    'telefone1',
    'telefone2',
    'operadora',
    'operadora_id',
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
    'angariacao_paga',
    'data_pagamento_angariacao',
    'comissao_estornada',
    'data_estorno_comissao',
    'created_at',
    'updated_at',
    'angariacao_valor',
    'angariacao_status',
    'portabilidade_status',
    'qtd_portabilidade',
    'plano_dental',
    'layout_venda',
    'tipo_contrato',
    'tipo_empresa',
    'data_abertura',
    'boas_vindas_enviado_em',
    'boas_vindas_enviado_por'
  ];

  protected $casts = [
    'data_vigencia' => 'date',
    'valor_contrato' => 'decimal:2',
    'vidas' => 'integer',
    'data_implantacao' => 'date',
    'data_abertura' => 'date',
    'boas_vindas_enviado_em' => 'datetime',
    'operadora_id' => 'integer',
    'qtd_portabilidade' => 'integer',
    'comissao_paga' => 'boolean',
    'angariacao_paga' => 'boolean',
    'data_pagamento_comissao' => 'datetime',
    'data_pagamento_angariacao' => 'datetime',
  ];

  public function titulares()
  {
    return $this->hasMany(\App\Models\VendaTitular::class, 'venda_id');
  }

  public function dependentes()
  {
    return $this->hasMany(\App\Models\VendaDependente::class, 'venda_id');
  }

  public function portabilidades()
  {
    return $this->hasMany(\App\Models\VendaPortabilidade::class, 'venda_id');
  }

  public function operadoraRelation()
  {
    return $this->belongsTo(\App\Models\Operadora::class, 'operadora_id');
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

  public function recebiveis()
  {
    return $this->hasMany(Recebivel::class, 'venda_id');
  }

  public function usuarioBoasVindas()
  {
    return $this->belongsTo(User::class, 'boas_vindas_enviado_por');
  }

  public function backoffice()
  {
    return $this->belongsTo(User::class, 'backoffice_id');
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
