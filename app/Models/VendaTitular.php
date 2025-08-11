<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendaTitular extends Model
{
    use HasFactory;

    protected $table = 'vendas_titulares';

    // Somente campos realmente preenchíveis
    protected $fillable = [
        'venda_id',
        'nome',
        'email',
        'telefone',
        'plano_id',
        'coparticipacao', // Y, N, PARCIAL, COMPLETA
    ];

    protected $casts = [
        'venda_id' => 'integer',
        'plano_id' => 'integer',
    ];

    // Atributos computados para exibir datas no fuso BR
    protected $appends = ['created_at_br', 'updated_at_br'];

    /** Relations */
    public function venda(): BelongsTo
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class, 'plano_id');
    }

    /** Scopes úteis */
    public function scopeDaVenda($query, int $vendaId)
    {
        return $query->where('venda_id', $vendaId);
    }

    /** Mutators */
    public function setNomeAttribute($value): void
    {
        $this->attributes['nome'] = mb_strtoupper($value ?? '', 'UTF-8');
    }

    public function setTelefoneAttribute($value): void
    {
        // Armazena somente dígitos (ex.: "11999998888")
        $this->attributes['telefone'] = preg_replace('/\D+/', '', $value ?? '');
    }

    /** Accessors para datas formatadas no fuso BR */
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
