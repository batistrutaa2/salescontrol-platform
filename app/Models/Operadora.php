<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class Operadora extends Model
{
    use HasFactory;
    protected $table = 'operadoras';

    protected $fillable = [
        'id',
        'empresa_id',
        'nome',
        'status',
        'created_at',
        'updated_at',
    ];

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }
}
