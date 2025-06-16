<?php

namespace App\Models\People;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CelularEmpresa extends Model
{
    protected $connection = 'people_db';
    protected $table = 'celulares_empresas';

    protected $fillable = [
        'empresa_id',
        'ddd',
        'numero',
        'plus',
        'ranking',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
