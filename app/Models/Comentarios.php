<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comentarios extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'comentarios';

    protected $fillable = [
        'id',
        'empresa_id',
        'user_id',
        'contato_id',
        'anotacao',
        'legado',
        'visivel',
        'supervisao',
        'fixado',
        'fixado_em',
        'editado_em',
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
