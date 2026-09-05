<?php

namespace App\Models\People\Assertiva;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\RequiresTenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssertivaEmail extends Model
{
    use BelongsToTenant, RequiresTenantContext;

    protected $connection = 'people_db';

    protected $table = 'assertiva_emails';

    protected $fillable = [
        'empresa_id',
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
