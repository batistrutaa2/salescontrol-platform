<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RenovacaoInteracao extends Model
{
    protected $table = 'renovacao_interacoes';
    protected $guarded = [];
    protected $casts = ['metadados' => 'array'];
    public function usuario() { return $this->belongsTo(User::class, 'user_id'); }
}
