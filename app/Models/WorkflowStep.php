<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class WorkflowStep extends BaseModel
{
    use HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workflow_definition_id',
        'name',
        'description',
        'step_type',
        'assignee_type',
        'assignee_id',
        'config',
        'order',
        'is_required',
        'timeout_hours',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'config' => 'array',
        'is_required' => 'boolean',
        'timeout_hours' => 'integer',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatable = ['name', 'description'];

    /**
     * Step types.
     */
    const TYPE_APPROVAL = 'approval';
    const TYPE_REVIEW = 'review';
    const TYPE_NOTIFICATION = 'notification';
    const TYPE_AUTOMATIC = 'automatic';
    const TYPE_CONDITION = 'condition';

    /**
     * Assignee types.
     */
    const ASSIGNEE_USER = 'user';
    const ASSIGNEE_ROLE = 'role';
    const ASSIGNEE_DEPARTMENT = 'department';
    const ASSIGNEE_SYSTEM = 'system';

    /**
     * Get the workflow definition.
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    /**
     * Get the workflow actions for this step.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class);
    }

    /**
     * Scope for required steps.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope for steps by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('step_type', $type);
    }

    /**
     * Get available step types.
     */
    public static function getStepTypes(): array
    {
        return [
            self::TYPE_APPROVAL => 'Approval',
            self::TYPE_REVIEW => 'Review',
            self::TYPE_NOTIFICATION => 'Notification',
            self::TYPE_AUTOMATIC => 'Automatic',
            self::TYPE_CONDITION => 'Condition',
        ];
    }

    /**
     * Get available assignee types.
     */
    public static function getAssigneeTypes(): array
    {
        return [
            self::ASSIGNEE_USER => 'User',
            self::ASSIGNEE_ROLE => 'Role',
            self::ASSIGNEE_DEPARTMENT => 'Department',
            self::ASSIGNEE_SYSTEM => 'System',
        ];
    }
}
