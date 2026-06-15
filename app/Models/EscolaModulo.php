<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EscolaModulo extends Model
{
    protected $table = 'escola_modulos';

    protected $fillable = [
        'empresa_id',
        'titulo',
        'descricao',
        'slug',
        'capa_path',
        'ordem',
        'ativo',
        'created_by',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'ordem' => 'integer',
    ];

    public function aulas()
    {
        return $this->hasMany(EscolaAula::class)->orderBy('ordem');
    }

    public function aulasAtivas()
    {
        return $this->hasMany(EscolaAula::class)->where('ativo', true)->orderBy('ordem');
    }

    public function criador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
