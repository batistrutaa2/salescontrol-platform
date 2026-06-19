<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class CredencialAcessoHistorico extends Model
{
    protected $table = 'credenciais_acesso_historico';

    public $timestamps = false;

    protected $fillable = [
        'empresa_id',
        'credencial_id',
        'user_id',
        'acao',
        'campo',
        'valor_anterior',
        'valor_novo',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return Carbon::instance($date)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s');
    }

    public function credencial()
    {
        return $this->belongsTo(CredencialAcesso::class, 'credencial_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
