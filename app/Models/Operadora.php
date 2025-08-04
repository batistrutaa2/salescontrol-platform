<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operadora extends Model
{
    use HasFactory;
    protected $table = 'operadoras';

    protected $fillable = [
        'id',
        'empresa_id',
        'contato_id',
        'nome',
        'status',
        'created_at',
        'updated_at',
    ];
}
