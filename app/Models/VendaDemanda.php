<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class VendaDemanda extends Model
{
  use HasFactory;

  protected $table = 'venda_demandas';

  protected $fillable = [
    'venda_id',
    'empresa_id',
    'created_by',
    'concluida_por',
    'tipo',
    'titulo',
    'descricao',
    'status',
    'concluida_em',
  ];

  protected $casts = [
    'concluida_em' => 'datetime',
  ];

  public function venda()
  {
    return $this->belongsTo(Vendas::class, 'venda_id');
  }

  public function criador()
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function concluidaPor()
  {
    return $this->belongsTo(User::class, 'concluida_por');
  }

  public function getCreatedAtAttribute($value)
  {
    return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
  }

  public function getUpdatedAtAttribute($value)
  {
    return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
  }

  public function getConcluidaEmAttribute($value)
  {
    return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
  }
}
