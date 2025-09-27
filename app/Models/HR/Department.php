<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hr_departments';

    protected $fillable = [
        'name',
        'code',
        'description',
        'parent_id',
        'manager_id',
        'location',
        'budget',
        'max_employees',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'max_employees' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'json'
    ];

    /**
     * Get the parent department
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    /**
     * Get child departments
     */
    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /**
     * Get all descendant departments recursively
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get the department manager
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get employees in this department
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'department_id');
    }

    /**
     * Get positions in this department
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class, 'department_id');
    }

    /**
     * Get active employees count
     */
    public function getActiveEmployeesCountAttribute()
    {
        return $this->employees()->where('employment_status', 'active')->count();
    }

    /**
     * Get department path (for nested departments)
     */
    public function getPathAttribute()
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }

    /**
     * Scope for active departments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for root departments (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Check if department can be deleted
     */
    public function canBeDeleted()
    {
        return $this->employees()->count() === 0 && 
               $this->children()->count() === 0 &&
               $this->positions()->count() === 0;
    }

    /**
     * Get budget utilization percentage
     */
    public function getBudgetUtilizationAttribute()
    {
        if (!$this->budget) return null;
        
        $totalSalaries = $this->employees()
            ->where('employment_status', 'active')
            ->sum('salary');
            
        return ($totalSalaries / $this->budget) * 100;
    }
}