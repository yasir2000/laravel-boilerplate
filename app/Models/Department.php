<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'metadata' => 'array',
        'budget' => 'decimal:2',
        'is_active' => 'boolean',
        'max_employees' => 'integer'
    ];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id');
    }

    public function positions()
    {
        return $this->hasMany(Position::class, 'department_id');
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

    public function getHierarchyLevelAttribute()
    {
        $level = 0;
        $department = $this;
        
        while ($department->parent) {
            $level++;
            $department = $department->parent;
        }
        
        return $level;
    }
}