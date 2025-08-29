<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estudos extends Model
{
    use HasFactory;

    protected $table = "estudos";

    protected $fillable = [
        'id',
        'empresa_id',
        'user_id',
        'titulo',
        'link_unico',
        'created_at',
        'updated_at'
    ];
}
