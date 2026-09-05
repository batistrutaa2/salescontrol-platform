<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operadora extends Model
{
    use BelongsToTenant, HasFactory;

    public const COPARTICIPACAO_SIM_NAO = 'SIM_NAO';

    public const COPARTICIPACAO_PARCIAL_COMPLETA = 'PARCIAL_COMPLETA';

    protected $table = 'operadoras';

    protected $fillable = [
        'id',
        'empresa_id',
        'nome',
        'diretorio_documentos',
        'coparticipacao_formato',
        'angariacao_padrao',
        'iof_percentual',
        'cor_marca',
        'logo_path',
        'app_ios_url',
        'app_android_url',
        'status',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'angariacao_padrao' => 'boolean',
            'iof_percentual' => 'decimal:2',
        ];
    }

    public function valoresCoparticipacao(): array
    {
        return $this->coparticipacao_formato === self::COPARTICIPACAO_PARCIAL_COMPLETA
            ? ['PARCIAL', 'COMPLETA']
            : ['Y', 'N'];
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
