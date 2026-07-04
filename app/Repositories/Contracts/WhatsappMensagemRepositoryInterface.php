<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface WhatsappMensagemRepositoryInterface
{
    public function getThread(int $conversaId, ?int $beforeId = null, int $limit = 30): Collection;
}
