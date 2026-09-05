<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('tenancy.bootstrap.admin_password');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException('Defina PLATFORM_ADMIN_PASSWORD antes de executar o seeder da plataforma.');
        }

        $empresa = Empresa::query()
            ->where('email', config('tenancy.bootstrap.company_email'))
            ->firstOrFail();

        User::query()->updateOrCreate(
            ['email' => config('tenancy.bootstrap.admin_email')],
            [
                'empresa_id' => $empresa->id,
                'user_role_id' => UserRole::DEVELOPER,
                'name' => config('tenancy.bootstrap.admin_name'),
                'email_verified_at' => now(),
                'password' => Hash::make($password),
                'ativo' => 'Y',
                'is_platform_admin' => true,
            ]
        );
    }
}
