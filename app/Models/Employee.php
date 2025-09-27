<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hr_employees';

    protected $fillable = [
        'user_id',
        'employee_id',
        'department_id',
        'position_id',
        'supervisor_id',
        'first_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'gender',
        'marital_status',
        'nationality',
        'national_id',
        'passport_number',
        'personal_email',
        'phone',
        'mobile',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'hire_date',
        'contract_start_date',
        'contract_end_date',
        'employment_type',
        'employment_status',
        'salary',
        'salary_currency',
        'salary_type',
        'hourly_rate',
        'work_schedule',
        'work_hours_per_week',
        'work_location',
        'remote_work_allowed',
        'vacation_days_per_year',
        'sick_days_per_year',
        'vacation_days_used',
        'sick_days_used',
        'profile_photo',
        'documents',
        'notes',
        'skills',
        'certifications',
        'education',
        'metadata'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'work_schedule' => 'array',
        'work_hours_per_week' => 'integer',
        'remote_work_allowed' => 'boolean',
        'vacation_days_per_year' => 'integer',
        'sick_days_per_year' => 'integer',
        'vacation_days_used' => 'integer',
        'sick_days_used' => 'integer',
        'documents' => 'array',
        'skills' => 'array',
        'certifications' => 'array',
        'education' => 'array',
        'metadata' => 'array'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'employee_id');
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class, 'employee_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('employment_status', 'active');
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function getDisplayNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function getTenureAttribute()
    {
        return $this->hire_date ? now()->diffInYears($this->hire_date) : null;
    }

    public function getVacationBalanceAttribute()
    {
        return $this->vacation_days_per_year - $this->vacation_days_used;
    }

    public function getSickBalanceAttribute()
    {
        return $this->sick_days_per_year - $this->sick_days_used;
    }
}