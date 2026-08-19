<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Jobs\ExcluirVendaDocumentoRemoto;
use App\Jobs\VerificarVendaDocumento;
use App\Models\VendaDocumento;
use App\Models\Vendas;
use App\Services\Documentos\RegistrarVendaDocumentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VendaDocumentoController extends Controller
{
    public function index(Vendas $venda): JsonResponse
    {
        $this->autorizar($venda);

        return response()->json([
            'empresa_id' => $venda->empresa_id,
            'status' => $venda->documentacao_status ?? 'PENDENTE',
            'diretorio' => $venda->documentacao_diretorio,
            'documentos' => $venda->documentos()
                ->whereNull('deleted_at')->latest()->get()->map(fn (VendaDocumento $doc) => $this->payload($doc)),
        ]);
    }

    public function store(Request $request, Vendas $venda, RegistrarVendaDocumentoService $registrador): JsonResponse
    {
        $this->autorizar($venda);
        $request->validate([
            'arquivo' => ['required', 'file', 'max:'.config('documentos.max_kilobytes')],
            'client_upload_id' => ['nullable', 'string', 'max:64'],
        ], [
            'arquivo.required' => 'Selecione um arquivo.',
            'arquivo.file' => 'O documento enviado é inválido.',
            'arquivo.max' => 'Cada arquivo pode ter no máximo 25 MB.',
        ]);

        $arquivo = $request->file('arquivo');
        $mime = (string) $arquivo->getMimeType();
        if ($mime !== 'application/pdf' && (! str_starts_with($mime, 'image/') || $mime === 'image/svg+xml')) {
            throw ValidationException::withMessages([
                'arquivo' => 'Envie um PDF ou uma imagem raster válida. SVG não é permitido.',
            ]);
        }

        $resultado = $registrador->registrar(
            $venda,
            $arquivo,
            $request->string('client_upload_id')->trim()->value(),
            (int) Auth::id()
        );

        return response()->json(
            $this->payload($resultado['documento']),
            $resultado['criado'] ? 201 : 200
        );
    }

    public function retry(Vendas $venda, VendaDocumento $documento): JsonResponse
    {
        $this->autorizarDocumento($venda, $documento);
        if (! Storage::disk('local')->exists($documento->caminho_temporario)) {
            throw ValidationException::withMessages(['arquivo' => 'A cópia temporária expirou. Envie o arquivo novamente.']);
        }

        if (! config('documentos.processamento_ativo')) {
            throw ValidationException::withMessages([
                'arquivo' => 'O processamento está aguardando a configuração do servidor de documentos.',
            ]);
        }

        $documento->update(['status' => 'RECEBIDO', 'erro' => null]);
        $venda->update(['documentacao_status' => 'PROCESSANDO']);
        VerificarVendaDocumento::dispatch($documento->id);

        return response()->json($this->payload($documento->fresh()));
    }

    public function destroy(Vendas $venda, VendaDocumento $documento): JsonResponse
    {
        $this->autorizarDocumento($venda, $documento);
        $role = (int) Auth::user()->user_role_id;
        if ($documento->status === 'DISPONIVEL' && ! in_array($role, [UserRole::BACKOFFICE, UserRole::ADMINISTRATIVO, UserRole::DEVELOPER], true)) {
            abort(403, 'Somente o backoffice pode excluir um documento já enviado.');
        }

        if ($documento->status === 'DISPONIVEL') {
            $documento->update(['status' => 'EXCLUSAO_PENDENTE', 'deleted_by' => Auth::id()]);
            ExcluirVendaDocumentoRemoto::dispatch($documento->id);
        } else {
            Storage::disk('local')->delete($documento->caminho_temporario);
            $documento->update(['status' => 'EXCLUIDO', 'deleted_by' => Auth::id(), 'deleted_at' => now()]);
        }
        $this->atualizarResumo($venda);

        return response()->json(['deleted' => true]);
    }

    private function autorizarDocumento(Vendas $venda, VendaDocumento $documento): void
    {
        $this->autorizar($venda);
        abort_unless($documento->venda_id === $venda->id, 404);
    }

    private function autorizar(Vendas $venda): void
    {
        abort_unless($venda->empresa_id === Auth::user()->empresa_id, 404);
        $role = (int) Auth::user()->user_role_id;
        abort_unless(in_array($role, [UserRole::VENDEDOR, UserRole::BACKOFFICE, UserRole::ADMINISTRATIVO, UserRole::DEVELOPER], true), 403);
        if ($role === UserRole::VENDEDOR) {
            abort_unless($venda->user_id === Auth::id(), 403);
        }
    }

    private function atualizarResumo(Vendas $venda): void
    {
        $status = $venda->documentos()->whereNull('deleted_at')->pluck('status');
        $resumo = $status->isEmpty() ? 'PENDENTE'
            : ($status->contains('FALHA') || $status->contains('BLOQUEADO') ? 'COM_FALHA'
                : ($status->every(fn ($item) => $item === 'DISPONIVEL') ? 'DISPONIVEL' : 'PROCESSANDO'));
        $venda->update(['documentacao_status' => $resumo]);
    }

    private function payload(VendaDocumento $doc): array
    {
        $role = (int) Auth::user()->user_role_id;
        $podeExcluirDisponivel = in_array($role, [UserRole::BACKOFFICE, UserRole::ADMINISTRATIVO, UserRole::DEVELOPER], true);

        return [
            'id' => $doc->id,
            'nome' => $doc->nome_original,
            'nome_remoto' => $doc->nome_remoto,
            'mime_type' => $doc->mime_type,
            'tamanho' => $doc->tamanho,
            'status' => $doc->status,
            'erro' => $doc->erro,
            'enviado_em' => $doc->enviado_em?->toIso8601String(),
            'verificado_em' => $doc->verificado_em?->toIso8601String(),
            'etapa' => match ($doc->status) {
                'RECEBIDO' => 'Recebido pelo CRM',
                'VERIFICANDO' => 'Verificando segurança',
                'AGUARDANDO_ENVIO' => 'Aguardando transferência',
                'ENVIANDO' => 'Transferindo ao servidor',
                'DISPONIVEL' => 'Disponível no servidor',
                'EXCLUSAO_PENDENTE' => 'Exclusão agendada',
                default => null,
            },
            'processamento_ativo' => (bool) config('documentos.processamento_ativo'),
            'pode_reenviar' => $doc->status === 'FALHA' && (bool) config('documentos.processamento_ativo'),
            'pode_excluir' => $doc->status !== 'EXCLUSAO_PENDENTE' && ($doc->status !== 'DISPONIVEL' || $podeExcluirDisponivel),
        ];
    }
}
