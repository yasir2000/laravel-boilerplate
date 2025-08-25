<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Translatable\HasTranslations;

class Notification extends BaseModel
{
    use HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'title',
        'message',
        'data',
        'channels',
        'read_at',
        'sent_at',
        'failed_at',
        'priority',
        'action_url',
        'action_text',
        'expires_at',
        'locale',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'channels' => 'array',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatable = ['title', 'message', 'action_text'];

    /**
     * Notification types.
     */
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';
    const TYPE_TASK_ASSIGNED = 'task_assigned';
    const TYPE_TASK_COMPLETED = 'task_completed';
    const TYPE_PROJECT_UPDATED = 'project_updated';
    const TYPE_WORKFLOW_APPROVAL = 'workflow_approval';
    const TYPE_SYSTEM = 'system';

    /**
     * Priority levels.
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Get the notifiable entity.
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user this notification belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'notifiable_id')->where('notifiable_type', User::class);
    }

    /**
     * Scope for unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for read notifications.
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope for notifications by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for notifications by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for active notifications (not expired).
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): bool
    {
        return $this->update(['read_at' => now()]);
    }

    /**
     * Check if the notification is read.
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if the notification is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get available notification types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_INFO => 'Information',
            self::TYPE_SUCCESS => 'Success',
            self::TYPE_WARNING => 'Warning',
            self::TYPE_ERROR => 'Error',
            self::TYPE_TASK_ASSIGNED => 'Task Assigned',
            self::TYPE_TASK_COMPLETED => 'Task Completed',
            self::TYPE_PROJECT_UPDATED => 'Project Updated',
            self::TYPE_WORKFLOW_APPROVAL => 'Workflow Approval',
            self::TYPE_SYSTEM => 'System',
        ];
    }

    /**
     * Get available priority levels.
     */
    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }
}
