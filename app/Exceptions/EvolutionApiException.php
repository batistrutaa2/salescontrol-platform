<?php

namespace App\Exceptions;

use RuntimeException;

class EvolutionApiException extends RuntimeException
{
    public function __construct(public readonly ?int $status = null)
    {
        parent::__construct(
            $status ? "Serviço de WhatsApp indisponível (HTTP {$status})." : 'Serviço de WhatsApp não configurado.'
        );
    }
}
