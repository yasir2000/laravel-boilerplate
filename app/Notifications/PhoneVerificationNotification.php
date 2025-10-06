<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PhoneVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $verificationCode;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $verificationCode)
    {
        $this->verificationCode = $verificationCode;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // In production, you would add 'sms' channel here
        // For now, we'll use database and mail for testing
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject(__('Phone Verification Code'))
                    ->greeting(__('Hello :name!', ['name' => $notifiable->name]))
                    ->line(__('Your phone verification code is: :code', ['code' => $this->verificationCode]))
                    ->line(__('This code will expire in 10 minutes.'))
                    ->line(__('If you did not request this verification, please ignore this message.'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'phone_verification',
            'message' => __('Your phone verification code is: :code', ['code' => $this->verificationCode]),
            'code' => $this->verificationCode,
            'expires_at' => now()->addMinutes(10),
        ];
    }

    /**
     * Get the SMS representation of the notification.
     * This would be used with a real SMS service like Twilio
     */
    public function toSms(object $notifiable): string
    {
        return __('Your verification code is: :code. Valid for 10 minutes.', [
            'code' => $this->verificationCode
        ]);
    }
}