<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ligacoes extends Model
{
  use HasFactory;

  protected $table = "ligacoes";

  protected $fillable = [
    'id',
    'empresa_id',
    'user_id',
    'contato_id',
    'telefone',
    'tabulacao_id',
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
