<?php

declare(strict_types=1);

namespace App\Observers;

use App\Mail\NovoCandidatoMail;
use App\Models\Candidato;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Quando um novo candidato é criado, notifica responsáveis pelo recrutamento.
 */
class CandidatoObserver
{
    public function created(Candidato $candidato): void
    {
        $linkAcesso = config('app.url') . '/recrutamento/candidatos?vaga=' . $candidato->vaga_id;

        $destinatarios = User::query()
            ->where('active', true)
            ->whereNotNull('email')
            ->whereHas('permissions', fn ($q) => $q->where('name', 'recrutamento.view-any'))
            ->orWhereHas('roles.permissions', fn ($q) => $q->where('name', 'recrutamento.view-any'))
            ->get(['id', 'email']);

        foreach ($destinatarios as $u) {
            Mail::to($u->email)->queue(new NovoCandidatoMail(
                candidato: $candidato,
                linkAcesso: $linkAcesso,
            ));
        }
    }
}
