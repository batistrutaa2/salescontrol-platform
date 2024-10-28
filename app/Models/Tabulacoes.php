<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tabulacoes extends Model
{
  use HasFactory;

  protected $table = "tabulacoes";

  protected $fillable = [
    'id',
    'empresa_id',
    'descricao',
    'tipo_tabulacao',
    'efetivo',
    'ordem_kanban',
    'status',
    'sub_tabulacao',
    'created_at',
    'updated_at'
  ];
}
