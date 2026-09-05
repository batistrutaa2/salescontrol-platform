<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\ValidatesTenantUserReferences;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class Preditiva extends Model
{
    use BelongsToTenant, ValidatesTenantUserReferences;

    protected $table = 'preditiva';

    protected $fillable = [
        'empresa_id',
        'contato_id',
        'user_id',
        'data_atribuicao',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $preditiva): void {
            if (self::shouldValidateTenantReference($preditiva, 'user_id')) {
                self::assertTenantMember((int) $preditiva->empresa_id, $preditiva->user_id, 'proprietário do lock preditivo', true);
            }

            if (self::shouldValidateTenantReference($preditiva, 'contato_id')
                && ! Contatos::query()->withoutGlobalScope('tenant')->whereKey($preditiva->contato_id)->where('empresa_id', $preditiva->empresa_id)->exists()) {
                throw new LogicException('O contato da fila preditiva não pertence à empresa ativa.');
            }
        });
    }

    public function contato()
    {
        return $this->belongsTo(Contatos::class, 'contato_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
