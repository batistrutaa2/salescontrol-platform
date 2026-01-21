<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendaPortabilidade extends Model
{
    use HasFactory;

    protected $table = 'vendas_portabilidades';

    protected $fillable = [
        'venda_id',
        'nome',
        'cpf',
        'data_nascimento',
        'operadora_anterior_id',
        'plano_anterior',
        'numero_carteirinha',
        'sequencial',
    ];

    protected $casts = [
        'venda_id' => 'integer',
        'operadora_anterior_id' => 'integer',
        'data_nascimento' => 'date',
        'sequencial' => 'integer',
    ];

    protected $appends = ['created_at_br', 'updated_at_br'];

    /** Relations */
    public function venda(): BelongsTo
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }

    public function operadoraAnterior(): BelongsTo
    {
        return $this->belongsTo(Operadora::class, 'operadora_anterior_id');
    }

    /** Scopes */
    public function scopeDaVenda($query, int $vendaId)
    {
        return $query->where('venda_id', $vendaId);
    }

    /** Mutators */
    public function setNomeAttribute($value): void
    {
        $this->attributes['nome'] = mb_strtoupper($value ?? '', 'UTF-8');
    }

    public function setCpfAttribute($value): void
    {
        $this->attributes['cpf'] = preg_replace('/\D+/', '', $value ?? '');
    }

    /** Accessors */
    public function getCreatedAtBrAttribute(): ?string
    {
        return $this->created_at
            ? $this->created_at->copy()->timezone('America/Sao_Paulo')->format('d/m/Y H:i:s')
            : null;
    }

    public function getUpdatedAtBrAttribute(): ?string
    {
        return $this->updated_at
            ? $this->updated_at->copy()->timezone('America/Sao_Paulo')->format('d/m/Y H:i:s')
            : null;
    }
}
