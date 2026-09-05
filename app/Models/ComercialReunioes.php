<?php

namespace App\Models;

use App\Models\Concerns\ValidatesTenantUserReferences;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

class ComercialReunioes extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use HasFactory, SoftDeletes;
    use ValidatesTenantUserReferences;

    protected $table = 'comercial_reunioes';

    protected $fillable = [
        'titulo',
        'user_id',
        'manager_id',
        'contato_id',
        'data_inicio',
        'data_final',
        'observacao',
        'location',
        'status',
    ];

    protected $casts = [
        'data_inicio' => 'datetime',
        'data_final' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $reuniao): void {
            if (self::shouldValidateTenantReference($reuniao, 'user_id')) {
                self::assertTenantActor((int) $reuniao->empresa_id, $reuniao->user_id, 'autor da reunião');
            }
            if (self::shouldValidateTenantReference($reuniao, 'manager_id')) {
                self::assertTenantMember((int) $reuniao->empresa_id, $reuniao->manager_id, 'gestor da reunião');
            }

            if (self::shouldValidateTenantReference($reuniao, 'contato_id')
                && $reuniao->contato_id !== null
                && ! Contatos::query()->withoutGlobalScope('tenant')->whereKey($reuniao->contato_id)->where('empresa_id', $reuniao->empresa_id)->exists()) {
                throw new LogicException('O contato da reunião não pertence à empresa ativa.');
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function contato()
    {
        return $this->belongsTo(Contatos::class, 'contato_id');
    }
}
