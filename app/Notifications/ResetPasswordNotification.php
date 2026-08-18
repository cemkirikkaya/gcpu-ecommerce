<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $email = urlencode($notifiable->getEmailForPasswordReset());
        $resetUrl = "{$frontendUrl}/reset-password?token={$this->token}&email={$email}";

        return (new MailMessage)
            ->subject('Şifre sıfırlama talebi')
            ->line('Hesabınız için şifre sıfırlama talebi aldık.')
            ->line('Şifrenizi sıfırlamak için aşağıdaki butona tıklayın. Bu bağlantı 60 dakika geçerlidir.')
            ->action('Şifreyi sıfırla', $resetUrl)
            ->line('Bu talebi siz oluşturmadıysanız herhangi bir işlem yapmanız gerekmez.');
    }
}
