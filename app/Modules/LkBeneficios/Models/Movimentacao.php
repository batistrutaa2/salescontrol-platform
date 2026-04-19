<?php

namespace App\Modules\LkBeneficios\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movimentacao extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'lk_beneficios_movimentacoes';

    protected $fillable = [
        'contrato_id',
        'beneficiario_id',
        'user_id',
        'tipo',
        'descricao',
        'valor_anterior',
        'valor_novo',
        'created_at',
    ];

    protected $casts = [
        'valor_anterior' => 'decimal:2',
        'valor_novo' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function beneficiario()
    {
        return $this->belongsTo(Beneficiario::class, 'beneficiario_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
