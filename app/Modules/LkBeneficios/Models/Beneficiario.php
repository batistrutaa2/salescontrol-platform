<?php

namespace App\Modules\LkBeneficios\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Beneficiario extends Model
{
    use HasFactory;

    protected $table = 'lk_beneficios_beneficiarios';

    protected $fillable = [
        'contrato_id',
        'tipo',
        'nome',
        'cpf',
        'data_nascimento',
        'grau_parentesco',
        'telefone',
        'email',
        'status',
        'data_inclusao',
        'data_exclusao',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'data_inclusao' => 'date',
        'data_exclusao' => 'date',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}
