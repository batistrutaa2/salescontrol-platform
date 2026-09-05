<?php

namespace App\Services\Enrichment\Assertiva;

use App\Models\People\Assertiva\AssertivaEmpresa;
use App\Models\People\Assertiva\AssertivaPessoa;
use App\Services\Enrichment\ConsultaService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

/**
 * Integração HTTP com a API Localize V3 da Assertiva + persistência nas tabelas
 * `assertiva_*` (people_db). É a fonte usada para telefone, e-mail e nome/endereço.
 *
 * Roteamento de fonte (Lemit x Assertiva) e cache-first ficam no {@see ConsultaService}.
 */
class AssertivaService
{
    private string $baseUrl;

    private int $idFinalidade;

    public function __construct(private AssertivaTokenManager $tokenManager)
    {
        $this->baseUrl = rtrim((string) config('services.assertiva.base_url'), '/');
        $this->idFinalidade = (int) config('services.assertiva.id_finalidade', 5);
    }

    public static function digits(?string $valor): string
    {
        return preg_replace('/\D+/', '', (string) $valor);
    }

    // ----------------------------------------------------------------------
    // Consultas principais (CPF/CNPJ) — usadas para enriquecer e obter protocolo
    // ----------------------------------------------------------------------

    public function consultarCpf(string $cpf): array
    {
        $cpf = self::digits($cpf);
        if (strlen($cpf) !== 11) {
            throw new InvalidArgumentException('CPF inválido.');
        }

        $data = $this->get('/localize/v3/cpf', ['cpf' => $cpf, 'idFinalidade' => $this->idFinalidade]);
        $pessoa = $this->persistirPessoaCompleta($cpf, $data);

        return ['fonte' => 'api_assertiva', 'data_consulta' => $pessoa?->data_consulta, 'pessoa' => $pessoa];
    }

    public function consultarCnpj(string $cnpj): array
    {
        $cnpj = self::digits($cnpj);
        if (strlen($cnpj) !== 14) {
            throw new InvalidArgumentException('CNPJ inválido.');
        }

        $data = $this->get('/localize/v3/cnpj', ['cnpj' => $cnpj, 'idFinalidade' => $this->idFinalidade]);
        $empresa = $this->persistirEmpresaCompleta($cnpj, $data);

        return ['fonte' => 'api_assertiva', 'data_consulta' => $empresa?->data_consulta, 'empresa' => $empresa];
    }

    // ----------------------------------------------------------------------
    // Consultas reversas (telefone / e-mail / nome-endereço)
    // ----------------------------------------------------------------------

    public function consultarTelefone(string $telefone): array
    {
        $telefone = self::digits($telefone);
        if (strlen($telefone) < 10) {
            throw new InvalidArgumentException('Telefone inválido.');
        }

        $data = $this->get('/localize/v3/telefone', ['telefone' => $telefone, 'idFinalidade' => $this->idFinalidade]);
        $resultado = $this->persistirReversa($data, ['numero' => $telefone]);

        return array_merge(['fonte' => 'api_assertiva', 'protocolo' => $data['cabecalho']['protocolo'] ?? null], $resultado);
    }

    public function consultarEmail(string $email): array
    {
        $email = trim($email);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail inválido.');
        }

        $data = $this->get('/localize/v3/email', ['email' => $email, 'idFinalidade' => $this->idFinalidade]);
        $resultado = $this->persistirReversa($data, ['email' => $email]);

