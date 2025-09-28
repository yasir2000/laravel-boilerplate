<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use Carbon\Carbon;

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
        'work_schedule' => 'json',
        'work_hours_per_week' => 'integer',
        'remote_work_allowed' => 'boolean',
        'vacation_days_per_year' => 'integer',
        'sick_days_per_year' => 'integer',
        'vacation_days_used' => 'integer',
        'sick_days_used' => 'integer',
        'documents' => 'json',
        'skills' => 'json',
        'certifications' => 'json',
        'education' => 'json',
        'metadata' => 'json'
    ];

    /**
     * Get the user account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the department
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Get the position
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    /**
     * Get the supervisor
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    /**
     * Get subordinates
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    /**
     * Get attendance records
     */
    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    /**
     * Get leave requests
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'employee_id');
    }

    /**
     * Get evaluations
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'employee_id');
    }

    /**
     * Employee documents relationship
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class, 'employee_id');
    }

    /**
     * Get profile photo document
     */
    public function profilePhoto()
    {
        return $this->hasOne(EmployeeDocument::class, 'employee_id')
            ->where('document_type', 'photo')
            ->latest('uploaded_at');
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute()
    {
        $name = $this->first_name;
        
        if ($this->middle_name) {
            $name .= ' ' . $this->middle_name;
        }
        
        $name .= ' ' . $this->last_name;
        
        return $name;
    }

    /**
     * Get age
     */
    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Get years of service
     */
    public function getYearsOfServiceAttribute()
    {
        return $this->hire_date ? $this->hire_date->diffInYears(now()) : 0;
    }

    /**
     * Get remaining vacation days
     */
    public function getRemainingVacationDaysAttribute()
    {
        return max(0, $this->vacation_days_per_year - $this->vacation_days_used);
    }

    /**
     * Get remaining sick days
     */
    public function getRemainingSickDaysAttribute()
    {
        return max(0, $this->sick_days_per_year - $this->sick_days_used);
    }

    /**
     * Get current month attendance
     */
    public function getCurrentMonthAttendance()
    {
        return $this->attendance()
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->get();
    }

    /**
     * Get annual salary (convert from hourly/monthly if needed)
     */
    public function getAnnualSalaryAttribute()
    {
        switch ($this->salary_type) {
            case 'yearly':
                return $this->salary;
            case 'monthly':
                return $this->salary * 12;
            case 'hourly':
                return $this->hourly_rate * $this->work_hours_per_week * 52;
            default:
                return $this->salary;
        }
    }

    /**
     * Scope for active employees
     */
    public function scopeActive($query)
    {
        return $query->where('employment_status', 'active');
    }

    /**
     * Scope by department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope by employment type
     */
    public function scopeByEmploymentType($query, $type)
    {
        return $query->where('employment_type', $type);
    }

    /**
     * Check if employee is on probation
     */
    public function isOnProbation()
    {
        if (!$this->contract_start_date) {
            return false;
        }
        
        $probationEndDate = $this->contract_start_date->addMonths(3);
        return now()->lessThan($probationEndDate);
    }

    /**
     * Get employment types
     */
    public static function getEmploymentTypes()
    {
        return [
            'full_time' => 'Full Time',
            'part_time' => 'Part Time',
            'contract' => 'Contract',
            'intern' => 'Intern'
        ];
    }

    /**
     * Get employment statuses
     */
    public static function getEmploymentStatuses()
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'terminated' => 'Terminated',
            'on_leave' => 'On Leave'
        ];
    }
}