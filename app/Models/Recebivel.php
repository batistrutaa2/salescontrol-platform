<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recebivel extends Model
{
    use HasFactory;

    protected $table = 'recebiveis';

    protected $fillable = [
        'empresa_id',
        'venda_id',
        'vendedor_id',
        'operadora',
        'plano',
        'parcela',
        'vitalicio',
        'valor',
        'data_prevista',
        'data_recebimento',
        'status',
    ];

    protected $casts = [
        'vitalicio'       => 'boolean',
        'data_prevista'   => 'date',
        'data_recebimento'=> 'date',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];


    // ========================
    // 🔗 Relacionamentos
    // ========================

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function venda()
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }
}
