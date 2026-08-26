<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadReservatorioExecucaoItem extends Model
{
    protected $table = 'lead_reservatorio_execucao_itens';

    protected $fillable = [
        'execucao_id', 'reservatorio_item_id', 'contato_id', 'vendedor_id', 'resultado', 'motivo',
    ];
}
