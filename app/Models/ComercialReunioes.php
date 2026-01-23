<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComercialReunioes extends Model
{
use HasFactory, SoftDeletes;

    protected $table = 'comercial_reunioes';

    protected $fillable = [
        'titulo',
        'user_id',
        'manager_id',
        'contato_id',
        'data_inicio',
        'data_final',
        'observacao',
        'location',
        'status'
    ];

    protected $casts = [
        'data_inicio' => 'datetime',
        'data_final' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function contato()
    {
        return $this->belongsTo(Contatos::class, 'contato_id');
    }
}
