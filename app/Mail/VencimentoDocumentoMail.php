<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Veiculo;
use Carbon\Carbon;

/**
 * E-mail de alerta de documento próximo do vencimento.
 *
 * Usado para alertar sobre vencimento de licenciamento, seguro, CNH etc.
 */
class VencimentoDocumentoMail extends NotificacaoBaseMail
{
    public function __construct(
        public readonly string $tipoDocumento,
        public readonly string $referente,
        public readonly Carbon $dataVencimento,
        public readonly string $linkAcesso,
    ) {}

    protected function subjectShort(): string
    {
        $dias = (int) now()->startOfDay()->diffInDays($this->dataVencimento->startOfDay(), false);
        if ($dias < 0) {
            return "{$this->tipoDocumento} VENCIDO de {$this->referente}";
        }
        if ($dias === 0) {
            return "{$this->tipoDocumento} vence HOJE — {$this->referente}";
        }
        return "{$this->tipoDocumento} de {$this->referente} vence em {$dias} dia(s)";
    }

    protected function viewName(): string
    {
        return 'emails.vencimento-documento';
    }

    protected function viewData(): array
    {
        $dias = (int) now()->startOfDay()->diffInDays($this->dataVencimento->startOfDay(), false);

        return [
            'titulo' => "Vencimento de {$this->tipoDocumento}",
            'tipoNotificacao' => 'Alerta de vencimento',
            'tipoDocumento' => $this->tipoDocumento,
            'referente' => $this->referente,
            'dataVencimento' => $this->dataVencimento,
            'diasRestantes' => $dias,
            'vencido' => $dias < 0,
            'linkAcesso' => $this->linkAcesso,
        ];
    }
}
