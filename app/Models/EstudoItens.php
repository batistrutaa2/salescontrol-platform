<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstudoItens extends Model
{
    use \App\Models\Concerns\BelongsToTenantThrough;
    use HasFactory;

    protected $table = 'estudo_itens';

    protected $fillable = [
        'id',
        'estudo_id',
        'operadora_id',
        'plano_id',
        'operadora_plano',
        'coparticipacao',
        'categoria',
        'reembolso_consulta',
        'created_at',
        'updated_at',
    ];

    public function estudo()
    {
        return $this->belongsTo(Estudos::class, 'estudo_id');
    }

    public function operadora()
    {
        return $this->belongsTo(Operadora::class, 'operadora_id');
    }

    public function plano()
    {
        return $this->belongsTo(Plano::class, 'plano_id');
    }

    // Cada item tem muitas vidas
    public function vidas()
    {
        return $this->hasMany(EstudoVidas::class, 'estudo_item_id');
    }
}
