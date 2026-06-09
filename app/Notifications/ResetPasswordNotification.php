<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordBase;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Email de recuperação de senha em português.
 *
 * Sobrescreve a notification padrão do Laravel para usar
 * o tom de voz e idioma do sistema.
 */
class ResetPasswordNotification extends ResetPasswordBase
{
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expira = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');

        return (new MailMessage)
            ->subject('Recuperação de senha — ' . config('app.name'))
            ->greeting('Olá!')
            ->line('Recebemos uma solicitação para redefinir a senha da sua conta.')
            ->action('Redefinir senha', $url)
            ->line("Este link expira em {$expira} minutos.")
            ->line('Se você não solicitou esta troca, ignore este email — sua senha não será alterada.')
            ->salutation('Atenciosamente, equipe ' . config('app.name'));
    }
}
