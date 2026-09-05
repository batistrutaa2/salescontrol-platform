<?php

namespace App\Models\People\Assertiva;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\RequiresTenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssertivaPessoa extends Model
{
    use BelongsToTenant, RequiresTenantContext;

    protected $connection = 'people_db';

    protected $table = 'assertiva_pessoas';

    protected $fillable = [
        'empresa_id',
        'cpf',
        'nome',
        'sexo',
        'data_nascimento',
        'mae_nome',
        'mae_cpf',
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
        return $this->hasMany(AssertivaTelefone::class, 'assertiva_pessoa_id');
    }

    public function enderecos(): HasMany
    {
        return $this->hasMany(AssertivaEndereco::class, 'assertiva_pessoa_id');
    }

    public function emails(): HasMany
    {
        return $this->hasMany(AssertivaEmail::class, 'assertiva_pessoa_id');
    }
}
