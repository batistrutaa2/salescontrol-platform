<?php

namespace App\Models\People\Assertiva;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssertivaEmail extends Model
{
    protected $connection = 'people_db';

    protected $table = 'assertiva_emails';

    protected $fillable = [
        'assertiva_pessoa_id',
        'assertiva_empresa_id',
        'email',
        'email_normalizado',
    ];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(AssertivaPessoa::class, 'assertiva_pessoa_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(AssertivaEmpresa::class, 'assertiva_empresa_id');
    }
}
