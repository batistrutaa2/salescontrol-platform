<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class MailingImportacao extends Model
{
    use BelongsToTenant;

    protected $table = 'mailing_importacoes';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'vendedor_id',
        'tabulacao_id',
        'nome_base',
        'arquivo_nome',
        'tipo_layout',
        'status',
        'total_itens',
        'total_novos',
        'total_duplicados',
        'total_invalidos',
        'total_importados',
        'total_resolvidos',
        'importados_em',
        'concluida_em',
    ];

    protected $casts = [
        'importados_em' => 'datetime',
        'concluida_em' => 'datetime',
    ];

    public function itens()
    {
        return $this->hasMany(MailingImportacaoItem::class, 'mailing_importacao_id');
    }
}
