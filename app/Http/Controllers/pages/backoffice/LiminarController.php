<?php

namespace App\Http\Controllers\pages\backoffice;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\CancelamentoLiminar;
use App\Models\CancelamentoLiminarDocumento;
use App\Models\CancelamentoLiminarHistorico;
use App\Models\VendaTitular;
use App\Models\VendaDependente;
use App\Models\Vendas;
use App\Notifications\LiminarStatusAlterado;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class LiminarController extends Controller
{
    private const ALLOWED_ROLES = [UserRole::BACKOFFICE, UserRole::ADMINISTRATIVO, UserRole::DEVELOPER];

    private function checkAccess(): void
    {
        if (!in_array(Auth::user()->user_role_id, self::ALLOWED_ROLES)) {
            abort(403, 'Acesso não autorizado.');
        }
    }

    private function empresaId(): int
    {
        return Auth::user()->empresa_id;
    }

    // Labels legíveis para enum values
    private static function statusLabel(string $value): string
    {
        return match ($value) {
            'NAO_INICIADA' => 'Não Iniciada',
            'EM_EXECUCAO'  => 'Em Execução',
            'BLOQUEADA'    => 'Bloqueada',
            'CONCLUIDA'    => 'Concluída',
            default        => $value,
        };
    }

    private static function faseLabel(?string $value): string
    {
        return match ($value) {
            'ASSINATURA_PROCURACAO'        => 'Assinatura da Procuração',
            'ENVIO_DOCUMENTOS'             => 'Envio de Documentos',
            'AGUARDANDO_LIMINAR'           => 'Aguardando Liminar',
            'AGUARDANDO_RETORNO_OPERADORA' => 'Aguardando Retorno da Operadora',
            'LIMINAR_CONCEDIDA'            => 'Liminar Concedida',
            default                        => $value ?? '—',
        };
    }

    private static function honorariosLabel(string $value): string
    {
        return match ($value) {
            'PENDENTE'       => 'Pendente',
            'PAGO'           => 'Pago',
            'NAO_SE_APLICA'  => 'N/A',
            default          => $value,
        };
    }

    private static function recebimentoLabel(string $value): string
    {
        return match ($value) {
            'BOLETO_GERADO' => 'Boleto Gerado',
            'PAGO'          => 'Pago',
            'PENDENTE'      => 'Pendente',
            default         => $value,
        };
    }

    public function index()
    {
        $this->checkAccess();
        return view('content.pages.backoffice.liminar.index');
    }

    public function getData(Request $request): JsonResponse
    {
        $this->checkAccess();

        $query = CancelamentoLiminar::with(['venda.user', 'responsavel'])
            ->where('cancelamentos_liminares.empresa_id', $this->empresaId());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('fase')) {
            $query->where('fase', $request->fase);
        }
        if ($request->filled('data_inicio') && $request->filled('data_fim')) {
            $dataInicio = Carbon::createFromFormat('d/m/Y', $request->data_inicio)->startOfDay();
            $dataFim    = Carbon::createFromFormat('d/m/Y', $request->data_fim)->endOfDay();
            $query->whereBetween('cancelamentos_liminares.created_at', [$dataInicio, $dataFim]);
        }
        if ($request->filled('responsavel_id')) {
            $query->where('responsavel_id', $request->responsavel_id);
        }
        if ($request->filled('venda_id')) {
            $query->where('cancelamentos_liminares.venda_id', $request->venda_id);
        }

        return DataTables::of($query)
            ->addColumn('beneficiario_nome', function (CancelamentoLiminar $row) {
                $ben = $row->getBeneficiario();
                return $ben ? $ben->nome : '—';
            })
            ->addColumn('beneficiario_tipo_label', fn($row) => $row->beneficiario_tipo === 'TITULAR' ? 'Titular' : 'Dependente')
            ->addColumn('status_label', fn($row) => self::statusLabel($row->status))
            ->addColumn('fase_label', fn($row) => self::faseLabel($row->fase))
            ->addColumn('honorarios_label', fn($row) => self::honorariosLabel($row->status_honorarios))
            ->addColumn('recebimento_label', fn($row) => self::recebimentoLabel($row->status_recebimento))
            ->addColumn('corretor_nome', fn($row) => $row->venda?->user?->name ?? '—')
            ->addColumn('responsavel_nome', fn($row) => $row->responsavel?->name ?? '—')
            ->addColumn('data_criacao', fn($row) => $row->getOriginal('created_at')
                ? Carbon::parse($row->getOriginal('created_at'))->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i')
                : '—')
            ->make(true);
    }

    public function store(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'venda_id'          => 'required|integer|exists:vendas,id',
            'beneficiario_tipo' => 'required|in:TITULAR,DEPENDENTE',
            'beneficiario_id'   => 'required|integer',
            'responsavel_id'    => 'nullable|integer|exists:users,id',
        ]);

        $venda = Vendas::where('id', $validated['venda_id'])
            ->where('empresa_id', $this->empresaId())
            ->firstOrFail();

        // Verifica se beneficiário existe
        if ($validated['beneficiario_tipo'] === 'TITULAR') {
            VendaTitular::where('id', $validated['beneficiario_id'])
                ->where('venda_id', $venda->id)
                ->firstOrFail();
        } else {
            VendaDependente::where('id', $validated['beneficiario_id'])
                ->where('venda_id', $venda->id)
                ->firstOrFail();
        }

        $liminar = CancelamentoLiminar::create([
            'empresa_id'        => $this->empresaId(),
            'venda_id'          => $venda->id,
            'beneficiario_tipo' => $validated['beneficiario_tipo'],
            'beneficiario_id'   => $validated['beneficiario_id'],
            'nome_contrato'     => $venda->nome_contrato,
            'responsavel_id'    => $validated['responsavel_id'] ?? Auth::id(),
            'status'            => 'NAO_INICIADA',
        ]);

        CancelamentoLiminarHistorico::create([
            'cancelamento_liminar_id' => $liminar->id,
            'user_id'                 => Auth::id(),
            'campo_alterado'          => 'status',
            'valor_anterior'          => null,
            'valor_novo'              => 'Não Iniciada',
            'observacao'              => 'Processo criado.',
        ]);

        return response()->json(['success' => true, 'id' => $liminar->id]);
    }

    public function show(int $id): JsonResponse
    {
        $this->checkAccess();

        $liminar = CancelamentoLiminar::with([
            'documentos.uploadedBy',
            'historico.usuario',
            'venda.user',
            'responsavel',
        ])
            ->where('empresa_id', $this->empresaId())
            ->findOrFail($id);

        $beneficiario = $liminar->getBeneficiario();

        return response()->json([
            'liminar'      => $liminar,
            'beneficiario' => $beneficiario ? ['id' => $beneficiario->id, 'nome' => $beneficiario->nome] : null,
            'documentos'   => $liminar->documentos->map(fn($d) => [
                'id'                   => $d->id,
                'tipo_documento'       => $d->tipo_documento,
                'tipo_documento_label' => $d->tipo_documento_label,
                'nome_original'        => $d->nome_original,
                'uploaded_by_nome'     => $d->uploadedBy?->name,
                'created_at'           => $d->created_at,
            ]),
            'historico' => $liminar->historico->map(fn($h) => [
                'campo_alterado' => $h->campo_alterado,
                'valor_anterior' => $h->valor_anterior,
                'valor_novo'     => $h->valor_novo,
                'observacao'     => $h->observacao,
                'usuario_nome'   => $h->usuario?->name,
                'created_at'     => $h->created_at,
            ]),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->checkAccess();

        $liminar = CancelamentoLiminar::where('empresa_id', $this->empresaId())->findOrFail($id);

        $validated = $request->validate([
            'status'              => 'nullable|in:NAO_INICIADA,EM_EXECUCAO,BLOQUEADA,CONCLUIDA',
            'fase'                => 'nullable|in:ASSINATURA_PROCURACAO,ENVIO_DOCUMENTOS,AGUARDANDO_LIMINAR,AGUARDANDO_RETORNO_OPERADORA,LIMINAR_CONCEDIDA',
            'data_envio'          => 'nullable|date_format:d/m/Y',
            'status_honorarios'   => 'nullable|in:PENDENTE,PAGO,NAO_SE_APLICA',
            'status_recebimento'  => 'nullable|in:BOLETO_GERADO,PAGO,PENDENTE',
            'valor_recebimento'   => 'nullable|numeric|min:0',
            'responsavel_id'      => 'nullable|integer|exists:users,id',
            'observacoes'         => 'nullable|string|max:2000',
        ]);

        $tracked = ['status', 'fase', 'status_honorarios', 'status_recebimento'];
        $notifyCorretor = false;
        $notifyFields = [];

        DB::beginTransaction();
        try {
            foreach ($tracked as $field) {
                if (isset($validated[$field]) && $validated[$field] !== $liminar->$field) {
                    $labelAnterior = match ($field) {
                        'status'             => self::statusLabel($liminar->$field ?? ''),
                        'fase'               => self::faseLabel($liminar->$field),
                        'status_honorarios'  => self::honorariosLabel($liminar->$field ?? ''),
                        'status_recebimento' => self::recebimentoLabel($liminar->$field ?? ''),
                        default              => $liminar->$field,
                    };
                    $labelNovo = match ($field) {
                        'status'             => self::statusLabel($validated[$field]),
                        'fase'               => self::faseLabel($validated[$field]),
                        'status_honorarios'  => self::honorariosLabel($validated[$field]),
                        'status_recebimento' => self::recebimentoLabel($validated[$field]),
                        default              => $validated[$field],
                    };

                    CancelamentoLiminarHistorico::create([
                        'cancelamento_liminar_id' => $liminar->id,
                        'user_id'                 => Auth::id(),
                        'campo_alterado'          => $field,
                        'valor_anterior'          => $labelAnterior,
                        'valor_novo'              => $labelNovo,
                    ]);

                    $notifyFields[] = ['campo' => $field, 'anterior' => $labelAnterior, 'novo' => $labelNovo];

                    if ($field === 'fase' && $validated[$field] === 'LIMINAR_CONCEDIDA') {
                        $notifyCorretor = true;
                    }
                }
            }

            if (isset($validated['data_envio'])) {
                $validated['data_envio'] = Carbon::createFromFormat('d/m/Y', $validated['data_envio'])->format('Y-m-d');
            }

            $liminar->fill($validated)->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erro ao atualizar.'], 500);
        }

        // Notificações após commit
        $beneficiario = $liminar->getBeneficiario();
        $nomeBeneficiario = $beneficiario?->nome ?? '—';

        foreach ($notifyFields as $nf) {
            $enviarEmail = $notifyCorretor && $nf['campo'] === 'fase';

            // Notifica o responsável pelo processo
            if ($liminar->responsavel_id && $liminar->responsavel_id !== Auth::id()) {
                $liminar->responsavel?->notify(new LiminarStatusAlterado(
                    liminarId: $liminar->id,
                    nomeContrato: $liminar->nome_contrato,
                    nomeBeneficiario: $nomeBeneficiario,
                    campoAlterado: $nf['campo'],
                    valorAnterior: $nf['anterior'],
                    valorNovo: $nf['novo'],
                    alteradoPorNome: Auth::user()->name,
                    enviarEmail: false,
                ));
            }

            // Notifica o corretor (com e-mail se liminar concedida)
            $corretor = $liminar->venda?->user;
            if ($corretor && $corretor->id !== Auth::id()) {
                $corretor->notify(new LiminarStatusAlterado(
                    liminarId: $liminar->id,
                    nomeContrato: $liminar->nome_contrato,
                    nomeBeneficiario: $nomeBeneficiario,
                    campoAlterado: $nf['campo'],
                    valorAnterior: $nf['anterior'],
                    valorNovo: $nf['novo'],
                    alteradoPorNome: Auth::user()->name,
                    enviarEmail: $enviarEmail,
                ));
            }
        }

        return response()->json(['success' => true]);
    }

    public function uploadDocumento(Request $request, int $id): JsonResponse
    {
        $this->checkAccess();

        $request->validate([
            'tipo_documento' => 'required|in:CARTEIRINHA,CARTAO_CNPJ,COMPROVANTE_PAGAMENTO,CONTRATO_SOCIAL,RETORNO_CANCELAMENTO,RG_CLIENTE',
            'arquivo'        => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $liminar = CancelamentoLiminar::where('empresa_id', $this->empresaId())->findOrFail($id);
        $arquivo = $request->file('arquivo');
        $ext = $arquivo->getClientOriginalExtension();
        $tipoDoc = strtolower($request->tipo_documento);
        $directory = "liminares/{$liminar->empresa_id}/{$liminar->id}";
        $fileName = "{$tipoDoc}.{$ext}";

        Storage::disk('s3')->putFileAs($directory, $arquivo, $fileName);
        $path = "{$directory}/{$fileName}";

        // Remove documento anterior do mesmo tipo se existir
        $existing = CancelamentoLiminarDocumento::where('cancelamento_liminar_id', $liminar->id)
            ->where('tipo_documento', $request->tipo_documento)
            ->first();
        if ($existing) {
            Storage::disk('s3')->delete($existing->path_s3);
            $existing->delete();
        }

        $doc = CancelamentoLiminarDocumento::create([
            'cancelamento_liminar_id' => $liminar->id,
            'empresa_id'              => $liminar->empresa_id,
            'tipo_documento'          => $request->tipo_documento,
            'nome_original'           => $arquivo->getClientOriginalName(),
            'path_s3'                 => $path,
            'uploaded_by'             => Auth::id(),
        ]);

        CancelamentoLiminarHistorico::create([
            'cancelamento_liminar_id' => $liminar->id,
            'user_id'                 => Auth::id(),
            'campo_alterado'          => 'documento',
            'valor_anterior'          => null,
            'valor_novo'              => $doc->tipo_documento_label,
            'observacao'              => "Documento enviado: {$arquivo->getClientOriginalName()}",
        ]);

        return response()->json([
            'success'              => true,
            'id'                   => $doc->id,
            'tipo_documento'       => $doc->tipo_documento,
            'tipo_documento_label' => $doc->tipo_documento_label,
            'nome_original'        => $doc->nome_original,
        ]);
    }

    public function destroyDocumento(int $id, int $docId): JsonResponse
    {
        $this->checkAccess();

        $liminar = CancelamentoLiminar::where('empresa_id', $this->empresaId())->findOrFail($id);
        $doc = CancelamentoLiminarDocumento::where('cancelamento_liminar_id', $liminar->id)->findOrFail($docId);

        Storage::disk('s3')->delete($doc->path_s3);
        $doc->delete();

        return response()->json(['success' => true]);
    }

    public function downloadDocumento(int $id, int $docId)
    {
        $this->checkAccess();

        $liminar = CancelamentoLiminar::where('empresa_id', $this->empresaId())->findOrFail($id);
        $doc = CancelamentoLiminarDocumento::where('cancelamento_liminar_id', $liminar->id)->findOrFail($docId);

        return Storage::disk('s3')->download($doc->path_s3, $doc->nome_original);
    }

    public function getBeneficiarios(int $vendaId): JsonResponse
    {
        $this->checkAccess();

        $venda = Vendas::where('id', $vendaId)
            ->where('empresa_id', $this->empresaId())
            ->with(['titulares.dependentes'])
            ->firstOrFail();

        $beneficiarios = [];

        foreach ($venda->titulares as $titular) {
            $temLiminar = CancelamentoLiminar::where('venda_id', $venda->id)
                ->where('beneficiario_tipo', 'TITULAR')
                ->where('beneficiario_id', $titular->id)
                ->whereNotIn('status', ['CONCLUIDA'])
                ->exists();

            $beneficiarios[] = [
                'id'         => $titular->id,
                'nome'       => $titular->nome,
                'tipo'       => 'TITULAR',
                'tipo_label' => 'Titular',
                'tem_liminar_ativa' => $temLiminar,
            ];

            foreach ($titular->dependentes as $dep) {
                $temLiminarDep = CancelamentoLiminar::where('venda_id', $venda->id)
                    ->where('beneficiario_tipo', 'DEPENDENTE')
                    ->where('beneficiario_id', $dep->id)
                    ->whereNotIn('status', ['CONCLUIDA'])
                    ->exists();

                $beneficiarios[] = [
                    'id'         => $dep->id,
                    'nome'       => $dep->nome,
                    'tipo'       => 'DEPENDENTE',
                    'tipo_label' => 'Dependente',
                    'tem_liminar_ativa' => $temLiminarDep,
                ];
            }
        }

        return response()->json(['beneficiarios' => $beneficiarios]);
    }
}
