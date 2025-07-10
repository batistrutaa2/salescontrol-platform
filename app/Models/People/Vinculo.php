<?php

namespace App\Models\People;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vinculo extends Model
{
    protected $connection = 'people_db';
    protected $table = 'vinculos';

    protected $fillable = [
        'pessoa_id',
        'cpf_vinculo',
        'nome_vinculo',
        'tipo_vinculo',
    ];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class);
    }
}
