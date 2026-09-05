<?php

namespace App\Models;

use App\Models\Concerns\ValidatesTenantUserReferences;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComissionamentoConfiguracoes extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use HasFactory;
    use ValidatesTenantUserReferences;

    protected $table = 'comissionamento_configuracao';

    protected $fillable = [
        'id',
        'empresa_id',
        'user_id',
        'percentual',
        'percentual_angariacao',
        'periodicidade',
        'imposto',
        'grade',
        'salario',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'percentual' => 'decimal:2',
        'percentual_angariacao' => 'decimal:2',
        'imposto' => 'decimal:2',
        'salario' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $configuracao): void {
            if (self::shouldValidateTenantReference($configuracao, 'user_id')) {
                self::assertTenantMember((int) $configuracao->empresa_id, $configuracao->user_id, 'configuração de comissão');
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
