<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class Empresa extends Model
{
  use HasFactory;

  protected $table = "empresas";

  protected $fillable = [
    'id',
    'nome_fantasia',
    'cpf_cnpj',
    'telefone',
    'email',
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
