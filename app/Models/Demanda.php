<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\ValidatesTenantUserReferences;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class Demanda extends Model
{
    use BelongsToTenant, HasFactory, ValidatesTenantUserReferences;

    protected $fillable = [
        'empresa_id',
        'origem',
        'venda_id',
        'tipo',
        'created_by',
        'assigned_to',
        'titulo',
        'descricao',
        'prioridade',
        'status',
        'data_limite',
        'concluida_em',
    ];

    protected $casts = [
        'data_limite' => 'date',
        'concluida_em' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $demanda): void {
            if (self::shouldValidateTenantReference($demanda, 'created_by')) {
                self::assertTenantActor((int) $demanda->empresa_id, $demanda->created_by, 'autor da demanda');
            }
            if (self::shouldValidateTenantReference($demanda, 'assigned_to')) {
                self::assertTenantMember((int) $demanda->empresa_id, $demanda->assigned_to, 'responsável da demanda', true);
            }

            if (self::shouldValidateTenantReference($demanda, 'venda_id')
                && $demanda->venda_id !== null
                && ! Vendas::query()->withoutGlobalScope('tenant')->whereKey($demanda->venda_id)->where('empresa_id', $demanda->empresa_id)->exists()) {
                throw new LogicException('A venda da demanda não pertence à empresa ativa.');
            }
        });
    }

    public function criador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function venda()
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }
}
