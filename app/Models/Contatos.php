<?php

namespace App\Models;

use App\Helpers\Helpers;
use App\Models\Concerns\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contatos extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'contatos';

    protected $fillable = [
        'id',
        'id_operacao',
        'empresa_id',
        'user_import_id',
        'nome_base',
        'status',
        'nome_cliente',
        'data_nascimento',
        'cpf',
        'plano',
        'categoria',
        'entidade',
        'telefone1',
        'telefone2',
        'telefone3',
        'email',
        'idades',
        'valor_plano_atual',
        'valor_negociacao',
        'ultimo_contato_preditiva',
        'created_at',
        'updated_at',
        'is_ads',
        'tipo_criativo',
        'tipo_layout',
        'vidas',
        'plano_ativo',
        'possui_cnpj',
    ];

    public function getDataNascimentoAttribute($value)
    {
        if (strpos($value, '/') === false && ! is_null($value) && $value !== '') {
            return Helpers::excelDateToPhpDate($value);
        }

        return $value;
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
