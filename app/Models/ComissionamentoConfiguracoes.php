<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ComissionamentoConfiguracoes extends Model
{
    use HasFactory;
    protected $table = "comissionamento_configuracao";
    protected $fillable = [
        'id',
        'empresa_id',
        'user_id',
        'percentual',
        'periodicidade',
        'imposto',
        'grade',
        'salario',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'percentual' => 'decimal:2',
        'imposto' => 'decimal:2',
        'salario' => 'decimal:2',
    ];

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
