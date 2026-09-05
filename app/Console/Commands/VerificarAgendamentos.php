<?php

namespace App\Console\Commands;

use App\Models\Agendamento;
use App\Notifications\AgendamentoNotificacao;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Console\Command;

class VerificarAgendamentos extends Command
{
    protected $signature = 'verificar:agendamentos';

    protected $description = 'Verifica agendamentos que devem ser notificados agora';

    public function handle()
    {
        $agora = Carbon::now()->format('Y-m-d H:i:00');

        $agendamentos = Agendamento::withoutGlobalScopes()
            ->where('horario_agendamento', $agora)
            ->where('notificado', 'N')
            ->get();

        foreach ($agendamentos as $agendamento) {
            app(TenantContext::class)->run((int) $agendamento->empresa_id, function () use ($agendamento) {
                $agendamento->load([
                    'user' => fn ($query) => $query->tenantMember((int) $agendamento->empresa_id),
                ]);

                if (! $agendamento->user) {
                    return;
                }

                $agendamento->user->notify(new AgendamentoNotificacao($agendamento));
                $agendamento->notificado = 'Y';
                $agendamento->save();
            });
        }

        $this->info("Verificação concluída em $agora");
    }
}
