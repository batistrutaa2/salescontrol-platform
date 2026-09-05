<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail de boas-vindas da empresa ativa ao cliente.
 *
 * Recebe um array de dados montado no Backoffice::buildDadosEmailPadrao().
 * Suporta dois modos:
 *   - 'padrao'        → template HTML estruturado (beneficiários, app, portal)
 *   - 'personalizado' → texto livre dentro do mesmo wrapper de marca
 */
class BoasVindasMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $dados) {}

    public function envelope(): Envelope
    {
        $empresa = $this->dados['nomeEmpresa'] ?? 'SalesControl';

        return new Envelope(
            subject: $this->dados['assunto'] ?? "Boas-vindas à {$empresa}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.boas-vindas',
            with: ['dados' => $this->dados],
        );
    }
}
