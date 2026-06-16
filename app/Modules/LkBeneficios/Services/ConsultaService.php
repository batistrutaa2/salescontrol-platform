<?php

namespace App\Modules\LkBeneficios\Services;

use App\Models\People\Assertiva\AssertivaEmail;
use App\Models\People\Assertiva\AssertivaEmpresa;
use App\Models\People\Assertiva\AssertivaPessoa;
use App\Models\People\Assertiva\AssertivaTelefone;
use App\Models\People\Celular;
use App\Models\People\Email;
use App\Models\People\Fixo;
use App\Modules\LkBeneficios\Services\Assertiva\AssertivaService;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Orquestrador de consultas de enriquecimento (cérebro do roteamento multi-fonte).
 *
 * Regras de negócio definidas pelo cliente:
 *  - CPF / CNPJ            -> fonte de API = Lemit
 *  - telefone / e-mail /
 *    nome-endereço         -> fonte de API = Assertiva
 *  - Cache-first cruzado: antes de chamar QUALQUER API, procura o dado nas DUAS
 *    bases locais (Lemit em `pessoas`/`empresas`/... e Assertiva em `assertiva_*`).
 *    Só consulta a API se não houver dado fresco (TTL padrão 3 meses por fonte).
 *
 * Toda resposta carrega `fonte`: local_db_lemit | local_db_assertiva | api_lemit | api_assertiva.
 */
class ConsultaService
{
    private int $lemitMonths;

    private int $assertivaMonths;

    public function __construct(
        private LemitService $lemit,
        private AssertivaService $assertiva,
    ) {
        $this->lemitMonths = (int) config('services.lemit.cache_months', 3);
        $this->assertivaMonths = (int) config('services.assertiva.cache_months', 3);
    }

    // ----------------------------------------------------------------------
    // Documento (CPF/CNPJ) com fonte explícita escolhida pelo vendedor
    //   fonte = 'lemit'     -> CPF/CNPJ via Lemit
    //   fonte = 'assertiva' -> CPF/CNPJ via Assertiva
    // ----------------------------------------------------------------------

    public function consultarDocumento(string $doc, string $fonte = 'lemit'): array
    {
        $digits = AssertivaService::digits($doc);
        $fonte = $fonte === 'assertiva' ? 'assertiva' : 'lemit';

        if (strlen($digits) === 11) {
            return $fonte === 'assertiva' ? $this->documentoAssertivaCpf($digits) : $this->lemit->consultarCpf($digits);
        }
        if (strlen($digits) === 14) {
            return $fonte === 'assertiva' ? $this->documentoAssertivaCnpj($digits) : $this->lemit->consultarCnpj($digits);
        }

        throw new InvalidArgumentException('Documento inválido. Informe um CPF (11 dígitos) ou CNPJ (14 dígitos).');
    }

    private function documentoAssertivaCpf(string $cpf): array
    {
        $pessoa = AssertivaPessoa::where('cpf', $cpf)->first();
        if ($pessoa && $this->fresco($pessoa->data_consulta, $this->assertivaMonths)) {
            $pessoa->load(['telefones', 'enderecos', 'emails']);

            return ['fonte' => 'local_db_assertiva', 'data_consulta' => $pessoa->data_consulta, 'pessoa' => $pessoa];
        }

        return $this->assertiva->consultarCpf($cpf);
    }

    private function documentoAssertivaCnpj(string $cnpj): array
    {
        $empresa = AssertivaEmpresa::where('cnpj', $cnpj)->first();
        if ($empresa && $this->fresco($empresa->data_consulta, $this->assertivaMonths)) {
            $empresa->load(['telefones', 'enderecos', 'emails']);

            return ['fonte' => 'local_db_assertiva', 'data_consulta' => $empresa->data_consulta, 'empresa' => $empresa];
        }

        return $this->assertiva->consultarCnpj($cnpj);
    }

    // ----------------------------------------------------------------------
    // Telefone -> Assertiva (cache-first cruzado por número normalizado)
    // ----------------------------------------------------------------------

