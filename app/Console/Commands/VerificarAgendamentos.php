<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agendamento;
use App\Models\User;
use App\Notifications\AgendamentoNotificacao;
use Carbon\Carbon;

class VerificarAgendamentos extends Command
{
    protected $signature = 'verificar:agendamentos';
    protected $description = 'Verifica agendamentos que devem ser notificados agora';

    public function handle()
    {
        $agora = Carbon::now()->format('Y-m-d H:i:00');

        $agendamentos = Agendamento::where('horario_agendamento', $agora)
            ->where('notificado', 'N')
            ->with('user') // relacionamento
            ->get();

        foreach ($agendamentos as $agendamento) {
            if ($agendamento->user) {
                $agendamento->user->notify(new AgendamentoNotificacao($agendamento));
                $agendamento->notificado = 'Y';
                $agendamento->save();
            }
        }

        $this->info("Verificação concluída em $agora");
    }
}
