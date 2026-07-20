<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{

    /**
     * The password reset token.
     */
    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Reset your ' . config('app.name') . ' password')
            ->view('emails.reset-password', [
                'resetUrl'  => $resetUrl,
                'userName'  => $notifiable->name,
                'appName'   => config('app.name'),
                'expiresIn' => config('auth.passwords.users.expire', 60),
            ]);
    }
}
