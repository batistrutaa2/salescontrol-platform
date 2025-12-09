<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendaHistorico extends Model
{
    use HasFactory;

    protected $table = 'vendas_historico';

    protected $fillable = [
        'empresa_id',
        'venda_id',
        'contato_corretor_id',
        'user_id',
        'tabulacao_anterior_id',
        'tabulacao_nova_id',
        'observacao',
        'motivo_pendencia',
    ];

    /**
     * Relacionamento com a venda
     */
    public function venda(): BelongsTo
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }

    /**
     * Relacionamento com o usuário que fez a alteração
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relacionamento com a tabulação anterior
     */
    public function tabulacaoAnterior(): BelongsTo
    {
        return $this->belongsTo(Tabulacoes::class, 'tabulacao_anterior_id');
    }

    /**
     * Relacionamento com a nova tabulação
     */
    public function tabulacaoNova(): BelongsTo
    {
        return $this->belongsTo(Tabulacoes::class, 'tabulacao_nova_id');
    }

    /**
     * Relacionamento com a empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresas::class, 'empresa_id');
    }

    /**
     * Relacionamento com o contato corretor
     */
    public function contatoCorretor(): BelongsTo
    {
        return $this->belongsTo(ContatosCorretores::class, 'contato_corretor_id');
    }

    /**
     * Scope para buscar histórico de uma venda específica
     */
    public function scopeDeVenda($query, $vendaId)
    {
        return $query->where('venda_id', $vendaId);
    }

    /**
     * Scope para buscar histórico por empresa
     */
    public function scopeDeEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    /**
     * Calcula o tempo que ficou no status anterior
     */
    public function getTempoNoStatusAnteriorAttribute()
    {
        // Buscar registro anterior
        $registroAnterior = self::where('venda_id', $this->venda_id)
            ->where('id', '<', $this->id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$registroAnterior) {
            return null;
        }

        $inicio = $registroAnterior->created_at;
        $fim = $this->created_at;

        return $inicio->diff($fim);
    }

    /**
     * Formata o tempo no status anterior para exibição
     */
    public function getTempoFormatadoAttribute()
    {
        $diff = $this->tempo_no_status_anterior;

        if (!$diff) {
            return 'Início';
        }

        if ($diff->days > 0) {
            return $diff->days . ' dia' . ($diff->days > 1 ? 's' : '');
        }

        if ($diff->h > 0) {
            return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
        }

        return $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
    }
}
