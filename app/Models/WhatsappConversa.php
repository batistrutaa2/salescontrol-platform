<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class WhatsappConversa extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $table = 'whatsapp_conversas';

    protected $fillable = [
        'empresa_id',
        'instancia_id',
        'user_id',
        'remote_jid',
        'numero',
        'numero_normalizado',
        'nome_whatsapp',
        'foto_url',
        'contato_id',
        'tabulacao_id',
        'last_message_at',
        'last_message_preview',
        'unread_count',
        'arquivada',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $conversa): void {
            $instanciaValida = WhatsappInstancia::query()
                ->whereKey($conversa->instancia_id)
                ->where('empresa_id', $conversa->empresa_id)
                ->where('user_id', $conversa->user_id)
                ->exists();

            if (! $instanciaValida) {
                throw new LogicException('A instância do WhatsApp não pertence à empresa e ao usuário da conversa.');
            }

            foreach ([Contatos::class => 'contato_id', Tabulacoes::class => 'tabulacao_id'] as $model => $foreignKey) {
                if ($conversa->{$foreignKey} !== null
                    && ! $model::query()->whereKey($conversa->{$foreignKey})->where('empresa_id', $conversa->empresa_id)->exists()) {
                    throw new LogicException('O vínculo da conversa não pertence à empresa ativa.');
                }
            }
        });
    }

    public function instancia(): BelongsTo
    {
        return $this->belongsTo(WhatsappInstancia::class, 'instancia_id');
    }

    public function contato(): BelongsTo
    {
        return $this->belongsTo(Contatos::class, 'contato_id');
    }

    public function tabulacao(): BelongsTo
    {
        return $this->belongsTo(Tabulacoes::class, 'tabulacao_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function mensagens(): HasMany
    {
        return $this->hasMany(WhatsappMensagem::class, 'conversa_id');
    }
}
