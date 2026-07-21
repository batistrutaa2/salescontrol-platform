<?php

namespace App\Http\Controllers\pages\backoffice;

use App\Enums\FaseCancelamento;
use App\Enums\ModalidadeCancelamento;
use App\Enums\TipoDemandaContrato;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Vendas;
use App\Repositories\Contracts\ProcessoVendaRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Read agregado da tela de acompanhamento do contrato, dividida em abas:
 * Contrato · Portabilidade · Cancelamento (por titular, com modalidade e fase).
 *
 * A aba de Contrato e a lista editável de portabilidade vivem no próprio Blade
 * (openContractPME); este controller alimenta a aba de Cancelamento e fornece o
 * resumo geral de processos. É controller fino — delega ao repository.
 */
class ProcessosVendaController extends Controller
{
    public function __construct(
        private ProcessoVendaRepositoryInterface $processos,
    ) {}

    public function dados(int $vendaId)
    {
        try {
            $empresaId = Auth::user()->empresa_id;

            $venda = Vendas::with('tabulacao:id,descricao')
                ->where('id', $vendaId)
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $venda) {
                return response()->json(['success' => false, 'message' => 'Contrato não encontrado.'], 404);
            }

            $cnpj = preg_replace('/\D+/', '', (string) $venda->cpf_cnpj);

            $grupos = TipoDemandaContrato::grupos();
            $cancelamentos = $this->processos->daVenda($vendaId, $empresaId)
                ->filter(fn ($p) => ($grupos[$p->tipo] ?? '') === 'cancelamentos')
                ->map(fn ($p) => $this->mapCancelamento($p))
                ->values();

            $titulares = $venda->titulares()
                ->get(['id', 'nome', 'cpf', 'email', 'plano_anterior', 'operadora_anterior_id', 'precisa_cancelamento'])
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'nome' => $t->nome,
                    'cpf' => $t->cpf,
                    'email' => $t->email,
                    'precisa_cancelamento' => (bool) $t->precisa_cancelamento,
                ]);

            $portabilidades = $venda->portabilidades()
                ->with('operadoraAnterior:id,nome')
                ->get()
                ->map(fn ($p) => [
                    'nome' => $p->nome,
                    'cpf' => $p->cpf,
                    'plano_anterior' => $p->plano_anterior,
                    'numero_carteirinha' => $p->numero_carteirinha,
                    'operadora_anterior' => $p->operadoraAnterior->nome ?? null,
                ]);

            return response()->json([
                'success' => true,
                'can_edit' => $this->canEditContract($venda),
                'venda' => [
                    'id' => $venda->id,
                    'nome_contrato' => $venda->nome_contrato,
                    'cpf_cnpj' => $venda->cpf_cnpj,
                    'operadora' => $venda->operadora,
                    'status' => $venda->tabulacao->descricao ?? '—',
                ],
                'resumo' => $this->processos->resumo($vendaId, $empresaId),
                'cancelamentos' => $cancelamentos,
                'titulares' => $titulares,
                'portabilidades' => $portabilidades,
                'contratos_anteriores' => $this->processos->contratosDoCnpj($cnpj, $empresaId, $venda->id),
                'acessos' => $this->processos->acessosPorCnpj($cnpj, $empresaId, $venda->id),
                'modalidades' => ModalidadeCancelamento::labels(),
                'fases' => FaseCancelamento::fluxo(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar processos: '.$e->getMessage(),
            ], 500);
        }
    }

    /** Atualiza modalidade / fase / protocolo de um processo de cancelamento. */
    public function atualizarCancelamento(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'modalidade' => ['nullable', 'string', 'in:'.implode(',', array_keys(ModalidadeCancelamento::labels()))],
                'fase' => ['required', 'string', 'in:'.implode(',', array_column(FaseCancelamento::fluxo(), 'value'))],
                'protocolo' => ['nullable', 'string', 'max:120'],
            ]);

            $empresaId = Auth::user()->empresa_id;

            $demanda = \App\Models\VendaDemanda::with('venda:id,empresa_id,backoffice_id')
                ->where('id', $id)
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $demanda || ! $demanda->venda) {
                return response()->json(['success' => false, 'message' => 'Processo não encontrado.'], 404);
            }

            if (! $this->canEditContract($demanda->venda)) {
                return response()->json(['success' => false, 'message' => 'Sem permissão para editar este contrato.'], 403);
            }

            $atualizada = $this->processos->atualizarCancelamento($id, $empresaId, $validated, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Cancelamento atualizado.',
                'cancelamento' => $this->mapCancelamento($atualizada),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Erro de validação.', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao atualizar: '.$e->getMessage()], 500);
        }
    }

    /**
     * Avança a fase de uma portabilidade a partir da tela do contrato.
     * Permissão por contrato (canEditContract), diferente do painel do gestor.
     */
    public function fasePortabilidade(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'fase' => ['required', 'string', 'in:'.implode(',', array_column(\App\Enums\FasePortabilidade::fluxo(), 'value'))],
            ]);

            $empresaId = Auth::user()->empresa_id;

            $port = \App\Models\VendaPortabilidade::with('venda:id,empresa_id,backoffice_id')
                ->where('id', $id)
                ->whereHas('venda', fn ($q) => $q->where('empresa_id', $empresaId))
                ->first();

            if (! $port || ! $port->venda) {
                return response()->json(['success' => false, 'message' => 'Portabilidade não encontrada.'], 404);
            }

            if (! $this->canEditContract($port->venda)) {
                return response()->json(['success' => false, 'message' => 'Sem permissão para editar este contrato.'], 403);
            }

            $this->processos->atualizarFasePortabilidade($id, $empresaId, $validated['fase'], Auth::id());

            $fase = \App\Enums\FasePortabilidade::from($validated['fase']);

            return response()->json([
                'success' => true,
                'message' => 'Fase da portabilidade atualizada.',
                'fase' => ['value' => $fase->value, 'label' => $fase->label(), 'final' => $fase->ehFinal()],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Erro de validação.', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao atualizar: '.$e->getMessage()], 500);
        }
    }

    private function mapCancelamento(\App\Models\VendaDemanda $p): array
    {
        $meta = $p->meta ?? [];
        $modalidade = $meta['modalidade'] ?? null;
        $fase = $p->status === 'CONCLUIDA'
            ? FaseCancelamento::CONCLUIDO->value
            : ($meta['fase'] ?? FaseCancelamento::SOLICITADO->value);

        return [
            'id' => $p->id,
            'titulo' => $p->titulo,
            'tipo' => $p->tipo,
            'tipo_label' => TipoDemandaContrato::tryFrom($p->tipo)?->label() ?? $p->tipo,
            'titular' => $p->titular ? ['id' => $p->titular->id, 'nome' => $p->titular->nome] : null,
            'operadora_anterior' => $p->operadoraAnterior->nome ?? null,
            'modalidade' => $modalidade,
            'modalidade_label' => $modalidade ? (ModalidadeCancelamento::tryFrom($modalidade)?->label()) : null,
            'fase' => $fase,
            'fase_label' => FaseCancelamento::tryFrom($fase)?->label() ?? $fase,
            'protocolo' => $meta['protocolo'] ?? null,
            'status' => $p->status,
            'origem' => $p->origem,
        ];
    }

    /**
     * ADM/DEV editam tudo; BACKOFFICE só o que implantou; demais somente leitura.
     */
    private function canEditContract(Vendas $sale): bool
    {
        $roleId = Auth::user()->user_role_id;

        if (in_array($roleId, [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER])) {
            return true;
        }

        if ($roleId === UserRole::BACKOFFICE) {
            return $sale->backoffice_id !== null && $sale->backoffice_id === Auth::id();
        }

        return false;
    }
}
