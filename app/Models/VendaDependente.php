<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendaDependente extends Model
{
    use HasFactory;

    protected $table = 'vendas_dependentes';

    protected $fillable = [
        'venda_id',
        'titular_id',
        'nome',
        'cpf',
        'data_nascimento',
        'email',
        'telefone1',
        'telefone2',
        'parentesco',
        'plano_id',
        'coparticipacao',
        'plano_anterior',
        'operadora_anterior_id',
    ];

    protected $casts = [
        'venda_id' => 'integer',
        'titular_id' => 'integer',
        'plano_id' => 'integer',
        'operadora_anterior_id' => 'integer',
        'data_nascimento' => 'date',
    ];

    protected $appends = ['created_at_br', 'updated_at_br'];

    /** Relations */
    public function venda(): BelongsTo
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }

    public function titular(): BelongsTo
    {
        return $this->belongsTo(VendaTitular::class, 'titular_id');
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class, 'plano_id');
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

    public function scopeDoTitular($query, int $titularId)
    {
        return $query->where('titular_id', $titularId);
    }

    /** Mutators */
    public function setNomeAttribute($value): void
    {
        $this->attributes['nome'] = mb_strtoupper($value ?? '', 'UTF-8');
    }

    public function setTelefone1Attribute($value): void
    {
        $this->attributes['telefone1'] = preg_replace('/\D+/', '', $value ?? '');
    }

    public function setTelefone2Attribute($value): void
    {
        $this->attributes['telefone2'] = preg_replace('/\D+/', '', $value ?? '');
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
