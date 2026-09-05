<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantIntegrationCredential extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = [
        'empresa_id',
        'user_id',
        'name',
        'token_hash',
        'abilities',
        'active',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function allows(string $ability): bool
    {
        return in_array($ability, $this->abilities ?? [], true);
    }
}
