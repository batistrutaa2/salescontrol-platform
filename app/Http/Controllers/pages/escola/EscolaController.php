<?php

namespace App\Http\Controllers\pages\escola;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\EscolaAula;
use App\Models\EscolaAulaMaterial;
use App\Models\EscolaAulaProgresso;
use App\Models\EscolaModulo;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EscolaController extends Controller
{
    /** Papéis que sempre acessam a Escola (gerem a plataforma). */
    private const ACESSO_TOTAL = [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER];

    private function empresaId(): int
    {
        return app(TenantContext::class)->id();
    }

    /**
     * Área do aluno é liberada individualmente: admin geral e developer sempre
     * acessam; os demais só com o flag escola_habilitada ligado.
     */
    private function checkAlunoAccess(): void
    {
        $user = Auth::user();
        if (in_array($user->user_role_id, self::ACESSO_TOTAL)) {
            return;
        }
        if (! $user->escola_habilitada) {
            abort(403, 'Você ainda não tem acesso à Academia Comercial.');
        }
    }

    private function tempUrl(?string $path, int $hours): ?string
    {
        if (! $path) {
            return null;
        }
        try {
            return Storage::disk('s3')->temporaryUrl($path, Carbon::now()->addHours($hours));
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Lista de módulos (área do aluno). */
    public function index(Request $request)
    {
        $this->checkAlunoAccess();

        $empresaId = $this->empresaId();
        $busca = trim((string) $request->get('q', ''));

        $modulosQuery = EscolaModulo::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->withCount(['aulasAtivas as total_aulas']);

        if ($busca !== '') {
            $modulosQuery->where(function ($q) use ($busca) {
                $q->where('titulo', 'like', "%{$busca}%")
                    ->orWhere('descricao', 'like', "%{$busca}%")
                    ->orWhereHas('aulasAtivas', function ($sub) use ($busca) {
                        $sub->where('titulo', 'like', "%{$busca}%");
                    });
            });
        }

        $modulos = $modulosQuery->orderBy('ordem')->orderBy('titulo')->get();

        // Aulas concluídas pelo usuário, agrupadas por módulo
        $concluidasPorModulo = EscolaAulaProgresso::query()
            ->where('escola_aula_progresso.empresa_id', $empresaId)
            ->where('escola_aula_progresso.user_id', Auth::id())
            ->where('escola_aula_progresso.concluida', true)
            ->join('escola_aulas', function ($join) {
                $join->on('escola_aulas.id', '=', 'escola_aula_progresso.escola_aula_id')
                    ->on('escola_aulas.empresa_id', '=', 'escola_aula_progresso.empresa_id');
            })
            ->where('escola_aulas.ativo', true)
            ->selectRaw('escola_aulas.escola_modulo_id as modulo_id, count(*) as total')
            ->groupBy('escola_aulas.escola_modulo_id')
            ->pluck('total', 'modulo_id');

        $modulos->each(function (EscolaModulo $modulo) use ($concluidasPorModulo) {
            $total = (int) $modulo->total_aulas;
            $concluidas = (int) ($concluidasPorModulo[$modulo->id] ?? 0);
            $modulo->aulas_concluidas = $concluidas;
            $modulo->percentual_modulo = $total > 0 ? (int) round(($concluidas / $total) * 100) : 0;
            $modulo->capa_url = $this->tempUrl($modulo->capa_path, 6);
        });

        return view('content.pages.escola.index', [
            'modulos' => $modulos,
            'busca' => $busca,
        ]);
    }

    /** Aulas de um módulo. */
    public function show(int $modulo)
    {
        $this->checkAlunoAccess();

        $empresaId = $this->empresaId();

        $modulo = EscolaModulo::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->findOrFail($modulo);

        $aulas = $modulo->aulasAtivas()->with('progressoDoUsuario')->get();

        return view('content.pages.escola.modulo', [
            'modulo' => $modulo,
            'aulas' => $aulas,
        ]);
    }

    /** Player de uma aula. */
    public function assistir(int $aula)
    {
        $this->checkAlunoAccess();

        $empresaId = $this->empresaId();

        $aula = EscolaAula::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->with(['modulo', 'materiais', 'progressoDoUsuario'])
            ->findOrFail($aula);

        abort_if(! $aula->modulo || ! $aula->modulo->ativo, 404);

        $videoUrl = $this->tempUrl($aula->video_path, 2);

        $aulasIrmas = $aula->modulo->aulasAtivas()->with('progressoDoUsuario')->get();

        return view('content.pages.escola.player', [
            'aula' => $aula,
            'modulo' => $aula->modulo,
            'videoUrl' => $videoUrl,
            'materiais' => $aula->materiais,
            'aulasIrmas' => $aulasIrmas,
            'posicaoInicial' => $aula->progressoDoUsuario?->ultima_posicao_segundos ?? 0,
        ]);
    }

    /** Salva o progresso da aula (chamado periodicamente pelo player). */
    public function salvarProgresso(Request $request, int $aula): JsonResponse
    {
        $this->checkAlunoAccess();
        $this->tenantMemberOrAbort($request->user());

        $empresaId = $this->empresaId();

        $validated = $request->validate([
            'posicao' => 'required|numeric|min:0',
            'duracao' => 'nullable|numeric|min:0',
        ]);

        $aula = EscolaAula::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->findOrFail($aula);

        $duracao = max(0, (int) ($aula->duracao_segundos ?? 0));
        $posicao = max(0, (int) floor($validated['posicao']));
        if ($duracao > 0) {
            $posicao = min($posicao, $duracao);
        }

        $percentual = 0;
        if ($duracao > 0) {
            $percentual = (int) min(100, round(($posicao / $duracao) * 100));
        }
        $percentualConclusao = (int) (Empresa::query()
            ->whereKey($empresaId)
            ->value('escola_percentual_conclusao') ?? 90);
        $concluida = $duracao > 0 && $percentual >= $percentualConclusao;

        $progresso = EscolaAulaProgresso::firstOrNew([
            'user_id' => Auth::id(),
            'escola_aula_id' => $aula->id,
        ]);

        $progresso->empresa_id = $empresaId;
        $progresso->ultima_posicao_segundos = $posicao;
        // Nunca regredir o percentual já alcançado
        $progresso->percentual = max((int) $progresso->percentual, $percentual);
        if ($concluida && ! $progresso->concluida) {
            $progresso->concluida = true;
            $progresso->concluida_em = Carbon::now();
        }
        $progresso->save();

        return response()->json([
            'success' => true,
            'percentual' => $progresso->percentual,
            'concluida' => $progresso->concluida,
        ]);
    }

    /** Download de material anexo de uma aula. */
    public function downloadMaterial(int $material)
    {
        $this->checkAlunoAccess();

        $material = EscolaAulaMaterial::where('empresa_id', $this->empresaId())
            ->findOrFail($material);

        return Storage::disk('s3')->download($material->path_s3, $material->nome_original);
    }
}
