<?php

namespace App\Models;

use App\Models\Concerns\ValidatesTenantUserReferences;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

class Agendamento extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use HasFactory;
    use ValidatesTenantUserReferences;

    protected $table = 'agendamentos';

    protected $fillable = [
        'id',
        'empresa_id',
        'user_id',
        'contato_id',
        'horario_agendamento',
        'observacao',
        'notificado',
        'created_at',
        'updated_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $agendamento): void {
            if (self::shouldValidateTenantReference($agendamento, 'user_id')) {
                self::assertTenantMember((int) $agendamento->empresa_id, $agendamento->user_id, 'responsável do agendamento');
            }

            if (self::shouldValidateTenantReference($agendamento, 'contato_id')
                && ! Contatos::query()->withoutGlobalScope('tenant')->whereKey($agendamento->contato_id)->where('empresa_id', $agendamento->empresa_id)->exists()) {
                throw new LogicException('O contato do agendamento não pertence à empresa ativa.');
            }
        });
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
