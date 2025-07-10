<?php

namespace App\Models\People;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Carro extends Model
{
    protected $connection = 'people_db';
    protected $table = 'carros';

    protected $fillable = [
        'pessoa_id',
        'placa',
        'marca',
        'ano_fabricacao',
        'ano_modelo',
        'renavan',
        'chassi',
        'data_licenciamento',
        'ranking',
    ];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class);
    }


}
