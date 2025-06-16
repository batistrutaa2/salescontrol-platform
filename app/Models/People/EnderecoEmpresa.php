<?php

namespace App\Models\People;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnderecoEmpresa extends Model
{
    protected $table = 'enderecos_empresas';
    protected $connection = 'people_db';

    protected $fillable = [
        'empresa_id',
        'endereco',
        'bairro',
        'cidade',
        'uf',
        'cep',
        'tipo',
        'ranking',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
