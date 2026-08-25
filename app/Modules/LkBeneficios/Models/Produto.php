<?php

namespace App\Modules\LkBeneficios\Models;

use App\Models\Operadora;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\LkBeneficios\ProdutoFactory::new();
    }

    protected $table = 'lk_beneficios_produtos';

    protected $fillable = [
        'empresa_id',
        'nome',
        'tipo',
        'subtipo',
        'modalidade',
        'operadora_id',
        'descricao',
        'coberturas',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'coberturas' => 'array',
    ];

    public function operadora()
    {
        return $this->belongsTo(Operadora::class, 'operadora_id');
    }

    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'produto_id');
    }
}
