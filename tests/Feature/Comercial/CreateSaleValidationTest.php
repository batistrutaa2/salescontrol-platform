<?php

namespace Tests\Feature\Comercial;

use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Vendas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateSaleValidationTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $vendedor;

    private int $contatoId;

    private int $operadoraId;

    private int $operadoraAnteriorId;

    private int $planoId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();

        $this->vendedor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);

        DB::table('tabulacoes')->insert([
            'id' => Tabulations::VENDA,
            'empresa_id' => $this->empresa->id,
            'descricao' => 'VENDA',
            'tipo_tabulacao' => 'C',
            'efetivo' => 'Y',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->operadoraId = DB::table('operadoras')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'nome' => 'OPERADORA TESTE',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->operadoraAnteriorId = DB::table('operadoras')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'nome' => 'OPERADORA ANTERIOR',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->planoId = DB::table('planos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'nome' => 'PLANO TESTE',
            'operadora_id' => $this->operadoraId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->vendedor->id,
            'nome_cliente' => 'Cliente Teste',
            'cpf' => '12345678901',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contatos_corretores')->insert([
            'empresa_id' => $this->empresa->id,
            'contato_id' => $this->contatoId,
            'user_id' => $this->vendedor->id,
            'tabulacao_id' => Tabulations::VENDA,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function titularValido(array $overrides = []): array
    {
        return array_merge([
            'nome' => 'TITULAR TESTE',
            'cpf' => '111.444.777-35',
            'data_nascimento' => '01/01/1990',
            'email' => 'titular@teste.com',
            'telefone1' => '(11) 91234-5678',
            'telefone2' => '',
            'cargo' => 'SOCIO',
            'plano_id' => $this->planoId,
            'coparticipacao' => 'Y',
            'plano_anterior' => 'NAO',
            'operadora_anterior_id' => '',
        ], $overrides);
    }

    private function dependenteValido(array $overrides = []): array
    {
        return array_merge([
            'nome' => 'DEPENDENTE TESTE',
            'cpf' => '222.555.888-96',
            'data_nascimento' => '05/05/2010',
            'email' => 'dependente@teste.com',
            'telefone1' => '(11) 95555-4444',
            'telefone2' => '',
            'parentesco' => 'FILHO',
            'plano_anterior' => 'NAO',
            'operadora_anterior_id' => '',
        ], $overrides);
    }

    private function payloadValido(array $overrides = []): array
    {
        return array_merge([
            'contato_id' => $this->contatoId,
            'tipo_contrato' => 'PME',
            'nome_contrato' => 'EMPRESA TESTE LTDA',
            'cpf_cnpj' => '12.345.678/0001-90',
            'tipo_empresa' => 'LTDA',
            'operadora_id' => $this->operadoraId,
            'titulares' => [$this->titularValido()],
        ], $overrides);
    }

    private function postVenda(array $payload)
    {
        return $this->actingAs($this->vendedor)
            ->from(route('comercial.novaProposta', $this->contatoId))
            ->post(route('comercial.createSale'), $payload);
    }

    public function test_titular_sem_campos_obrigatorios_retorna_erros(): void
    {
        $response = $this->postVenda($this->payloadValido([
            'titulares' => [
                $this->titularValido([
                    'cpf' => '',
                    'data_nascimento' => '',
                    'email' => '',
                    'telefone1' => '',
                    'cargo' => '',
                ]),
            ],
        ]));

        $response->assertSessionHasErrors([
            'titulares.0.cpf',
            'titulares.0.data_nascimento',
            'titulares.0.email',
            'titulares.0.telefone1',
            'titulares.0.cargo',
        ]);
        $this->assertDatabaseCount('vendas', 0);
    }

    public function test_dois_titulares_nao_podem_ter_o_mesmo_email(): void
    {
        $response = $this->postVenda($this->payloadValido([
            'titulares' => [
                $this->titularValido(['email' => 'mesmo@teste.com']),
                $this->titularValido([
                    'email' => 'MESMO@teste.com',
                    'telefone1' => '(11) 98888-7777',
                    'cpf' => '333.666.999-24',
                ]),
            ],
        ]));

        $response->assertSessionHasErrors(['titulares.0.email', 'titulares.1.email']);
        $this->assertDatabaseCount('vendas', 0);
    }

    public function test_dois_titulares_nao_podem_ter_o_mesmo_telefone_mesmo_com_mascaras_diferentes(): void
    {
        $response = $this->postVenda($this->payloadValido([
            'titulares' => [
                $this->titularValido(['telefone1' => '(11) 91234-5678']),
                $this->titularValido([
                    'email' => 'outro@teste.com',
                    'telefone1' => '11912345678',
                    'cpf' => '333.666.999-24',
                ]),
            ],
        ]));

        $response->assertSessionHasErrors(['titulares.1.telefone1']);
        $this->assertDatabaseCount('vendas', 0);
    }

    public function test_plano_anterior_sim_exige_operadora_anterior(): void
    {
        $response = $this->postVenda($this->payloadValido([
            'titulares' => [
                $this->titularValido([
                    'plano_anterior' => 'SIM',
                    'operadora_anterior_id' => '',
                ]),
            ],
        ]));

        $response->assertSessionHasErrors(['titulares.0.operadora_anterior_id']);
        $this->assertDatabaseCount('vendas', 0);
    }

    public function test_dependente_sem_campos_obrigatorios_retorna_erros(): void
    {
        $response = $this->postVenda($this->payloadValido([
            'titulares' => [
                $this->titularValido([
                    'dependentes' => [
                        $this->dependenteValido([
                            'cpf' => '',
                            'data_nascimento' => '',
                            'email' => '',
                            'telefone1' => '',
                            'parentesco' => '',
                        ]),
                    ],
                ]),
            ],
        ]));

        $response->assertSessionHasErrors([
            'titulares.0.dependentes.0.cpf',
            'titulares.0.dependentes.0.data_nascimento',
            'titulares.0.dependentes.0.email',
            'titulares.0.dependentes.0.telefone1',
            'titulares.0.dependentes.0.parentesco',
        ]);
        $this->assertDatabaseCount('vendas', 0);
    }

    public function test_dependente_com_plano_anterior_sim_exige_operadora_anterior(): void
    {
        $response = $this->postVenda($this->payloadValido([
            'titulares' => [
                $this->titularValido([
                    'dependentes' => [
                        $this->dependenteValido([
                            'plano_anterior' => 'SIM',
                            'operadora_anterior_id' => '',
                        ]),
                    ],
                ]),
            ],
        ]));

        $response->assertSessionHasErrors(['titulares.0.dependentes.0.operadora_anterior_id']);
    }

    public function test_dependente_pode_repetir_email_e_telefone_do_titular(): void
    {
        $response = $this->postVenda($this->payloadValido([
            'titulares' => [
                $this->titularValido([
                    'dependentes' => [
                        $this->dependenteValido([
                            'email' => 'titular@teste.com',
                            'telefone1' => '(11) 91234-5678',
                        ]),
                    ],
                ]),
            ],
        ]));

        $response->assertSessionDoesntHaveErrors([
            'titulares.0.dependentes.0.email',
            'titulares.0.dependentes.0.telefone1',
        ]);
        $this->assertDatabaseCount('vendas', 1);
        $this->assertDatabaseCount('vendas_dependentes', 1);
    }

    public function test_adesao_nao_exige_cargo(): void
    {
        $response = $this->postVenda($this->payloadValido([
            'tipo_contrato' => 'ADESAO',
            'tipo_empresa' => '',
            'titulares' => [
                $this->titularValido(['cargo' => '']),
            ],
        ]));

        $response->assertSessionDoesntHaveErrors(['titulares.0.cargo']);
        $this->assertDatabaseCount('vendas', 1);
    }

    public function test_happy_path_cria_venda_com_titular_e_dependente(): void
    {
        $response = $this->postVenda($this->payloadValido([
            'titulares' => [
                $this->titularValido([
                    'plano_anterior' => 'SIM',
                    'operadora_anterior_id' => $this->operadoraAnteriorId,
                    'dependentes' => [$this->dependenteValido()],
                ]),
            ],
        ]));

        $response->assertRedirect(route('sale.listSale'));
        $response->assertSessionHas('status', 'success');

        $venda = Vendas::first();
        $this->assertNotNull($venda);
        $this->assertSame($this->empresa->id, $venda->empresa_id);

        $this->assertDatabaseHas('vendas_titulares', [
            'venda_id' => $venda->id,
            'nome' => 'TITULAR TESTE',
            'operadora_anterior_id' => $this->operadoraAnteriorId,
        ]);
        $this->assertDatabaseHas('vendas_dependentes', [
            'venda_id' => $venda->id,
            'nome' => 'DEPENDENTE TESTE',
            'parentesco' => 'FILHO',
        ]);
    }
}
