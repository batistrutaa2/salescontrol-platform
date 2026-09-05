<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TenantServiceCredential extends Model
{
    use BelongsToTenant;

    public const SERVICE_VOIP_MAIS = 'voip_mais';

    protected $fillable = [
        'empresa_id',
        'service',
        'endpoint',
        'credentials',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'active' => 'boolean',
        ];
    }
}
