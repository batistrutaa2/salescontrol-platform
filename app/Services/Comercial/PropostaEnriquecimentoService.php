<?php

namespace App\Services\Comercial;

use App\Modules\LkBeneficios\Services\LemitService;
use App\Support\DocumentoFiscal;
use Carbon\Carbon;
use Throwable;

class PropostaEnriquecimentoService
{
    public function __construct(private LemitService $lemit) {}

    public function consultar(string $documento): array
    {
        $documento = DocumentoFiscal::somenteDigitos($documento);
        $resultado = strlen($documento) === 14
            ? $this->lemit->consultarCnpj($documento)
            : $this->lemit->consultarCpf($documento);

        return $this->normalizar($resultado, strlen($documento) === 14);
    }

    private function normalizar(array $resultado, bool $empresa): array
    {
        $registro = $resultado[$empresa ? 'empresa' : 'pessoa'] ?? null;
        if (is_object($registro) && method_exists($registro, 'toArray')) {
            if (method_exists($registro, 'loadMissing')) {
                $relacoes = collect(['emails', 'telefones', 'celulares', 'fixos'])
                    ->filter(fn (string $relacao) => method_exists($registro, $relacao))
                    ->values()->all();
                $registro->loadMissing($relacoes);
            }
            $registro = $registro->toArray();
        }
        $registro = is_array($registro) ? $registro : [];

        $nome = $empresa
            ? ($registro['razao_social'] ?? $registro['razaoSocial'] ?? $registro['nome_fantasia'] ?? null)
            : ($registro['nome'] ?? null);

        $emails = $this->emails($registro['emails'] ?? []);
        $telefones = $this->telefones($registro);
        $dados = [
            'nome' => $this->texto($nome),
            'data_nascimento' => $empresa ? null : $this->dataBr($registro['data_nascimento'] ?? $registro['dataNascimento'] ?? null),
            'data_abertura' => $empresa ? $this->dataBr($registro['data_abertura'] ?? $registro['data_fundacao'] ?? $registro['dataAbertura'] ?? null) : null,
            'email' => $emails[0] ?? null,
            'telefone1' => $telefones[0] ?? null,
            'telefone2' => $telefones[1] ?? null,
        ];

        return ['encontrado' => $dados['nome'] !== null, 'dados' => $dados];
    }

    private function emails(mixed $itens): array
    {
        return collect(is_iterable($itens) ? $itens : [])
            ->map(fn ($item) => is_object($item) ? $item->toArray() : $item)
            ->map(fn ($item) => filter_var($item['email'] ?? null, FILTER_VALIDATE_EMAIL) ?: null)
            ->filter()->unique()->values()->all();
    }

    private function telefones(array $registro): array
    {
        $itens = $registro['telefones'] ?? array_merge($registro['celulares'] ?? [], $registro['fixos'] ?? []);

        return collect(is_iterable($itens) ? $itens : [])
            ->map(function ($item) {
                $item = is_object($item) ? $item->toArray() : $item;
                $numero = DocumentoFiscal::somenteDigitos(($item['ddd'] ?? '').($item['numero'] ?? $item['numero_normalizado'] ?? ''));

                return strlen($numero) >= 10 ? $numero : null;
            })
            ->filter()->unique()->take(2)->values()->all();
    }

    private function dataBr(mixed $data): ?string
    {
        if (! $data) {
            return null;
        }

        try {
            return Carbon::parse($data)->format('d/m/Y');
        } catch (Throwable) {
            return null;
        }
    }

    private function texto(mixed $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }

    private function dadosVazios(): array
    {
        return array_fill_keys(['nome', 'data_nascimento', 'data_abertura', 'email', 'telefone1', 'telefone2'], null);
    }
}
