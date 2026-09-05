<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\ValidatesTenantUserReferences;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class Recebivel extends Model
{
    use BelongsToTenant, HasFactory, ValidatesTenantUserReferences;

    protected $table = 'recebiveis';

    protected $fillable = [
        'empresa_id',
        'venda_id',
        'vendedor_id',
        'operadora',
        'plano',
        'parcela',
        'vitalicio',
        'valor',
        'data_prevista',
        'data_recebimento',
        'status',
    ];

    protected $casts = [
        'vitalicio' => 'boolean',
        'data_prevista' => 'date',
        'data_recebimento' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $recebivel): void {
            if (self::shouldValidateTenantReference($recebivel, 'vendedor_id')) {
                self::assertTenantMember((int) $recebivel->empresa_id, $recebivel->vendedor_id, 'vendedor do recebível');
            }

            if (self::shouldValidateTenantReference($recebivel, 'venda_id')
                && ! Vendas::query()->withoutGlobalScope('tenant')->whereKey($recebivel->venda_id)->where('empresa_id', $recebivel->empresa_id)->exists()) {
                throw new LogicException('A venda do recebível não pertence à empresa ativa.');
            }
        });
    }

    // ========================
    // 🔗 Relacionamentos
    // ========================

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function venda()
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }
}
