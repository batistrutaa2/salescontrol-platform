<?php

namespace App\Models\People\Assertiva;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssertivaEndereco extends Model
{
    protected $connection = 'people_db';

    protected $table = 'assertiva_enderecos';

    protected $fillable = [
        'assertiva_pessoa_id',
        'assertiva_empresa_id',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'cep',
    ];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(AssertivaPessoa::class, 'assertiva_pessoa_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(AssertivaEmpresa::class, 'assertiva_empresa_id');
    }
}
