<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable base para as notificações do sistema.
 *
 * Padroniza:
 *   - Subject com prefixo [ETC]
 *   - View baseada em `emails.layout` com slot de conteúdo
 *   - Sempre enfileirada (ShouldQueue) — não bloqueia request
 *
 * As classes filhas implementam:
 *   - subjectShort(): string  — assunto curto (sem o prefixo)
 *   - viewName(): string      — view específica do email
 *   - viewData(): array       — variáveis pra view
 */
abstract class NotificacaoBaseMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    abstract protected function subjectShort(): string;

    abstract protected function viewName(): string;

    /**
     * @return array<string, mixed>
     */
    abstract protected function viewData(): array;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[ETC] ' . $this->subjectShort(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->viewName(),
            with: $this->viewData(),
        );
    }
}
