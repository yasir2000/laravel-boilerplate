<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hr_teams';

    protected $fillable = [
        'name',
        'description',
        'department',
        'team_lead_id',
        'team_type',
        'status',
        'icon',
        'performance_score',
        'goals',
        'metadata',
        'is_active'
    ];

    protected $casts = [
        'goals' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'performance_score' => 'integer'
    ];

    // Relationships
    
    /**
     * Get the team lead (User who leads this team)
     */
    public function teamLead()
    {
        return $this->belongsTo(User::class, 'team_lead_id');
    }

    /**
     * Get all team members (Many-to-many relationship)
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'hr_team_members', 'team_id', 'user_id')
                    ->withTimestamps()
                    ->withPivot(['role', 'joined_at', 'is_active']);
    }

    /**
     * Get active team members only
     */
    public function activeMembers()
    {
        return $this->members()->wherePivot('is_active', true);
    }

    /**
     * Get the department this team belongs to (if applicable)
     */
    public function departmentModel()
    {
        return $this->belongsTo(Department::class, 'department', 'name');
    }

    /**
     * Get team projects/tasks
     */
    public function projects()
    {
        return $this->hasMany(Project::class, 'team_id');
    }

    /**
     * Get team performance evaluations
     */
    public function evaluations()
    {
        return $this->hasMany(TeamEvaluation::class);
    }

    // Scopes
    
    /**
     * Scope to get only active teams
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    /**
     * Scope to filter by department
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by team type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('team_type', $type);
    }

    // Helper Methods
    
    /**
     * Get the total number of team members
     */
    public function getMemberCountAttribute()
    {
        return $this->activeMembers()->count();
    }

    /**
     * Check if user is team lead
     */
    public function isTeamLead($user)
    {
        return $this->team_lead_id === $user->id;
    }

    /**
     * Check if user is a member of this team
     */
    public function isMember($user)
    {
        return $this->activeMembers()->where('users.id', $user->id)->exists();
    }

    /**
     * Add a member to the team
     */
    public function addMember($user, $role = 'member')
    {
        return $this->members()->syncWithoutDetaching([
            $user->id => [
                'role' => $role,
                'joined_at' => now(),
                'is_active' => true
            ]
        ]);
    }

    /**
     * Remove a member from the team
     */
    public function removeMember($user)
    {
        return $this->members()->updateExistingPivot($user->id, [
            'is_active' => false
        ]);
    }

    /**
     * Update team performance score
     */
    public function updatePerformanceScore($score)
    {
        $this->update(['performance_score' => $score]);
    }

    /**
     * Get team statistics
     */
    public function getStatistics()
    {
        return [
            'total_members' => $this->member_count,
            'performance_score' => $this->performance_score,
            'status' => $this->status,
            'team_type' => $this->team_type,
            'department' => $this->department,
            'created_date' => $this->created_at->format('Y-m-d'),
            'lead_name' => $this->teamLead?->name ?? 'No Lead Assigned'
        ];
    }

    /**
     * Get team members with their roles
     */
    public function getMembersWithRoles()
    {
        return $this->activeMembers()->get()->map(function ($member) {
            return [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->pivot->role,
                'joined_at' => $member->pivot->joined_at,
                'is_lead' => $this->isTeamLead($member)
            ];
        });
    }

    /**
     * Available team statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            'forming' => 'Forming',
            'active' => 'Active', 
            'performing' => 'Performing',
            'on-hold' => 'On Hold',
            'completed' => 'Completed',
            'disbanded' => 'Disbanded'
        ];
    }

    /**
     * Available team types
     */
    public static function getAvailableTypes()
    {
        return [
            'project' => 'Project Team',
            'permanent' => 'Permanent Team',
            'cross-functional' => 'Cross-Functional Team',
            'task-force' => 'Task Force',
            'committee' => 'Committee',
            'working-group' => 'Working Group'
        ];
    }

    /**
     * Get teams for a specific user (either as lead or member)
     */
    public static function forUser($user)
    {
        return static::where('team_lead_id', $user->id)
                    ->orWhereHas('activeMembers', function ($query) use ($user) {
                        $query->where('users.id', $user->id);
                    })
                    ->active()
                    ->get();
    }
}