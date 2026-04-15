<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CancelamentoLiminarHistorico extends Model
{
    protected $table = 'cancelamentos_liminares_historico';

    protected $fillable = [
        'cancelamento_liminar_id',
        'user_id',
        'campo_alterado',
        'valor_anterior',
        'valor_novo',
        'observacao',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return Carbon::instance($date)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s');
    }

    public function cancelamentoLiminar()
    {
        return $this->belongsTo(CancelamentoLiminar::class, 'cancelamento_liminar_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
