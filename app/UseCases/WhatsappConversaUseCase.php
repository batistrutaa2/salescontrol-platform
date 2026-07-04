<?php

namespace App\UseCases;

use App\Enums\UserRole;
use App\Helpers\Helpers;
use App\Models\Contatos;
use App\Models\ContatosCorretores;
use App\Models\User;
use App\Models\WhatsappConversa;
use App\Models\WhatsappMensagem;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;
use App\Repositories\Contracts\WhatsappConversaRepositoryInterface;
use App\Repositories\Contracts\WhatsappInstanciaRepositoryInterface;
use App\Services\Whatsapp\ConversaService;
use Illuminate\Support\Facades\Storage;

class WhatsappConversaUseCase
{
    public function __construct(
        private WhatsappConversaRepositoryInterface $conversaRepository,
        private TabulacoesRepositoryInterface $tabulacoesRepository,
        private ConversaService $conversaService,
        private WhatsappInstanciaRepositoryInterface $instanciaRepository
    ) {}

    /**
     * Vendedor vê só as próprias conversas; demais roles enxergam a empresa toda.
     */
    public function escopoUsuario(User $user): ?int
    {
        return (int) $user->user_role_id === UserRole::VENDEDOR ? $user->id : null;
    }

    public function podeInteragir(User $user, WhatsappConversa $conversa): bool
    {
        return (int) $user->user_role_id === UserRole::VENDEDOR
          && (int) $conversa->user_id === (int) $user->id;
    }

    /**
     * Board no mesmo shape do kanban comercial (Comercial::structureBoardData):
     * [{id, title, order, item: [...]}] ordenado por ordem_kanban.
     */
    public function getBoardData(User $user): array
    {
        $tabulacoes = $this->tabulacoesRepository->getTabulationsCompanieCommercial($user->empresa_id);
        $conversas = $this->conversaRepository->getConversasKanban($user->empresa_id, $this->escopoUsuario($user));

        $mostrarVendedor = in_array((int) $user->user_role_id, [
            UserRole::ADMINISTRATIVO,
            UserRole::BACKOFFICE,
            UserRole::SUPERVISOR,
            UserRole::DEVELOPER,
        ], true);

        $boardData = [];

        foreach ($tabulacoes as $tabulacao) {
            $items = $conversas
                ->filter(fn ($conversa) => (int) $conversa->tabulacao_id === (int) $tabulacao['id'])
                ->map(fn ($conversa) => [
                    'id' => $conversa->id,
                    'title' => $conversa->contato?->nome_cliente ?: ($conversa->nome_whatsapp ?: $conversa->numero),
                    'numero' => $conversa->numero,
                    'nome_whatsapp' => $conversa->nome_whatsapp,
                    'foto_url' => $conversa->foto_url,
                    'contato_id' => $conversa->contato_id,
                    'contato_nome' => $conversa->contato?->nome_cliente,
                    'temperatura' => $conversa->lead_temperatura,
                    'tabulacao-id' => $tabulacao['id'],
                    'last_message_preview' => $conversa->last_message_preview,
                    'last_message_at' => $conversa->last_message_at?->timezone('America/Sao_Paulo')->format('d/m/Y H:i'),
                    'data_create' => $conversa->created_at?->timezone('America/Sao_Paulo')->format('d/m/Y H:i:s'),
                    'unread_count' => $conversa->unread_count,
                    'user-id' => $conversa->user_id,
                    'user-name' => $mostrarVendedor ? $conversa->vendedor?->name : null,
                ])
                ->values()
                ->toArray();

            $boardData[] = [
                'id' => Helpers::normalizeStatusName($tabulacao['id']),
                'title' => $tabulacao['descricao'],
                'order' => $tabulacao['ordem_kanban'],
                'item' => $items,
            ];
        }

        usort($boardData, fn ($a, $b) => strcmp($a['order'], $b['order']));

        return $boardData;
    }

    public function changeStatus(User $user, int $conversaId, int $tabulacaoId): bool
    {
        $conversa = $this->conversaRepository->findParaUsuario($conversaId, $user->empresa_id, $this->escopoUsuario($user));

        if (! $conversa) {
            return false;
        }

        return $this->conversaRepository->changeStatusConversa($conversa->id, $tabulacaoId);
    }

