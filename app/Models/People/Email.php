<?php

namespace App\Models\People;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Email extends Model
{
    protected $connection = 'people_db';
    protected $table = 'emails';

    protected $fillable = [
        'pessoa_id',
        'email',
        'ranking',
        'possui_cookie',
    ];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class);
    }
}
