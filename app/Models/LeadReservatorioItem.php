<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadReservatorioItem extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    public const STATUS_DISPONIVEL = 'DISPONIVEL';

    public const STATUS_DISTRIBUIDO = 'DISTRIBUIDO';

    public const STATUS_BLOQUEADO = 'BLOQUEADO';

    public const ORIGEM_IMPORTACAO = 'IMPORTACAO';

    public const ORIGEM_MARKETING = 'MARKETING';

    public const ORIGEM_MIGRACAO = 'MIGRACAO_INICIAL';

    protected $table = 'lead_reservatorio_itens';

    protected $fillable = [
        'empresa_id', 'contato_id', 'origem', 'status', 'entrou_por', 'entrou_em',
        'distribuido_para', 'distribuido_por', 'distribuido_em', 'bloqueado_motivo',
    ];

    protected $casts = [
        'entrou_em' => 'datetime',
        'distribuido_em' => 'datetime',
    ];

    public function contato()
    {
        return $this->belongsTo(Contatos::class, 'contato_id');
    }
}
