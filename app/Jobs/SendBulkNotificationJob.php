<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\NotificationService;
use App\Models\User;

class SendBulkNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $userIds;
    protected array $notificationData;
    protected string $channel;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(array $userIds, array $notificationData, string $channel = 'database')
    {
        $this->userIds = $userIds;
        $this->notificationData = $notificationData;
        $this->channel = $channel;
        
        // Set queue priority
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        foreach ($this->userIds as $userId) {
            try {
                $user = User::find($userId);
                if ($user) {
                    $notificationService->sendToUser(
                        $user,
                        $this->notificationData,
                        $this->channel
                    );
                }
            } catch (\Exception $e) {
                \Log::error("Failed to send notification to user {$userId}: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Bulk notification job failed: ' . $exception->getMessage(), [
            'user_ids' => $this->userIds,
            'notification_data' => $this->notificationData,
            'channel' => $this->channel,
        ]);
    }
}