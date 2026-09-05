<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plano extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'planos';

    protected $fillable = [
        'id',
        'empresa_id',
        'operadora_id',
        'nome',
        'status',
        'acomodacao',
        'created_at',
        'updated_at',
    ];

    public function operadora()
    {
        return $this->belongsTo(Operadora::class, 'operadora_id');
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }
}
