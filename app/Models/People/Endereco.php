<?php

namespace App\Models\People;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Endereco extends Model
{
    protected $connection = 'people_db';
    protected $table = 'enderecos';

    protected $fillable = [
        'pessoa_id',
        'endereco',
        'bairro',
        'cidade',
        'uf',
        'cep',
        'tipo',
        'ranking',
    ];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class);
    }
}
