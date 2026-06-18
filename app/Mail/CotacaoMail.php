<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail de Cotação enviado pelo vendedor ao cliente.
 *
 * O remetente (from) é o e-mail do próprio vendedor (@lkbrokers.com), com
 * reply-to apontando para ele. O corpo é a mensagem escrita pelo vendedor
 * (HTML do editor), embrulhada num template padronizado com assinatura.
 * Um anexo PDF (a cotação) é opcional.
 */
class CotacaoMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array  $dados  assunto, mensagemHtml, vendedorNome, vendedorEmail, vendedorWhatsapp
     * @param  string|null  $anexoPath  caminho absoluto do PDF a anexar
     * @param  string|null  $anexoNome  nome do arquivo exibido no e-mail
     */
    public function __construct(
        public array $dados,
        public ?string $anexoPath = null,
        public ?string $anexoNome = null,
    ) {}

    public function envelope(): Envelope
    {
        $email = $this->dados['vendedorEmail'];
        $nome = $this->dados['vendedorNome'] ?? $email;

        return new Envelope(
            from: new Address($email, $nome),
            replyTo: [new Address($email, $nome)],
            subject: $this->dados['assunto'] ?? 'Cotação',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cotacao',
            with: ['dados' => $this->dados],
        );
    }

    public function attachments(): array
    {
        if (! $this->anexoPath) {
            return [];
        }

        return [
            Attachment::fromPath($this->anexoPath)
                ->as($this->anexoNome ?? 'cotacao.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
