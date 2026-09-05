<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\User;
use App\Notifications\BirthdayGreetingNotification;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SendBirthdayGreetings extends Command
{
    protected $signature = 'birthdays:send {--dry-run} {--empresa= : Limita a uma empresa específica}';

    protected $description = 'Envia parabéns de aniversário para usuários que fazem aniversário hoje';

    public function handle(): int
    {
        $today = Carbon::now('America/Sao_Paulo');
        $month = (int) $today->format('m');
        $day = (int) $today->format('d');
        $isFeb28ForLeap = ! $today->isLeapYear() && $month === 2 && $day === 28;

        $lock = Cache::lock("birthdays:{$today->toDateString()}", 600);
        if (! $lock->get()) {
            $this->warn('Já está rodando. Abortando para evitar duplicidade.');

            return self::SUCCESS;
        }

        try {
            $empresas = $this->empresasSelecionadas();
            if ($empresas === null) {
                return self::FAILURE;
            }

            $context = app(TenantContext::class);
            $context->clear();
            $count = 0;

            try {
                foreach ($empresas as $empresaId) {
                    $context->run($empresaId, function () use ($month, $day, $isFeb28ForLeap, $today, &$count): void {
                        $users = User::query()
                            ->tenantMember(app(TenantContext::class)->id())
                            ->where('ativo', 'Y')
                            ->whereNotNull('data_nascimento')
                            ->where(function ($query) use ($month, $day, $isFeb28ForLeap) {
                                $query->whereMonth('data_nascimento', $month)->whereDay('data_nascimento', $day);
                                if ($isFeb28ForLeap) {
                                    $query->orWhere(function ($leapQuery) {
                                        $leapQuery->whereMonth('data_nascimento', 2)->whereDay('data_nascimento', 29);
                                    });
                                }
                            })
                            ->where(function ($query) use ($today) {
                                $query->whereNull('data_nascimento_notified_at')
                                    ->orWhereYear('data_nascimento_notified_at', '<', $today->year);
                            })
                            ->get();

                        foreach ($users as $user) {
                            $message = $this->makeMessage($user->name);
                            $count++;

                            if ($this->option('dry-run')) {
                                $this->line("DRY-RUN -> {$user->email} | {$message}");

                                continue;
                            }

                            DB::transaction(function () use ($user, $message, $today) {
                                $user->notify(new BirthdayGreetingNotification($message));
                                $user->forceFill(['data_nascimento_notified_at' => $today->toDateString()])->save();
                            });
                        }
                    });
                }
            } finally {
                $context->clear();
            }

            $this->info($this->option('dry-run')
                ? "Dry-run concluído. {$count} usuários elegíveis."
                : "Parabéns enviados para {$count} usuário(s).");

            return self::SUCCESS;
        } finally {
            optional($lock)->release();
        }
    }

    private function makeMessage(?string $nome): string
    {
        $nome = $nome ?: 'Você';
        $templates = [
            "Hoje é dia de celebrar você, {$nome}! 🎂 Que este novo ciclo traga saúde, foco e muitas vitórias. Conte com a gente!",
            "Parabéns, {$nome}! 🎉 Desejamos um ano cheio de conquistas, boas ideias e projetos de sucesso. Aproveite seu dia!",
            "Feliz aniversário, {$nome}! 🥳 Que não falte alegria, prosperidade e boas surpresas neste novo capítulo.",
        ];

        return $templates[array_rand($templates)];
    }

    private function empresasSelecionadas(): ?array
    {
        $empresaId = filter_var($this->option('empresa'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($this->option('empresa') !== null && $empresaId === false) {
            $this->error('Empresa inválida.');

            return null;
        }

        $ids = Empresa::query()
            ->when($empresaId, fn ($query) => $query->whereKey($empresaId))
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($empresaId && $ids === []) {
            $this->error('Empresa inválida.');

            return null;
        }

        return $ids;
    }
}
