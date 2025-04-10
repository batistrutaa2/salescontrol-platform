<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Preditiva extends Model
{
  use HasFactory;

  protected $table = "preditiva";

  protected $fillable = [
    'id',
    'empresa_id',
    'contato_id',
    'status',
    'created_at',
    'updated_at',
  ];
}
