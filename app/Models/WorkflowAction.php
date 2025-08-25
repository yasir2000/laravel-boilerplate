<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAction extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workflow_instance_id',
        'workflow_step_id',
        'user_id',
        'action',
        'comment',
        'data',
        'performed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'performed_at' => 'datetime',
    ];

    /**
     * Action types.
     */
    const ACTION_APPROVE = 'approve';
    const ACTION_REJECT = 'reject';
    const ACTION_REQUEST_CHANGES = 'request_changes';
    const ACTION_COMMENT = 'comment';
    const ACTION_REASSIGN = 'reassign';
    const ACTION_CANCEL = 'cancel';
    const ACTION_COMPLETE = 'complete';

    /**
     * Get the workflow instance.
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    /**
     * Get the workflow step.
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for actions by type.
     */
    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Get available actions.
     */
    public static function getActions(): array
    {
        return [
            self::ACTION_APPROVE => 'Approve',
            self::ACTION_REJECT => 'Reject',
            self::ACTION_REQUEST_CHANGES => 'Request Changes',
            self::ACTION_COMMENT => 'Comment',
            self::ACTION_REASSIGN => 'Reassign',
            self::ACTION_CANCEL => 'Cancel',
            self::ACTION_COMPLETE => 'Complete',
        ];
    }
}
