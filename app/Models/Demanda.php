<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Demanda extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'created_by',
        'assigned_to',
        'titulo',
        'descricao',
        'prioridade',
        'status',
        'data_limite',
        'concluida_em'
    ];

    protected $casts = [
        'data_limite' => 'date',
        'concluida_em' => 'datetime',
    ];

    public function criador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function responsavel()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
