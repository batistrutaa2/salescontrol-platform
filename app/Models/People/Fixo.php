<?php

namespace App\Models\People;

use App\Models\People\Pessoa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Fixo extends Model
{
    protected $connection = 'people_db';
    protected $table = 'fixos';

    protected $fillable = [
        'pessoa_id',
        'ddd',
        'numero',
    ];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class);
    }
}
