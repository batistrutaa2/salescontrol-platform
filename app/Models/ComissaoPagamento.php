<?php

namespace App\Models;

use App\Models\Concerns\ValidatesTenantUserReferences;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

class ComissaoPagamento extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use HasFactory;
    use ValidatesTenantUserReferences;

    protected $table = 'comissao_pagamentos';

    protected $fillable = [
        'id',
        'pago_em',
        'conta_pagamento_id',
        'empresa_id',
        'vendedor_id',
        'mes',
        'data_pagamento',
        'percentual_comissao',
        'percentual_imposto',
        'total_bruto',
        'total_imposto',
        'total_liquido',
        'salario',
        'total_receber',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $pagamento): void {
            if (self::shouldValidateTenantReference($pagamento, 'vendedor_id')) {
                self::assertTenantMember((int) $pagamento->empresa_id, $pagamento->vendedor_id, 'pagamento de comissão');
            }

            if (self::shouldValidateTenantReference($pagamento, 'created_by')) {
                self::assertTenantActor((int) $pagamento->empresa_id, $pagamento->created_by, 'pagamento de comissão');
            }

            if (self::shouldValidateTenantReference($pagamento, 'conta_pagamento_id')
                && $pagamento->conta_pagamento_id
                && ! DB::table('contas_pagamento')
                    ->where('id', $pagamento->conta_pagamento_id)
                    ->where('user_id', $pagamento->vendedor_id)
                    ->exists()) {
                throw new LogicException('A conta de pagamento não pertence ao vendedor.');
            }
        });
    }
}
