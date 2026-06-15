<?php

namespace Tests\Feature\Escola;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\EscolaAula;
use App\Models\EscolaAulaProgresso;
use App\Models\EscolaModulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

        return EscolaModulo::create(array_merge([
            'empresa_id' => $empresa->id,
            'titulo' => 'Portabilidade',
            'ativo' => true,
            'ordem' => 0,
        ], $attrs));
    }

    private function criarAula(EscolaModulo $modulo, array $attrs = []): EscolaAula
    {
        return EscolaAula::create(array_merge([
            'empresa_id' => $modulo->empresa_id,
            'escola_modulo_id' => $modulo->id,
            'titulo' => 'Aula 1',
            'ativo' => true,
            'ordem' => 0,
        ], $attrs));
    }

    private function habilitar(User $user): User
    {
        $user->update(['escola_habilitada' => true]);

        return $user->fresh();
    }

    // ----------------------------------------------------------- Gestão (admin)

    /** @test */
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

    /** @test */
    public function store_modulo_valida_titulo_obrigatorio(): void
    {
        $this->actingAs($this->admin)->postJson(route('escola.gestao.modulos.store'), [
            'descricao' => 'sem titulo',
        ])->assertStatus(422)->assertJsonValidationErrors(['titulo']);
    }

    /** @test */
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

    /** @test */
    public function vendedor_recebe_403_na_area_de_gestao(): void
    {
        $this->actingAs($this->vendedor)->get(route('escola.gestao.index'))->assertForbidden();
        $this->actingAs($this->vendedor)->postJson(route('escola.gestao.modulos.store'), ['titulo' => 'X'])->assertForbidden();
    }

    /** @test */
    public function supervisor_recebe_403_na_area_de_gestao(): void
    {
        $this->actingAs($this->supervisor)->get(route('escola.gestao.index'))->assertForbidden();
        $this->actingAs($this->supervisor)->get(route('escola.gestao.acessos'))->assertForbidden();
    }

    // ----------------------------------------------------------- Acesso do aluno

    /** @test */
    public function vendedor_sem_acesso_recebe_403_na_escola(): void
    {
        $this->actingAs($this->vendedor)->get(route('escola.index'))->assertForbidden();
    }

    /** @test */
    public function vendedor_habilitado_acessa_a_escola(): void
    {
        $this->criarModulo();
        $habilitado = $this->habilitar($this->vendedor);

        $this->actingAs($habilitado)->get(route('escola.index'))->assertOk();
    }

    /** @test */
    public function admin_sempre_acessa_a_escola_sem_flag(): void
    {
        $this->actingAs($this->admin)->get(route('escola.index'))->assertOk();
    }

    /** @test */
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

    /** @test */
    public function progresso_usa_upsert_e_nao_duplica_linhas(): void
    {
        $modulo = $this->criarModulo();
        $aula = $this->criarAula($modulo, ['duracao_segundos' => 100]);
        $habilitado = $this->habilitar($this->vendedor);

        $this->actingAs($habilitado)->postJson(route('escola.aulas.progresso', $aula->id), ['posicao' => 10, 'duracao' => 100]);
        $this->actingAs($habilitado)->postJson(route('escola.aulas.progresso', $aula->id), ['posicao' => 50, 'duracao' => 100]);

        $this->assertEquals(1, EscolaAulaProgresso::where('user_id', $this->vendedor->id)->where('escola_aula_id', $aula->id)->count());
    }

    // ----------------------------------------------------------- Multi-tenant

    /** @test */
    public function admin_de_outra_empresa_nao_edita_modulo_alheio(): void
    {
        $modulo = $this->criarModulo();

        $this->actingAs($this->adminOutraEmpresa)
            ->putJson(route('escola.gestao.modulos.update', $modulo->id), ['titulo' => 'Hackeado'])
            ->assertNotFound();

        $this->assertDatabaseHas('escola_modulos', ['id' => $modulo->id, 'titulo' => 'Portabilidade']);
    }

    /** @test */
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

    // ----------------------------------------------------------- Liberar acesso

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
    public function admin_anexa_material_pdf_a_uma_aula(): void
    {
        Storage::fake('s3');

        $modulo = $this->criarModulo();
        $aula = $this->criarAula($modulo);

        $pdf = \Illuminate\Http\Testing\File::create('material.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin)->post(route('escola.gestao.materiais.store', $aula->id), [
            'arquivo' => $pdf,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('escola_aula_materiais', [
            'escola_aula_id' => $aula->id,
            'empresa_id' => $this->empresa->id,
        ]);
    }
}
