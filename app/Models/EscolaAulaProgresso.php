<?php

namespace App\Models;

use App\Models\Concerns\ValidatesTenantUserReferences;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class EscolaAulaProgresso extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use ValidatesTenantUserReferences;

    protected $table = 'escola_aula_progresso';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'escola_aula_id',
        'ultima_posicao_segundos',
        'percentual',
        'concluida',
        'concluida_em',
    ];

    protected $casts = [
        'concluida' => 'boolean',
        'concluida_em' => 'datetime',
        'percentual' => 'integer',
        'ultima_posicao_segundos' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $progresso): void {
            if (self::shouldValidateTenantReference($progresso, 'user_id')) {
                self::assertTenantMember((int) $progresso->empresa_id, $progresso->user_id, 'aluno do progresso');
            }

            if (self::shouldValidateTenantReference($progresso, 'escola_aula_id')
                && ! EscolaAula::query()->withoutGlobalScope('tenant')->whereKey($progresso->escola_aula_id)->where('empresa_id', $progresso->empresa_id)->exists()) {
                throw new LogicException('A aula do progresso não pertence à empresa ativa.');
            }
        });
    }

    public function aula()
    {
        return $this->belongsTo(EscolaAula::class, 'escola_aula_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
