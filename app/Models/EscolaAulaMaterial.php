<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EscolaAulaMaterial extends Model
{
    protected $table = 'escola_aula_materiais';

    protected $fillable = [
        'empresa_id',
        'escola_aula_id',
        'titulo',
        'path_s3',
        'nome_original',
        'mime',
        'tamanho_bytes',
        'uploaded_by',
    ];

    protected $casts = [
        'tamanho_bytes' => 'integer',
    ];

    public function aula()
    {
        return $this->belongsTo(EscolaAula::class, 'escola_aula_id');
    }
}
