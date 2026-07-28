<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class CancelamentoPosVendaHistorico extends Model
{
    protected $table = 'cancelamentos_pos_venda_historico';

    protected $fillable = [
        'cancelamento_pos_venda_id',
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

    public function cancelamento()
    {
        return $this->belongsTo(CancelamentoPosVenda::class, 'cancelamento_pos_venda_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
