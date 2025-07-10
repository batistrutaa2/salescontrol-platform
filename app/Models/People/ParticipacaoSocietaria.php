<?php

namespace App\Models\People;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ParticipacaoSocietaria extends Model
{
    protected $connection = 'people_db';
    protected $table = 'participacoes_societarias';

    protected $fillable = [
        'pessoa_id',
        'nome',
        'cnpj',
        'capital_social',
        'participacao_socio',
        'data_fundacao',
        'situacao_cadastral',
    ];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class);
    }
}
