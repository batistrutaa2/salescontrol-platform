<?php

namespace App\Models;

use App\Models\Concerns\ValidatesTenantUserReferences;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ramais extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use HasFactory;
    use ValidatesTenantUserReferences;

    protected $table = 'ramais';

    protected $fillable = [
        'id',
        'empresa_id',
        'user_id',
        'ramal',
        'created_at',
        'updated_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $ramal): void {
            if (self::shouldValidateTenantReference($ramal, 'user_id')) {
                self::assertTenantMember((int) $ramal->empresa_id, $ramal->user_id, 'ramal');
            }
        });
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
