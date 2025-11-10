<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaDiaria extends Model
{
    use HasFactory;

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
        $percentual = $this->percentual_concluido;

        if ($percentual >= 100) {
            return 'concluido';
        } elseif ($percentual >= 75) {
            return 'bom';
        } elseif ($percentual >= 50) {
            return 'atencao';
        } else {
            return 'critico';
        }
    }
}
