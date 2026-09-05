<?php

namespace App\Models\People\Assertiva;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\RequiresTenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssertivaTelefone extends Model
{
    use BelongsToTenant, RequiresTenantContext;

    protected $connection = 'people_db';

    protected $table = 'assertiva_telefones';

    protected $fillable = [
        'empresa_id',
        'assertiva_pessoa_id',
        'assertiva_empresa_id',
        'numero_normalizado',
        'numero',
        'tipo',
        'whatsapp',
        'nao_perturbe',
        'relacao',
    ];

    protected $casts = [
        'whatsapp' => 'boolean',
        'nao_perturbe' => 'boolean',
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
