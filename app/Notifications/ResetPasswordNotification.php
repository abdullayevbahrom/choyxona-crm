<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $setting = Setting::current();

        $resetUrl = $this->resetUrl($notifiable);
        $expire = (int) config(
            'auth.passwords.'.config('auth.defaults.passwords').'.expire',
        );

        $brandName = $setting->company_name ?: config('app.name', 'Choyxona CRM');
        $fromName = $setting->notification_from_name ?: $brandName;
        $fromEmail = $setting->notification_from_email ?: config(
            'mail.from.address',
            'hello@example.com',
        );

        $logoUrl = $setting->notification_logo_url
            ? (string) $setting->notification_logo_url
            : asset('favicon.svg');

        return (new MailMessage)
            ->from($fromEmail, $fromName)
            ->subject(__('Reset Password Notification'))
            ->markdown('mail.auth.reset-password', [
                'brandName' => $brandName,
                'logoUrl' => $logoUrl,
                'resetUrl' => $resetUrl,
                'expire' => $expire,
            ]);
    }
}
