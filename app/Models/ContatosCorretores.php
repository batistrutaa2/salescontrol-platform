<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ContatosCorretores extends Model
{
  use HasFactory;

  use HasFactory;

  protected $table = "contatos_corretores";

  protected $fillable = [
    'id',
    'empresa_id',
    'contato_id',
    'user_id',
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
