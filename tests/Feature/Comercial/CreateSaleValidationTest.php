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
            'cpf' => '529.982.247-25',
            'data_nascimento' => '05/05/2010',
            'email' => 'dependente@teste.com',
            'telefone1' => '(11) 95555-4444',
            'telefone2' => '',
            'parentesco' => 'FILHO',
            'plano_id' => $this->planoId,
            'coparticipacao' => 'Y',
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
            'cpf_cnpj' => '12.345.678/0001-95',
            'tipo_empresa' => 'LTDA',
            'operadora_id' => $this->operadoraId,
            'valor_contrato' => 'R$ 500,00',
            'angariacao_status' => 'NAO',
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

    public function test_portabilidade_exige_nome_operadora_e_plano_de_destino(): void
    {
        $response = $this->postVenda($this->payloadValido([
            'portabilidades' => [[
                'nome' => '',
                'cpf' => '',
                'operadora_anterior_id' => '',
                'operadora_destino_id' => '',
                'plano_destino_id' => '',
            ]],
        ]));

        $response->assertSessionHasErrors([
            'portabilidades.0.nome',
            'portabilidades.0.operadora_destino_id',
            'portabilidades.0.plano_destino_id',
        ]);
        $this->assertDatabaseCount('vendas', 0);
    }

    public function test_plano_da_portabilidade_deve_pertencer_a_operadora_de_destino(): void
    {
        $response = $this->postVenda($this->payloadValido([
            'portabilidades' => [[
                'nome' => 'CLIENTE PORTABILIDADE',
                'cpf' => '111.444.777-35',
                'operadora_anterior_id' => $this->operadoraAnteriorId,
                'operadora_destino_id' => $this->operadoraAnteriorId,
                'plano_destino_id' => $this->planoId,
            ]],
        ]));

        $response->assertSessionHasErrors(['portabilidades.0.plano_destino_id']);
        $this->assertDatabaseCount('vendas', 0);
    }

    public function test_portabilidade_nao_aceita_destino_de_outra_empresa(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $outraOperadoraId = DB::table('operadoras')->insertGetId([
            'empresa_id' => $outraEmpresa->id,
            'nome' => 'OPERADORA EXTERNA',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $outroPlanoId = DB::table('planos')->insertGetId([
            'empresa_id' => $outraEmpresa->id,
            'operadora_id' => $outraOperadoraId,
            'nome' => 'PLANO EXTERNO',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postVenda($this->payloadValido([
            'portabilidades' => [[
                'nome' => 'CLIENTE EXTERNO',
                'cpf' => '111.444.777-35',
                'operadora_anterior_id' => $this->operadoraAnteriorId,
                'operadora_destino_id' => $outraOperadoraId,
                'plano_destino_id' => $outroPlanoId,
            ]],
        ]));

        $response->assertSessionHasErrors([
            'portabilidades.0.operadora_destino_id',
            'portabilidades.0.plano_destino_id',
        ]);
        $this->assertDatabaseCount('vendas', 0);
    }

    public function test_happy_path_salva_destino_da_portabilidade_e_calcula_quantidade(): void
    {
        $response = $this->postVenda($this->payloadValido([
            'portabilidade_status' => 'NAO',
            'qtd_portabilidade' => 0,
            'portabilidades' => [[
                'nome' => 'Cliente Portabilidade',
                'cpf' => '111.444.777-35',
                'operadora_anterior_id' => $this->operadoraAnteriorId,
                'operadora_destino_id' => $this->operadoraId,
                'plano_destino_id' => $this->planoId,
            ]],
        ]));

        $response->assertRedirect(route('sale.listSale'));
        $venda = Vendas::firstOrFail();
        $this->assertSame('SIM', $venda->portabilidade_status);
        $this->assertSame(1, $venda->qtd_portabilidade);
        $this->assertDatabaseHas('vendas_portabilidades', [
            'venda_id' => $venda->id,
            'nome' => 'CLIENTE PORTABILIDADE',
            'cpf' => '11144477735',
            'operadora_anterior_id' => $this->operadoraAnteriorId,
            'operadora_destino_id' => $this->operadoraId,
            'plano_destino_id' => $this->planoId,
        ]);
    }

    public function test_tela_exibe_modal_de_adicionar_portabilidade(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('comercial.novaProposta', $this->contatoId))
            ->assertOk()
            ->assertSee('Adicionar Portabilidade')
            ->assertSee('Quem está contratando?')
            ->assertSee('aria-label="Etapa 1 de 5: Identificação, atual"', false)
            ->assertSee('Monte os grupos familiares')
            ->assertSee('Esta proposta possui portabilidade?')
            ->assertSee('window.propostaLookupUrl', false)
            ->assertSee('id="btn-consultar-portabilidade-cpf"', false)
            ->assertSee('Digite um CPF válido para consultar na Lemit.')
            ->assertSee('Operadora anterior')
            ->assertSee('Operadora de destino')
            ->assertSee('Plano de destino');
    }

    public function test_tela_separa_produto_valores_e_move_observacoes_para_etapa_final(): void
    {
        $response = $this->actingAs($this->vendedor)
            ->get(route('comercial.novaProposta', $this->contatoId));

        $response
            ->assertOk()
            ->assertSee('Produto vendido')
            ->assertSee('Valores da venda')
            ->assertSee('Observações para o pós-venda')
            ->assertSee('maxlength="10000"', false);

        $html = $response->getContent();
        $produto = strpos($html, 'id="np-product-card-title"');
        $valores = strpos($html, 'id="np-values-card-title"');
        $etapaFinal = strpos($html, 'data-step-panel="5"');
        $observacoes = strpos($html, 'id="obs_contrato"');
        $documentos = strpos($html, 'id="np-documentos-destino"');

        $this->assertSame(1, substr_count($html, 'id="obs_contrato"'));
        $this->assertNotFalse($produto);
        $this->assertNotFalse($valores);
        $this->assertNotFalse($etapaFinal);
        $this->assertNotFalse($observacoes);
        $this->assertNotFalse($documentos);
        $this->assertTrue($produto < $valores);
        $this->assertTrue($etapaFinal < $observacoes && $observacoes < $documentos);
    }

    public function test_tela_entrega_estrutura_interativa_dos_beneficiarios(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('comercial.novaProposta', $this->contatoId))
            ->assertOk()
            ->assertSee('data-step-panel="3"', false)
            ->assertSee('id="titulares-container"', false)
            ->assertSee('id="template-titular"', false)
            ->assertSee('id="btn-add-titular"', false)
            ->assertSee('id="btn-add-titular" aria-describedby="np-add-titular-hint" disabled', false)
            ->assertSee('class="btn-action btn-add-dep" disabled', false)
            ->assertSee('class="titular-completion" role="status"', false)
            ->assertSee('id="dep-modal-overlay"', false)
            ->assertSee('id="btn-dep-modal-save"', false);
    }

    public function test_adesao_nao_exige_cargo(): void
    {
        $response = $this->postVenda($this->payloadValido([
            'tipo_contrato' => 'ADESAO',
            'cpf_cnpj' => '529.982.247-25',
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
            'plano_id' => $this->planoId,
            'coparticipacao' => 'Y',
        ]);
    }

    public function test_cadastro_preserva_observacao_maior_que_255_caracteres(): void
    {
        $observacao = "O plano sul america engloba MARCOS - MAURO E PATRICIA\n\n"
            ."Faremos ativação do Mauro e familia\n"
            ."E portabilidade do Marcos e Patricia\n\n"
            ."Inseri os documentos neste primeiro momento apenas da ativação.\n"
            .'problema com a carta de permanencia do grupo que esta na porto, esposa e filhos do Mauro, ela caiu na retenção e n conseguiu obter.';

        $this->assertGreaterThan(255, mb_strlen($observacao));

        $this->postVenda($this->payloadValido(['obs_contrato' => $observacao]))
            ->assertRedirect(route('sale.listSale'))
            ->assertSessionHasNoErrors();

        $this->assertSame($observacao, Vendas::firstOrFail()->obs_contrato);
    }

    public function test_cadastro_rejeita_observacao_acima_do_limite_documentado(): void
    {
        $this->postVenda($this->payloadValido(['obs_contrato' => str_repeat('a', 10001)]))
            ->assertSessionHasErrors(['obs_contrato']);

        $this->assertDatabaseCount('vendas', 0);
    }

    public function test_valor_e_angariacao_condicional_sao_obrigatorios(): void
    {
        $response = $this->postVenda($this->payloadValido([
            'valor_contrato' => 'R$ 0,00',
            'angariacao_status' => 'SIM',
            'taxa_angariacao' => 'R$ 0,00',
        ]));

        $response->assertSessionHasErrors(['valor_contrato', 'taxa_angariacao']);
        $this->assertDatabaseCount('vendas', 0);
    }
}
