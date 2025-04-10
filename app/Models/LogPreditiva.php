<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogPreditiva extends Model
{
  use HasFactory;

  protected $table = "log_preditiva";

  protected $fillable = [
    'id',
    'empresa_id',
    'user_id',
    'contato_id',
    'tabulacao',
    'acao',
    'created_at',
    'updated_at',
  ];
}
