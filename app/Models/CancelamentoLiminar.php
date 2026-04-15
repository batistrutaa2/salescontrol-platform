<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CancelamentoLiminar extends Model
{
    protected $table = 'cancelamentos_liminares';

    protected $fillable = [
        'empresa_id',
        'venda_id',
        'beneficiario_tipo',
        'beneficiario_id',
        'nome_contrato',
        'responsavel_id',
        'status',
        'fase',
        'data_envio',
        'status_honorarios',
        'status_recebimento',
        'valor_recebimento',
        'observacoes',
    ];

    protected $casts = [
        'valor_recebimento' => 'decimal:2',
    ];

    /**
     * Formata todas as datas do model para o padrão BR ao serializar (toArray/toJson).
     * Recebe um DateTimeInterface (Carbon já resolvido) — nunca uma string d/m/Y —
     * evitando o Carbon::parse() sobre formato não reconhecido.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return Carbon::instance($date)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s');
    }

    // ---------------------------------------------------------------
    // data_envio: sem cast 'date' (removido para evitar conflito com
    // serializeDate). O banco armazena Y-m-d; o accessor converte para d/m/Y.
    // ---------------------------------------------------------------
    public function getDataEnvioAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return Carbon::createFromFormat('Y-m-d', substr($value, 0, 10))->format('d/m/Y');
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ---------------------------------------------------------------
    // Relacionamentos
    // ---------------------------------------------------------------
    public function venda()
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function documentos()
    {
        return $this->hasMany(CancelamentoLiminarDocumento::class, 'cancelamento_liminar_id');
    }

    public function historico()
    {
        return $this->hasMany(CancelamentoLiminarHistorico::class, 'cancelamento_liminar_id')
            ->orderBy('created_at', 'desc');
    }

    public function getBeneficiario()
    {
        if ($this->beneficiario_tipo === 'TITULAR') {
            return VendaTitular::find($this->beneficiario_id);
        }
        return VendaDependente::find($this->beneficiario_id);
    }
}
