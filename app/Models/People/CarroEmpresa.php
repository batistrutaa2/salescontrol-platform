<?php

namespace App\Models\People;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarroEmpresa extends Model
{
   protected $connection = 'people_db';
       protected $table = 'carros_empresas';

    protected $fillable = [
        'empresa_id',
        'placa',
        'marca',
        'ano_fabricacao',
        'ano_modelo',
        'renavan',
        'chassi',
        'data_licenciamento',
        'ranking',
    ];

    protected $dates = [
        'data_licenciamento',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
