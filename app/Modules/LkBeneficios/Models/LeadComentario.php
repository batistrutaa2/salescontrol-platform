<?php

namespace App\Modules\LkBeneficios\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LeadComentario extends Model
{
    protected $table = 'lk_beneficios_lead_comentarios';

    protected $fillable = [
        'empresa_id',
        'lead_id',
        'user_id',
        'anotacao',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
