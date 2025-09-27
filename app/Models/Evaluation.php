<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory;

    protected $table = 'hr_evaluations';

    protected $fillable = [
        'employee_id',
        'evaluator_id',
        'type',
        'period_start',
        'period_end',
        'overall_rating',
        'goals_rating',
        'skills_rating',
        'communication_rating',
        'teamwork_rating',
        'leadership_rating',
        'innovation_rating',
        'attendance_rating',
        'goals_achieved',
        'goals_total',
        'strengths',
        'areas_for_improvement',
        'development_plan',
        'comments',
        'employee_comments',
        'status',
        'submitted_at',
        'reviewed_at',
        'next_review_date',
        'attachments',
        'metadata'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'overall_rating' => 'decimal:2',
        'goals_rating' => 'decimal:2',
        'skills_rating' => 'decimal:2',
        'communication_rating' => 'decimal:2',
        'teamwork_rating' => 'decimal:2',
        'leadership_rating' => 'decimal:2',
        'innovation_rating' => 'decimal:2',
        'attendance_rating' => 'decimal:2',
        'goals_achieved' => 'integer',
        'goals_total' => 'integer',
        'strengths' => 'array',
        'areas_for_improvement' => 'array',
        'development_plan' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'next_review_date' => 'date',
        'attachments' => 'array',
        'metadata' => 'array'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForPeriod($query, $year = null)
    {
        if ($year) {
            return $query->whereYear('period_start', $year);
        }
        return $query;
    }

    // Accessors
    public function getAverageRatingAttribute()
    {
        $ratings = [
            $this->goals_rating,
            $this->skills_rating,
            $this->communication_rating,
            $this->teamwork_rating,
            $this->leadership_rating,
            $this->innovation_rating,
            $this->attendance_rating
        ];
        
        $validRatings = array_filter($ratings, function($rating) {
            return !is_null($rating) && $rating > 0;
        });
        
        return count($validRatings) > 0 ? array_sum($validRatings) / count($validRatings) : 0;
    }

    public function getGoalsAchievementPercentageAttribute()
    {
        if ($this->goals_total > 0) {
            return ($this->goals_achieved / $this->goals_total) * 100;
        }
        return 0;
    }

    public function getPerformanceLevelAttribute()
    {
        $rating = $this->overall_rating ?: $this->average_rating;
        
        return match(true) {
            $rating >= 4.5 => 'Excellent',
            $rating >= 3.5 => 'Good',
            $rating >= 2.5 => 'Satisfactory',
            $rating >= 1.5 => 'Needs Improvement',
            default => 'Unsatisfactory'
        };
    }

    public function getCanEditAttribute()
    {
        return in_array($this->status, ['draft', 'submitted']);
    }
}