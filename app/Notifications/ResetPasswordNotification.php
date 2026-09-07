<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $resetUrl = url('/reset-password/' . $this->token . '?email=' . urlencode($notifiable->email));

        return (new MailMessage)
            ->subject('Reset your password')
            ->view('emails.auth.reset-password', [
                'user' => $notifiable,
                'resetUrl' => $resetUrl,
            ]);
    }
}