    public function consultarTelefone(string $telefone): array
    {
        $numero = AssertivaService::digits($telefone);
        if (strlen($numero) < 10) {
            throw new InvalidArgumentException('Telefone inválido.');
        }

        // 1) Base Assertiva
        $tel = AssertivaTelefone::where('numero_normalizado', $numero)->latest('updated_at')->first();
        if ($tel) {
            $owner = $tel->assertiva_pessoa_id ? $tel->pessoa : $tel->empresa;
            if ($owner && $this->fresco($owner->data_consulta, $this->assertivaMonths)) {
                $owner->load(['telefones', 'enderecos', 'emails']);

                return [
                    'fonte' => 'local_db_assertiva',
                    'data_consulta' => $owner->data_consulta,
                    $tel->assertiva_pessoa_id ? 'pessoa' : 'empresa' => $owner,
                ];
            }
        }

        // 2) Base Lemit (Celular/Fixo -> Pessoa)
        if ($pessoaLemit = $this->acharPessoaLemitPorTelefone($numero)) {
            return [
                'fonte' => 'local_db_lemit',
                'data_consulta' => $pessoaLemit->data_consulta ?? $pessoaLemit->created_at,
                'pessoa' => $pessoaLemit,
            ];
        }

        // 3) API Assertiva
        return $this->assertiva->consultarTelefone($numero);
    }

    // ----------------------------------------------------------------------
    // E-mail -> Assertiva (cache-first cruzado)
    // ----------------------------------------------------------------------

    public function consultarEmail(string $email): array
    {
        $email = trim($email);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail inválido.');
        }
        $normalizado = mb_strtolower($email);

        // 1) Base Assertiva
        $row = AssertivaEmail::where('email_normalizado', $normalizado)->latest('updated_at')->first();
        if ($row) {
            $owner = $row->assertiva_pessoa_id ? $row->pessoa : $row->empresa;
            if ($owner && $this->fresco($owner->data_consulta, $this->assertivaMonths)) {
                $owner->load(['telefones', 'enderecos', 'emails']);

                return [
                    'fonte' => 'local_db_assertiva',
                    'data_consulta' => $owner->data_consulta,
                    $row->assertiva_pessoa_id ? 'pessoa' : 'empresa' => $owner,
                ];
            }
        }

        // 2) Base Lemit
        $emailLemit = Email::where('email', $normalizado)->orWhere('email', $email)->first();
        if ($emailLemit && $emailLemit->pessoa) {
            $pessoa = $emailLemit->pessoa;
            if ($this->fresco($pessoa->data_consulta ?? $pessoa->created_at, $this->lemitMonths)) {
                return [
                    'fonte' => 'local_db_lemit',
                    'data_consulta' => $pessoa->data_consulta ?? $pessoa->created_at,
                    'pessoa' => $pessoa,
                ];
            }
        }

        // 3) API Assertiva
        return $this->assertiva->consultarEmail($email);
    }

    // ----------------------------------------------------------------------
    // Nome / endereço -> Assertiva (sem chave de cache confiável; vai à API)
    // ----------------------------------------------------------------------

    public function consultarNomeEndereco(array $filtros): array
    {
        return $this->assertiva->consultarNomeEndereco($filtros);
    }

    // ----------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------

    private function fresco($dataConsulta, int $months): bool
    {
        if (! $dataConsulta) {
            return false;
        }

        return Carbon::parse($dataConsulta)->gt(now()->subMonths($months));
    }

    private function acharPessoaLemitPorTelefone(string $numero)
    {
        $match = function ($query) use ($numero) {
            return $query->whereRaw("CONCAT(COALESCE(ddd,''), COALESCE(numero,'')) = ?", [$numero])
                ->orWhere('numero', $numero);
        };

        $registro = Celular::where($match)->first() ?? Fixo::where($match)->first();
        if (! $registro || ! $registro->pessoa) {
            return null;
        }

        $pessoa = $registro->pessoa;
        if (! $this->fresco($pessoa->data_consulta ?? $pessoa->created_at, $this->lemitMonths)) {
            return null;
        }

        $pessoa->load(['celulares', 'fixos', 'emails', 'enderecos']);

        return $pessoa;
    }
}
