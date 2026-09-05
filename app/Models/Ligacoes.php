<?php

namespace App\Models;

use App\Models\Concerns\ValidatesTenantUserReferences;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

class Ligacoes extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use HasFactory;
    use ValidatesTenantUserReferences;

    protected $table = 'ligacoes';

    protected $fillable = [
        'id',
        'empresa_id',
        'user_id',
        'contato_id',
        'telefone',
        'id_call',
        'tabulacao_id',
        'created_at',
        'updated_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $ligacao): void {
            if (self::shouldValidateTenantReference($ligacao, 'user_id')) {
                self::assertTenantMember((int) $ligacao->empresa_id, $ligacao->user_id, 'ligação');
            }

            foreach ([Contatos::class => 'contato_id', Tabulacoes::class => 'tabulacao_id'] as $model => $foreignKey) {
                if (self::shouldValidateTenantReference($ligacao, $foreignKey)
                    && ! $model::query()->withoutGlobalScope('tenant')->whereKey($ligacao->{$foreignKey})->where('empresa_id', $ligacao->empresa_id)->exists()) {
                    throw new LogicException('O vínculo da ligação não pertence à empresa ativa.');
                }
            }
        });
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }
}
