<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeadAtividade extends Model
{
    use HasFactory;

    protected $table = "lead_atividades";

    protected $fillable = [
      'id',
      'empresa_id',
      'user_id',
      'contato_id',
      'tabulacao_anterior_id',
      'tabulacao_atual_id',
      'log_descricao',
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
