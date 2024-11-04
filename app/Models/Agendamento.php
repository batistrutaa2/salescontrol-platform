<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agendamento extends Model
{
  use HasFactory;

  protected $table = "agendamentos";

  protected $fillable = [
    'id',
    'empresa_id',
    'user_id',
    'contato_id',
    'horario_agendamento',
    'observacao',
    'notificado',
    'created_at',
    'updated_at',
  ];

}
