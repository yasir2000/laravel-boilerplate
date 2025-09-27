<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hr_positions';

    protected $fillable = [
        'title',
        'code',
        'description',
        'department_id',
        'level',
        'min_salary',
        'max_salary',
        'requirements',
        'responsibilities',
        'is_active',
        'skills',
        'metadata'
    ];

    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'is_active' => 'boolean',
        'skills' => 'json',
        'metadata' => 'json'
    ];

    /**
     * Get the department this position belongs to
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Get employees in this position
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'position_id');
    }

    /**
     * Get active employees count
     */
    public function getActiveEmployeesCountAttribute()
    {
        return $this->employees()->where('employment_status', 'active')->count();
    }

    /**
     * Get salary range formatted
     */
    public function getSalaryRangeAttribute()
    {
        if (!$this->min_salary && !$this->max_salary) {
            return 'Not specified';
        }
        
        if (!$this->min_salary) {
            return 'Up to $' . number_format($this->max_salary, 2);
        }
        
        if (!$this->max_salary) {
            return 'From $' . number_format($this->min_salary, 2);
        }
        
        return '$' . number_format($this->min_salary, 2) . ' - $' . number_format($this->max_salary, 2);
    }

    /**
     * Scope for active positions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope by level
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Check if position can be deleted
     */
    public function canBeDeleted()
    {
        return $this->employees()->count() === 0;
    }

    /**
     * Get available levels
     */
    public static function getLevels()
    {
        return [
            'intern' => 'Intern',
            'junior' => 'Junior',
            'mid' => 'Mid-level',
            'senior' => 'Senior',
            'lead' => 'Team Lead',
            'manager' => 'Manager',
            'senior_manager' => 'Senior Manager',
            'director' => 'Director',
            'vp' => 'Vice President',
            'c_level' => 'C-Level Executive'
        ];
    }

    /**
     * Get level display name
     */
    public function getLevelDisplayAttribute()
    {
        $levels = self::getLevels();
        return $levels[$this->level] ?? $this->level;
    }
}