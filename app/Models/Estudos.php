<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estudos extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'estudos';

    protected $fillable = [
        'id',
        'empresa_id',
        'user_id',
        'titulo',
        'link_unico',
        'created_at',
        'updated_at',
    ];

    // Um estudo tem muitos itens
    public function itens()
    {
        return $this->hasMany(EstudoItens::class, 'estudo_id');
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }

    /**
     * Relacionamento com o usuário
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relacionamento com a empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
