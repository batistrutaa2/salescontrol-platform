<?php

namespace App\Models;

use App\Models\Concerns\ValidatesTenantUserReferences;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class RenovacaoOportunidade extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use ValidatesTenantUserReferences;

    protected $table = 'renovacao_oportunidades';

    protected $guarded = [];

    protected $casts = [
        'data_base' => 'date', 'elegivel_desde' => 'date', 'recontato_em' => 'date',
        'contatada_em' => 'datetime', 'respondida_em' => 'datetime',
        'cotacao_solicitada_em' => 'datetime', 'convertida_em' => 'datetime', 'encerrada_em' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $oportunidade): void {
            if (self::shouldValidateTenantReference($oportunidade, 'vendedor_original_id')) {
                self::assertTenantMember((int) $oportunidade->empresa_id, $oportunidade->vendedor_original_id, 'vendedor original da renovação', true);
            }
            if (self::shouldValidateTenantReference($oportunidade, 'responsavel_id')) {
                self::assertTenantMember((int) $oportunidade->empresa_id, $oportunidade->responsavel_id, 'responsável da renovação', true);
            }

            if (self::shouldValidateTenantReference($oportunidade, 'venda_referencia_id')
                && ! Vendas::query()->withoutGlobalScope('tenant')->whereKey($oportunidade->venda_referencia_id)->where('empresa_id', $oportunidade->empresa_id)->exists()) {
                throw new LogicException('A venda da renovação não pertence à empresa ativa.');
            }
        });
    }

    public function vendaReferencia()
    {
        return $this->belongsTo(Vendas::class, 'venda_referencia_id');
    }

    public function vendedorOriginal()
    {
        return $this->belongsTo(User::class, 'vendedor_original_id');
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function interacoes()
    {
        return $this->hasMany(RenovacaoInteracao::class, 'oportunidade_id')->latest();
    }
}
