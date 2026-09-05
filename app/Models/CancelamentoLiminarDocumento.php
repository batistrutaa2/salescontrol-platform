<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class CancelamentoLiminarDocumento extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $table = 'cancelamentos_liminares_documentos';

    protected $fillable = [
        'cancelamento_liminar_id',
        'empresa_id',
        'tipo_documento',
        'nome_original',
        'path_s3',
        'uploaded_by',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return Carbon::instance($date)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s');
    }

    public function cancelamentoLiminar()
    {
        return $this->belongsTo(CancelamentoLiminar::class, 'cancelamento_liminar_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getTipoDocumentoLabelAttribute(): string
    {
        return match ($this->tipo_documento) {
            'CARTEIRINHA' => 'Carteirinha',
            'CARTAO_CNPJ' => 'Cartão do CNPJ',
            'COMPROVANTE_PAGAMENTO' => 'Comprovante de Pagamento',
            'CONTRATO_SOCIAL' => 'Contrato Social',
            'RETORNO_CANCELAMENTO' => 'Retorno do Cancelamento',
            'RG_CLIENTE' => 'RG da Cliente',
            'PRINT_PROTOCOLO' => 'Print do Protocolo',
            'AUDIO_OPERADORA' => 'Áudio da operadora',
            'CONCLUSAO_LIMINAR' => 'Decisão da Liminar',
            default => $this->tipo_documento,
        };
    }
}
