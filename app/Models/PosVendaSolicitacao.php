<?php

namespace App\Models;

use App\Models\Concerns\ValidatesTenantUserReferences;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class PosVendaSolicitacao extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use ValidatesTenantUserReferences;

    protected $table = 'pos_venda_solicitacoes';

    public const STATUS_ABERTA = 'ABERTA';

    public const STATUS_CONCLUIDA = 'CONCLUIDA';

    public const STATUS_CANCELADA = 'CANCELADA';

    public const ORIGEM_BACKOFFICE = 'BACKOFFICE';

    public const ORIGEM_VENDEDOR = 'VENDEDOR';

    protected $fillable = [
        'venda_id',
        'empresa_id',
        'tipo',
        'etapa_id',
        'titulo',
        'descricao',
        'status',
        'prioridade',
        'data_limite',
        'data_retorno',
        'origem',
        'responsavel_id',
        'created_by',
        'concluida_em',
    ];

    protected $casts = [
        'prioridade' => 'boolean',
        'data_limite' => 'date',
        'data_retorno' => 'date',
        'concluida_em' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $solicitacao): void {
            if (self::shouldValidateTenantReference($solicitacao, 'created_by')) {
                self::assertTenantActor((int) $solicitacao->empresa_id, $solicitacao->created_by, 'autor da solicitação');
            }
            if (self::shouldValidateTenantReference($solicitacao, 'responsavel_id')) {
                self::assertTenantMember((int) $solicitacao->empresa_id, $solicitacao->responsavel_id, 'responsável da solicitação', true);
            }

            if ((self::shouldValidateTenantReference($solicitacao, 'venda_id')
                    && ! Vendas::query()->withoutGlobalScope('tenant')->whereKey($solicitacao->venda_id)->where('empresa_id', $solicitacao->empresa_id)->exists())
                || (self::shouldValidateTenantReference($solicitacao, 'etapa_id')
                    && ! PosVendaFluxoEtapa::query()->withoutGlobalScope('tenant')->whereKey($solicitacao->etapa_id)->where('empresa_id', $solicitacao->empresa_id)->exists())) {
                throw new LogicException('A venda ou etapa da solicitação não pertence à empresa ativa.');
            }
        });
    }

    public function venda()
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }

    public function etapa()
    {
        return $this->belongsTo(PosVendaFluxoEtapa::class, 'etapa_id');
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function criador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function historico()
    {
        return $this->hasMany(PosVendaSolicitacaoHistorico::class, 'solicitacao_id')->orderByDesc('id');
    }
}
