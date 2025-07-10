<?php

namespace App\Models\People;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocioEmpresa extends Model
{
    protected $connection = 'people_db';
    protected $table = 'socios_empresas';

    protected $fillable = [
        'empresa_id',
        'cpf',
        'nome',
        'participacao',
        'capital_social',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
