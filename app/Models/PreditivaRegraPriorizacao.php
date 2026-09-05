<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PreditivaRegraPriorizacao extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $table = 'preditiva_regras_priorizacao';

    protected $fillable = [
        'empresa_id',
        'nome',
        'descricao',
        'campo',
        'operador',
        'valor',
        'peso',
        'ativo',
        'ordem',
    ];

    /**
     * Campos permitidos para priorização com seus tipos e operadores válidos
     */
    public const CAMPOS_PERMITIDOS = [
        'valor_plano_atual' => [
            'tipo' => 'decimal',
            'label' => 'Valor do Plano Atual',
            'operadores' => ['=', '!=', '>', '>=', '<', '<='],
        ],
        'entidade' => [
            'tipo' => 'string',
            'label' => 'Entidade',
            'operadores' => ['=', '!=', 'LIKE', 'IN'],
        ],
        'categoria' => [
            'tipo' => 'string',
            'label' => 'Categoria',
            'operadores' => ['=', '!=', 'LIKE', 'IN'],
        ],
        'plano' => [
            'tipo' => 'string',
            'label' => 'Plano',
            'operadores' => ['=', '!=', 'LIKE', 'IN'],
        ],
        'vidas' => [
            'tipo' => 'integer',
            'label' => 'Quantidade de Vidas',
            'operadores' => ['=', '!=', '>', '>=', '<', '<='],
        ],
        'plano_ativo' => [
            'tipo' => 'enum',
            'label' => 'Plano Ativo',
            'operadores' => ['=', '!='],
            'valores' => ['Y', 'N'],
        ],
        'possui_cnpj' => [
            'tipo' => 'enum',
            'label' => 'Possui CNPJ',
            'operadores' => ['=', '!='],
            'valores' => ['Y', 'N'],
        ],
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }

    public function scopeAtivas($query)
    {
        return $query->where('ativo', 'Y');
    }

    public function scopeOrdenadas($query)
    {
        return $query->orderBy('ordem', 'asc');
    }
}
