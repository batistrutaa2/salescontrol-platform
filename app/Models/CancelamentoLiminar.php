<?php

namespace App\Models;

use App\Models\Concerns\ValidatesTenantUserReferences;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class CancelamentoLiminar extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use ValidatesTenantUserReferences;

    protected $table = 'cancelamentos_liminares';

    protected $fillable = [
        'empresa_id',
        'venda_id',
        'beneficiario_tipo',
        'beneficiario_id',
        'nome_contrato',
        'nome_empresa',
        'cnpj',
        'protocolo_cancelamento',
        'email_procuracao',
        'nome_responsavel_procuracao',
        'responsavel_id',
        'status',
        'fase',
        'data_envio',
        'data_fim_plano',
        'data_contratacao',
        'data_solicitacao_cancelamento',
        'data_ultimo_pagamento_boleto',
        'cobertura_comprovante_inicio',
        'cobertura_comprovante_fim',
        'data_vencimento_boleto_1',
        'data_vencimento_boleto_2',
        'status_honorarios',
        'status_recebimento',
        'valor_recebimento',
        'observacoes',
    ];

    protected $casts = [
        'valor_recebimento' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $liminar): void {
            if (self::shouldValidateTenantReference($liminar, 'responsavel_id')) {
                self::assertTenantMember((int) $liminar->empresa_id, $liminar->responsavel_id, 'responsável da liminar', true);
            }

            if (self::shouldValidateTenantReference($liminar, 'venda_id')
                && $liminar->venda_id !== null
                && ! Vendas::query()->withoutGlobalScope('tenant')->whereKey($liminar->venda_id)->where('empresa_id', $liminar->empresa_id)->exists()) {
                throw new LogicException('A venda da liminar não pertence à empresa ativa.');
            }
        });
    }

    /**
     * Formata todas as datas do model para o padrão BR ao serializar (toArray/toJson).
     * Recebe um DateTimeInterface (Carbon já resolvido) — nunca uma string d/m/Y —
     * evitando o Carbon::parse() sobre formato não reconhecido.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return Carbon::instance($date)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s');
    }

    // ---------------------------------------------------------------
    // Datas em DATAS_BR: sem cast 'date' (evita conflito com serializeDate).
    // O banco armazena Y-m-d; os accessors convertem para d/m/Y na saída.
    // ---------------------------------------------------------------
    private static function formatarDataBr($value): ?string
    {
        if (! $value) {
            return null;
        }
        try {
            return Carbon::createFromFormat('Y-m-d', substr($value, 0, 10))->format('d/m/Y');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getDataEnvioAttribute($value): ?string
    {
        return self::formatarDataBr($value);
    }

    public function getDataFimPlanoAttribute($value): ?string
    {
        return self::formatarDataBr($value);
    }

    public function getDataContratacaoAttribute($value): ?string
    {
        return self::formatarDataBr($value);
    }

    public function getDataSolicitacaoCancelamentoAttribute($value): ?string
    {
        return self::formatarDataBr($value);
    }

    public function getDataUltimoPagamentoBoletoAttribute($value): ?string
    {
        return self::formatarDataBr($value);
    }

    public function getCoberturaComprovanteInicioAttribute($value): ?string
    {
        return self::formatarDataBr($value);
    }

    public function getCoberturaComprovanteFimAttribute($value): ?string
    {
        return self::formatarDataBr($value);
    }

    public function getDataVencimentoBoleto1Attribute($value): ?string
    {
        return self::formatarDataBr($value);
    }

    public function getDataVencimentoBoleto2Attribute($value): ?string
    {
        return self::formatarDataBr($value);
    }

    // ---------------------------------------------------------------
    // Relacionamentos
    // ---------------------------------------------------------------
    public function venda()
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function documentos()
    {
        return $this->hasMany(CancelamentoLiminarDocumento::class, 'cancelamento_liminar_id');
    }

    public function historico()
    {
        return $this->hasMany(CancelamentoLiminarHistorico::class, 'cancelamento_liminar_id')
            ->orderBy('created_at', 'desc');
    }

    public function getBeneficiario()
    {
        if ($this->beneficiario_tipo === 'TITULAR') {
            return VendaTitular::find($this->beneficiario_id);
        }

        return VendaDependente::find($this->beneficiario_id);
    }
}
