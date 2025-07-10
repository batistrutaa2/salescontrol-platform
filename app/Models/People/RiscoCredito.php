<?php

namespace App\Models\People;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RiscoCredito extends Model
{
    protected $connection = 'people_db';
    protected $table = 'riscos_credito';

    protected $fillable = [
        'pessoa_id',
        'cpf_cnpj',
        'score_credito',
    ];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class);
    }
}
