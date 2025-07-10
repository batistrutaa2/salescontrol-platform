<?php

namespace App\Models\People;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixoEmpresa extends Model
{
      protected $connection = 'people_db';

      protected $table = 'fixos_empresas';

    protected $fillable = [
        'empresa_id',
        'ddd',
        'numero',
        'ranking',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
