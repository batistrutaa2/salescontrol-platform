<?php

namespace App\Http\Controllers\pages\comercial;

use App\Http\Controllers\Controller;
use App\Jobs\EnviarReuniaoAgendadaWhatsappJob;
use App\Models\ComercialReunioes;
use App\Models\Contatos;
use App\Models\ContatosCorretores;
use App\Models\User;
use App\Notifications\NovaReuniaoAgendada;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ReunioesComercial extends Controller
{
    public function index()
    {
        $managers = User::whereIn('user_role_id', ['2', '5'])
            ->where('ativo', 'Y')
            ->where('empresa_id', Auth::user()->empresa_id)
            ->get();

        return view('content.pages.reunioes.index', compact('managers'));
    }

    public function getReunioes()
    {
        $empresaId = Auth::user()->empresa_id;

        $reunioes = ComercialReunioes::with(['user', 'manager', 'contato'])
            ->where('empresa_id', $empresaId)
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
        $empresaId = Auth::user()->empresa_id;

        $total = ComercialReunioes::where('empresa_id', $empresaId)->count();
        $scheduled = ComercialReunioes::where('empresa_id', $empresaId)->where('status', 'scheduled')->count();
        $completed = ComercialReunioes::where('empresa_id', $empresaId)->where('status', 'completed')->count();
        $cancelled = ComercialReunioes::where('empresa_id', $empresaId)->where('status', 'cancelled')->count();

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
        $empresaId = Auth::user()->empresa_id;
        $userId = Auth::id();
        $search = $request->get('search', '');

        // Busca contatos que pertencem ao vendedor logado através de contatos_corretores
        $query = Contatos::select('contatos.*')
            ->join('contatos_corretores', 'contatos.id', '=', 'contatos_corretores.contato_id')
            ->where('contatos.empresa_id', $empresaId)
            ->where('contatos_corretores.user_id', $userId);

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
        try {
            $validated = $request->validate([
                'titulo' => 'required|string|max:255',
                'manager_id' => 'required|exists:users,id',
                'contato_id' => 'nullable|exists:contatos,id',
                'data_inicio' => 'required|date',
                'data_final' => 'required|date|after:data_inicio',
                'location' => 'nullable|string|max:255',
                'observacao' => 'nullable|string',
            ]);

            // Obter o ID da empresa do usuário logado
            $empresaId = Auth::user()->empresa_id;

            // Verificar se o usuário selecionado é realmente um gestor
            $manager = User::where('id', $request->manager_id)
                ->whereIn('user_role_id', ['2', '5'])
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $manager) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'O usuário selecionado não é um gestor comercial.',
                ], 422);
            }

            // Validar que o contato pertence ao vendedor (se informado)
            if ($request->contato_id) {
                $contatoCorretor = ContatosCorretores::where('contato_id', $request->contato_id)
                    ->where('user_id', Auth::id())
                    ->where('empresa_id', $empresaId)
                    ->first();

                if (! $contatoCorretor) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'O contato selecionado não pertence à sua carteira.',
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
            $reuniao->load(['manager', 'contato']);

            $admins = User::whereIn('user_role_id', ['2', '5'])
                ->where('empresa_id', $empresaId)
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
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'manager_id' => 'required|exists:users,id',
            'contato_id' => 'nullable|exists:contatos,id',
            'data_inicio' => 'required|date',
            'data_final' => 'required|date|after:data_inicio',
            'location' => 'nullable|string|max:255',
            'observacao' => 'nullable|string',
            'status' => 'nullable|in:scheduled,completed,cancelled',
        ]);

        // Obter o ID da empresa do usuário logado
        $empresaId = Auth::user()->empresa_id;

        // Buscar a reunião e verificar se pertence à empresa do usuário
        $reuniao = ComercialReunioes::where('id', $id)
            ->where('empresa_id', $empresaId)
            ->first();

        if (! $reuniao) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reunião não encontrada ou você não tem permissão para editá-la.',
            ], 404);
        }

        // Verificar se o usuário selecionado é realmente um gestor
        $manager = User::where('id', $request->manager_id)
            ->whereIn('user_role_id', ['2', '5'])
            ->where('empresa_id', $empresaId)
            ->first();

        if (! $manager) {
            return response()->json([
                'status' => 'error',
                'message' => 'O usuário selecionado não é um gestor comercial.',
            ], 422);
        }

        // Validar que o contato pertence ao vendedor (se informado)
        if ($request->contato_id) {
            $contatoCorretor = ContatosCorretores::where('contato_id', $request->contato_id)
                ->where('user_id', $reuniao->user_id)
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $contatoCorretor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'O contato selecionado não pertence à carteira do vendedor.',
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
        $reuniao->load(['contato']);

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
        $empresaId = Auth::user()->empresa_id;

        // Buscar a reunião e verificar se pertence à empresa do usuário
        $reuniao = ComercialReunioes::where('id', $id)
            ->where('empresa_id', $empresaId)
            ->first();

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
        // Obter o ID da empresa do usuário logado
        $empresaId = Auth::user()->empresa_id;

        // Verificar se o gestor pertence à mesma empresa do usuário
        $manager = User::where('id', $managerId)
            ->whereIn('user_role_id', ['2', '5'])
            ->where('empresa_id', $empresaId)
            ->first();

        if (! $manager) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gestor não encontrado ou não pertence à sua empresa.',
            ], 404);
        }

        // Definir horário comercial (8h às 18h)
        $workStartHour = 8;
        $workEndHour = 18;

        // Duração padrão de cada slot em minutos (por exemplo, 60 minutos = 1 hora)
        $slotDuration = 60;

        // Obter reuniões existentes para o gestor na data especificada
        $existingMeetings = ComercialReunioes::where('manager_id', $managerId)
            ->where('empresa_id', $empresaId)
            ->whereDate('data_inicio', $date)
            ->orderBy('data_inicio')
            ->get();

        // Criar array com todos os slots possíveis no horário comercial
        $availableSlots = [];
        $currentDate = Carbon::parse($date)->setHour($workStartHour)->setMinute(0)->setSecond(0);
        $endWorkDay = Carbon::parse($date)->setHour($workEndHour)->setMinute(0)->setSecond(0);

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
            'date' => Carbon::parse($date)->format('d/m/Y'),
            'manager' => [
                'id' => $manager->id,
                'name' => $manager->name,
            ],
            'available_slots' => $availableSlots,
        ]);
    }
}
