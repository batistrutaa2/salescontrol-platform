<?php

namespace App\Modules\LkBeneficios\Services;

use App\Models\People\Empresa;
use App\Models\People\Pessoa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LemitService
{
    private string $apiToken;

    private string $baseUrl;

    private int $cacheMonths;

    public function __construct()
    {
        $this->apiToken = (string) config('services.lemit.api_key', '');
        $this->baseUrl = rtrim((string) config('services.lemit.base_url'), '/');
        $this->cacheMonths = (int) config('services.lemit.cache_months', 3);

        if ($this->apiToken === '' || $this->baseUrl === '') {
            throw new RuntimeException('Lemit: credenciais ausentes. Configure LEMIT_API_TOKEN e LEMIT_BASE_URL no .env');
        }
    }

    public function consultarCpf(string $cpf): array
    {
        $cpf = preg_replace('/\D+/', '', $cpf);
        if (strlen($cpf) !== 11) {
            throw new \InvalidArgumentException('CPF inválido.');
        }

        $pessoa = Pessoa::where('cpf', $cpf)->first();
        if ($pessoa && $this->aindaNoCache($pessoa->data_consulta ?? $pessoa->created_at)) {
            return [
                'fonte' => 'local_db',
                'data_consulta' => $pessoa->data_consulta,
                'pessoa' => $this->formatarPessoa($pessoa),
            ];
        }

        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Bearer '.$this->apiToken,
        ])->post($this->baseUrl.'/pessoa', [
            'documento' => $cpf,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Lemit: falha ao consultar CPF (HTTP '.$response->status().').');
        }

        $data = $response->json();
        $this->persistirPessoa($data);

        return array_merge(['fonte' => 'api'], $data);
    }

    public function consultarCnpj(string $cnpj): array
    {
        $cnpj = preg_replace('/\D+/', '', $cnpj);
        if (strlen($cnpj) !== 14) {
            throw new \InvalidArgumentException('CNPJ inválido.');
        }

        $empresa = Empresa::where('cnpj', $cnpj)->first();
        if ($empresa && $this->aindaNoCache($empresa->created_at)) {
            $empresa->load(['enderecos', 'celulares', 'fixos', 'emails', 'socios', 'carros']);

            return [
                'fonte' => 'local_db',
                'data_consulta' => $empresa->created_at,
                'empresa' => $empresa,
            ];
        }

        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Bearer '.$this->apiToken,
        ])->post($this->baseUrl.'/empresa', [
            'documento' => $cnpj,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Lemit: falha ao consultar CNPJ (HTTP '.$response->status().').');
        }

        $json = $response->json();
        $this->persistirEmpresa($json['empresa'] ?? null);

        return array_merge(['fonte' => 'api'], $json);
    }

    private function aindaNoCache($dataConsulta): bool
    {
        if (! $dataConsulta) {
            return false;
        }

        return Carbon::parse($dataConsulta)->gt(now()->subMonths($this->cacheMonths));
    }

    private function persistirPessoa(array $data): void
    {
        $pessoaData = $data['pessoa'] ?? null;
        if (! $pessoaData || empty($pessoaData['cpf'])) {
            return;
        }

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

        $pessoa->celulares()->delete();
        $pessoa->fixos()->delete();
        $pessoa->emails()->delete();
        $pessoa->enderecos()->delete();
        $pessoa->carros()->delete();
        $pessoa->vinculos()->delete();
        $pessoa->riscosCredito()->delete();
        $pessoa->participacoesSocietarias()->delete();

        foreach ($pessoaData['celulares'] ?? [] as $item) {
            $pessoa->celulares()->create($item);
        }
        foreach ($pessoaData['fixos'] ?? [] as $item) {
            $pessoa->fixos()->create($item);
        }
        foreach ($pessoaData['emails'] ?? [] as $item) {
            $pessoa->emails()->create($item);
        }
        foreach ($pessoaData['enderecos'] ?? [] as $item) {
            $pessoa->enderecos()->create($item);
        }
        foreach ($pessoaData['carros'] ?? [] as $item) {
            $pessoa->carros()->create($item);
        }
        foreach ($pessoaData['vinculos'] ?? [] as $item) {
            $pessoa->vinculos()->create($item);
        }
        if (! empty($pessoaData['risco_credito'])) {
            $pessoa->riscosCredito()->create($pessoaData['risco_credito']);
        }
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

    private function persistirEmpresa(?array $empresaData): void
    {
        if (! $empresaData || empty($empresaData['cnpj'])) {
            return;
        }

        $empresa = Empresa::updateOrCreate(
            ['cnpj' => $empresaData['cnpj']],
            [
                'razao_social' => $empresaData['razao_social'] ?? null,
                'nome_fantasia' => $empresaData['nome_fantasia'] ?? null,
                'data_fundacao' => $empresaData['data_fundacao'] ?? null,
                'tipo' => $empresaData['tipo'] ?? null,
                'situacao' => $empresaData['situacao'] ?? null,
                'cnae_numero' => $empresaData['cnae']['numero'] ?? null,
                'cnae_tipo' => $empresaData['cnae']['tipo'] ?? null,
                'cnae_segmento' => $empresaData['cnae']['segmento'] ?? null,
                'cnae_descricao' => $empresaData['cnae']['descricao'] ?? null,
            ]
        );

        $empresa->enderecos()->delete();
        $empresa->celulares()->delete();
        $empresa->fixos()->delete();
        $empresa->emails()->delete();
        $empresa->socios()->delete();
        $empresa->carros()->delete();

        $endereco = $empresaData['endereco'] ?? null;
        if ($endereco) {
            $empresa->enderecos()->create([
                'endereco' => $endereco['endereco'] ?? null,
                'bairro' => $endereco['bairro'] ?? null,
                'cidade' => $endereco['cidade'] ?? null,
                'uf' => $endereco['uf'] ?? null,
                'cep' => $endereco['cep'] ?? null,
                'tipo' => $endereco['tipo'] ?? null,
                'ranking' => $endereco['ranking'] ?? null,
            ]);
        }

        foreach ($empresaData['celulares'] ?? [] as $item) {
            $empresa->celulares()->create($item);
        }
        foreach ($empresaData['fixos'] ?? [] as $item) {
            $empresa->fixos()->create($item);
        }
        foreach ($empresaData['emails'] ?? [] as $item) {
            $empresa->emails()->create($item);
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
    }

    private function formatarPessoa(Pessoa $pessoa): array
    {
        $pessoa->load([
            'celulares', 'fixos', 'emails', 'enderecos', 'carros',
            'vinculos', 'riscosCredito', 'participacoesSocietarias',
        ]);

        $data = $pessoa->toArray();

        $data['participacao_societaria'] = $pessoa->participacoesSocietarias->map(fn ($i) => [
            'nome' => $i->nome,
            'cnpj' => $i->cnpj,
            'capital_social' => $i->capital_social,
            'participacao_socio' => $i->participacao_socio,
            'data_fundacao' => $i->data_fundacao,
            'situacao_cadastral' => $i->situacao_cadastral,
        ])->toArray();

        $data['celulares'] = $pessoa->celulares->map(fn ($i) => [
            'ddd' => $i->ddd, 'numero' => $i->numero, 'plus' => $i->plus,
            'ranking' => $i->ranking, 'whatsapp' => $i->whatsapp,
        ])->toArray();

        $data['fixos'] = $pessoa->fixos->map(fn ($i) => [
            'numero' => $i->numero, 'ddd' => $i->ddd,
        ])->toArray();

        $data['emails'] = $pessoa->emails->map(fn ($i) => [
            'email' => $i->email, 'ranking' => $i->ranking, 'possui_cookie' => $i->possui_cookie,
        ])->toArray();

        $data['enderecos'] = $pessoa->enderecos->map(fn ($i) => [
            'endereco' => $i->endereco, 'bairro' => $i->bairro, 'cidade' => $i->cidade,
            'uf' => $i->uf, 'cep' => $i->cep, 'tipo' => $i->tipo, 'ranking' => $i->ranking,
        ])->toArray();

        $data['carros'] = $pessoa->carros->map(fn ($i) => [
            'placa' => $i->placa, 'marca' => $i->marca,
            'ano_fabricacao' => $i->ano_fabricacao, 'ano_modelo' => $i->ano_modelo,
            'renavan' => $i->renavan, 'chassi' => $i->chassi,
            'data_licenciamento' => $i->data_licenciamento, 'ranking' => $i->ranking,
        ])->toArray();

        $data['vinculos'] = $pessoa->vinculos->map(fn ($i) => [
            'cpf_vinculo' => $i->cpf_vinculo,
            'nome_vinculo' => $i->nome_vinculo,
            'tipo_vinculo' => $i->tipo_vinculo,
        ])->toArray();

        $data['risco_credito'] = $pessoa->riscosCredito->map(fn ($i) => [
            'cpf_cnpj' => $i->cpf_cnpj,
            'score_credito' => $i->score_credito,
        ])->toArray();

        unset($data['participacoesSocietarias']);

        return $data;
    }
}
