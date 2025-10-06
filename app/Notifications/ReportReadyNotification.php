<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $reportType;
    protected string $cacheKey;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $reportType, string $cacheKey)
    {
        $this->reportType = $reportType;
        $this->cacheKey = $cacheKey;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('HR Report Ready')
            ->line("Your {$this->reportType} report has been generated and is ready for download.")
            ->action('View Report', url("/reports/download/{$this->cacheKey}"))
            ->line('The report will be available for download for the next 24 hours.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Report Ready',
            'message' => "Your {$this->reportType} report is ready for download",
            'report_type' => $this->reportType,
            'cache_key' => $this->cacheKey,
            'download_url' => "/reports/download/{$this->cacheKey}",
        ];
    }
}