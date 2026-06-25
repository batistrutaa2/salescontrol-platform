<?php

namespace App\Http\Controllers\pages\backoffice;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\CancelamentoLiminar;
use App\Models\CancelamentoLiminarDocumento;
use App\Models\CancelamentoLiminarHistorico;
use App\Models\Vendas;
use App\Notifications\LiminarStatusAlterado;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LiminarController extends Controller
{
    // Quem pode ver o kanban, mover cards e baixar documentos.
    private const ALLOWED_ROLES = [
        UserRole::BACKOFFICE,
        UserRole::ADMINISTRATIVO,
        UserRole::DEVELOPER,
        UserRole::ADVOGADA,
    ];

    // Quem pode abrir processo e gerenciar documentos (a advogada não abre/exclui).
    private const BACKOFFICE_ROLES = [
        UserRole::BACKOFFICE,
        UserRole::ADMINISTRATIVO,
        UserRole::DEVELOPER,
    ];

    // Colunas do kanban (campo `fase`), na ordem do fluxo.
    private const FASES = [
        'CANCELAMENTO_ABERTO',
        'PROCURACAO_ENVIADA',
        'PROCURACAO_ASSINADA',
        'LIMINAR_CONCEDIDA',
    ];

    // Documentos obrigatórios na abertura: campo do form => tipo_documento.
    private const DOCUMENTOS_ABERTURA = [
        'doc_carteirinha' => 'CARTEIRINHA',
        'doc_contrato_social' => 'CONTRATO_SOCIAL',
        'doc_cartao_cnpj' => 'CARTAO_CNPJ',
        'doc_rg_cliente' => 'RG_CLIENTE',
        'doc_comprovante_pagamento' => 'COMPROVANTE_PAGAMENTO',
    ];

    // Documentos opcionais na abertura: campo do form => tipo_documento.
    private const DOCUMENTOS_OPCIONAIS = [
        'doc_print_protocolo' => 'PRINT_PROTOCOLO',
        'doc_audio_hapvida' => 'AUDIO_HAPVIDA',
    ];

    // Todos os tipos aceitos no upload avulso (modal de detalhe).
    private const TIPOS_DOCUMENTO = [
        'CARTEIRINHA', 'CARTAO_CNPJ', 'COMPROVANTE_PAGAMENTO', 'CONTRATO_SOCIAL',
        'RETORNO_CANCELAMENTO', 'RG_CLIENTE', 'PRINT_PROTOCOLO', 'AUDIO_HAPVIDA',
        'CONCLUSAO_LIMINAR',
    ];

    // Regras de mime/tamanho por natureza do arquivo.
    // Áudio via mimetypes (o mime real do upload) — `mimes:mp3` falharia porque o
    // Symfony adivinha 'mpga' para audio/mpeg. Cobre os formatos comuns (WhatsApp/Hapvida).
    private const MIME_IMAGEM_PDF = 'mimes:pdf,jpg,jpeg,png|max:10240';

    private const MIME_AUDIO = 'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/ogg,audio/opus,audio/mp4,audio/x-m4a,audio/aac,audio/webm,audio/3gpp|max:25600';

    // Campos de data preenchidos na abertura (chegam em d/m/Y, salvam em Y-m-d).
    private const DATAS_PROCURACAO = [
        'data_fim_plano',
        'data_contratacao',
        'data_solicitacao_cancelamento',
        'data_ultimo_pagamento_boleto',
        'cobertura_comprovante_inicio',
        'cobertura_comprovante_fim',
        'data_vencimento_boleto_1',
        'data_vencimento_boleto_2',
    ];

    private function checkAccess(): void
    {
        if (! in_array(Auth::user()->user_role_id, self::ALLOWED_ROLES)) {
            abort(403, 'Acesso não autorizado.');
        }
    }

    private function checkBackoffice(): void
    {
        if (! in_array(Auth::user()->user_role_id, self::BACKOFFICE_ROLES)) {
            abort(403, 'Acesso não autorizado.');
        }
    }

    private function empresaId(): int
    {
        return Auth::user()->empresa_id;
    }

    private function isAdvogada(): bool
    {
        return (int) Auth::user()->user_role_id === UserRole::ADVOGADA;
    }

    // ---------------------------------------------------------------
    // Labels e cores das colunas
    // ---------------------------------------------------------------
    private static function faseLabel(?string $value): string
    {
        return match ($value) {
            'CANCELAMENTO_ABERTO' => 'Cancelamento Aberto',
            'PROCURACAO_ENVIADA' => 'Procuração enviada',
            'PROCURACAO_ASSINADA' => 'Procuração assinada',
            'LIMINAR_CONCEDIDA' => 'Liminar concedida',
            default => $value ?? '—',
        };
    }

    private static function faseCurto(string $value): string
    {
        return match ($value) {
            'CANCELAMENTO_ABERTO' => 'Aberto',
            'PROCURACAO_ENVIADA' => 'Enviada',
            'PROCURACAO_ASSINADA' => 'Assinada',
            'LIMINAR_CONCEDIDA' => 'Concedida',
            default => $value,
        };
    }

    private static function faseGradiente(string $value): array
    {
        return match ($value) {
            'CANCELAMENTO_ABERTO' => ['start' => '#06B6D4', 'end' => '#22D3EE'],
            'PROCURACAO_ENVIADA' => ['start' => '#F59E0B', 'end' => '#FBBF24'],
            'PROCURACAO_ASSINADA' => ['start' => '#7C3AED', 'end' => '#A78BFA'],
            'LIMINAR_CONCEDIDA' => ['start' => '#10B981', 'end' => '#34D399'],
            default => ['start' => '#94A3B8', 'end' => '#CBD5E1'],
        };
    }

    private static function honorariosLabel(string $value): string
    {
        return match ($value) {
            'PENDENTE' => 'Pendente',
            'PAGO' => 'Pago',
            'NAO_SE_APLICA' => 'N/A',
            default => $value,
        };
    }

    private static function recebimentoLabel(string $value): string
    {
        return match ($value) {
            'BOLETO_GERADO' => 'Boleto Gerado',
            'PAGO' => 'Pago',
            'PENDENTE' => 'Pendente',
            default => $value,
        };
    }

    private function colunas(): array
    {
        return array_map(fn ($fase) => [
            'id' => $fase,
            'label' => self::faseLabel($fase),
            'curto' => self::faseCurto($fase),
            'gradiente' => self::faseGradiente($fase),
        ], self::FASES);
    }

    // ---------------------------------------------------------------
    // Views / dados
    // ---------------------------------------------------------------
    public function index()
    {
        $this->checkAccess();

        return view('content.pages.backoffice.liminar.index', [
            'colunas' => $this->colunas(),
            'isAdvogada' => $this->isAdvogada(),
        ]);
    }

    public function dados(Request $request): JsonResponse
    {
        $this->checkAccess();

        $registros = CancelamentoLiminar::with(['venda.user', 'responsavel'])
            ->where('cancelamentos_liminares.empresa_id', $this->empresaId())
            ->orderBy('updated_at', 'desc')
            ->get();

        $colunas = [];
        foreach (self::FASES as $fase) {
            $colunas[$fase] = [];
        }

        foreach ($registros as $row) {
            $fase = in_array($row->fase, self::FASES, true) ? $row->fase : 'CANCELAMENTO_ABERTO';
            $ben = $row->getBeneficiario();

            $colunas[$fase][] = [
                'id' => $row->id,
                'nome_contrato' => $row->nome_contrato,
                'nome_empresa' => $row->nome_empresa,
                'protocolo' => $row->protocolo_cancelamento,
                'beneficiario_nome' => $ben?->nome ?? '—',
                'beneficiario_tipo' => $row->beneficiario_tipo === 'TITULAR' ? 'Titular' : 'Dependente',
                'corretor_nome' => $row->venda?->user?->name ?? '—',
                'responsavel_nome' => $row->responsavel?->name ?? '—',
                'fase' => $fase,
                'data_criacao' => $row->getOriginal('created_at')
                    ? Carbon::parse($row->getOriginal('created_at'))->setTimezone('America/Sao_Paulo')->format('d/m/Y')
                    : '—',
            ];
        }

        return response()->json(['colunas' => $colunas]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->checkBackoffice();

        $rules = [
            'responsavel_id' => 'nullable|integer|exists:users,id',
            'nome_empresa' => 'required|string|max:255',
            'cnpj' => 'required|string|max:20',
            'protocolo_cancelamento' => 'required|string|max:255',
            'email_procuracao' => 'required|email|max:255',
        ];
        foreach (self::DATAS_PROCURACAO as $campo) {
            $rules[$campo] = 'required|date_format:d/m/Y';
        }
        foreach (array_keys(self::DOCUMENTOS_ABERTURA) as $campo) {
            $rules[$campo] = 'required|file|'.self::MIME_IMAGEM_PDF;
        }
        // Opcionais: print do protocolo (imagem/pdf) e áudio Hapvida (áudio).
        $rules['doc_print_protocolo'] = 'nullable|file|'.self::MIME_IMAGEM_PDF;
        $rules['doc_audio_hapvida'] = 'nullable|file|'.self::MIME_AUDIO;

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            $payload = [
                'empresa_id' => $this->empresaId(),
                'venda_id' => null,
                'beneficiario_tipo' => null,
                'beneficiario_id' => null,
                // Sem venda atrelada: o nome da empresa contratante é o título do processo.
                'nome_contrato' => $validated['nome_empresa'],
                'nome_empresa' => $validated['nome_empresa'],
                'cnpj' => $validated['cnpj'],
                'protocolo_cancelamento' => $validated['protocolo_cancelamento'],
                'email_procuracao' => $validated['email_procuracao'],
                'responsavel_id' => $validated['responsavel_id'] ?? Auth::id(),
                'status' => 'EM_EXECUCAO',
                'fase' => 'CANCELAMENTO_ABERTO',
            ];
            foreach (self::DATAS_PROCURACAO as $campo) {
                $payload[$campo] = Carbon::createFromFormat('d/m/Y', $validated[$campo])->format('Y-m-d');
            }

            $liminar = CancelamentoLiminar::create($payload);

            foreach (self::DOCUMENTOS_ABERTURA as $campo => $tipo) {
                $this->salvarDocumento($liminar, $request->file($campo), $tipo, false);
            }
            foreach (self::DOCUMENTOS_OPCIONAIS as $campo => $tipo) {
                if ($request->hasFile($campo)) {
                    $this->salvarDocumento($liminar, $request->file($campo), $tipo, false);
                }
            }

            CancelamentoLiminarHistorico::create([
                'cancelamento_liminar_id' => $liminar->id,
                'user_id' => Auth::id(),
                'campo_alterado' => 'fase',
                'valor_anterior' => null,
                'valor_novo' => self::faseLabel('CANCELAMENTO_ABERTO'),
                'observacao' => 'Processo aberto com documentação completa.',
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Liminar store falhou', ['erro' => $e->getMessage(), 'exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Erro ao abrir o processo.'], 500);
        }

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

        // toArray() não aplica os accessors das colunas terminadas em _1/_2:
        // Str::snake('DataVencimentoBoleto1') vira 'data_vencimento_boleto1' (sem o _ antes
        // do dígito) e não casa com a coluna 'data_vencimento_boleto_1'. O acesso direto
        // funciona, então reinjetamos o valor formatado (d/m/Y) no payload.
        $liminarData = $liminar->toArray();
        $liminarData['data_vencimento_boleto_1'] = $liminar->data_vencimento_boleto_1;
        $liminarData['data_vencimento_boleto_2'] = $liminar->data_vencimento_boleto_2;

        return response()->json([
            'liminar' => $liminarData,
            'fase_label' => self::faseLabel($liminar->fase),
            'beneficiario' => $beneficiario ? ['id' => $beneficiario->id, 'nome' => $beneficiario->nome] : null,
            'documentos' => $liminar->documentos->map(fn ($d) => [
                'id' => $d->id,
                'tipo_documento' => $d->tipo_documento,
                'tipo_documento_label' => $d->tipo_documento_label,
                'nome_original' => $d->nome_original,
                'uploaded_by_nome' => $d->uploadedBy?->name,
                'created_at' => optional($d->created_at)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i'),
            ]),
            'historico' => $liminar->historico->map(fn ($h) => [
                'campo_alterado' => $h->campo_alterado,
                'valor_anterior' => $h->valor_anterior,
                'valor_novo' => $h->valor_novo,
                'observacao' => $h->observacao,
                'usuario_nome' => $h->usuario?->name,
                'created_at' => optional($h->created_at)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i'),
            ]),
        ]);
    }

    /**
     * Move um card de coluna (altera a fase) — endpoint do kanban.
     * Notifica responsável e corretor a cada mudança (sino + e-mail).
     *
     * Concluir (fase = LIMINAR_CONCEDIDA) exige anexar o PDF da decisão/conclusão,
     * salvo como documento CONCLUSAO_LIMINAR. Sem o PDF, a fase não muda.
     */
    public function mover(Request $request, int $id): JsonResponse
    {
        $this->checkAccess();

        $liminar = CancelamentoLiminar::where('empresa_id', $this->empresaId())->findOrFail($id);

        $concluindo = $request->input('fase') === 'LIMINAR_CONCEDIDA'
            && $liminar->fase !== 'LIMINAR_CONCEDIDA';

        $rules = ['fase' => 'required|in:'.implode(',', self::FASES)];
        if ($concluindo) {
            $rules['documento_conclusao'] = 'required|file|mimes:pdf|max:10240';
        }
        $validated = $request->validate($rules);

        if ($validated['fase'] === $liminar->fase) {
            return response()->json(['success' => true]);
        }

        $anterior = self::faseLabel($liminar->fase);
        $novo = self::faseLabel($validated['fase']);

        DB::beginTransaction();
        try {
            if ($concluindo) {
                $this->salvarDocumento($liminar, $request->file('documento_conclusao'), 'CONCLUSAO_LIMINAR');
            }

            CancelamentoLiminarHistorico::create([
                'cancelamento_liminar_id' => $liminar->id,
                'user_id' => Auth::id(),
                'campo_alterado' => 'fase',
                'valor_anterior' => $anterior,
                'valor_novo' => $novo,
            ]);

            $liminar->fase = $validated['fase'];
            if ($validated['fase'] === 'LIMINAR_CONCEDIDA') {
                $liminar->status = 'CONCLUIDA';
            }
            $liminar->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Liminar mover falhou', ['erro' => $e->getMessage(), 'exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Erro ao mover o processo.'], 500);
        }

        $this->notificarAlteracao($liminar, 'fase', $anterior, $novo);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->checkBackoffice();

        $liminar = CancelamentoLiminar::where('empresa_id', $this->empresaId())->findOrFail($id);

        $validated = $request->validate([
            'status' => 'nullable|in:NAO_INICIADA,EM_EXECUCAO,BLOQUEADA,CONCLUIDA',
            'data_envio' => 'nullable|date_format:d/m/Y',
            'status_honorarios' => 'nullable|in:PENDENTE,PAGO,NAO_SE_APLICA',
            'status_recebimento' => 'nullable|in:BOLETO_GERADO,PAGO,PENDENTE',
            'valor_recebimento' => 'nullable|numeric|min:0',
            'responsavel_id' => 'nullable|integer|exists:users,id',
            'observacoes' => 'nullable|string|max:2000',
        ]);

        $tracked = ['status', 'status_honorarios', 'status_recebimento'];
        $alteracoes = [];

        DB::beginTransaction();
        try {
            foreach ($tracked as $field) {
                if (isset($validated[$field]) && $validated[$field] !== $liminar->$field) {
                    $labelAnterior = $this->labelDe($field, $liminar->$field);
                    $labelNovo = $this->labelDe($field, $validated[$field]);

                    CancelamentoLiminarHistorico::create([
                        'cancelamento_liminar_id' => $liminar->id,
                        'user_id' => Auth::id(),
                        'campo_alterado' => $field,
                        'valor_anterior' => $labelAnterior,
                        'valor_novo' => $labelNovo,
                    ]);

                    $alteracoes[] = ['campo' => $field, 'anterior' => $labelAnterior, 'novo' => $labelNovo];
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

        // Alterações de dados/financeiro avisam só o responsável — nunca o vendedor.
        foreach ($alteracoes as $a) {
            $this->notificarAlteracao($liminar, $a['campo'], $a['anterior'], $a['novo'], incluirVendedor: false);
        }

        return response()->json(['success' => true]);
    }

    private function labelDe(string $field, ?string $value): string
    {
        return match ($field) {
            'status' => match ($value) {
                'NAO_INICIADA' => 'Não Iniciada',
                'EM_EXECUCAO' => 'Em Execução',
                'BLOQUEADA' => 'Bloqueada',
                'CONCLUIDA' => 'Concluída',
                default => $value ?? '—',
            },
            'status_honorarios' => self::honorariosLabel($value ?? ''),
            'status_recebimento' => self::recebimentoLabel($value ?? ''),
            'fase' => self::faseLabel($value),
            default => $value ?? '—',
        };
    }

    /**
     * Notifica responsável e corretor (sino + e-mail), excluindo quem fez a alteração.
     */
    /**
     * Notifica sobre uma alteração no processo.
     *
     * Regra de negócio: o VENDEDOR só é avisado quando o status do kanban (fase)
     * muda — nunca em alterações de dados/financeiro. Por isso o $incluirVendedor:
     * `mover()` chama com true; `update()` chama com false (só o responsável).
     */
    private function notificarAlteracao(CancelamentoLiminar $liminar, string $campo, ?string $anterior, string $novo, bool $incluirVendedor = true): void
    {
        $nomeBeneficiario = $liminar->getBeneficiario()?->nome ?? '—';

        $destinatarios = [$liminar->responsavel];
        if ($incluirVendedor) {
            $destinatarios[] = $liminar->venda?->user;
        }

        $alvos = collect($destinatarios)
            ->filter()
            ->unique('id')
            ->reject(fn ($u) => $u->id === Auth::id());

        foreach ($alvos as $usuario) {
            $usuario->notify(new LiminarStatusAlterado(
                liminarId: $liminar->id,
                nomeContrato: $liminar->nome_contrato,
                nomeBeneficiario: $nomeBeneficiario,
                campoAlterado: $campo,
                valorAnterior: $anterior,
                valorNovo: $novo,
                alteradoPorNome: Auth::user()->name,
            ));
        }
    }

    // ---------------------------------------------------------------
    // Documentos
    // ---------------------------------------------------------------
    private function salvarDocumento(CancelamentoLiminar $liminar, UploadedFile $arquivo, string $tipoDocumento, bool $log = true): CancelamentoLiminarDocumento
    {
        $ext = $arquivo->getClientOriginalExtension();
        $tipoDoc = strtolower($tipoDocumento);
        $directory = "liminares/{$liminar->empresa_id}/{$liminar->id}";
        $fileName = "{$tipoDoc}.{$ext}";

        Storage::disk('s3')->putFileAs($directory, $arquivo, $fileName);
        $path = "{$directory}/{$fileName}";

        // Remove documento anterior do mesmo tipo se existir.
        $existing = CancelamentoLiminarDocumento::where('cancelamento_liminar_id', $liminar->id)
            ->where('tipo_documento', $tipoDocumento)
            ->first();
        if ($existing) {
            Storage::disk('s3')->delete($existing->path_s3);
            $existing->delete();
        }

        $doc = CancelamentoLiminarDocumento::create([
            'cancelamento_liminar_id' => $liminar->id,
            'empresa_id' => $liminar->empresa_id,
            'tipo_documento' => $tipoDocumento,
            'nome_original' => $arquivo->getClientOriginalName(),
            'path_s3' => $path,
            'uploaded_by' => Auth::id(),
        ]);

        if ($log) {
            CancelamentoLiminarHistorico::create([
                'cancelamento_liminar_id' => $liminar->id,
                'user_id' => Auth::id(),
                'campo_alterado' => 'documento',
                'valor_anterior' => null,
                'valor_novo' => $doc->tipo_documento_label,
                'observacao' => "Documento enviado: {$arquivo->getClientOriginalName()}",
            ]);
        }

        return $doc;
    }

    /**
     * Exclui um processo de liminar — restrito a backoffice/admin (não advogada).
     * Documentos e histórico saem por cascade no banco; os arquivos no S3 são
     * removidos manualmente antes.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->checkBackoffice();

        $liminar = CancelamentoLiminar::with('documentos')
            ->where('empresa_id', $this->empresaId())
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            foreach ($liminar->documentos as $doc) {
                Storage::disk('s3')->delete($doc->path_s3);
            }
            $liminar->delete();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Liminar destroy falhou', ['erro' => $e->getMessage(), 'exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Erro ao excluir o processo.'], 500);
        }

        return response()->json(['success' => true]);
    }

    public function uploadDocumento(Request $request, int $id): JsonResponse
    {
        $this->checkBackoffice();

        // Áudio (Hapvida) aceita formatos de áudio; os demais tipos seguem imagem/pdf.
        $regraArquivo = $request->input('tipo_documento') === 'AUDIO_HAPVIDA'
            ? self::MIME_AUDIO
            : self::MIME_IMAGEM_PDF;

        $request->validate([
            'tipo_documento' => 'required|in:'.implode(',', self::TIPOS_DOCUMENTO),
            'arquivo' => 'required|file|'.$regraArquivo,
        ]);

        $liminar = CancelamentoLiminar::where('empresa_id', $this->empresaId())->findOrFail($id);

        $doc = $this->salvarDocumento($liminar, $request->file('arquivo'), $request->tipo_documento);

        return response()->json([
            'success' => true,
            'id' => $doc->id,
            'tipo_documento' => $doc->tipo_documento,
            'tipo_documento_label' => $doc->tipo_documento_label,
            'nome_original' => $doc->nome_original,
        ]);
    }

    public function destroyDocumento(int $id, int $docId): JsonResponse
    {
        $this->checkBackoffice();

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

    /**
     * Busca processos concluídos (fase LIMINAR_CONCEDIDA) por nome da empresa ou
     * CNPJ/CPF — usado pela pesquisa no topo do kanban para reabrir o processo e
     * acessar o documento de conclusão anexado.
     */
    public function buscarConcluidas(Request $request): JsonResponse
    {
        $this->checkAccess();

        $termo = trim((string) $request->query('q', ''));
        if (mb_strlen($termo) < 2) {
            return response()->json(['liminares' => []]);
        }

        $termoDigitos = preg_replace('/\D+/', '', $termo);

        $liminares = CancelamentoLiminar::where('empresa_id', $this->empresaId())
            ->where('fase', 'LIMINAR_CONCEDIDA')
            ->where(function ($q) use ($termo, $termoDigitos) {
                $q->where('nome_empresa', 'like', "%{$termo}%")
                    ->orWhere('cnpj', 'like', "%{$termo}%");
                if ($termoDigitos !== '') {
                    // O CNPJ/CPF é gravado com máscara; normaliza os dígitos da coluna
                    // para casar com a busca só por números.
                    $cnpjDigitos = "REPLACE(REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '/', ''), '-', ''), ' ', '')";
                    $q->orWhereRaw("{$cnpjDigitos} LIKE ?", ["%{$termoDigitos}%"]);
                }
            })
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get(['id', 'nome_empresa', 'cnpj', 'protocolo_cancelamento'])
            ->map(fn ($l) => [
                'id' => $l->id,
                'nome_empresa' => $l->nome_empresa,
                'cnpj' => $l->cnpj,
                'protocolo' => $l->protocolo_cancelamento,
            ]);

        return response()->json(['liminares' => $liminares]);
    }

    /**
     * Busca contratos da empresa por nome ou CPF/CNPJ (autocomplete do "Novo Processo").
     */
    public function buscarContratos(Request $request): JsonResponse
    {
        $this->checkBackoffice();

        $termo = trim((string) $request->query('q', ''));
        if (mb_strlen($termo) < 2) {
            return response()->json(['contratos' => []]);
        }

        $termoDigitos = preg_replace('/\D+/', '', $termo);

        $contratos = Vendas::where('empresa_id', $this->empresaId())
            ->where(function ($q) use ($termo, $termoDigitos) {
                $q->where('nome_contrato', 'like', "%{$termo}%")
                    ->orWhere('numero_proposta', 'like', "%{$termo}%")
                    ->orWhere('cpf_cnpj', 'like', "%{$termo}%");
                if ($termoDigitos !== '') {
                    $q->orWhere('cpf_cnpj', 'like', "%{$termoDigitos}%");
                }
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'nome_contrato', 'cpf_cnpj', 'numero_proposta'])
            ->map(fn ($v) => [
                'id' => $v->id,
                'nome_contrato' => $v->nome_contrato,
                'cpf_cnpj' => $v->cpf_cnpj,
                'numero_proposta' => $v->numero_proposta,
            ]);

        return response()->json(['contratos' => $contratos]);
    }

    public function getBeneficiarios(int $vendaId): JsonResponse
    {
        $this->checkBackoffice();

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
                'id' => $titular->id,
                'nome' => $titular->nome,
                'tipo' => 'TITULAR',
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
                    'id' => $dep->id,
                    'nome' => $dep->nome,
                    'tipo' => 'DEPENDENTE',
                    'tipo_label' => 'Dependente',
                    'tem_liminar_ativa' => $temLiminarDep,
                ];
            }
        }

        return response()->json(['beneficiarios' => $beneficiarios]);
    }
}
