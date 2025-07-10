<?php

namespace App\Models\People;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailEmpresa extends Model
{
      protected $connection = 'people_db';
      protected $table = 'emails_empresas';

    protected $fillable = [
        'empresa_id',
        'email',
        'ranking',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
