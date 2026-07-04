<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappConversa extends Model
{
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
