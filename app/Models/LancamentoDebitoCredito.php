<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LancamentoDebitoCredito extends Model
{
    use HasFactory;

    protected $table = 'lancamentos_debito_credito';

    // Constantes auxiliares
    public const NAT_DEBITO  = 'DEBITO';
    public const NAT_CREDITO = 'CREDITO';

    public const ST_PENDENTE = 'pendente';
    public const ST_PAGO     = 'pago';
    public const ST_CANCEL   = 'cancelado';

    protected $fillable = [
        'empresa_id',
        'vendedor_id',
        'created_by',
        'natureza',           // DEBITO | CREDITO
        'categoria',          // MOTIVACIONAL | AJUSTE | DESCONTO | OUTRO...
        'valor_bruto',
        'imposto_perc',
        'imposto_valor',      // snapshot calculado
        'valor_liquido',      // snapshot calculado (com sinal)
        'mes',                // YYYY-MM
        'descricao',
        'status',             // pendente | pago | cancelado
        'comissao_pagamento_id',
        'pago_em',
        'parcelado',          // boolean
        'parcela_atual',      // 1, 2, 3...
        'parcelas_total',     // 10, 12...
        'valor_total_original', // valor total antes de parcelar
        'lancamento_principal_id', // FK para primeira parcela
    ];

    protected $casts = [
        'valor_bruto'        => 'decimal:2',
        'imposto_perc'       => 'decimal:2',
        'imposto_valor'      => 'decimal:2',
        'valor_liquido'      => 'decimal:2',
        'valor_total_original' => 'decimal:2',
        'parcelado'          => 'boolean',
        'pago_em'            => 'datetime',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
    ];

    /* -----------------------------------------------------------------
     |  Relationships
     |------------------------------------------------------------------*/
    public function empresa()
    {
        // Ajuste o Model se no seu projeto não for App\Models\Empresa
        return $this->belongsTo(\App\Models\Empresa::class, 'empresa_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(\App\Models\User::class, 'vendedor_id');
    }

    public function autor()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function pagamento()
    {
        // Ajuste o Model se usar outro nome (ex.: ComissaoPagamento)
        return $this->belongsTo(\App\Models\ComissaoPagamento::class, 'comissao_pagamento_id');
    }

    public function lancamentoPrincipal()
    {
        return $this->belongsTo(self::class, 'lancamento_principal_id');
    }

    public function parcelas()
    {
        return $this->hasMany(self::class, 'lancamento_principal_id');
    }

    /* -----------------------------------------------------------------
     |  Scopes
     |------------------------------------------------------------------*/
    public function scopeEmpresa($q, $empresaId)
    {
        return $q->where('empresa_id', $empresaId);
    }

    public function scopeDoMes($q, string $mes)
    {
        return $q->where('mes', $mes); // YYYY-MM
    }

    public function scopeDoVendedor($q, int $vendedorId)
    {
        return $q->where('vendedor_id', $vendedorId);
    }

    public function scopePendentes($q)
    {
        return $q->where('status', self::ST_PENDENTE);
    }

    public function scopePagos($q)
    {
        return $q->where('status', self::ST_PAGO);
    }

    public function scopeCreditos($q)
    {
        return $q->where('natureza', self::NAT_CREDITO);
    }

    public function scopeDebitos($q)
    {
        return $q->where('natureza', self::NAT_DEBITO);
    }

    /* -----------------------------------------------------------------
     |  Mutators / Normalização
     |------------------------------------------------------------------*/
    public function setMesAttribute($value): void
    {
        // aceita 'YYYY-MM' (preferido) ou normaliza datas 'YYYY-MM-DD'
        $value = (string) $value;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $this->attributes['mes'] = substr($value, 0, 7);
        } else {
            $this->attributes['mes'] = substr($value, 0, 7);
        }
    }

    public function setDescricaoAttribute($value): void
    {
        $this->attributes['descricao'] = $value ? trim($value) : null;
    }

    /* -----------------------------------------------------------------
     |  Lifecycle: sempre recalcula snapshots ao salvar
     |------------------------------------------------------------------*/
    protected static function booted(): void
    {
        static::saving(function (self $m) {
            // defaults defensivos
            $bruto   = (float) ($m->valor_bruto ?? 0);
            $impPerc = (float) ($m->imposto_perc ?? 0);

            // calcula snapshots
            $impVal  = round($bruto * ($impPerc / 100), 2);
            $liqPos  = round($bruto - $impVal, 2);

            $m->imposto_valor = $impVal;

            // líquido com sinal: débito negativo, crédito positivo
            $m->valor_liquido = ($m->natureza === self::NAT_DEBITO) ? -$liqPos : $liqPos;

            // normaliza status padrão
            if (!$m->status) {
                $m->status = self::ST_PENDENTE;
            }

            // normaliza mes (YYYY-MM) se vier vazio
            if (!$m->mes && $m->created_at) {
                $m->mes = $m->created_at->format('Y-m');
            } elseif (!$m->mes) {
                $m->mes = now()->format('Y-m');
            }
        });
    }

    /* -----------------------------------------------------------------
     |  Helpers
     |------------------------------------------------------------------*/
    public function isCredito(): bool
    {
        return $this->natureza === self::NAT_CREDITO;
    }

    public function isDebito(): bool
    {
        return $this->natureza === self::NAT_DEBITO;
    }

    public function marcarComoPago(int $pagamentoId = null): void
    {
        $this->status = self::ST_PAGO;
        $this->pago_em = now();
        if ($pagamentoId) {
            $this->comissao_pagamento_id = $pagamentoId;
        }
        $this->save();
    }

    public function cancelar(): void
    {
        $this->status = self::ST_CANCEL;
        $this->save();
    }

    public function isParcelado(): bool
    {
        return (bool) $this->parcelado;
    }

    public function getDescricaoCompleta(): string
    {
        $desc = $this->descricao ?? '';

        if ($this->isParcelado() && $this->parcela_atual && $this->parcelas_total) {
            $parcela = sprintf('Parcela %d/%d', $this->parcela_atual, $this->parcelas_total);
            return $desc ? "{$desc} - {$parcela}" : $parcela;
        }

        return $desc;
    }

    public function getParcelaFormatada(): ?string
    {
        if (!$this->isParcelado() || !$this->parcela_atual || !$this->parcelas_total) {
            return null;
        }

        return sprintf('%d/%d', $this->parcela_atual, $this->parcelas_total);
    }
}
