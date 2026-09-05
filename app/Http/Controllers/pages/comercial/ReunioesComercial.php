<?php

namespace App\Http\Controllers\pages\comercial;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Jobs\EnviarReuniaoAgendadaWhatsappJob;
use App\Models\ComercialReunioes;
use App\Models\Contatos;
use App\Models\ContatosCorretores;
use App\Models\Empresa;
use App\Models\User;
use App\Notifications\NovaReuniaoAgendada;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReunioesComercial extends Controller
{
    public function index()
    {
        $empresaId = $this->tenantId();
        $managers = User::query()->tenantMember($empresaId)
            ->whereIn('user_role_id', [UserRole::ADMINISTRATIVO, UserRole::SUPERVISOR])
            ->where('ativo', 'Y')
            ->get();
        $agendaSettings = Empresa::query()->findOrFail($empresaId);

        return view('content.pages.reunioes.index', compact('managers', 'agendaSettings'));
    }

    public function getReunioes()
    {
        $empresaId = $this->tenantId();

        $reunioes = $this->visibleMeetings(ComercialReunioes::with([
            'user' => fn ($query) => $query->tenantActor((int) $empresaId),
            'manager' => fn ($query) => $query->tenantMember((int) $empresaId),
            'contato' => fn ($query) => $query->where('contatos.empresa_id', $empresaId),
        ])->where('empresa_id', $empresaId), Auth::user())
            ->get()
            ->map(function ($reuniao) {
                $startDate = $reuniao->data_inicio ? $reuniao->data_inicio->format('Y-m-d\TH:i:s') : null;
                $endDate = $reuniao->data_final ? $reuniao->data_final->format('Y-m-d\TH:i:s') : null;

                if (! $startDate || ! $endDate) {
                    return null;
                }

                return [
                    'id' => $reuniao->id,
                    'title' => $reuniao->titulo,
                    'start' => $startDate,
                    'end' => $endDate,
                    'extendedProps' => [
                        'calendar' => $this->getCalendarCategory($reuniao->status),
                        'location' => $reuniao->location ?? '',
                        'description' => $reuniao->observacao ?? '',
                        'manager_id' => $reuniao->manager_id,
                        'manager_name' => $reuniao->manager->name ?? 'Sem gestor',
                        'user_name' => $reuniao->user->name ?? 'Sem usuário',
                        'status' => $reuniao->status,
                        'contato_id' => $reuniao->contato_id,
                        'contato_nome' => $reuniao->contato->nome_cliente ?? null,
                        'contato_telefone' => $reuniao->contato->telefone1 ?? null,
                        'contato_cpf' => $reuniao->contato->cpf ?? null,
                    ],
                ];
            })->filter();

        return response()->json($reunioes);
    }

    public function getStats()
    {
        $empresaId = $this->tenantId();

        $query = $this->visibleMeetings(
            ComercialReunioes::query()->where('empresa_id', $empresaId),
            Auth::user(),
        );
        $total = (clone $query)->count();
        $scheduled = (clone $query)->where('status', 'scheduled')->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $cancelled = (clone $query)->where('status', 'cancelled')->count();

        return response()->json([
            'total' => $total,
            'scheduled' => $scheduled,
            'completed' => $completed,
            'cancelled' => $cancelled,
        ]);
    }

    private function getCalendarCategory($status)
    {
        return match ($status) {
            'completed' => 'Success',
            'cancelled' => 'Danger',
            default => 'Business',
        };
    }

    public function getSellerContacts(Request $request)
    {
        $empresaId = $this->tenantId();
        $user = Auth::user();
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
        ]);
        $search = trim((string) ($data['search'] ?? ''));

        $query = Contatos::select('contatos.*')
            ->where('contatos.empresa_id', $empresaId)
            ->when(! $this->canManageMeetings($user), function (Builder $query) use ($empresaId, $user) {
                $query->whereExists(function ($assignment) use ($empresaId, $user) {
                    $assignment->selectRaw('1')
                        ->from('contatos_corretores')
                        ->whereColumn('contatos_corretores.contato_id', 'contatos.id')
                        ->whereColumn('contatos_corretores.empresa_id', 'contatos.empresa_id')
                        ->where('contatos_corretores.empresa_id', $empresaId)
                        ->where('contatos_corretores.user_id', $user->id);
                });
            });

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('contatos.nome_cliente', 'like', "%{$search}%")
                    ->orWhere('contatos.telefone1', 'like', "%{$search}%")
                    ->orWhere('contatos.telefone2', 'like', "%{$search}%")
                    ->orWhere('contatos.cpf', 'like', "%{$search}%");
            });
        }

        $contatos = $query->limit(20)->get();

        $results = $contatos->map(function ($contato) {
            return [
                'id' => $contato->id,
                'text' => $contato->nome_cliente,
                'telefone' => $contato->telefone1,
                'cpf' => $contato->cpf,
                'plano' => $contato->plano,
            ];
        });

        return response()->json([
            'results' => $results,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'manager_id' => ['required', $this->managerExistsRule($this->tenantId())],
            'contato_id' => ['nullable', Rule::exists('contatos', 'id')->where('empresa_id', $this->tenantId())],
            'data_inicio' => 'required|date',
            'data_final' => 'required|date|after:data_inicio',
            'location' => 'nullable|string|max:255',
            'observacao' => 'nullable|string',
        ]);

        try {
            // Obter o ID da empresa do usuário logado
            $empresaId = $this->tenantId();

            // Verificar se o usuário selecionado é realmente um gestor
            $manager = User::query()->tenantMember($empresaId)
                ->where('id', $request->manager_id)
                ->whereIn('user_role_id', [UserRole::ADMINISTRATIVO, UserRole::SUPERVISOR])
                ->first();

            if (! $manager) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'O usuário selecionado não é um gestor comercial.',
                ], 422);
            }

            // Validar que o contato pertence ao vendedor (se informado)
            if (! empty($validated['contato_id'])) {
                if (! $this->contactIsAvailable((int) $validated['contato_id'], Auth::user(), (int) $empresaId)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'O contato selecionado não está disponível para este usuário.',
                    ], 422);
                }
            }

            // Converter strings de data para objetos Carbon
            $dataInicio = Carbon::parse($request->data_inicio);
            $dataFinal = Carbon::parse($request->data_final);

            // Criar nova reunião
            $reuniao = new ComercialReunioes();
            $reuniao->titulo = $request->titulo;
            $reuniao->user_id = Auth::id();
            $reuniao->manager_id = $request->manager_id;
            $reuniao->contato_id = $request->contato_id;
            $reuniao->empresa_id = $empresaId;
            $reuniao->data_inicio = $dataInicio;
            $reuniao->data_final = $dataFinal;
            $reuniao->location = $request->location;
            $reuniao->observacao = $request->observacao;
            $reuniao->status = 'scheduled';
            $reuniao->save();

            // Carregar relações
            $reuniao->load([
                'manager' => fn ($query) => $query->tenantMember((int) $empresaId),
                'contato' => fn ($query) => $query->where('contatos.empresa_id', $empresaId),
            ]);

            $admins = User::query()->tenantMember($empresaId)
                ->whereIn('user_role_id', [UserRole::ADMINISTRATIVO, UserRole::SUPERVISOR])
                ->get();

            foreach ($admins as $admin) {
                $admin->notify(new NovaReuniaoAgendada($reuniao));
            }

            EnviarReuniaoAgendadaWhatsappJob::dispatch($reuniao->id);

            // Retornar dados da reunião para atualizar o calendário
            return response()->json([
                'status' => 'success',
                'message' => 'Reunião agendada com sucesso!',
                'reuniao' => [
                    'id' => $reuniao->id,
                    'title' => $reuniao->titulo,
                    'start' => $reuniao->data_inicio->format('Y-m-d\TH:i:s'),
                    'end' => $reuniao->data_final->format('Y-m-d\TH:i:s'),
                    'extendedProps' => [
                        'calendar' => 'Business',
                        'location' => $reuniao->location ?? '',
                        'description' => $reuniao->observacao ?? '',
                        'manager_id' => $reuniao->manager_id,
                        'manager_name' => $manager->name,
                        'user_name' => Auth::user()->name,
                        'status' => $reuniao->status,
                        'contato_id' => $reuniao->contato_id,
                        'contato_nome' => $reuniao->contato->nome_cliente ?? null,
                        'contato_telefone' => $reuniao->contato->telefone1 ?? null,
                        'contato_cpf' => $reuniao->contato->cpf ?? null,
                    ],
                ],
            ]);
        } catch (\Throwable $th) {
            report($th);

            return response()->json([
                'status' => 'error',
                'message' => 'Não foi possível agendar a reunião neste momento.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'manager_id' => ['required', $this->managerExistsRule($this->tenantId())],
            'contato_id' => ['nullable', Rule::exists('contatos', 'id')->where('empresa_id', $this->tenantId())],
            'data_inicio' => 'required|date',
            'data_final' => 'required|date|after:data_inicio',
            'location' => 'nullable|string|max:255',
            'observacao' => 'nullable|string',
            'status' => 'nullable|in:scheduled,completed,cancelled',
        ]);

        // Obter o ID da empresa do usuário logado
        $empresaId = $this->tenantId();

        // Buscar a reunião e verificar se pertence à empresa do usuário
        $reuniao = $this->visibleMeetings(
            ComercialReunioes::where('id', $id)->where('empresa_id', $empresaId),
            Auth::user(),
        )->first();

        if (! $reuniao) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reunião não encontrada ou você não tem permissão para editá-la.',
            ], 404);
        }

        // Verificar se o usuário selecionado é realmente um gestor
        $manager = User::query()->tenantMember($empresaId)
            ->where('id', $request->manager_id)
            ->whereIn('user_role_id', [UserRole::ADMINISTRATIVO, UserRole::SUPERVISOR])
            ->first();

        if (! $manager) {
            return response()->json([
                'status' => 'error',
                'message' => 'O usuário selecionado não é um gestor comercial.',
            ], 422);
        }

        // Validar que o contato pertence ao vendedor (se informado)
        if (! empty($validated['contato_id'])) {
            if (! $this->contactIsAvailable((int) $validated['contato_id'], Auth::user(), (int) $empresaId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'O contato selecionado não está disponível para este usuário.',
                ], 422);
            }
        }

        // Converter strings de data para objetos Carbon
        $dataInicio = Carbon::parse($request->data_inicio);
        $dataFinal = Carbon::parse($request->data_final);

        // Atualizar reunião
        $reuniao->titulo = $request->titulo;
        $reuniao->manager_id = $request->manager_id;
        $reuniao->contato_id = $request->contato_id;
        $reuniao->data_inicio = $dataInicio;
        $reuniao->data_final = $dataFinal;
        $reuniao->location = $request->location;
        $reuniao->observacao = $request->observacao;
        if ($request->has('status')) {
            $reuniao->status = $request->status;
        }
        $reuniao->save();

        // Carregar relações
        $reuniao->load([
            'contato' => fn ($query) => $query->where('contatos.empresa_id', $empresaId),
        ]);

        // Retornar dados da reunião para atualizar o calendário
        return response()->json([
            'status' => 'success',
            'message' => 'Reunião atualizada com sucesso!',
            'reuniao' => [
                'id' => $reuniao->id,
                'title' => $reuniao->titulo,
                'start' => $reuniao->data_inicio->format('Y-m-d\TH:i:s'),
                'end' => $reuniao->data_final->format('Y-m-d\TH:i:s'),
                'extendedProps' => [
                    'calendar' => $this->getCalendarCategory($reuniao->status),
                    'location' => $reuniao->location ?? '',
                    'description' => $reuniao->observacao ?? '',
                    'manager_id' => $reuniao->manager_id,
                    'manager_name' => $manager->name,
                    'user_name' => Auth::user()->name,
                    'status' => $reuniao->status,
                    'contato_id' => $reuniao->contato_id,
                    'contato_nome' => $reuniao->contato->nome_cliente ?? null,
                    'contato_telefone' => $reuniao->contato->telefone1 ?? null,
                    'contato_cpf' => $reuniao->contato->cpf ?? null,
                ],
            ],
        ]);
    }

    public function destroy($id)
    {
        // Obter o ID da empresa do usuário logado
        $empresaId = $this->tenantId();

        // Buscar a reunião e verificar se pertence à empresa do usuário
        $reuniao = $this->visibleMeetings(
            ComercialReunioes::where('id', $id)->where('empresa_id', $empresaId),
            Auth::user(),
        )->first();

        if (! $reuniao) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reunião não encontrada ou você não tem permissão para excluí-la.',
            ], 404);
        }

        $reuniao->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Reunião excluída com sucesso!',
        ]);
    }

    public function getAvailableSlots($managerId, $date)
    {
        $empresaId = $this->tenantId();
        $data = Validator::make([
            'manager_id' => $managerId,
            'date' => $date,
        ], [
            'manager_id' => ['required', 'integer', $this->managerExistsRule($empresaId)],
            'date' => ['required', 'date_format:Y-m-d'],
        ])->validate();

        // Verificar se o gestor pertence à mesma empresa do usuário
        $manager = User::query()->tenantMember($empresaId)
            ->where('id', $managerId)
            ->whereIn('user_role_id', [UserRole::ADMINISTRATIVO, UserRole::SUPERVISOR])
            ->first();

        if (! $manager) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gestor não encontrado ou não pertence à sua empresa.',
            ], 404);
        }

        $empresa = Empresa::query()->findOrFail($empresaId);
        $workStart = substr((string) $empresa->reuniao_horario_inicio, 0, 5);
        $workEnd = substr((string) $empresa->reuniao_horario_fim, 0, 5);
        $slotDuration = (int) $empresa->reuniao_duracao_minutos;

        // Obter reuniões existentes para o gestor na data especificada
        $existingMeetings = ComercialReunioes::where('manager_id', $managerId)
            ->where('empresa_id', $empresaId)
            ->whereDate('data_inicio', $data['date'])
            ->orderBy('data_inicio')
            ->get();

        // Criar array com todos os slots possíveis no horário comercial
        $availableSlots = [];
        $currentDate = Carbon::parse($data['date'].' '.$workStart);
        $endWorkDay = Carbon::parse($data['date'].' '.$workEnd);

        while ($currentDate < $endWorkDay) {
            $slotEnd = (clone $currentDate)->addMinutes($slotDuration);

            // Verificar se este slot está disponível (não conflita com reuniões existentes)
            $isAvailable = true;

            foreach ($existingMeetings as $meeting) {
                $meetingStart = Carbon::parse($meeting->data_inicio);
                $meetingEnd = Carbon::parse($meeting->data_final);

                // Verificar se há sobreposição de horários
                if (
                    ($currentDate >= $meetingStart && $currentDate < $meetingEnd) || // Início do slot durante uma reunião
                    ($slotEnd > $meetingStart && $slotEnd <= $meetingEnd) || // Fim do slot durante uma reunião
                    ($currentDate <= $meetingStart && $slotEnd >= $meetingEnd) // Slot engloba uma reunião
                ) {
                    $isAvailable = false;
                    break;
                }
            }

            if ($isAvailable) {
                $availableSlots[] = [
                    'start' => $currentDate->format('Y-m-d H:i:s'),
                    'end' => $slotEnd->format('Y-m-d H:i:s'),
                    'start_formatted' => $currentDate->format('H:i'),
                    'end_formatted' => $slotEnd->format('H:i'),
                    'label' => $currentDate->format('H:i').' - '.$slotEnd->format('H:i'),
                ];
            }

            // Avançar para o próximo slot
            $currentDate->addMinutes($slotDuration);
        }

        return response()->json([
            'status' => 'success',
            'date' => Carbon::parse($data['date'])->format('d/m/Y'),
            'manager' => [
                'id' => $manager->id,
                'name' => $manager->name,
            ],
            'available_slots' => $availableSlots,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'reuniao_horario_inicio' => ['required', 'date_format:H:i'],
            'reuniao_horario_fim' => ['required', 'date_format:H:i', 'after:reuniao_horario_inicio'],
            'reuniao_duracao_minutos' => [
                'required',
                'integer',
                'between:15,240',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ((int) $value % 5 !== 0) {
                        $fail('A duração deve ser informada em intervalos de 5 minutos.');
                    }
                },
            ],
        ]);

        $empresa = Empresa::query()->findOrFail($this->tenantId());
        $empresa->fill($data)->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Configuração da agenda atualizada.',
                'settings' => $data,
            ]);
        }

        return redirect()->route('comercialReunioes.index')
            ->with('status', 'success')
            ->with('message', 'Configuração da agenda atualizada.');
    }

    private function managerExistsRule(int $empresaId)
    {
        return Rule::exists('users', 'id')->where(fn ($query) => $query
            ->where('empresa_id', $empresaId)
            ->where('is_platform_admin', false)
            ->where('ativo', 'Y')
            ->whereIn('user_role_id', [UserRole::ADMINISTRATIVO, UserRole::SUPERVISOR]));
    }

    private function visibleMeetings(Builder $query, User $user): Builder
    {
        return $query->when(
            ! $this->canManageMeetings($user),
            fn (Builder $meetings) => $meetings->where('user_id', $user->id),
        );
    }

    private function canManageMeetings(User $user): bool
    {
        return $user->isPlatformAdmin() || in_array((int) $user->user_role_id, [
            UserRole::ADMINISTRATIVO,
            UserRole::SUPERVISOR,
            UserRole::DEVELOPER,
        ], true);
    }

    private function contactIsAvailable(int $contatoId, User $user, int $empresaId): bool
    {
        if ($this->canManageMeetings($user)) {
            return Contatos::query()
                ->where('empresa_id', $empresaId)
                ->whereKey($contatoId)
                ->exists();
        }

        return ContatosCorretores::query()
            ->where('empresa_id', $empresaId)
            ->where('contato_id', $contatoId)
            ->where('user_id', $user->id)
            ->exists();
    }
}
