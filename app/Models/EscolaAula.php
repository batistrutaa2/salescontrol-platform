<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EscolaAula extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $table = 'escola_aulas';

    protected $fillable = [
        'empresa_id',
        'escola_modulo_id',
        'titulo',
        'descricao',
        'video_path',
        'video_nome_original',
        'video_mime',
        'video_tamanho_bytes',
        'duracao_segundos',
        'ordem',
        'ativo',
        'created_by',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'ordem' => 'integer',
        'duracao_segundos' => 'integer',
        'video_tamanho_bytes' => 'integer',
    ];

    public function modulo()
    {
        return $this->belongsTo(EscolaModulo::class, 'escola_modulo_id');
    }

    public function materiais()
    {
        return $this->hasMany(EscolaAulaMaterial::class, 'escola_aula_id');
    }

    public function progressos()
    {
        return $this->hasMany(EscolaAulaProgresso::class, 'escola_aula_id');
    }

    public function progressoDoUsuario()
    {
        return $this->hasOne(EscolaAulaProgresso::class, 'escola_aula_id')
            ->where('user_id', Auth::id());
    }
}
