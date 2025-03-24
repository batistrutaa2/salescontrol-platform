<?php

namespace App\Models;

use App\Helpers\Helpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Contatos extends Model
{
  use HasFactory;

  protected $table = "contatos";

  protected $fillable = [
    'id',
    'id_operacao',
    'empresa_id',
    'user_import_id',
    'nome_base',
    'status',
    'nome_cliente',
    'data_nascimento',
    'cpf',
    'plano',
    'categoria',
    'entidade',
    'telefone1',
    'telefone2',
    'telefone3',
    'email',
    'idades',
    'valor_plano_atual',
    'valor_negociacao',
    'created_at',
    'updated_at',
    'is_ads',
    'tipo_criativo',
    'vidas',
    'plano_ativo',
    'possui_cnpj'
  ];

  public function getDataNascimentoAttribute($value)
  {
      if (strpos($value, '/') === false && !is_null($value) && $value !== '') {
          return Helpers::excelDateToPhpDate($value);
      }
      return $value;
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
