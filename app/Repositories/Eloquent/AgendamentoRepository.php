<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Models\Agendamento;
use App\Models\Contatos;
use App\Repositories\Contracts\AgendamentoRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AgendamentoRepository implements AgendamentoRepositoryInterface
{
    public function __construct(private readonly Agendamento $model) {}

    public function updateOrCreate($contatoId, $horarioAgendamento, $observacao)
    {
        $empresaId = (int) app(\App\Support\TenantContext::class)->id();

        if (! Contatos::query()->whereKey((int) $contatoId)->exists()) {
            return false;
        }

        return $this->model->updateOrCreate(
            ['empresa_id' => $empresaId, 'contato_id' => (int) $contatoId],
            [
                'user_id' => Auth::id(),
                'horario_agendamento' => $horarioAgendamento,
                'observacao' => $observacao,
                'notificado' => 'N',
            ]
        );
    }

    public function getSchedules($rulerUser): Collection
    {
        $empresaId = (int) app(\App\Support\TenantContext::class)->id();
        $gestao = in_array((int) $rulerUser, [
            UserRole::ADMINISTRATIVO,
            UserRole::DEVELOPER,
            UserRole::SUPERVISOR,
        ], true);

        return $this->queryComRelacionamentos($empresaId)
            ->when(! $gestao, fn ($query) => $query->where('agendamentos.user_id', Auth::id()))
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('vendas')
                    ->whereColumn('vendas.contato_id', 'agendamentos.contato_id')
                    ->whereColumn('vendas.empresa_id', 'agendamentos.empresa_id');
            })
            ->get()
            ->map(function ($item) {
                if ($item->horario_agendamento) {
                    $item->horario_agendamento = Carbon::parse($item->horario_agendamento)->format('Y-m-d H:i:s');
                }

                return $item;
            });
    }

    public function LateAppointments(): Collection
    {
        return $this->lateAppointmentsQuery()->get();
    }

    public function LateAppointmentsParaNotificar(): Collection
    {
        return $this->lateAppointmentsQuery()->get();
    }

    public function appointmentsDelaystonotify(): Collection
    {
        return $this->lateAppointmentsQuery()
            ->where('agendamentos.notificado', 'N')
            ->get();
    }

    public function deleteSchedule($id): bool
    {
        $lead = $this->model
            ->where('empresa_id', app(\App\Support\TenantContext::class)->id())
            ->where('contato_id', (int) $id)
            ->first();

        return $lead ? (bool) $lead->delete() : false;
    }

    private function queryComRelacionamentos(int $empresaId): Builder
    {
        return $this->model
            ->select(
                'c.id',
                'b.name AS nome_corretor',
                'c.nome_cliente',
                'agendamentos.horario_agendamento',
                'agendamentos.observacao',
                'agendamentos.notificado'
            )
            ->join('users AS b', function ($join) {
                $join->on('b.id', '=', 'agendamentos.user_id')
                    ->on('b.empresa_id', '=', 'agendamentos.empresa_id')
                    ->where('b.is_platform_admin', false);
            })
            ->join('contatos AS c', function ($join) {
                $join->on('c.id', '=', 'agendamentos.contato_id')
                    ->on('c.empresa_id', '=', 'agendamentos.empresa_id');
            })
            ->where('agendamentos.empresa_id', $empresaId);
    }

    private function lateAppointmentsQuery(): Builder
    {
        return $this->queryComRelacionamentos((int) app(\App\Support\TenantContext::class)->id())
            ->where('agendamentos.horario_agendamento', '<', now())
            ->where('agendamentos.user_id', Auth::id());
    }
}
