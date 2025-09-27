<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'salary_currency',
        'requirements',
        'responsibilities',
        'skills_required',
        'experience_required',
        'education_required',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'requirements' => 'array',
        'responsibilities' => 'array',
        'skills_required' => 'array',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'experience_required' => 'integer'
    ];

    // Relationships
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'position_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getEmployeeCountAttribute()
    {
        return $this->employees()->count();
    }

    public function getSalaryRangeAttribute()
    {
        if ($this->min_salary && $this->max_salary) {
            return "{$this->salary_currency} {$this->min_salary} - {$this->max_salary}";
        }
        return null;
    }
}