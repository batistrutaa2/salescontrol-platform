<?php

namespace App\Models\People\Assertiva;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssertivaTelefone extends Model
{
    protected $connection = 'people_db';

    protected $table = 'assertiva_telefones';

    protected $fillable = [
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
