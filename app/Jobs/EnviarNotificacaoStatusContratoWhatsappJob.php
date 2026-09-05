<?php

namespace App\Jobs;

use App\Enums\TabulationCode;
use App\Jobs\Concerns\UsesTenantContext;
use App\Models\Empresa;
use App\Models\Tabulacoes;
use App\Models\User;
use App\Models\Vendas;
use App\Services\StatusContratoWhatsappFormatter;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EnviarNotificacaoStatusContratoWhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UsesTenantContext;

    public int $tries = 3;

    public array $backoff = [60, 180, 600];

    public const WHITELIST_STATUS = [
        TabulationCode::IMPLANTADO,
        TabulationCode::BOLETO_DISPONIVEL,
        TabulationCode::PENDENCIA,
        TabulationCode::ESTORNO,
        TabulationCode::REGULARIZADO,
        TabulationCode::ANALISE_OPERADORA,
    ];

    // WhatsApp/Baileys aceita documentos até 100MB. Margem de 5MB para overhead de multipart/encoding.
    private const MAX_MEDIA_BYTES = 95 * 1024 * 1024;

    public function __construct(
        public int $vendaId,
        public int $tabulacaoId,
        public ?string $alteradoPorNome = null,
        public ?string $motivo = null,
    ) {}

    public function handle(WhatsappService $whatsapp, StatusContratoWhatsappFormatter $formatter): void
    {
        $venda = Vendas::find($this->vendaId);
        if (! $venda) {
            Log::warning('StatusContratoWhats: venda não encontrada', ['venda_id' => $this->vendaId]);

            return;
        }

        $empresa = Empresa::find($venda->empresa_id);
        if (! $empresa || empty($empresa->whatsapp_token)) {
            Log::warning('StatusContratoWhats: empresa sem whatsapp_token', [
                'venda_id' => $this->vendaId,
                'empresa_id' => $venda->empresa_id,
            ]);

            return;
        }

        $vendedor = User::query()->tenantMember((int) $venda->empresa_id)->find($venda->user_id);
        if (! $vendedor || empty($vendedor->whatsapp)) {
            Log::warning('StatusContratoWhats: vendedor sem whatsapp cadastrado', [
                'venda_id' => $this->vendaId,
                'user_id' => $venda->user_id,
            ]);

            return;
        }

        $tabulacao = Tabulacoes::query()
            ->where('empresa_id', $venda->empresa_id)
            ->whereNotNull('codigo')
            ->find($this->tabulacaoId);

        if (! $tabulacao || ! in_array($tabulacao->codigo, self::WHITELIST_STATUS, true)) {
            Log::warning('StatusContratoWhats: tabulacao inválida para a empresa ou fora da whitelist', [
                'venda_id' => $this->vendaId,
                'empresa_id' => $venda->empresa_id,
                'tabulacao_id' => $this->tabulacaoId,
            ]);

            return;
        }

        $body = $formatter->format(
            $venda,
            $tabulacao->codigo,
            $tabulacao->descricao,
            $this->alteradoPorNome,
            $this->motivo,
            $empresa->nome_fantasia ?? null,
        );

        $modo = 'texto';
        if ($tabulacao->codigo === TabulationCode::BOLETO_DISPONIVEL) {
            $rel = trim((string) ($venda->path_boleto_disponivel ?? ''), '/');

            if ($rel !== '' && Storage::exists($rel)) {
                $abs = Storage::path($rel);
                $size = Storage::size($rel);

                if ($size <= self::MAX_MEDIA_BYTES) {
                    $resp = $whatsapp->sendMedia($empresa->whatsapp_token, $vendedor->whatsapp, $abs, $body);
                    $modo = 'midia';
                } else {
                    Log::warning('StatusContratoWhats: boleto excede limite, fallback texto', [
                        'venda_id' => $this->vendaId,
                        'size' => $size,
                        'limit' => self::MAX_MEDIA_BYTES,
                    ]);
                    $body .= "\n\n_O boleto será encaminhado pelo financeiro (arquivo grande)._";
                    $resp = $whatsapp->send($empresa->whatsapp_token, $vendedor->whatsapp, $body);
                }
            } else {
                Log::warning('StatusContratoWhats: boleto sem arquivo no disk, fallback texto', [
                    'venda_id' => $this->vendaId,
                    'path' => $rel,
                ]);
                $body .= "\n\n_O boleto será encaminhado em instantes._";
                $resp = $whatsapp->send($empresa->whatsapp_token, $vendedor->whatsapp, $body);
            }
        } else {
            $resp = $whatsapp->send($empresa->whatsapp_token, $vendedor->whatsapp, $body);
        }

        Log::info('StatusContratoWhats: envio', [
            'venda_id' => $this->vendaId,
            'empresa_id' => $venda->empresa_id,
            'user_id' => $vendedor->id,
            'tabulacao_id' => $this->tabulacaoId,
            'modo' => $modo,
            'success' => $resp['success'] ?? false,
        ]);
    }
}
