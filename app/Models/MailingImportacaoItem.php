<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailingImportacaoItem extends Model
{
    protected $table = 'mailing_importacao_itens';

    protected $fillable = [
        'mailing_importacao_id',
        'linha',
        'cpf',
        'nome',
        'payload',
        'classificacao',
        'motivo',
        'contato_existente_id',
        'contato_importado_id',
        'resolucao',
        'resolvido_por',
        'resolvido_em',
    ];

    protected $casts = [
        'payload' => 'array',
        'resolvido_em' => 'datetime',
    ];

    public function importacao()
    {
        return $this->belongsTo(MailingImportacao::class, 'mailing_importacao_id');
    }
}
