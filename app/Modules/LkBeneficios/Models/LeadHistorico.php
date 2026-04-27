<?php

namespace App\Modules\LkBeneficios\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadHistorico extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'lk_beneficios_lead_historico';

    protected $fillable = [
        'lead_id',
        'user_id',
        'status_anterior',
        'status_novo',
        'observacao',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
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
