<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * E-mail criado pelo backoffice para o cliente (conta do cliente na implantação).
 */
class VendaEmailCriado extends Model
{
    use HasFactory;

    protected $table = 'venda_emails_criados';

    protected $fillable = [
        'venda_id',
        'empresa_id',
        'titular_id',
        'email',
        'senha',
        'observacao',
        'created_by',
    ];

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Vendas::class, 'venda_id');
    }

    public function titular(): BelongsTo
    {
        return $this->belongsTo(VendaTitular::class, 'titular_id');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
