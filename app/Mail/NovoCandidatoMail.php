<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Candidato;

/**
 * E-mail informando que um novo candidato se inscreveu em uma vaga.
 */
class NovoCandidatoMail extends NotificacaoBaseMail
{
    public function __construct(
        public readonly Candidato $candidato,
        public readonly string $linkAcesso,
    ) {
        $this->candidato->loadMissing('vaga:id,titulo');
    }

    protected function subjectShort(): string
    {
        $tituloVaga = $this->candidato->vaga?->titulo ?? 'Vaga';
        return "Novo candidato para {$tituloVaga}: {$this->candidato->nome}";
    }

    protected function viewName(): string
    {
        return 'emails.novo-candidato';
    }

    protected function viewData(): array
    {
        return [
            'titulo' => 'Novo candidato',
            'tipoNotificacao' => 'Recrutamento',
            'candidato' => $this->candidato,
            'linkAcesso' => $this->linkAcesso,
        ];
    }
}
