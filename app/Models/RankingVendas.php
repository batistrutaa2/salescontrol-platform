<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RankingVendas extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use HasFactory;

    protected $table = 'ranking_de_vendas';

    protected $fillable = [
        'id',
        'empresa_id',
        'user_id',
        '_id',
        'name',
        'cpf',
        'company_id',
        'teams',
        'email',
        'created_at',
        'updated_at',
    ];
}
