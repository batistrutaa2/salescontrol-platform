<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendaDocumento extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $table = 'venda_documentos';

    protected $fillable = [
        'venda_id', 'empresa_id', 'uploaded_by', 'deleted_by', 'client_upload_id',
        'nome_original', 'nome_remoto', 'mime_type', 'tamanho', 'sha256',
        'caminho_temporario', 'diretorio_remoto', 'caminho_remoto', 'status',
        'tentativas', 'erro', 'enviado_em', 'expira_em', 'deleted_at',
        'verificado_em', 'processamento_iniciado_em', 'ultima_tentativa_em',
    ];

    protected $casts = [
        'tamanho' => 'integer',
        'tentativas' => 'integer',
        'enviado_em' => 'datetime',
        'expira_em' => 'datetime',
        'deleted_at' => 'datetime',
        'verificado_em' => 'datetime',
        'processamento_iniciado_em' => 'datetime',
        'ultima_tentativa_em' => 'datetime',
    ];

    public function venda()
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
