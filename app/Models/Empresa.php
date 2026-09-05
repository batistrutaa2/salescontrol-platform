<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'empresas';

    protected $fillable = [
        'nome_fantasia',
        'cpf_cnpj',
        'cpf_cnpj_normalizado',
        'telefone',
        'email',
        'whatsapp_token',
        'reuniao_horario_inicio',
        'reuniao_horario_fim',
        'reuniao_duracao_minutos',
        'escola_percentual_conclusao',
        'tv_percentual_atencao',
        'tv_percentual_bom',
        'demandas_concluidas_janela_dias',
        'financeiro_mrr_janela_meses',
        'financeiro_historico_meses',
        'financeiro_previsao_meses',
        'pos_venda_aniversarios_janela_dias',
    ];

    protected $hidden = [
        'whatsapp_token',
    ];

    protected function casts(): array
    {
        return [
            'whatsapp_token' => 'encrypted',
            'reuniao_duracao_minutos' => 'integer',
            'escola_percentual_conclusao' => 'integer',
            'tv_percentual_atencao' => 'integer',
            'tv_percentual_bom' => 'integer',
            'demandas_concluidas_janela_dias' => 'integer',
            'financeiro_mrr_janela_meses' => 'integer',
            'financeiro_historico_meses' => 'integer',
            'financeiro_previsao_meses' => 'integer',
            'pos_venda_aniversarios_janela_dias' => 'integer',
        ];
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }
}
