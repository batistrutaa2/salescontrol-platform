<?php

namespace App\Http\Controllers\pages\comercial;

use Illuminate\Http\Request;
use App\Models\People\Pessoa;
use App\Models\People\Empresa;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class ConsultaController extends Controller
{
    private $apiToken = 'fLcajNZ8d9XAB45jqtFTqgxy1lu2UpFHTMCAuTyi';
    private $baseUrl = 'https://api.lemit.com.br/api/v1/consulta';

    public function consultarPessoa(Request $request)
    {
        $request->validate([
            'cpf' => 'required|string|size:11'
        ]);

        $cpf = $request->cpf;

        // 1) Verifica se já existe na base local
        $pessoa = Pessoa::where('cpf', $cpf)->first();

        if ($pessoa) {
            // 2) Checa se a última consulta é mais recente que 3 meses
            $dataConsulta = \Carbon\Carbon::parse($pessoa->data_consulta);
            if ($dataConsulta->gt(now()->subMonths(3))) {
                // ✅ Dentro do prazo: retorna direto do banco local!
                return response()->json([
                    'data_consulta' => $pessoa->data_consulta,
                    'pessoa' => $this->formatPessoaResponse($pessoa),
                    'fonte' => 'local_db'
                ]);
            }
        }

        // 3) Caso não exista ou esteja desatualizado -> consulta na API
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->post($this->baseUrl . '/pessoa', [
                'documento' => $cpf
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Atualiza ou cria tudo de novo
                $this->salvarOuAtualizarPessoa($data);

                return response()->json($data);
            }

            return response()->json([
                'error' => 'CPF não encontrado ou erro na consulta'
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro interno do servidor',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function consultarEmpresa(Request $request)
    {
        $request->validate([
            'cnpj' => 'required|string|size:14'
        ]);

        try {
            // Buscar no banco local
            $empresa = Empresa::where('cnpj', $request->cnpj)->first();

            if ($empresa) {
                // Se já existe, verificar se a consulta é recente
                $diff = now()->diffInMonths($empresa->created_at);
                if ($diff < 3) {
                    // Se for recente (< 3 meses), retornar dados locais + relacionamentos
                    $empresa->load(['enderecos', 'celulares', 'fixos', 'emails', 'socios', 'carros']);
                    return response()->json([
                        'data_consulta' => $empresa->created_at,
                        'empresa' => $empresa
                    ]);
                }
            }

            // Se não existir ou estiver desatualizado, consultar a API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->post($this->baseUrl . '/empresa', [
                'documento' => $request->cnpj
            ]);

            if ($response->successful()) {
                $json = $response->json();
                $empresaData = $json['empresa'] ?? null;

                if (!$empresaData) {
                    return response()->json(['error' => 'Retorno da API inválido'], 500);
                }

                // Atualizar ou criar empresa principal
                $empresa = Empresa::updateOrCreate(
                    ['cnpj' => $empresaData['cnpj']],
                    [
                        'razao_social'   => $empresaData['razao_social'] ?? null,
                        'nome_fantasia'  => $empresaData['nome_fantasia'] ?? null,
                        'data_fundacao'  => $empresaData['data_fundacao'] ?? null,
                        'tipo'           => $empresaData['tipo'] ?? null,
                        'situacao'       => $empresaData['situacao'] ?? null,
                        'cnae_numero'    => $empresaData['cnae']['numero'] ?? null,
                        'cnae_tipo'      => $empresaData['cnae']['tipo'] ?? null,
                        'cnae_segmento'  => $empresaData['cnae']['segmento'] ?? null,
                        'cnae_descricao' => $empresaData['cnae']['descricao'] ?? null,
                    ]
                );

                // Sincronizar relacionamentos (endereços, celulares, fixos, emails, socios, carros)
                $empresa->enderecos()->delete();
                $empresa->celulares()->delete();
                $empresa->fixos()->delete();
                $empresa->emails()->delete();
                $empresa->socios()->delete();
                $empresa->carros()->delete();

                foreach ($empresaData['endereco'] ? [$empresaData['endereco']] : [] as $endereco) {
                    $empresa->enderecos()->create([
                        'endereco' => $endereco['endereco'] ?? null,
                        'bairro'   => $endereco['bairro'] ?? null,
                        'cidade'   => $endereco['cidade'] ?? null,
                        'uf'       => $endereco['uf'] ?? null,
                        'cep'      => $endereco['cep'] ?? null,
                        'tipo'     => $endereco['tipo'] ?? null,
                        'ranking'  => $endereco['ranking'] ?? null,
                    ]);
                }

                foreach ($empresaData['celulares'] ?? [] as $celular) {
                    $empresa->celulares()->create($celular);
                }

                foreach ($empresaData['fixos'] ?? [] as $fixo) {
                    $empresa->fixos()->create($fixo);
                }

                foreach ($empresaData['emails'] ?? [] as $email) {
                    $empresa->emails()->create($email);
                }

                foreach ($empresaData['socios'] ?? [] as $socio) {
                    $empresa->socios()->create([
                        'cpf' => $socio['cpf'] ?? null,
                        'nome' => $socio['nome'] ?? null,
                        'participacao' => $socio['participacao'] ?? null,
                        'capital_social' => $socio['capital_social'] ?? null,
                    ]);
                }

                foreach ($empresaData['carros'] ?? [] as $carro) {
                    $empresa->carros()->create([
                        'placa' => $carro['placa'] ?? null,
                        'marca' => $carro['marca'] ?? null,
                        'ano_fabricacao' => $carro['ano_fabricacao'] ?? null,
                        'ano_modelo' => $carro['ano_modelo'] ?? null,
                        'renavan' => $carro['renavan'] ?? null,
                        'chassi' => $carro['chassi'] ?? null,
                        'data_licenciamento' => $carro['data_licenciamento'] ?? null,
                        'ranking' => $carro['ranking'] ?? null,
                    ]);
                }

                // Retornar o que veio da API
                return response()->json($json);
            }

            return response()->json([
                'error' => 'CNPJ não encontrado ou erro na consulta'
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro interno do servidor',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function salvarOuAtualizarPessoa(array $data)
    {
        $pessoaData = $data['pessoa'] ?? [];

        // 1) Atualiza ou cria Pessoa
        $pessoa = Pessoa::updateOrCreate(
            ['cpf' => $pessoaData['cpf']],
            [
                'nome' => $pessoaData['nome'] ?? null,
                'data_nascimento' => $pessoaData['data_nascimento'] ?? null,
                'sexo' => $pessoaData['sexo'] ?? null,
                'nome_mae' => $pessoaData['nome_mae'] ?? null,
                'falecido' => $pessoaData['falecido'] ?? false,
                'situacao_cpf' => $pessoaData['situacao_cpf'] ?? null,
                'renda' => $pessoaData['renda'] ?? null,
                'ocupacao' => $pessoaData['ocupacao'] ?? null,
                'data_consulta' => $data['data_consulta'] ?? now(),
            ]
        );

        // 2) Apaga registros filhos antigos (garante consistência)
        $pessoa->celulares()->delete();
        $pessoa->fixos()->delete();
        $pessoa->emails()->delete();
        $pessoa->enderecos()->delete();
        $pessoa->carros()->delete();
        $pessoa->vinculos()->delete();
        $pessoa->riscosCredito()->delete();
        $pessoa->participacoesSocietarias()->delete();

        // 3) Salva os arrays filhos
        foreach ($pessoaData['celulares'] ?? [] as $celular) {
            $pessoa->celulares()->create($celular);
        }

        foreach ($pessoaData['fixos'] ?? [] as $fixo) {
            $pessoa->fixos()->create($fixo);
        }

        foreach ($pessoaData['emails'] ?? [] as $email) {
            $pessoa->emails()->create($email);
        }

        foreach ($pessoaData['enderecos'] ?? [] as $endereco) {
            $pessoa->enderecos()->create($endereco);
        }

        foreach ($pessoaData['carros'] ?? [] as $carro) {
            $pessoa->carros()->create($carro);
        }

        foreach ($pessoaData['vinculos'] ?? [] as $vinculo) {
            $pessoa->vinculos()->create($vinculo);
        }

        if (!empty($pessoaData['risco_credito'])) {
            $pessoa->riscosCredito()->create($pessoaData['risco_credito']);
        }

        // PADRONIZANDO chave para participacao societaria
        foreach ($pessoaData['participacao_societaria'] ?? [] as $part) {
            $pessoa->participacoesSocietarias()->create([
                'nome' => $part['nome'] ?? null,
                'cnpj' => $part['cnpj'] ?? null,
                'capital_social' => $part['capital_social'] ?? null,
                'participacao_socio' => $part['participacao_socio'] ?? null,
                'data_fundacao' => $part['data_fundacao'] ?? null,
                'situacao_cadastral' => $part['situacao_cadastral'] ?? null,
            ]);
        }
    }

    private function formatPessoaResponse(Pessoa $pessoa)
    {
        // Formatar o objeto Pessoa para o mesmo formato da API, principalmente a chave "participacao_societaria"
        $data = $pessoa->toArray();

        // Ajustar participacoes societarias para a chave correta e formato esperado
        $data['participacao_societaria'] = $pessoa->participacoesSocietarias->map(function ($item) {
            return [
                'nome' => $item->nome,
                'cnpj' => $item->cnpj,
                'capital_social' => $item->capital_social,
                'participacao_socio' => $item->participacao_socio,
                'data_fundacao' => $item->data_fundacao,
                'situacao_cadastral' => $item->situacao_cadastral,
            ];
        })->toArray();

        // Remove a chave antiga para não duplicar
        unset($data['participacoesSocietarias']);

        return $data;
    }
}
