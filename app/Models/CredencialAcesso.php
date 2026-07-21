<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CredencialAcesso extends Model
{
    use HasFactory;

    protected $table = 'credenciais_acesso';

    protected $fillable = [
        'empresa_id',
        'operadora_id',
        'venda_id',
        'cnpj',
        'tipo',
        'nome',
        'login',
        'senha',
        'observacao',
        'status',
        'created_by',
        'updated_by',
    ];

    // Padroniza o nome da empresa/pessoa sempre em MAIÚSCULAS (e sem espaços nas pontas).
    // Centraliza a regra para criação, edição, importação por tela e comando.
    public function setNomeAttribute($value): void
    {
        $this->attributes['nome'] = $value === null ? null : mb_strtoupper(trim($value), 'UTF-8');
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }

    public function operadora()
    {
        return $this->belongsTo(Operadora::class, 'operadora_id');
    }

    public function venda()
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }

    public function criadoPor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function atualizadoPor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function historico()
    {
        return $this->hasMany(CredencialAcessoHistorico::class, 'credencial_id')->orderByDesc('id');
    }
}
