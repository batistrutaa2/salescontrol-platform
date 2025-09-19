<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\BirthdayGreetingNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SendBirthdayGreetings extends Command
{
    protected $signature = 'birthdays:send {--dry-run}';
    protected $description = 'Envia parabéns de aniversário para usuários que fazem aniversário hoje';

    public function handle(): int
    {
        $tz = 'America/Sao_Paulo';
        $today = Carbon::now($tz);
        $month = (int)$today->format('m');
        $day   = (int)$today->format('d');

        // Trata 29/02 em anos não bissextos (opção: enviar em 28/02)
        if (!$today->isLeapYear() && $month === 2 && $day === 28) {
            $isFeb28ForLeap = true;
        } else {
            $isFeb28ForLeap = false;
        }

        $lock = Cache::lock("birthdays:{$today->toDateString()}", 600);
        if (!$lock->get()) {
            $this->warn('Já está rodando. Abortando para evitar duplicidade.');
            return self::SUCCESS;
        }

        try {
            $users = User::query()
                ->whereNotNull('data_nascimento')
                ->where(function ($q) use ($month, $day, $isFeb28ForLeap) {
                    $q->whereMonth('data_nascimento', $month)->whereDay('data_nascimento', $day);
                    if ($isFeb28ForLeap) {
                        $q->orWhere(function ($qq) {
                            $qq->whereMonth('data_nascimento', 2)->whereDay('data_nascimento', 29);
                        });
                    }
                })
                ->where(function ($q) {
                    $q->whereNull('data_nascimento_notified_at')
                      ->orWhereYear('data_nascimento_notified_at', '<', now('America/Sao_Paulo')->year);
                })
                ->get();

            $count = 0;
            foreach ($users as $user) {
                $msg = $this->makeMessage($user->name);

                if ($this->option('dry-run')) {
                    $this->line("DRY-RUN -> {$user->email} | {$msg}");
                    continue;
                }

                DB::transaction(function () use ($user, $msg, $today) {
                    $user->notify(new BirthdayGreetingNotification($msg));
                    $user->forceFill(['data_nascimento_notified_at' => $today->toDateString()])->save();
                });

                $count++;
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
}

