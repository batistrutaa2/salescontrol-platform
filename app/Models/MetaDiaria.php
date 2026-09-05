<?php

namespace App\Models;

use App\Models\Concerns\ValidatesTenantUserReferences;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaDiaria extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use HasFactory;
    use ValidatesTenantUserReferences;

    protected $table = 'metas_diarias';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'data',
        'meta_cotacoes',
        'cotacoes_realizadas',
    ];

    protected $casts = [
        'data' => 'date',
        'meta_cotacoes' => 'integer',
        'cotacoes_realizadas' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $meta): void {
            if (self::shouldValidateTenantReference($meta, 'user_id')) {
                self::assertTenantMember((int) $meta->empresa_id, $meta->user_id, 'vendedor da meta');
            }
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPercentualConcluidoAttribute(): float
    {
        if ($this->meta_cotacoes == 0) {
            return 0;
        }

        return round(($this->cotacoes_realizadas / $this->meta_cotacoes) * 100, 2);
    }

    public function getStatusAttribute(): string
    {
        $empresa = $this->relationLoaded('empresa')
            ? $this->getRelation('empresa')
            : $this->empresa()->firstOrFail();

        return $this->statusPara(
            (int) $empresa->tv_percentual_atencao,
            (int) $empresa->tv_percentual_bom,
        );
    }

    public function statusPara(int $percentualAtencao, int $percentualBom): string
    {
        $percentual = $this->percentual_concluido;

        if ($percentual >= 100) {
            return 'concluido';
        } elseif ($percentual >= $percentualBom) {
            return 'bom';
        } elseif ($percentual >= $percentualAtencao) {
            return 'atencao';
        } else {
            return 'critico';
        }
    }
}
