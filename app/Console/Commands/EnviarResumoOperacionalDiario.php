<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Jobs\EnviarResumoDiarioWhatsappJob;
use App\Models\Empresa;
use App\Models\User;
use App\Services\ResumoOperacionalFormatter;
use App\Services\ResumoOperacionalService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EnviarResumoOperacionalDiario extends Command
{
    protected $signature = 'resumo-diario:send
        {--dry-run : Não dispara Job, apenas imprime}
        {--empresa= : Restringe a uma empresa}
        {--user= : Restringe a um admin específico}
        {--data= : Reprocessa um dia (YYYY-MM-DD)}
        {--phone=* : Envia para números brutos (modo teste; --empresa obrigatória)}';

    protected $description = 'Envia o resumo operacional do dia por WhatsApp para administradores';

    public function handle(
        ResumoOperacionalService $service,
        ResumoOperacionalFormatter $formatter,
    ): int {
        $tz   = 'America/Sao_Paulo';
        $hoje = $this->option('data')
            ? Carbon::parse($this->option('data'), $tz)->startOfDay()
            : Carbon::now($tz)->startOfDay();

        $phones = (array) ($this->option('phone') ?? []);

        if (! empty($phones)) {
            return $this->runPhoneTeste($phones, $hoje, $service, $formatter);
        }

        return $this->runFluxoNormal($hoje, $service, $formatter);
    }

    private function runPhoneTeste(
        array $phones,
        Carbon $hoje,
        ResumoOperacionalService $service,
        ResumoOperacionalFormatter $formatter,
    ): int {
        $empresaId = $this->option('empresa');
        if (! $empresaId) {
            $this->error('--empresa é obrigatória quando --phone é informada.');
            return self::FAILURE;
        }

        $empresa = Empresa::find($empresaId);
        if (! $empresa) {
            $this->error("Empresa {$empresaId} não encontrada.");
            return self::FAILURE;
        }

        if (empty($empresa->whatsapp_token)) {
            $this->error("Empresa {$empresaId} não tem whatsapp_token configurado. Configure pelo Backoffice.");
            return self::FAILURE;
        }

        foreach ($phones as $phone) {
            if ($this->option('dry-run')) {
                $snapshot = $service->montarSnapshot((int) $empresaId, $hoje);
                $body     = $formatter->format(
                    $snapshot,
                    'Teste manual',
                    $empresa->nome_fantasia ?? 'Sua corretora',
                    $hoje,
                );
                $this->line("DRY-RUN -> {$phone}");
                $this->line($body);
                $this->line('---');
                continue;
            }

            EnviarResumoDiarioWhatsappJob::dispatchToPhone(
                (int) $empresaId,
                $phone,
                $hoje->toDateString(),
            );
            $this->info("Enfileirado para {$phone} (empresa {$empresaId}).");
        }

        return self::SUCCESS;
    }

    private function runFluxoNormal(
        Carbon $hoje,
        ResumoOperacionalService $service,
        ResumoOperacionalFormatter $formatter,
    ): int {
        $lock = Cache::lock("resumo-diario:{$hoje->toDateString()}", 1800);
        if (! $lock->get()) {
            $this->warn('Já está rodando. Abortando para evitar duplicidade.');
            return self::SUCCESS;
        }

        try {
            $empresas = Empresa::whereNotNull('whatsapp_token')
                ->when($this->option('empresa'), fn ($q, $id) => $q->where('id', $id))
                ->get();

            $totalEnfileirados = 0;
            foreach ($empresas as $empresa) {
                $admins = User::where('empresa_id', $empresa->id)
                    ->whereIn('user_role_id', [
                        UserRole::ADMINISTRATIVO,
                        UserRole::DEVELOPER,
                        UserRole::SUPERVISOR,
                    ])
                    ->where('ativo', 'Y')
                    ->whereNotNull('whatsapp')
                    ->when($this->option('user'), fn ($q, $u) => $q->where('id', $u))
                    ->get();

                if ($admins->isEmpty()) {
                    Log::info('ResumoDiario: empresa sem admin elegível', [
                        'empresa_id' => $empresa->id,
                    ]);
                    continue;
                }

                foreach ($admins as $admin) {
                    if ($this->option('dry-run')) {
                        $snapshot = $service->montarSnapshot((int) $empresa->id, $hoje);
                        $body     = $formatter->format(
                            $snapshot,
                            $admin->name,
                            $empresa->nome_fantasia ?? 'Sua corretora',
                            $hoje,
                        );
                        $this->line("DRY-RUN -> {$admin->whatsapp} (admin {$admin->id} / empresa {$empresa->id})");
                        $this->line($body);
                        $this->line('---');
                        continue;
                    }

                    EnviarResumoDiarioWhatsappJob::dispatch(
                        (int) $empresa->id,
                        (int) $admin->id,
                        $hoje->toDateString(),
                    );
                    $totalEnfileirados++;
                }
            }

            $this->info($this->option('dry-run')
                ? 'Dry-run concluído.'
                : "Resumo enfileirado para {$totalEnfileirados} admin(s).");

            return self::SUCCESS;
        } finally {
            optional($lock)->release();
        }
    }
}
