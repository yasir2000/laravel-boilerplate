<?php

namespace App\Services;

use App\Models\User;
use App\Events\NotificationSent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Send a notification to a user
     */
    public function sendToUser(User $user, string $title, string $message, array $data = []): void
    {
        $notification = $user->notifications()->create([
            'title' => $title,
            'message' => $message,
            'type' => $data['type'] ?? 'info',
            'priority' => $data['priority'] ?? 'medium',
            'data' => $data['extra'] ?? [],
            'icon' => $data['icon'] ?? null,
            'action_url' => $data['action_url'] ?? null,
            'action_text' => $data['action_text'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        // Broadcast real-time notification
        event(new NotificationSent($user, [
            'id' => $notification->id,
            'title' => $title,
            'message' => $message,
            'type' => $notification->type,
            'priority' => $notification->priority,
            'icon' => $notification->icon,
            'action_url' => $notification->action_url,
            'action_text' => $notification->action_text,
            'created_at' => $notification->created_at->toISOString(),
        ]));

        // Send email for high priority notifications
        if (in_array($data['priority'] ?? 'medium', ['high', 'urgent'])) {
            try {
                // Send email notification (implement your email logic here)
                Log::info("High priority notification sent to {$user->email}: {$title}");
            } catch (\Exception $e) {
                Log::error("Failed to send email notification: " . $e->getMessage());
            }
        }
    }

    /**
     * Send notification to multiple users
     */
    public function sendToUsers(array $users, string $title, string $message, array $data = []): void
    {
        foreach ($users as $user) {
            $this->sendToUser($user, $title, $message, $data);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);
        
        if (!$notification) {
            return false;
        }

        $notification->update(['read_at' => now()]);
        return true;
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(User $user): int
    {
        return $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Delete a notification
     */
    public function deleteNotification(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);
        
        if (!$notification) {
            return false;
        }

        $notification->delete();
        return true;
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount(User $user): int
    {
        return $user->notifications()
            ->whereNull('read_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->count();
    }

    /**
     * Get user notifications with pagination
     */
    public function getUserNotifications(User $user, int $perPage = 15, bool $unreadOnly = false)
    {
        $query = $user->notifications()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at', 'desc');

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        return $query->paginate($perPage);
    }

    /**
     * Clean up expired notifications
     */
    public function cleanupExpiredNotifications(): int
    {
        return \App\Models\Notification::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();
    }

    /**
     * Send system notification
     */
    public function sendSystemNotification(string $title, string $message, array $data = []): void
    {
        $users = User::where('is_active', true)->get();
        $this->sendToUsers($users, $title, $message, array_merge($data, [
            'type' => 'info',
            'priority' => 'medium',
            'icon' => 'system'
        ]));
    }
}
