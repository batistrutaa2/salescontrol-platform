<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'empresa_id',
        'user_role_id',
        'is_platform_admin',
        'email',
        'whatsapp',
        'ativo',
        'data_nascimento',
        'data_nascimento_notified_at',
        'password',
        'excluir_ranking',
        'escola_habilitada',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
            'data_nascimento' => 'date',
            'data_nascimento_notified_at' => 'date',
            'excluir_ranking' => 'boolean',
            'escola_habilitada' => 'boolean',
        ];
    }

    public function isPlatformAdmin(): bool
    {
        return (bool) $this->is_platform_admin
            || (int) $this->user_role_id === UserRole::DEVELOPER;
    }

    public function scopeTenantMember(Builder $query, int $empresaId): Builder
    {
        return $query
            ->where($this->qualifyColumn('empresa_id'), $empresaId)
            ->where($this->qualifyColumn('is_platform_admin'), false)
            ->where(function (Builder $roles) {
                $roles->whereNull($this->qualifyColumn('user_role_id'))
                    ->orWhere($this->qualifyColumn('user_role_id'), '!=', UserRole::DEVELOPER);
            });
    }

    /**
     * Identidades que podem aparecer em trilhas de auditoria do tenant.
     * O master pertence à empresa de origem da conta, mas pode ser o autor
     * legítimo de uma ação executada após selecionar outra empresa.
     */
    public function scopeTenantActor(Builder $query, int $empresaId): Builder
    {
        return $query->where(function (Builder $visibility) use ($empresaId) {
            $visibility->where($this->qualifyColumn('empresa_id'), $empresaId)
                ->orWhere($this->qualifyColumn('is_platform_admin'), true)
                ->orWhere($this->qualifyColumn('user_role_id'), UserRole::DEVELOPER);
        });
    }

    public function role()
    {
        return $this->belongsTo(Roles::class, 'user_role_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') : null;
    }

    public function vendas()
    {
        return $this->hasMany(Vendas::class, 'user_id');
    }
}
