<?php

namespace App\Models\People;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Celular extends Model
{
    protected $connection = 'people_db';
    protected $table = 'celulares';

    protected $fillable = [
        'pessoa_id',
        'ddd',
        'numero',
        'plus',
        'ranking',
        'whatsapp',
    ];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class);
    }
}
