<?php

namespace Tests\Feature\LkBeneficios;

use App\Models\People\Assertiva\AssertivaPessoa;
use App\Modules\LkBeneficios\Services\Assertiva\AssertivaService;
use App\Modules\LkBeneficios\Services\Assertiva\AssertivaTokenManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class AssertivaServiceTest extends AssertivaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.assertiva.client_id' => 'cli',
            'services.assertiva.client_secret' => 'sec',
            'services.assertiva.base_url' => 'https://api.assertivasolucoes.com.br',
            'services.assertiva.id_finalidade' => 5,
        ]);
        Cache::flush();
    }

    private function service(): AssertivaService
    {
        return new AssertivaService(new AssertivaTokenManager());
    }

    public function test_consultar_telefone_persiste_pessoa_e_vincula_numero(): void
    {
        Http::fake([
            '*/oauth2/v3/token' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/localize/v3/telefone*' => Http::response([
                'cabecalho' => ['protocolo' => 'proto-1'],
                'resposta' => [
                    'pessoaFisica' => [
                        'cpf' => '123.456.789-01',
                        'nome' => 'JOAO MARIO',
                        'dataNascimento' => '14/04/1970',
                        'nomeMae' => 'MARIA',
                    ],
                ],
            ]),
        ]);

        $res = $this->service()->consultarTelefone('11999998888');

        $this->assertSame('api_assertiva', $res['fonte']);
        $this->assertSame('proto-1', $res['protocolo']);

        $pessoa = AssertivaPessoa::where('cpf', '12345678901')->first();
        $this->assertNotNull($pessoa);
        $this->assertSame('JOAO MARIO', $pessoa->nome);
        $this->assertDatabaseHas('assertiva_telefones', [
            'assertiva_pessoa_id' => $pessoa->id,
            'numero_normalizado' => '11999998888',
        ], 'people_db');
    }

    public function test_consultar_cpf_persiste_pessoa_completa(): void
    {
        Http::fake([
            '*/oauth2/v3/token' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/localize/v3/cpf*' => Http::response([
                'cabecalho' => ['protocolo' => 'proto-cpf'],
                'resposta' => [
                    'dadosCadastrais' => [
                        'cpf' => '123.456.789-01',
                        'nome' => 'JOAO MARIO',
                        'sexo' => 'Masculino',
                        'maeNome' => 'MARIA',
                    ],
                    'telefones' => [
                        'fixos' => [['numero' => '(19) 3553-6256']],
                        'moveis' => [['numero' => '(11) 99898-9898', 'aplicativos' => ['whatsapp' => true]]],
                    ],
                    'enderecos' => [[
                        'tipoLogradouro' => 'R', 'logradouro' => 'DONATO', 'numero' => '32',
                        'cidade' => 'CAMPINAS', 'uf' => 'SP', 'cep' => '13070-074',
                    ]],
                    'emails' => [['email' => 'a@b.com']],
                ],
            ]),
        ]);

        $this->service()->consultarCpf('12345678901');

        $pessoa = AssertivaPessoa::where('cpf', '12345678901')->first();
        $this->assertNotNull($pessoa);
        $this->assertSame(2, $pessoa->telefones()->count());
        // payload completo guardado (nada se perde)
        $this->assertSame('MARIA', $pessoa->payload['dadosCadastrais']['maeNome']);
        $this->assertDatabaseHas('assertiva_telefones', ['numero_normalizado' => '11998989898', 'whatsapp' => 1, 'tipo' => 'MOVEL'], 'people_db');
        $this->assertDatabaseHas('assertiva_enderecos', ['cidade' => 'CAMPINAS', 'uf' => 'SP'], 'people_db');
        $this->assertDatabaseHas('assertiva_emails', ['email_normalizado' => 'a@b.com'], 'people_db');
    }

    public function test_renova_token_e_repete_quando_recebe_401(): void
    {
        Http::fake([
            '*/oauth2/v3/token' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/localize/v3/telefone*' => Http::sequence()
                ->push([], 401)
                ->push([
                    'cabecalho' => ['protocolo' => 'p'],
                    'resposta' => ['pessoaFisica' => ['cpf' => '12345678901', 'nome' => 'X']],
                ]),
        ]);

        $res = $this->service()->consultarTelefone('11999998888');

        $this->assertSame('api_assertiva', $res['fonte']);
        $this->assertNotNull(AssertivaPessoa::where('cpf', '12345678901')->first());
    }

    public function test_telefone_invalido_lanca_excecao(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service()->consultarTelefone('123');
    }
}
