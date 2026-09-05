<?php

namespace App\Models;

use App\Models\Concerns\ValidatesTenantUserReferences;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappInstancia extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use ValidatesTenantUserReferences;

    protected $table = 'whatsapp_instancias';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'instance_name',
        'instance_id',
        'numero_conectado',
        'status',
        'webhook_token',
        'last_status_at',
        'connected_at',
    ];

    protected $casts = [
        'last_status_at' => 'datetime',
        'connected_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $instancia): void {
            if (self::shouldValidateTenantReference($instancia, 'user_id')) {
                self::assertTenantMember((int) $instancia->empresa_id, $instancia->user_id, 'instância do WhatsApp');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function conversas(): HasMany
    {
        return $this->hasMany(WhatsappConversa::class, 'instancia_id');
    }
}
