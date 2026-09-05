<?php

namespace App\Repositories\Contracts;

use App\Models\WhatsappConversa;
use Illuminate\Support\Collection;

interface WhatsappConversaRepositoryInterface
{
    public function getConversasLista(int $empresaId, ?int $userId, ?string $busca = null, string $modo = 'ativas'): Collection;

    public function getConversasKanban(int $empresaId, ?int $userId): Collection;

    public function findParaUsuario(int $conversaId, int $empresaId, ?int $userId): ?WhatsappConversa;

    public function changeStatusConversa(int $conversaId, int $empresaId, int $tabulacaoId): bool;

    public function vincularContato(int $conversaId, int $empresaId, ?int $contatoId): bool;

    public function zerarNaoLidas(int $conversaId, int $empresaId): void;

    public function setArquivada(int $conversaId, int $empresaId, bool $arquivada): bool;
}
