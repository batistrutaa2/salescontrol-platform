<?php

namespace App\Http\Controllers\pages\comercial;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\ComercialReunioes;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Notifications\NovaReuniaoAgendada;


class ReunioesComercial extends Controller
{
    public function index()
    {
        $managerRoleId = UserRole::where('tipo_usuario', 'ADMINISTRATIVO', 'SUPERVISOR')->first()->id;

        $managers = User::where('user_role_id', $managerRoleId)
            ->where('ativo', 'Y')
            ->where('empresa_id', Auth::user()->empresa_id)
            ->get();
        return view('content.pages.reunioes.index', compact('managers'));
    }

    public function getReunioes()
    {
        $reunioes = ComercialReunioes::with(['user', 'manager'])->get()->map(function ($reuniao) {
            $startDate = $reuniao->data_inicio ? $reuniao->data_inicio->format('Y-m-d\TH:i:s') : null;
            $endDate = $reuniao->data_final ? $reuniao->data_final->format('Y-m-d\TH:i:s') : null;

            if (!$startDate || !$endDate) {
                return null;
            }

            return [
                'id' => $reuniao->id,
                'title' => $reuniao->titulo,
                'start' => $startDate,
                'end' => $endDate,
                'extendedProps' => [
                    'calendar' => 'Business', // Categoria padrão para reuniões comerciais
                    'location' => $reuniao->location ?? '',
                    'description' => $reuniao->observacao ?? '',
                    'manager_id' => $reuniao->manager_id,
                    'manager_name' => $reuniao->manager->name ?? 'Sem gestor',
                    'user_name' => $reuniao->user->name ?? 'Sem usuário'
                ]
            ];
        })->filter();

        return response()->json($reunioes);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'titulo' => 'required|string|max:255',
                'manager_id' => 'required|exists:users,id',
                'data_inicio' => 'required|date',
                'data_final' => 'required|date|after:data_inicio',
                'location' => 'nullable|string|max:255',
                'observacao' => 'nullable|string'
            ]);

            // Obter o ID da empresa do usuário logado
            $empresaId = Auth::user()->empresa_id;

            // Verificar se o usuário selecionado é realmente um gestor
            $managerRoleId = UserRole::where('tipo_usuario', 'ADMINISTRATIVO', 'SUPERVISOR')->first()->id;
            $manager = User::where('id', $request->manager_id)
                ->where('user_role_id', $managerRoleId)
                ->first();

            if (!$manager) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'O usuário selecionado não é um gestor comercial.'
                ], 422);
            }

            // Converter strings de data para objetos Carbon
            $dataInicio = Carbon::parse($request->data_inicio);
            $dataFinal = Carbon::parse($request->data_final);

            // Criar nova reunião
            $reuniao = new ComercialReunioes();
            $reuniao->titulo = $request->titulo;
            $reuniao->user_id = Auth::id(); // ID do usuário logado (vendedor)
            $reuniao->manager_id = $request->manager_id;
            $reuniao->empresa_id = $empresaId; // Adicionar empresa_id do usuário logado
            $reuniao->data_inicio = $dataInicio;
            $reuniao->data_final = $dataFinal;
            $reuniao->location = $request->location;
            $reuniao->observacao = $request->observacao;
            $reuniao->status = 'scheduled';
            $reuniao->save();


            $admins = User::where('user_role_id', $managerRoleId)
                ->where('empresa_id', $empresaId)
                ->get();

            foreach ($admins as $admin) {
                $admin->notify(new NovaReuniaoAgendada($reuniao));
            }


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
                        'user_name' => Auth::user()->name
                    ]
                ]
            ]);
        } catch (\Throwable $th) {
            dd($th);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'manager_id' => 'required|exists:users,id',
            'data_inicio' => 'required|date',
            'data_final' => 'required|date|after:data_inicio',
            'location' => 'nullable|string|max:255',
            'observacao' => 'nullable|string'
        ]);

        // Obter o ID da empresa do usuário logado
        $empresaId = Auth::user()->empresa_id;

        // Buscar a reunião e verificar se pertence à empresa do usuário
        $reuniao = ComercialReunioes::where('id', $id)
            ->where('empresa_id', $empresaId)
            ->first();

        if (!$reuniao) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reunião não encontrada ou você não tem permissão para editá-la.'
            ], 404);
        }

        // Verificar se o usuário selecionado é realmente um gestor
        $managerRoleId = UserRole::where('tipo_usuario', 'ADMINISTRATIVO', 'SUPERVISOR')->first()->id;
        $manager = User::where('id', $request->manager_id)
            ->where('user_role_id', $managerRoleId)
            ->first();

        if (!$manager) {
            return response()->json([
                'status' => 'error',
                'message' => 'O usuário selecionado não é um gestor comercial.'
            ], 422);
        }

        // Converter strings de data para objetos Carbon
        $dataInicio = Carbon::parse($request->data_inicio);
        $dataFinal = Carbon::parse($request->data_final);

        // Atualizar reunião
        $reuniao->titulo = $request->titulo;
        $reuniao->manager_id = $request->manager_id;
        $reuniao->data_inicio = $dataInicio;
        $reuniao->data_final = $dataFinal;
        $reuniao->location = $request->location;
        $reuniao->observacao = $request->observacao;
        $reuniao->save();

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
                    'calendar' => 'Business',
                    'location' => $reuniao->location ?? '',
                    'description' => $reuniao->observacao ?? '',
                    'manager_id' => $reuniao->manager_id,
                    'manager_name' => $manager->name,
                    'user_name' => Auth::user()->name
                ]
            ]
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

        if (!$reuniao) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reunião não encontrada ou você não tem permissão para excluí-la.'
            ], 404);
        }

        $reuniao->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Reunião excluída com sucesso!'
        ]);
    }

    public function getAvailableSlots($managerId, $date)
    {
        // Obter o ID da empresa do usuário logado
        $empresaId = Auth::user()->empresa_id;

        // Verificar se o gestor pertence à mesma empresa do usuário
        $managerRoleId = UserRole::where('tipo_usuario', 'ADMINISTRATIVO','SUPERVISOR')->first()->id;
        $manager = User::where('id', $managerId)
            ->where('user_role_id', $managerRoleId)
            ->where('empresa_id', $empresaId)
            ->first();

        if (!$manager) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gestor não encontrado ou não pertence à sua empresa.'
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
                    'label' => $currentDate->format('H:i') . ' - ' . $slotEnd->format('H:i')
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
                'name' => $manager->name
            ],
            'available_slots' => $availableSlots
        ]);
    }
}
