<?php

namespace App\Models;

use App\Models\Concerns\ValidatesTenantUserReferences;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContatosCorretores extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use HasFactory;
    use ValidatesTenantUserReferences;

    protected $table = 'contatos_corretores';

    protected static function booted()
    {
        static::saving(function (self $contatoCorretor): void {
            if (self::shouldValidateTenantReference($contatoCorretor, 'user_id')) {
                self::assertTenantMember((int) $contatoCorretor->empresa_id, $contatoCorretor->user_id, 'proprietário do lead', true);
            }
        });

        // Lead atribuído a um vendedor: revincula conversas de WhatsApp órfãs desse número
        static::created(function (self $contatoCorretor) {
            if ($contatoCorretor->contato_id && $contatoCorretor->user_id) {
                \App\Jobs\Whatsapp\RevincularConversasContato::dispatch(
                    (int) $contatoCorretor->contato_id,
                    (int) $contatoCorretor->user_id
                );
            }
        });

        // Lead transferido entre vendedores: revincula para o novo dono
        static::updated(function (self $contatoCorretor) {
            if ($contatoCorretor->wasChanged('user_id') && $contatoCorretor->contato_id && $contatoCorretor->user_id) {
                \App\Jobs\Whatsapp\RevincularConversasContato::dispatch(
                    (int) $contatoCorretor->contato_id,
                    (int) $contatoCorretor->user_id
                );
            }
        });
    }

    protected $fillable = [
        'id',
        'empresa_id',
        'contato_id',
        'user_id',
        'tabulacao_id',
        'temperatura',
        'created_at',
        'updated_at',
    ];

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }

    public function contato()
    {
        return $this->belongsTo(Contatos::class, 'contato_id');  // Relacionamento com a tabela contatos
    }

    public function tabulacao()
    {
        return $this->belongsTo(Tabulacoes::class, 'tabulacao_id');  // Relacionamento com a tabela tabulacoes
    }

    public function subTabulacao()
    {
        return $this->belongsTo(Tabulacoes::class, 'sub_tabulacao_id');  // Relacionamento com a sub_tabulacao
    }
}
