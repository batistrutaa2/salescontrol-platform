<?php

namespace App\Models\People\Assertiva;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssertivaEmpresa extends Model
{
    protected $connection = 'people_db';

    protected $table = 'assertiva_empresas';

    protected $fillable = [
        'cnpj',
        'razao_social',
        'nome_fantasia',
        'data_abertura',
        'cnae',
        'cnae_descricao',
        'situacao_cadastral',
        'protocolo',
        'payload',
        'data_consulta',
    ];

    protected $casts = [
        'data_consulta' => 'datetime',
        'payload' => 'array',
    ];

    public function telefones(): HasMany
    {
        return $this->hasMany(AssertivaTelefone::class, 'assertiva_empresa_id');
    }

    public function enderecos(): HasMany
    {
        return $this->hasMany(AssertivaEndereco::class, 'assertiva_empresa_id');
    }

    public function emails(): HasMany
    {
        return $this->hasMany(AssertivaEmail::class, 'assertiva_empresa_id');
    }
}
