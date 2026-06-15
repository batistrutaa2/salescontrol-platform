<?php

namespace App\Http\Controllers\pages\escola;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\EscolaAula;
use App\Models\EscolaAulaMaterial;
use App\Models\EscolaAulaProgresso;
use App\Models\EscolaModulo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EscolaAdminController extends Controller
{
    private const ALLOWED_ROLES = [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER];

    private const VIDEO_MIMES = ['video/mp4', 'video/webm', 'video/quicktime'];

    private const MAX_VIDEO_BYTES = 2147483648; // 2 GB

    private function checkAccess(): void
    {
        if (! in_array(Auth::user()->user_role_id, self::ALLOWED_ROLES)) {
            abort(403, 'Acesso não autorizado.');
        }
    }

    private function empresaId(): int
    {
        return Auth::user()->empresa_id;
    }

    // ----------------------------------------------------------------- Módulos

    public function index()
    {
        $this->checkAccess();

        $modulos = EscolaModulo::where('empresa_id', $this->empresaId())
            ->withCount('aulas')
            ->orderBy('ordem')
            ->orderBy('titulo')
            ->get();

        return view('content.pages.escola.gestao.index', ['modulos' => $modulos]);
    }

    public function storeModulo(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:2000',
            'ordem' => 'nullable|integer|min:0',
            'ativo' => 'nullable|boolean',
            'capa' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $modulo = EscolaModulo::create([
            'empresa_id' => $this->empresaId(),
            'titulo' => $validated['titulo'],
            'descricao' => $validated['descricao'] ?? null,
            'slug' => Str::slug($validated['titulo']),
            'ordem' => $validated['ordem'] ?? 0,
            'ativo' => $request->boolean('ativo', true),
            'created_by' => Auth::id(),
        ]);

        if ($request->hasFile('capa')) {
            $modulo->capa_path = $this->salvarCapa($request->file('capa'), $modulo);
            $modulo->save();
        }

        return response()->json(['success' => true, 'id' => $modulo->id]);
    }

    public function updateModulo(Request $request, int $modulo): JsonResponse
    {
        $this->checkAccess();

        $modulo = EscolaModulo::where('empresa_id', $this->empresaId())->findOrFail($modulo);

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:2000',
            'ordem' => 'nullable|integer|min:0',
            'ativo' => 'nullable|boolean',
            'capa' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $modulo->titulo = $validated['titulo'];
        $modulo->descricao = $validated['descricao'] ?? null;
        $modulo->slug = Str::slug($validated['titulo']);
        $modulo->ordem = $validated['ordem'] ?? $modulo->ordem;
        $modulo->ativo = $request->boolean('ativo', $modulo->ativo);

        if ($request->hasFile('capa')) {
            if ($modulo->capa_path) {
                Storage::disk('s3')->delete($modulo->capa_path);
            }
            $modulo->capa_path = $this->salvarCapa($request->file('capa'), $modulo);
        }

        $modulo->save();

        return response()->json(['success' => true]);
    }

    public function destroyModulo(int $modulo): JsonResponse
    {
        $this->checkAccess();

        $modulo = EscolaModulo::where('empresa_id', $this->empresaId())
            ->with('aulas.materiais')
            ->findOrFail($modulo);

        // Remove objetos S3 (FK cascade NÃO apaga arquivos)
        foreach ($modulo->aulas as $aula) {
            $this->apagarArquivosDaAula($aula);
        }
        if ($modulo->capa_path) {
            Storage::disk('s3')->delete($modulo->capa_path);
        }

        $modulo->delete(); // cascade nas aulas/materiais/progresso

        return response()->json(['success' => true]);
    }

    public function reordenarModulos(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'ordens' => 'required|array',
            'ordens.*.id' => 'required|integer',
            'ordens.*.ordem' => 'required|integer|min:0',
        ]);

        foreach ($validated['ordens'] as $item) {
            EscolaModulo::where('empresa_id', $this->empresaId())
                ->where('id', $item['id'])
                ->update(['ordem' => $item['ordem']]);
        }

        return response()->json(['success' => true]);
    }

    // ------------------------------------------------------------------- Aulas

    public function aulas(int $modulo)
    {
        $this->checkAccess();

        $modulo = EscolaModulo::where('empresa_id', $this->empresaId())->findOrFail($modulo);
        $aulas = $modulo->aulas()->withCount('materiais')->with('materiais')->get();

        return view('content.pages.escola.gestao.aulas', [
            'modulo' => $modulo,
            'aulas' => $aulas,
        ]);
    }

    public function storeAula(Request $request, int $modulo): JsonResponse
    {
        $this->checkAccess();

        $modulo = EscolaModulo::where('empresa_id', $this->empresaId())->findOrFail($modulo);

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:5000',
            'ordem' => 'nullable|integer|min:0',
            'ativo' => 'nullable|boolean',
        ]);

        $aula = EscolaAula::create([
            'empresa_id' => $this->empresaId(),
            'escola_modulo_id' => $modulo->id,
            'titulo' => $validated['titulo'],
            'descricao' => $validated['descricao'] ?? null,
            'ordem' => $validated['ordem'] ?? 0,
            'ativo' => $request->boolean('ativo', true),
            'created_by' => Auth::id(),
        ]);

        return response()->json(['success' => true, 'id' => $aula->id]);
    }

    public function updateAula(Request $request, int $aula): JsonResponse
    {
        $this->checkAccess();

        $aula = EscolaAula::where('empresa_id', $this->empresaId())->findOrFail($aula);

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:5000',
            'ordem' => 'nullable|integer|min:0',
            'ativo' => 'nullable|boolean',
        ]);

        $aula->titulo = $validated['titulo'];
        $aula->descricao = $validated['descricao'] ?? null;
        $aula->ordem = $validated['ordem'] ?? $aula->ordem;
        $aula->ativo = $request->boolean('ativo', $aula->ativo);
        $aula->save();

        return response()->json(['success' => true]);
    }

    public function destroyAula(int $aula): JsonResponse
    {
        $this->checkAccess();

        $aula = EscolaAula::where('empresa_id', $this->empresaId())
            ->with('materiais')
            ->findOrFail($aula);

        $this->apagarArquivosDaAula($aula);
        $aula->delete();

        return response()->json(['success' => true]);
    }

    public function reordenarAulas(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'ordens' => 'required|array',
            'ordens.*.id' => 'required|integer',
            'ordens.*.ordem' => 'required|integer|min:0',
        ]);

        foreach ($validated['ordens'] as $item) {
            EscolaAula::where('empresa_id', $this->empresaId())
                ->where('id', $item['id'])
                ->update(['ordem' => $item['ordem']]);
        }

        return response()->json(['success' => true]);
    }

    // ----------------------------------------------------------- Upload vídeo

    /** Gera a URL assinada para o browser enviar o vídeo direto ao S3. */
    public function presignUpload(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'aula_id' => 'required|integer',
            'filename' => 'required|string|max:255',
            'content_type' => 'required|string',
            'size' => 'required|integer|min:1',
        ]);

        if (! in_array($validated['content_type'], self::VIDEO_MIMES, true)) {
            return response()->json(['success' => false, 'message' => 'Formato de vídeo não suportado (use MP4, WebM ou MOV).'], 422);
        }
        if ($validated['size'] > self::MAX_VIDEO_BYTES) {
            return response()->json(['success' => false, 'message' => 'Vídeo excede o limite de 2 GB.'], 422);
        }

        $aula = EscolaAula::where('empresa_id', $this->empresaId())->findOrFail($validated['aula_id']);

        $ext = strtolower(pathinfo($validated['filename'], PATHINFO_EXTENSION)) ?: 'mp4';
        $key = "escola/videos/{$aula->empresa_id}/{$aula->id}/".Str::uuid().".{$ext}";

        $resultado = Storage::disk('s3')->temporaryUploadUrl(
            $key,
            Carbon::now()->addMinutes(20),
            ['ContentType' => $validated['content_type']]
        );

        return response()->json([
            'success' => true,
            'url' => $resultado['url'],
            'headers' => $resultado['headers'],
            'key' => $key,
        ]);
    }

    /** Confirma o upload do vídeo e grava os metadados na aula. */
    public function confirmarVideo(Request $request, int $aula): JsonResponse
    {
        $this->checkAccess();

        $aula = EscolaAula::where('empresa_id', $this->empresaId())->findOrFail($aula);

        $validated = $request->validate([
            'key' => 'required|string',
            'nome_original' => 'required|string|max:255',
            'mime' => 'required|string',
            'tamanho' => 'nullable|integer|min:0',
            'duracao_segundos' => 'nullable|integer|min:0',
        ]);

        // A key precisa pertencer ao prefixo desta aula/empresa
        $prefixoEsperado = "escola/videos/{$aula->empresa_id}/{$aula->id}/";
        if (! Str::startsWith($validated['key'], $prefixoEsperado)) {
            return response()->json(['success' => false, 'message' => 'Chave de upload inválida.'], 422);
        }

        // Remove vídeo anterior, se houver
        if ($aula->video_path && $aula->video_path !== $validated['key']) {
            Storage::disk('s3')->delete($aula->video_path);
        }

        $aula->video_path = $validated['key'];
        $aula->video_nome_original = $validated['nome_original'];
        $aula->video_mime = $validated['mime'];
        $aula->video_tamanho_bytes = $validated['tamanho'] ?? null;
        $aula->duracao_segundos = $validated['duracao_segundos'] ?? null;
        $aula->save();

        return response()->json(['success' => true]);
    }

    // -------------------------------------------------------------- Materiais

    public function storeMaterial(Request $request, int $aula): JsonResponse
    {
        $this->checkAccess();

        $aula = EscolaAula::where('empresa_id', $this->empresaId())->findOrFail($aula);

        $request->validate([
            'arquivo' => 'required|file|mimes:pdf|max:20480',
            'titulo' => 'nullable|string|max:255',
        ]);

        $arquivo = $request->file('arquivo');
        $ext = $arquivo->getClientOriginalExtension();
        $directory = "escola/materiais/{$aula->empresa_id}/{$aula->id}";
        $fileName = Str::uuid().".{$ext}";

        Storage::disk('s3')->putFileAs($directory, $arquivo, $fileName);

        $material = EscolaAulaMaterial::create([
            'empresa_id' => $aula->empresa_id,
            'escola_aula_id' => $aula->id,
            'titulo' => $request->input('titulo') ?: $arquivo->getClientOriginalName(),
            'path_s3' => "{$directory}/{$fileName}",
            'nome_original' => $arquivo->getClientOriginalName(),
            'mime' => $arquivo->getClientMimeType(),
            'tamanho_bytes' => $arquivo->getSize(),
            'uploaded_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'id' => $material->id,
            'titulo' => $material->titulo,
            'nome_original' => $material->nome_original,
        ]);
    }

    public function destroyMaterial(int $material): JsonResponse
    {
        $this->checkAccess();

        $material = EscolaAulaMaterial::where('empresa_id', $this->empresaId())->findOrFail($material);
        Storage::disk('s3')->delete($material->path_s3);
        $material->delete();

        return response()->json(['success' => true]);
    }

    // -------------------------------------------------------------- Relatório

    public function relatorio()
    {
        $this->checkAccess();

        $modulos = EscolaModulo::where('empresa_id', $this->empresaId())
            ->orderBy('titulo')
            ->get(['id', 'titulo']);

        $vendedores = User::where('empresa_id', $this->empresaId())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('content.pages.escola.gestao.relatorio', [
            'modulos' => $modulos,
            'vendedores' => $vendedores,
        ]);
    }

    public function relatorioData(Request $request): JsonResponse
    {
        $this->checkAccess();

        $empresaId = $this->empresaId();
        $moduloId = $request->integer('modulo_id') ?: null;
        $userId = $request->integer('user_id') ?: null;

        // Total de aulas ativas (por escopo de módulo, se filtrado)
        $totalAulasQuery = EscolaAula::where('empresa_id', $empresaId)->where('ativo', true);
        if ($moduloId) {
            $totalAulasQuery->where('escola_modulo_id', $moduloId);
        }
        $totalAulas = (int) $totalAulasQuery->count();

        // Progresso agregado por usuário
        $query = EscolaAulaProgresso::query()
            ->where('escola_aula_progresso.empresa_id', $empresaId)
            ->join('escola_aulas', 'escola_aulas.id', '=', 'escola_aula_progresso.escola_aula_id')
            ->join('users', 'users.id', '=', 'escola_aula_progresso.user_id')
            ->where('escola_aulas.ativo', true);

        if ($moduloId) {
            $query->where('escola_aulas.escola_modulo_id', $moduloId);
        }
        if ($userId) {
            $query->where('escola_aula_progresso.user_id', $userId);
        }

        $linhas = $query
            ->selectRaw('users.id as user_id, users.name as nome')
            ->selectRaw('SUM(escola_aula_progresso.concluida = 1) as concluidas')
            ->selectRaw('COUNT(*) as iniciadas')
            ->selectRaw('MAX(escola_aula_progresso.updated_at) as ultima_atividade')
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.name')
            ->get();

        $data = $linhas->map(function ($l) use ($totalAulas) {
            $concluidas = (int) $l->concluidas;

            return [
                'nome' => $l->nome,
                'concluidas' => $concluidas,
                'iniciadas' => (int) $l->iniciadas,
                'total_aulas' => $totalAulas,
                'percentual' => $totalAulas > 0 ? (int) round(($concluidas / $totalAulas) * 100) : 0,
                'ultima_atividade' => $l->ultima_atividade
                    ? Carbon::parse($l->ultima_atividade)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i')
                    : '—',
            ];
        });

        return response()->json(['data' => $data]);
    }

    // ----------------------------------------------------------- Liberar acesso

    /** Tela para liberar/bloquear o acesso de usuários à área do aluno. */
    public function acessos(Request $request)
    {
        $this->checkAccess();

        $busca = trim((string) $request->get('q', ''));

        $usuarios = User::where('empresa_id', $this->empresaId())
            ->whereNotIn('user_role_id', [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER])
            ->when($busca !== '', function ($q) use ($busca) {
                $q->where(function ($sub) use ($busca) {
                    $sub->where('name', 'like', "%{$busca}%")
                        ->orWhere('email', 'like', "%{$busca}%");
                });
            })
            ->with('role:id,tipo_usuario')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'user_role_id', 'escola_habilitada', 'ativo']);

        return view('content.pages.escola.gestao.acessos', [
            'usuarios' => $usuarios,
            'busca' => $busca,
            'totalLiberados' => $usuarios->where('escola_habilitada', true)->count(),
        ]);
    }

    /** Liga/desliga o acesso de um usuário (mesma empresa). */
    public function toggleAcesso(Request $request, int $usuario): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'habilitada' => 'required|boolean',
        ]);

        $user = User::where('empresa_id', $this->empresaId())
            ->whereNotIn('user_role_id', [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER])
            ->findOrFail($usuario);

        $user->escola_habilitada = $validated['habilitada'];
        $user->save();

        return response()->json([
            'success' => true,
            'habilitada' => $user->escola_habilitada,
        ]);
    }

    // ---------------------------------------------------------------- Helpers

    private function salvarCapa($arquivo, EscolaModulo $modulo): string
    {
        $ext = $arquivo->getClientOriginalExtension();
        $directory = "escola/capas/{$modulo->empresa_id}/{$modulo->id}";
        $fileName = Str::uuid().".{$ext}";
        Storage::disk('s3')->putFileAs($directory, $arquivo, $fileName);

        return "{$directory}/{$fileName}";
    }

    private function apagarArquivosDaAula(EscolaAula $aula): void
    {
        if ($aula->video_path) {
            Storage::disk('s3')->delete($aula->video_path);
        }
        foreach ($aula->materiais as $material) {
            Storage::disk('s3')->delete($material->path_s3);
        }
    }
}