    public function vincularContato(User $user, int $conversaId, ?int $contatoId): bool
    {
        $conversa = $this->conversaRepository->findParaUsuario($conversaId, $user->empresa_id, $this->escopoUsuario($user));

        if (! $conversa) {
            return false;
        }

        // Segurança: o contato precisa estar atribuído ao vendedor da conversa
        if ($contatoId !== null) {
            $atribuido = \Illuminate\Support\Facades\DB::table('contatos_corretores')
                ->where('empresa_id', $user->empresa_id)
                ->where('user_id', $conversa->user_id)
                ->where('contato_id', $contatoId)
                ->exists();

            if (! $atribuido) {
                return false;
            }
        }

        return $this->conversaRepository->vincularContato($conversa->id, $contatoId);
    }

    /**
     * Inicia uma conversa nova a partir de um número digitado pelo vendedor,
     * opcionalmente já criando o lead vinculado.
     *
     * @return array{conversa_id?: int, erro?: string}
     */
    public function novaConversa(User $user, string $numeroBruto, ?string $nome, bool $criarLead): array
    {
        $instancia = $this->instanciaRepository->findByUser($user->empresa_id, $user->id);

        if (! $instancia || $instancia->status !== 'CONECTADA') {
            return ['erro' => 'Conecte o seu WhatsApp antes de iniciar conversas.'];
        }

        $digitos = preg_replace('/\D/', '', $numeroBruto);

        if (str_starts_with($digitos, '55') && strlen($digitos) >= 12) {
            $semPais = substr($digitos, 2);
        } else {
            $semPais = $digitos;
        }

        if (strlen($semPais) < 10 || strlen($semPais) > 11) {
            return ['erro' => 'Informe o número com DDD (ex: 11 98888-7777).'];
        }

        $remoteJid = '55'.$semPais.'@s.whatsapp.net';

        $contatoId = null;

        if ($criarLead) {
            // Telefones em contatos seguem a convenção do projeto: dígitos sem o 55
            $contato = Contatos::create([
                'empresa_id' => $user->empresa_id,
                'user_import_id' => $user->id,
                'nome_cliente' => $nome ?: 'Contato WhatsApp',
                'telefone1' => $semPais,
            ]);

            ContatosCorretores::create([
                'empresa_id' => $user->empresa_id,
                'contato_id' => $contato->id,
                'user_id' => $user->id,
                'tabulacao_id' => $this->conversaService->primeiraTabulacaoComercial($user->empresa_id),
                'temperatura' => 'FRIO',
            ]);

            $contatoId = $contato->id;
        }

        $conversa = $this->conversaService->resolverConversa($instancia, $remoteJid, $nome);

        if ($contatoId && ! $conversa->contato_id) {
            $this->conversaRepository->vincularContato($conversa->id, $contatoId);
        }

        return ['conversa_id' => $conversa->id];
    }

    /**
     * Limpa o histórico da conversa (mensagens + mídias), mantendo a conversa.
     */
    public function limparConversa(User $user, int $conversaId): bool
    {
        $conversa = $this->conversaRepository->findParaUsuario($conversaId, $user->empresa_id, $this->escopoUsuario($user));

        if (! $conversa || ! $this->podeInteragir($user, $conversa)) {
            return false;
        }

        Storage::disk('public')->deleteDirectory("whatsapp/{$conversa->empresa_id}/{$conversa->id}");
        WhatsappMensagem::where('conversa_id', $conversa->id)->delete();

        $conversa->update([
            'last_message_preview' => null,
            'unread_count' => 0,
        ]);

        return true;
    }

    /**
     * Apaga a conversa por completo (mensagens, mídias e o registro).
     */
    public function apagarConversa(User $user, int $conversaId): bool
    {
        $conversa = $this->conversaRepository->findParaUsuario($conversaId, $user->empresa_id, $this->escopoUsuario($user));

        if (! $conversa || ! $this->podeInteragir($user, $conversa)) {
            return false;
        }

        Storage::disk('public')->deleteDirectory("whatsapp/{$conversa->empresa_id}/{$conversa->id}");
        WhatsappMensagem::where('conversa_id', $conversa->id)->delete();
        $conversa->delete();

        return true;
    }
}
