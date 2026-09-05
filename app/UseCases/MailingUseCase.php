<?php

namespace App\UseCases;

use App\Enums\TabulationCode;
use App\Helpers\Helpers;
use App\Imports\ContatosImport;
use App\Models\ContatosCorretores;
use App\Repositories\Eloquent\ContatosRepository;
use App\Services\TabulationCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MailingUseCase
{
    protected $contatosRepository;

    public function __construct(ContatosRepository $contatosRepositoryInterface, private readonly TabulationCatalog $tabulations)
    {
        $this->contatosRepository = $contatosRepositoryInterface;
    }

    public function importaMailing(Request $request)
    {
        try {
            $uniqueIdBase = Helpers::generateUniqueId();
            $cpfs = [];

            $rows = Excel::toArray(new ContatosImport(Auth::user()->id, app(\App\Support\TenantContext::class)->id(), $request->base, $uniqueIdBase), $request->file('file'));
            foreach ($rows[0] as $row) {
                if (! is_null($row['cpf'])) {
                    $cpfs[] = Helpers::cleanSpecialCharacters($row['cpf']);
                }
            }

            $cpfsFound = $this->contatosRepository->searchForCpfsFound($cpfs);

            if (count($cpfsFound) > 0) {
                return response()->json([
                    'message' => count($cpfsFound).' CPFs já se encontram na sua base de dados.',
                    'cpfs' => $cpfsFound,
                    'error' => true,
                ]);
            } else {

                DB::transaction(function () use ($request, $uniqueIdBase): void {
                    $empresaId = (int) app(\App\Support\TenantContext::class)->id();
                    Excel::import(new ContatosImport(Auth::user()->id, $empresaId, $request->base, $uniqueIdBase), $request->file('file'));

                    $this->contatosRepository->getNewlyImportedBase($uniqueIdBase, $empresaId)
                        ->each(function ($contato) use ($request): void {
                            ContatosCorretores::create([
                                'empresa_id' => $contato->empresa_id,
                                'contato_id' => $contato->id,
                                'user_id' => $request->id_user,
                                'tabulacao_id' => $request->tabulacao,
                                'temperatura' => 'FRIO',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        });
                });

                return response()->json([
                    'error' => false,
                    'message' => 'Mailing importado com sucesso.',
                    'cpfs' => $cpfsFound,
                ], 201);
            }
        } catch (\Throwable $th) {
            report($th);

            return response()->json([
                'error' => true,
                'message' => 'Não foi possível importar o mailing neste momento.',
            ], 500);
        }
    }

    public function createLead(array $data)
    {
        try {
            $usuario = Auth::user();
            $proprietarioId = $usuario->isPlatformAdmin() ? null : $usuario->id;
            $uniqueIdBase = Helpers::generateUniqueId();
            $searchCpf = $this->contatosRepository->searchForCpfFound(Helpers::cleanSpecialCharacters($data['cpf']));

            if (! is_null($searchCpf)) {
                return [
                    'status' => 'error',
                    'message' => 'O CPF/CNPJ Cadastrado já existe na base de dados',
                    'error' => true,
                ];
            }

            DB::transaction(function () use ($data, $proprietarioId, $uniqueIdBase, $usuario): void {
                $empresaId = (int) app(\App\Support\TenantContext::class)->id();
                $createBase = $this->contatosRepository->create([
                    'id_operacao' => $uniqueIdBase,
                    'empresa_id' => $empresaId,
                    'user_import_id' => $usuario->id,
                    'nome_base' => ! empty($data['nome_base']) ? trim($data['nome_base']) : 'CADASTRO_MANUAL_'.now()->format('Ymd_His'),
                    'nome_cliente' => $data['nome_cliente'],
                    'data_nascimento' => $data['data_nascimento'] ?? null,
                    'cpf' => Helpers::cleanSpecialCharacters($data['cpf']),
                    'plano' => $data['plano'] ?? '',
                    'categoria' => $data['categoria'] ?? '',
                    'entidade' => Helpers::cleanSpecialCharacters($data['entidade'] ?? ''),
                    'telefone1' => Helpers::cleanSpecialCharacters($data['telefone1']),
                    'telefone2' => Helpers::cleanSpecialCharacters($data['telefone2'] ?? ''),
                    'telefone3' => Helpers::cleanSpecialCharacters($data['telefone3'] ?? ''),
                    'email' => $data['email'] ?? '',
                    'idades' => $data['idades'] ?? '',
                    'valor_plano_atual' => Helpers::formatCurrencyToDecimal($data['valor_plano_atual'] ?? '0'),
                ]);

                ContatosCorretores::create([
                    'empresa_id' => app(\App\Support\TenantContext::class)->id(),
                    'contato_id' => $createBase->id,
                    'user_id' => $proprietarioId,
                    'tabulacao_id' => $this->tabulations->id((int) app(\App\Support\TenantContext::class)->id(), TabulationCode::PROSPECCAO),
                    'temperatura' => 'FRIO',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            return [
                'status' => 'sucess',
                'message' => 'Cliente Cadastrado com sucesso',
                'error' => false,
            ];
        } catch (\Throwable $th) {
            report($th);

            return [
                'status' => 'error',
                'message' => 'Não foi possível cadastrar o cliente neste momento.',
                'error' => true,
            ];
        }
    }

    public function sendRemaketing(array $data) {}
}
