<?php

namespace App\Services;

use App\Models\Contatos;
use App\Models\ContatosCorretores;
use App\Models\Preditiva;
use App\Models\PreditivaConfiguracao;
use App\Models\PreditivaEnvio;
use App\Repositories\Eloquent\ContatosRepository;
use Illuminate\Support\Facades\DB;

/**
 * Orquestra o reenvio de leads frios para a preditiva (reciclagem).
 *
 * NAO depende de Auth — recebe empresa_id e user_id explicitos, para funcionar
 * tanto no controller (envio manual) quanto no command agendado (envio automatico).
 *
 * Garantia: leads COM VENDEDOR nunca sao enviados nem desvinculados, porque o
 * envio so ocorre se getElegibilidadeLead retornar elegivel (que exclui COM VENDEDOR).
 */
class ReciclagemLeadsService
{
    public function __construct(private ContatosRepository $contatosRepository) {}

    /**
     * Envia um unico lead para a preditiva, gravando o log duravel em preditiva_envios.
     * Re-valida a elegibilidade (defesa contra corrida) antes de mexer em qualquer tabela.
     */
    public function enviarParaPreditiva(int $contatoId, int $empresaId, string $origem, ?int $userId, int $dias): bool
    {
        $elegibilidadeInicial = $this->contatosRepository->getElegibilidadeLead($contatoId, $empresaId, $dias);
        if (! $elegibilidadeInicial) {
            // virou COM VENDEDOR / ganhou venda / ja esta na preditiva / esquentou
            return false;
        }

        try {
            return DB::transaction(function () use ($contatoId, $empresaId, $origem, $userId, $dias): bool {
                $contatoExiste = Contatos::query()
                    ->where('id', $contatoId)
                    ->where('empresa_id', $empresaId)
                    ->lockForUpdate()
                    ->exists();
                if (! $contatoExiste) {
                    return false;
                }

                // Outra execução pode ter reciclado ou atribuído o lead enquanto
                // aguardávamos o lock. Revalide toda a regra dentro da transação.
                $eleg = $this->contatosRepository->getElegibilidadeLead($contatoId, $empresaId, $dias);
                if (! $eleg) {
                    return false;
                }

                // Reativa contato descartado
                Contatos::where('id', $contatoId)
                    ->where('empresa_id', $empresaId)
                    ->where('status', 'N')
                    ->update(['status' => 'Y']);

                // Desvincula de corretor (so REMARKETING chega aqui com vinculo; nunca COM VENDEDOR)
                ContatosCorretores::where('contato_id', $contatoId)
                    ->where('empresa_id', $empresaId)
                    ->delete();

                // Evita duplicidade na fila
                Preditiva::where('contato_id', $contatoId)
                    ->where('empresa_id', $empresaId)
                    ->delete();

                Preditiva::create([
                    'empresa_id' => $empresaId,
                    'contato_id' => $contatoId,
                    'status' => 'Y',
                ]);

                PreditivaEnvio::create([
                    'empresa_id' => $empresaId,
                    'contato_id' => $contatoId,
                    'enviado_em' => now(),
                    'origem' => $origem,
                    'enviado_por' => $userId,
                    'situacao_origem' => $eleg->situacao,
                    'dias_inativo' => (int) $eleg->dias_parado,
                ]);

                return true;
            });
        } catch (\Throwable $th) {
            report($th);
            throw $th;
        }
    }

    /**
     * Envia em lote. $ids = leads selecionados; null = puxa todos os elegiveis (com teto).
     */
    public function enviarElegiveisEmLote(int $empresaId, ?array $ids, string $origem, ?int $userId, ?int $limite = null): array
    {
        $config = PreditivaConfiguracao::getOrDefault($empresaId);
        $dias = (int) $config->dias_sem_contato_reenvio;

        if ($ids === null) {
            $cap = $limite ?? (int) $config->limite_envio_diario;
            $ids = $this->contatosRepository->getIdsLeadsFriosElegiveis($empresaId, $dias, $cap);
        } else {
            $ids = array_values(array_unique(array_map('intval', $ids)));
            if ($limite !== null) {
                $ids = array_slice($ids, 0, $limite);
            }
        }

        $enviados = 0;
        $ignorados = 0;
        $erros = 0;

        foreach ($ids as $id) {
            try {
                if ($this->enviarParaPreditiva((int) $id, $empresaId, $origem, $userId, $dias)) {
                    $enviados++;
                } else {
                    $ignorados++;
                }
            } catch (\Throwable $th) {
                $erros++;
            }
        }

        return [
            'enviados' => $enviados,
            'ignorados' => $ignorados,
            'erros' => $erros,
            'total' => count($ids),
        ];
    }
}
