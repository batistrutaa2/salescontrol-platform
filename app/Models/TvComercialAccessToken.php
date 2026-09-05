<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TvComercialAccessToken extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = [
        'empresa_id',
        'created_by',
        'token_hash',
        'token_encrypted',
        'active',
        'last_used_at',
    ];

    protected $hidden = [
        'token_hash',
        'token_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'token_encrypted' => 'encrypted',
            'active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }
}
