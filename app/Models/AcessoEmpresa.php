<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcessoEmpresa extends Model
{
    use \App\Models\Concerns\BelongsToTenantThrough;
    use HasFactory;

    protected $table = 'acesso_empresas';

    protected $fillable = [
        'venda_id',
        'email',
        'senha',
        'cpf',
    ];

    /**
     * Relacionamento com a venda
     */
    public function venda()
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }
}
