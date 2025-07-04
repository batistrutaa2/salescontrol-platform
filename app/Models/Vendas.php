<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Vendas extends Model
{
  use HasFactory;

  protected $table = "vendas";

  protected $fillable = [
    'id',
    'empresa_id',
    'user_id',
    'contato_id',
    'nome_contrato',
    'cpf_cnpj',
    'email',
    'data_vigencia',
    'telefone1',
    'telefone2',
    'operadora',
    'nome_plano',
    'valor_contrato',
    'vidas',
    'obs_contrato',
    'created_at',
    'updated_at'
  ];

  protected $casts = [
        'data_vigencia' => 'date',
        'valor_contrato' => 'decimal:2',
        'vidas' => 'integer',
    ];

    // Relacionamento com User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contatoCorretor()
    {
        return $this->hasOne(ContatosCorretores::class, 'contato_id', 'contato_id');
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
