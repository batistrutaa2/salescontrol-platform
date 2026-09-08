<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\UserRolesSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BootstrapPlatformAdmin extends Command
{
    protected $signature = 'platform:bootstrap-admin
        {--name= : Nome do administrador (ou PLATFORM_ADMIN_NAME)}
        {--email= : E-mail do administrador (ou PLATFORM_ADMIN_EMAIL)}';

    protected $description = 'Cria, uma única vez, o administrador master da plataforma sem vinculá-lo a uma empresa';

    public function handle(): int
    {
        $name = trim((string) ($this->option('name') ?: config('tenancy.bootstrap.admin_name')));
        $email = mb_strtolower(trim((string) ($this->option('email') ?: config('tenancy.bootstrap.admin_email'))));

        $identity = Validator::make(compact('name', 'email'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        if ($identity->fails()) {
            $this->components->error($identity->errors()->first());

            return self::FAILURE;
        }

        $existing = User::query()->where('email', $email)->first();

        if ($existing) {
            if (! $existing->isPlatformAdmin()) {
                $this->components->error('O e-mail informado já pertence a um usuário sem acesso master. Nenhuma permissão foi alterada.');

                return self::FAILURE;
            }

            $this->components->info('O administrador master já existe; nenhuma credencial foi alterada.');

            return self::SUCCESS;
        }

        $password = (string) config('tenancy.bootstrap.admin_password');
        $credentials = Validator::make(['password' => $password], [
            'password' => ['required', 'string', 'min:12'],
        ]);

        if ($credentials->fails()) {
            $this->components->error('Defina PLATFORM_ADMIN_PASSWORD com pelo menos 12 caracteres para o primeiro bootstrap.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $email, $password): void {
            (new UserRolesSeeder)->run();

            User::query()->create([
                'name' => $name,
                'email' => $email,
                'email_verified_at' => now(),
                'password' => Hash::make($password),
                'empresa_id' => null,
                'user_role_id' => UserRole::DEVELOPER,
                'is_platform_admin' => true,
                'ativo' => 'Y',
            ]);
        });

        $this->components->info('Administrador master criado. No primeiro login, cadastre e selecione a primeira empresa.');

        return self::SUCCESS;
    }
}