        return array_merge(['fonte' => 'api_assertiva', 'protocolo' => $data['cabecalho']['protocolo'] ?? null], $resultado);
    }

    /**
     * Busca por nome/endereço. $filtros aceita as chaves da API
     * (buscarPor, nomeOuRazaoSocial, uf, cidade, bairro, cepOuNomeRua, ...).
     *
     * Diferente das outras buscas, esta retorna uma LISTA de candidatos (até 10 PF
     * + 10 PJ) com dados parciais. O vendedor escolhe um para então consultar o
     * documento completo. Não persiste os parciais (a gravação ocorre na escolha).
     */
    public function consultarNomeEndereco(array $filtros): array
    {
        if (empty($filtros['buscarPor'])) {
            throw new InvalidArgumentException('Informe o parâmetro buscarPor.');
        }

        $data = $this->get('/localize/v3/nome-endereco', array_merge($filtros, ['idFinalidade' => $this->idFinalidade]));
        $resp = is_array($data['resposta'] ?? null) ? $data['resposta'] : [];

        return [
            'fonte' => 'api_assertiva',
            'tipo' => 'lista',
            'protocolo' => $data['cabecalho']['protocolo'] ?? null,
            'pessoasFisicas' => $this->comoLista($resp['pessoaFisica'] ?? null),
            'pessoasJuridicas' => $this->comoLista($resp['pessoaJuridica'] ?? null),
        ];
    }

    /**
     * Normaliza o retorno (lista de candidatos OU objeto único) sempre em lista.
     */
    private function comoLista($valor): array
    {
        if (! is_array($valor) || $valor === []) {
            return [];
        }
        // objeto associativo único (não é lista indexada) -> embrulha em lista
        if (array_keys($valor) !== range(0, count($valor) - 1)) {
            return [$valor];
        }

        return array_values(array_filter($valor, 'is_array'));
    }

    // ----------------------------------------------------------------------
    // HTTP
    // ----------------------------------------------------------------------

    private function get(string $path, array $query): array
    {
        $response = $this->request($path, $query);

        // Token pode ter sido invalidado no servidor antes de expirar no cache.
        if ($response->status() === 401) {
            $this->tokenManager->renovar();
            $response = $this->request($path, $query);
        }

        // 404 = "Não localizamos nenhuma pessoa/empresa..." — resultado vazio
        // legítimo (não é falha). A resposta ainda traz o cabeçalho/protocolo.
        if ($response->status() === 404) {
            return $response->json() ?? [];
        }

        if (! $response->successful()) {
            throw new RuntimeException('Assertiva: falha ao consultar '.$path.' (HTTP '.$response->status().').');
        }

        return $response->json() ?? [];
    }

    private function request(string $path, array $query): Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.$this->tokenManager->getToken(),
            'Accept' => 'application/json',
        ])->get($this->baseUrl.$path, $query);
    }

    // ----------------------------------------------------------------------
    // Persistência
    // ----------------------------------------------------------------------

    private function persistirPessoaCompleta(string $cpf, array $data): ?AssertivaPessoa
    {
        $resp = is_array($data['resposta'] ?? null) ? $data['resposta'] : [];
        $dc = $resp['dadosCadastrais'] ?? [];

        $pessoa = AssertivaPessoa::updateOrCreate(
            ['cpf' => $cpf],
            [
                'nome' => $dc['nome'] ?? null,
                'sexo' => $dc['sexo'] ?? null,
                'data_nascimento' => $dc['dataNascimento'] ?? null,
                'mae_nome' => $dc['maeNome'] ?? null,
                'mae_cpf' => self::digits($dc['maeCpf'] ?? null) ?: null,
                'situacao_cadastral' => $dc['situacaoCadastral'] ?? null,
                'protocolo' => $data['cabecalho']['protocolo'] ?? null,
                'payload' => $resp,
                'data_consulta' => now(),
            ]
        );

        $pessoa->telefones()->delete();
        $pessoa->enderecos()->delete();
        $pessoa->emails()->delete();

        $this->salvarTelefones($pessoa->telefones(), $resp['telefones'] ?? []);

        foreach ($resp['enderecos'] ?? [] as $end) {
            $pessoa->enderecos()->create($this->mapEndereco($end));
        }
        foreach ($resp['emails'] ?? [] as $mail) {
            $pessoa->emails()->create($this->mapEmail($mail['email'] ?? null));
        }

        return $pessoa;
    }

    private function persistirEmpresaCompleta(string $cnpj, array $data): ?AssertivaEmpresa
    {
        $resp = is_array($data['resposta'] ?? null) ? $data['resposta'] : [];
        $dc = $resp['dadosCadastrais'] ?? [];

        $empresa = AssertivaEmpresa::updateOrCreate(
            ['cnpj' => $cnpj],
            [
                'razao_social' => $dc['razaoSocial'] ?? null,
                'nome_fantasia' => $dc['nomeFantasia'] ?? null,
                'data_abertura' => $dc['dataAbertura'] ?? null,
                'cnae' => isset($dc['cnae']) ? (string) $dc['cnae'] : null,
                'cnae_descricao' => $dc['cnaeDescricao'] ?? null,
                'situacao_cadastral' => $dc['situacaoCadastral'] ?? null,
                'protocolo' => $data['cabecalho']['protocolo'] ?? null,
                'payload' => $resp,
                'data_consulta' => now(),
            ]
        );

        $empresa->telefones()->delete();
        $empresa->enderecos()->delete();
        $empresa->emails()->delete();

        $this->salvarTelefones($empresa->telefones(), $resp['telefones'] ?? []);

        foreach ($resp['enderecos'] ?? [] as $end) {
            $empresa->enderecos()->create($this->mapEndereco($end));
        }
        foreach ($resp['emails'] ?? [] as $mail) {
            $empresa->emails()->create($this->mapEmail($mail['email'] ?? null));
        }

        return $empresa;
    }

    /**
     * Persiste o resultado de uma consulta reversa (telefone/e-mail/nome-endereço),
     * que devolve apenas dados básicos de pessoaFisica/pessoaJuridica.
     * $extra opcionalmente anexa o telefone/e-mail pesquisado ao registro.
     */
    private function persistirReversa(array $data, array $extra): array
    {
        $resp = is_array($data['resposta'] ?? null) ? $data['resposta'] : [];
        $protocolo = $data['cabecalho']['protocolo'] ?? null;
        $out = ['pessoa' => null, 'empresa' => null];

        if ($pf = ($resp['pessoaFisica'] ?? null)) {
            $cpf = self::digits($pf['cpf'] ?? null);
            if ($cpf !== '') {
                $pessoa = AssertivaPessoa::updateOrCreate(
                    ['cpf' => $cpf],
                    [
                        'nome' => $pf['nome'] ?? null,
                        'data_nascimento' => $pf['dataNascimento'] ?? null,
                        'mae_nome' => $pf['nomeMae'] ?? null,
                        'protocolo' => $protocolo,
                        'payload' => $pf,
                        'data_consulta' => now(),
                    ]
                );
                $this->anexarExtra($pessoa, 'pessoa', $extra);
                $out['pessoa'] = $pessoa->fresh(['telefones', 'emails']);
            }
        }

        if ($pj = ($resp['pessoaJuridica'] ?? null)) {
            $cnpj = self::digits($pj['cnpj'] ?? null);
            if ($cnpj !== '') {
                $empresa = AssertivaEmpresa::updateOrCreate(
                    ['cnpj' => $cnpj],
                    [
                        'razao_social' => $pj['razaoSocial'] ?? null,
                        'data_abertura' => $pj['dataAbertura'] ?? null,
                        'protocolo' => $protocolo,
                        'payload' => $pj,
                        'data_consulta' => now(),
                    ]
                );
                $this->anexarExtra($empresa, 'empresa', $extra);
                $out['empresa'] = $empresa->fresh(['telefones', 'emails']);
            }
        }

        return $out;
    }

    private function anexarExtra(AssertivaPessoa|AssertivaEmpresa $owner, string $tipo, array $extra): void
    {
        $fk = $tipo === 'pessoa' ? 'assertiva_pessoa_id' : 'assertiva_empresa_id';

        if (! empty($extra['numero'])) {
            $owner->telefones()->updateOrCreate(
                [$fk => $owner->id, 'numero_normalizado' => $extra['numero']],
                ['numero' => $extra['numero']]
            );
        }
        if (! empty($extra['email'])) {
            $owner->emails()->updateOrCreate(
                [$fk => $owner->id, 'email_normalizado' => mb_strtolower($extra['email'])],
                ['email' => $extra['email']]
            );
        }
    }

    private function salvarTelefones($relation, array $telefones): void
    {
        foreach (['fixos' => 'FIXO', 'moveis' => 'MOVEL'] as $chave => $tipo) {
            foreach ($telefones[$chave] ?? [] as $tel) {
                $numero = $tel['numero'] ?? null;
                $relation->create([
                    'numero' => $numero,
                    'numero_normalizado' => self::digits($numero),
                    'tipo' => $tipo,
                    'whatsapp' => (bool) ($tel['aplicativos']['whatsapp'] ?? false),
                    'nao_perturbe' => (bool) ($tel['naoPerturbe'] ?? false),
                    'relacao' => $tel['relacao'] ?? null,
                ]);
            }
        }
    }

    private function mapEndereco(array $end): array
    {
        $logradouro = trim(($end['tipoLogradouro'] ?? '').' '.($end['logradouro'] ?? ''));

        return [
            'logradouro' => $logradouro !== '' ? $logradouro : null,
            'numero' => $end['numero'] ?? null,
            'complemento' => $end['complemento'] ?? null,
            'bairro' => $end['bairro'] ?? null,
            'cidade' => $end['cidade'] ?? null,
            'uf' => $end['uf'] ?? null,
            'cep' => $end['cep'] ?? null,
        ];
    }

    private function mapEmail(?string $email): array
    {
        return [
            'email' => $email,
            'email_normalizado' => $email ? mb_strtolower($email) : null,
        ];
    }
}
