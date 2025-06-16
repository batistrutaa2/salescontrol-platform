<?php

namespace App\Http\Controllers\pages\comercial;

use Illuminate\Http\Request;
use App\Models\People\Pessoa;
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
                'pessoa' => $pessoa->load([
                    'celulares',
                    'fixos',
                    'emails',
                    'enderecos',
                    'carros',
                    'vinculos',
                    'riscosCredito',
                    'participacoesSocietarias',
                ]),
                'data_consulta' => $pessoa->data_consulta,
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
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->post($this->baseUrl . '/empresa', [
                'documento' => $request->cnpj
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'CNPJ não encontrado ou erro na consulta'
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro interno do servidor'
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

        foreach ($pessoaData['participacao_societaria'] ?? [] as $part) {
            $pessoa->participacoesSocietarias()->create($part);
        }
    }
}
