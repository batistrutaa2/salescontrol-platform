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
    'empresa_id',
    'user_id',
    'contato_id',
    'nome_contrato',
    'cpf_cnpj',
    'email',
    'data_vigencia',
    'telefone1',
    'telefone2',
    'operadora',
    'nome_plano',
    'valor_contrato',
    'obs_contrato',
    'created_at',
    'updated_at'
  ];

  public function getCreatedAtAttribute($value)
  {
    return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
  }

  public function getUpdatedAtAttribute($value)
  {
    return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
  }
}
