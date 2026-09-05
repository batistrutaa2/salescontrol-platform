<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use LogicException;

class WhatsappMensagem extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $table = 'whatsapp_mensagens';

    protected $fillable = [
        'empresa_id',
        'conversa_id',
        'message_id',
        'direcao',
        'tipo',
        'body',
        'media_path',
        'media_mime',
        'media_size',
        'quoted_message_id',
        'ack',
        'status_envio',
        'erro_envio',
        'message_timestamp',
    ];

    protected $casts = [
        'message_timestamp' => 'datetime',
        'ack' => 'integer',
    ];

    protected $appends = ['media_url'];

    protected static function booted(): void
    {
        static::saving(function (self $mensagem): void {
            $conversaValida = WhatsappConversa::query()
                ->whereKey($mensagem->conversa_id)
                ->where('empresa_id', $mensagem->empresa_id)
                ->exists();

            if (! $conversaValida) {
                throw new LogicException('A conversa da mensagem não pertence à empresa ativa.');
            }
        });
    }

    public function conversa(): BelongsTo
    {
        return $this->belongsTo(WhatsappConversa::class, 'conversa_id');
    }

    public function getMediaUrlAttribute(): ?string
    {
        return $this->media_path ? Storage::url($this->media_path) : null;
    }
}
