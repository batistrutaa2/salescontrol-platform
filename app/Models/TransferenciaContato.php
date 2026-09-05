<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferenciaContato extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use HasFactory;

    protected $table = 'transferencia_contatos';

    protected $fillable = [
        'id',
        'empresa_id',
        'contato_id',
        'de_users_id',
        'para_user_id',
        'responsavel_transferencia',
        'created_at',
        'updated_at',
    ];
}
