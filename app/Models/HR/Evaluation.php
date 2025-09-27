<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Evaluation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hr_evaluations';

    protected $fillable = [
        'employee_id',
        'evaluator_id',
        'evaluation_period_start',
        'evaluation_period_end',
        'evaluation_type',
        'overall_score',
        'overall_rating',
        'performance_scores',
        'goals_achievement',
        'competencies',
        'strengths',
        'areas_for_improvement',
        'goals_next_period',
        'development_plan',
        'employee_comments',
        'evaluator_comments',
        'status',
        'completed_at',
        'acknowledged_at',
        'employee_acknowledged',
        'salary_recommendation',
        'salary_increase_percentage',
        'promotion_recommendation',
        'recommendations',
        'metadata'
    ];

    protected $casts = [
        'evaluation_period_start' => 'date',
        'evaluation_period_end' => 'date',
        'overall_score' => 'decimal:2',
        'performance_scores' => 'json',
        'goals_achievement' => 'json',
        'competencies' => 'json',
        'completed_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'employee_acknowledged' => 'boolean',
        'salary_increase_percentage' => 'decimal:2',
        'metadata' => 'json'
    ];

    /**
     * Get the employee being evaluated
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Get the evaluator
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    /**
     * Check if evaluation is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if evaluation is acknowledged by employee
     */
    public function isAcknowledged()
    {
        return $this->employee_acknowledged;
    }

    /**
     * Get evaluation types
     */
    public static function getEvaluationTypes()
    {
        return [
            'annual' => 'Annual Review',
            'quarterly' => 'Quarterly Review',
            'probation' => 'Probation Review',
            'special' => 'Special Review'
        ];
    }

    /**
     * Get rating options
     */
    public static function getRatings()
    {
        return [
            'excellent' => 'Excellent',
            'good' => 'Good',
            'satisfactory' => 'Satisfactory',
            'needs_improvement' => 'Needs Improvement',
            'poor' => 'Poor'
        ];
    }

    /**
     * Get status options
     */
    public static function getStatuses()
    {
        return [
            'draft' => 'Draft',
            'pending_review' => 'Pending Review',
            'completed' => 'Completed',
            'acknowledged' => 'Acknowledged'
        ];
    }
}