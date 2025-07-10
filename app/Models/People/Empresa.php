<?php

namespace App\Models\People;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresas';
    protected $connection = 'people_db';

    protected $fillable = [
        'cnpj',
        'razao_social',
        'nome_fantasia',
        'data_fundacao',
        'tipo',
        'situacao',
        'cnae_numero',
        'cnae_tipo',
        'cnae_segmento',
        'cnae_descricao',
    ];

    protected $dates = [
        'data_fundacao',
        'created_at',
        'updated_at',
    ];

    // Relacionamentos

    public function enderecos()
    {
        return $this->hasMany(EnderecoEmpresa::class, 'empresa_id');
    }

    public function celulares()
    {
        return $this->hasMany(CelularEmpresa::class, 'empresa_id');
    }

    public function fixos()
    {
        return $this->hasMany(FixoEmpresa::class, 'empresa_id');
    }

    public function emails()
    {
        return $this->hasMany(EmailEmpresa::class, 'empresa_id');
    }

    public function socios()
    {
        return $this->hasMany(SocioEmpresa::class, 'empresa_id');
    }

    public function carros()
    {
        return $this->hasMany(CarroEmpresa::class, 'empresa_id');
    }
}
