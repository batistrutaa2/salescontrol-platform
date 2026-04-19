<?php

namespace App\Modules\LkBeneficios\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apolice extends Model
{
    use HasFactory;

    protected $table = 'lk_beneficios_apolices';

    protected $fillable = [
        'contrato_id',
        'versao',
        'arquivo_path',
        'data_emissao',
        'observacao',
    ];

    protected $casts = [
        'data_emissao' => 'date',
        'versao' => 'integer',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}
