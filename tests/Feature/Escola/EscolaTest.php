<?php

namespace Tests\Feature\Escola;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\EscolaAula;
use App\Models\EscolaAulaProgresso;
use App\Models\EscolaModulo;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EscolaTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private Empresa $outraEmpresa;

    private User $admin;

    private User $vendedor;

    private User $supervisor;

    private User $adminOutraEmpresa;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::BACKOFFICE, 'tipo_usuario' => 'BACKOFFICE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::SUPERVISOR, 'tipo_usuario' => 'SUPERVISOR', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->outraEmpresa = Empresa::factory()->create();

        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
        $this->vendedor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
            'escola_habilitada' => false,
        ]);
        $this->supervisor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::SUPERVISOR,
            'ativo' => 'Y',
        ]);
        $this->adminOutraEmpresa = User::factory()->create([
            'empresa_id' => $this->outraEmpresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
    }

    private function criarModulo(?Empresa $empresa = null, array $attrs = []): EscolaModulo
    {
        $empresa = $empresa ?? $this->empresa;

        return app(TenantContext::class)->run($empresa->id, fn () => EscolaModulo::create(array_merge([
            'empresa_id' => $empresa->id,
            'titulo' => 'Portabilidade',
            'ativo' => true,
            'ordem' => 0,
        ], $attrs)));
    }

    private function criarAula(EscolaModulo $modulo, array $attrs = []): EscolaAula
    {
        return app(TenantContext::class)->run((int) $modulo->empresa_id, fn () => EscolaAula::create(array_merge([
            'empresa_id' => $modulo->empresa_id,
            'escola_modulo_id' => $modulo->id,
            'titulo' => 'Aula 1',
            'ativo' => true,
            'ordem' => 0,
        ], $attrs)));
    }

    private function habilitar(User $user): User
    {
        $user->update(['escola_habilitada' => true]);

        return $user->fresh();
    }

    // ----------------------------------------------------------- Gestão (admin)

    #[Test]
    public function admin_cria_modulo_com_sucesso(): void
    {
        $resp = $this->actingAs($this->admin)->postJson(route('escola.gestao.modulos.store'), [
            'titulo' => 'Negociação',
            'descricao' => 'Técnicas de fechamento',
            'ordem' => 1,
            'ativo' => 1,
        ]);

        $resp->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('escola_modulos', [
            'empresa_id' => $this->empresa->id,
            'titulo' => 'Negociação',
        ]);
    }

    #[Test]
    public function store_modulo_valida_titulo_obrigatorio(): void
    {
        $this->actingAs($this->admin)->postJson(route('escola.gestao.modulos.store'), [
            'descricao' => 'sem titulo',
        ])->assertStatus(422)->assertJsonValidationErrors(['titulo']);
    }

    #[Test]
    public function admin_cria_aula_em_um_modulo(): void
    {
        $modulo = $this->criarModulo();

        $this->actingAs($this->admin)->postJson(route('escola.gestao.aulas.store', $modulo->id), [
            'titulo' => 'Introdução',
            'ordem' => 0,
            'ativo' => 1,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('escola_aulas', [
            'escola_modulo_id' => $modulo->id,
            'titulo' => 'Introdução',
        ]);
    }

    #[Test]
    public function vendedor_recebe_403_na_area_de_gestao(): void
    {
        $this->actingAs($this->vendedor)->get(route('escola.gestao.index'))->assertForbidden();
        $this->actingAs($this->vendedor)->postJson(route('escola.gestao.modulos.store'), ['titulo' => 'X'])->assertForbidden();
    }

    #[Test]
    public function supervisor_recebe_403_na_area_de_gestao(): void
    {
        $this->actingAs($this->supervisor)->get(route('escola.gestao.index'))->assertForbidden();
        $this->actingAs($this->supervisor)->get(route('escola.gestao.acessos'))->assertForbidden();
    }

    // ----------------------------------------------------------- Acesso do aluno

    #[Test]
    public function vendedor_sem_acesso_recebe_403_na_escola(): void
    {
        $this->actingAs($this->vendedor)->get(route('escola.index'))->assertForbidden();
    }

    #[Test]
    public function vendedor_habilitado_acessa_a_escola(): void
    {
        $this->criarModulo();
        $habilitado = $this->habilitar($this->vendedor);

        $this->actingAs($habilitado)->get(route('escola.index'))->assertOk();
    }

    #[Test]
    public function admin_sempre_acessa_a_escola_sem_flag(): void
    {
        $this->actingAs($this->admin)->get(route('escola.index'))->assertOk();
    }

    #[Test]
    public function salvar_progresso_marca_conclusao_acima_de_90_porcento(): void
    {
        $modulo = $this->criarModulo();
        $aula = $this->criarAula($modulo, ['duracao_segundos' => 100]);
        $habilitado = $this->habilitar($this->vendedor);

        $this->actingAs($habilitado)->postJson(
            route('escola.aulas.progresso', $aula->id),
            ['posicao' => 95, 'duracao' => 100]
        )->assertOk()->assertJson(['concluida' => true, 'percentual' => 95]);

        $this->assertDatabaseHas('escola_aula_progresso', [
            'user_id' => $this->vendedor->id,
            'escola_aula_id' => $aula->id,
            'concluida' => true,
        ]);
    }

    #[Test]
    public function progresso_usa_upsert_e_nao_duplica_linhas(): void
    {
        $modulo = $this->criarModulo();
        $aula = $this->criarAula($modulo, ['duracao_segundos' => 100]);
        $habilitado = $this->habilitar($this->vendedor);

        $this->actingAs($habilitado)->postJson(route('escola.aulas.progresso', $aula->id), ['posicao' => 10, 'duracao' => 100]);
        $this->actingAs($habilitado)->postJson(route('escola.aulas.progresso', $aula->id), ['posicao' => 50, 'duracao' => 100]);

        $this->assertEquals(1, EscolaAulaProgresso::where('user_id', $this->vendedor->id)->where('escola_aula_id', $aula->id)->count());
    }

    #[Test]
    public function progresso_usa_duracao_do_servidor_e_percentual_configurado_na_empresa(): void
    {
        $modulo = $this->criarModulo();
        $aula = $this->criarAula($modulo, ['duracao_segundos' => 100]);
        $habilitado = $this->habilitar($this->vendedor);

        $this->actingAs($this->admin)
            ->putJson(route('escola.gestao.configuracoes.update'), [
                'escola_percentual_conclusao' => 75,
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'escola_percentual_conclusao' => 75,
            ]);

        $this->actingAs($habilitado)
            ->postJson(route('escola.aulas.progresso', $aula->id), [
                'posicao' => 74,
                'duracao' => 1,
            ])
            ->assertOk()
            ->assertJson(['concluida' => false, 'percentual' => 74]);

        $this->actingAs($habilitado)
            ->postJson(route('escola.aulas.progresso', $aula->id), [
                'posicao' => 75,
                'duracao' => 999,
            ])
            ->assertOk()
            ->assertJson(['concluida' => true, 'percentual' => 75]);

        $this->assertSame(75, $this->empresa->fresh()->escola_percentual_conclusao);
        $this->assertSame(90, $this->outraEmpresa->fresh()->escola_percentual_conclusao);
    }

    #[Test]
    public function progresso_sem_duracao_confirmada_nao_pode_ser_concluido_pelo_cliente(): void
    {
        $modulo = $this->criarModulo();
        $aula = $this->criarAula($modulo, ['duracao_segundos' => null]);
        $habilitado = $this->habilitar($this->vendedor);

        $this->actingAs($habilitado)
            ->postJson(route('escola.aulas.progresso', $aula->id), [
                'posicao' => 100,
                'duracao' => 100,
            ])
            ->assertOk()
            ->assertJson(['concluida' => false, 'percentual' => 0]);
    }

    // ----------------------------------------------------------- Multi-tenant

    #[Test]
    public function admin_de_outra_empresa_nao_edita_modulo_alheio(): void
    {
        $modulo = $this->criarModulo();

        $this->actingAs($this->adminOutraEmpresa)
            ->putJson(route('escola.gestao.modulos.update', $modulo->id), ['titulo' => 'Hackeado'])
            ->assertNotFound();

        $this->assertDatabaseHas('escola_modulos', ['id' => $modulo->id, 'titulo' => 'Portabilidade']);
    }

    #[Test]
    public function aluno_habilitado_so_enxerga_modulos_da_propria_empresa(): void
    {
        $this->criarModulo($this->empresa, ['titulo' => 'Modulo Empresa A']);
        $this->criarModulo($this->outraEmpresa, ['titulo' => 'Modulo Empresa B']);
        $habilitado = $this->habilitar($this->vendedor);

        $this->actingAs($habilitado)
            ->get(route('escola.index'))
            ->assertOk()
            ->assertSee('Modulo Empresa A')
            ->assertDontSee('Modulo Empresa B');
    }

    #[Test]
    public function reordenacao_rejeita_lote_com_id_de_outra_empresa_sem_alteracao_parcial(): void
    {
        $moduloLocal = $this->criarModulo($this->empresa, ['ordem' => 1]);
        $moduloExterno = $this->criarModulo($this->outraEmpresa, ['ordem' => 2]);

        $this->actingAs($this->admin)
            ->postJson(route('escola.gestao.modulos.reordenar'), [
                'ordens' => [
                    ['id' => $moduloLocal->id, 'ordem' => 99],
                    ['id' => $moduloExterno->id, 'ordem' => 0],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ordens.1.id']);

        $aulaLocal = $this->criarAula($moduloLocal, ['ordem' => 1]);
        $aulaExterna = $this->criarAula($moduloExterno, ['ordem' => 2]);

        $this->actingAs($this->admin)
            ->postJson(route('escola.gestao.aulas.reordenar'), [
                'ordens' => [
                    ['id' => $aulaLocal->id, 'ordem' => 99],
                    ['id' => $aulaExterna->id, 'ordem' => 0],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ordens.1.id']);

        $this->assertSame(1, $moduloLocal->fresh()->ordem);
        $this->assertSame(1, $aulaLocal->fresh()->ordem);
    }

    #[Test]
    public function relatorio_rejeita_filtros_de_outra_empresa(): void
    {
        $moduloExterno = $this->criarModulo($this->outraEmpresa);

        $this->actingAs($this->admin)
            ->getJson(route('escola.gestao.relatorio.data', ['modulo_id' => $moduloExterno->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['modulo_id']);

        $this->actingAs($this->admin)
            ->getJson(route('escola.gestao.relatorio.data', ['user_id' => $this->adminOutraEmpresa->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    }

    #[Test]
    public function master_sem_empresa_de_origem_administra_somente_a_empresa_ativa(): void
    {
        $master = User::factory()->create([
            'empresa_id' => null,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);
        $moduloEmpresaA = $this->criarModulo($this->empresa, ['titulo' => 'Treinamento Empresa A']);
        $moduloOutraEmpresa = $this->criarModulo($this->outraEmpresa, ['titulo' => 'Treinamento Empresa B']);

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $this->outraEmpresa->id])
            ->get(route('escola.gestao.index'))
            ->assertOk()
            ->assertSee($moduloOutraEmpresa->titulo)
            ->assertDontSee($moduloEmpresaA->titulo);

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $this->outraEmpresa->id])
            ->postJson(route('escola.gestao.modulos.store'), [
                'titulo' => 'Funil de treinamento B',
            ])
            ->assertOk();

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $this->outraEmpresa->id])
            ->putJson(route('escola.gestao.configuracoes.update'), [
                'escola_percentual_conclusao' => 80,
            ])
            ->assertOk();

        $this->assertDatabaseHas('escola_modulos', [
            'empresa_id' => $this->outraEmpresa->id,
            'titulo' => 'Funil de treinamento B',
        ]);
        $this->assertSame(90, $this->empresa->fresh()->escola_percentual_conclusao);
        $this->assertSame(80, $this->outraEmpresa->fresh()->escola_percentual_conclusao);
        $this->assertNull($master->fresh()->empresa_id);
    }

    #[Test]
    public function master_global_nao_grava_progresso_como_aluno_da_empresa_ativa(): void
    {
        $master = User::factory()->create([
            'empresa_id' => null,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);
        $aula = $this->criarAula($this->criarModulo());

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $this->empresa->id])
            ->postJson(route('escola.aulas.progresso', $aula->id), [
                'posicao' => 10,
                'duracao' => 100,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('escola_aula_progresso', [
            'empresa_id' => $this->empresa->id,
            'user_id' => $master->id,
        ]);
    }

    // ----------------------------------------------------------- Liberar acesso

    #[Test]
    public function admin_libera_acesso_de_vendedor(): void
    {
        $this->actingAs($this->admin)->postJson(
            route('escola.gestao.acessos.toggle', $this->vendedor->id),
            ['habilitada' => 1]
        )->assertOk()->assertJson(['success' => true, 'habilitada' => true]);

        $this->assertTrue($this->vendedor->fresh()->escola_habilitada);

        // E agora o vendedor consegue acessar
        $this->actingAs($this->vendedor->fresh())->get(route('escola.index'))->assertOk();
    }

    #[Test]
    public function admin_remove_acesso_de_vendedor(): void
    {
        $this->habilitar($this->vendedor);

        $this->actingAs($this->admin)->postJson(
            route('escola.gestao.acessos.toggle', $this->vendedor->id),
            ['habilitada' => 0]
        )->assertOk()->assertJson(['success' => true, 'habilitada' => false]);

        $this->assertFalse($this->vendedor->fresh()->escola_habilitada);
        $this->actingAs($this->vendedor->fresh())->get(route('escola.index'))->assertForbidden();
    }

    #[Test]
    public function toggle_de_acesso_respeita_multi_tenant(): void
    {
        $vendedorOutraEmpresa = User::factory()->create([
            'empresa_id' => $this->outraEmpresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);

        $this->actingAs($this->admin)->postJson(
            route('escola.gestao.acessos.toggle', $vendedorOutraEmpresa->id),
            ['habilitada' => 1]
        )->assertNotFound();

        $this->assertFalse($vendedorOutraEmpresa->fresh()->escola_habilitada);
    }

    // ----------------------------------------------------------- Upload / materiais

    #[Test]
    public function presign_rejeita_formato_de_video_invalido(): void
    {
        $modulo = $this->criarModulo();
        $aula = $this->criarAula($modulo);

        $this->actingAs($this->admin)->postJson(route('escola.gestao.upload.presign'), [
            'aula_id' => $aula->id,
            'filename' => 'arquivo.exe',
            'content_type' => 'application/octet-stream',
            'size' => 1000,
        ])->assertStatus(422);
    }

    #[Test]
    public function admin_anexa_material_pdf_a_uma_aula(): void
    {
        Storage::fake('s3');

        $modulo = $this->criarModulo();
        $aula = $this->criarAula($modulo);

        $pdf = File::create('material.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin)->post(route('escola.gestao.materiais.store', $aula->id), [
            'arquivo' => $pdf,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('escola_aula_materiais', [
            'escola_aula_id' => $aula->id,
            'empresa_id' => $this->empresa->id,
        ]);
    }
}
