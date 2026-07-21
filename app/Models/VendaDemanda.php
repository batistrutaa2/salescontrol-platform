<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendaDemanda extends Model
{
    use HasFactory;

    protected $table = 'venda_demandas';

    protected $fillable = [
        'venda_id',
        'titular_id',
        'cnpj',
        'operadora_anterior_id',
        'empresa_id',
        'created_by',
        'origem',
        'concluida_por',
        'tipo',
        'titulo',
        'descricao',
        'meta',
        'credencial_id',
        'responsavel_id',
        'status',
        'concluida_em',
    ];

    protected $casts = [
        'concluida_em' => 'datetime',
        'meta' => 'array',
    ];

    public function venda()
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }

    public function titular()
    {
        return $this->belongsTo(VendaTitular::class, 'titular_id');
    }

    public function operadoraAnterior()
    {
        return $this->belongsTo(Operadora::class, 'operadora_anterior_id');
    }

    public function credencial()
    {
        return $this->belongsTo(CredencialAcesso::class, 'credencial_id');
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function criador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function concluidaPor()
    {
        return $this->belongsTo(User::class, 'concluida_por');
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }

    public function getConcluidaEmAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }
}
