<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ComentariosLegado extends Model
{
  use HasFactory;

  protected $table = "comentarios_legado";

  protected $fillable = [
    'id',
    'nome_autor',
    'nome_cliente',
    'cpf',
    'telefone1',
    'telefone2',
    'telefone3',
    'anotacao',
    'created_at',
    'updated_at',
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
