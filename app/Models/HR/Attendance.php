<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'hr_attendance';

    protected $fillable = [
        'employee_id',
        'date',
        'check_in_time',
        'check_out_time',
        'expected_check_in',
        'expected_check_out',
        'break_start',
        'break_end',
        'break_duration_minutes',
        'total_work_minutes',
        'overtime_minutes',
        'late_minutes',
        'early_departure_minutes',
        'status',
        'attendance_type',
        'check_in_location',
        'check_out_location',
        'check_in_device',
        'check_out_device',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_latitude',
        'check_out_longitude',
        'notes',
        'admin_notes',
        'requires_approval',
        'is_approved',
        'approved_by',
        'approved_at',
        'metadata'
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'expected_check_in' => 'datetime',
        'expected_check_out' => 'datetime',
        'break_start' => 'datetime',
        'break_end' => 'datetime',
        'break_duration_minutes' => 'integer',
        'total_work_minutes' => 'integer',
        'overtime_minutes' => 'integer',
        'late_minutes' => 'integer',
        'early_departure_minutes' => 'integer',
        'check_in_latitude' => 'decimal:8',
        'check_in_longitude' => 'decimal:8',
        'check_out_latitude' => 'decimal:8',
        'check_out_longitude' => 'decimal:8',
        'requires_approval' => 'boolean',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'metadata' => 'json'
    ];

    /**
     * Get the employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Get the approver
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get formatted work duration
     */
    public function getWorkDurationAttribute()
    {
        if (!$this->total_work_minutes) {
            return '0h 0m';
        }
        
        $hours = floor($this->total_work_minutes / 60);
        $minutes = $this->total_work_minutes % 60;
        
        return $hours . 'h ' . $minutes . 'm';
    }

    /**
     * Get formatted overtime duration
     */
    public function getOvertimeDurationAttribute()
    {
        if (!$this->overtime_minutes) {
            return '0h 0m';
        }
        
        $hours = floor($this->overtime_minutes / 60);
        $minutes = $this->overtime_minutes % 60;
        
        return $hours . 'h ' . $minutes . 'm';
    }

    /**
     * Check if employee is currently checked in
     */
    public function isCheckedIn()
    {
        return $this->check_in_time && !$this->check_out_time;
    }

    /**
     * Check if employee was late
     */
    public function isLate()
    {
        return $this->late_minutes > 0;
    }

    /**
     * Check if employee left early
     */
    public function leftEarly()
    {
        return $this->early_departure_minutes > 0;
    }

    /**
     * Calculate total work time automatically
     */
    public function calculateWorkTime()
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return;
        }
        
        $totalMinutes = $this->check_in_time->diffInMinutes($this->check_out_time);
        $workMinutes = $totalMinutes - $this->break_duration_minutes;
        
        $this->total_work_minutes = max(0, $workMinutes);
        
        // Calculate overtime (assuming 8 hours = 480 minutes standard)
        $standardWorkMinutes = 480;
        $this->overtime_minutes = max(0, $workMinutes - $standardWorkMinutes);
    }

    /**
     * Calculate late minutes
     */
    public function calculateLateMinutes()
    {
        if (!$this->check_in_time || !$this->expected_check_in) {
            return;
        }
        
        $this->late_minutes = max(0, $this->expected_check_in->diffInMinutes($this->check_in_time));
    }

    /**
     * Calculate early departure minutes
     */
    public function calculateEarlyDepartureMinutes()
    {
        if (!$this->check_out_time || !$this->expected_check_out) {
            return;
        }
        
        if ($this->check_out_time->lessThan($this->expected_check_out)) {
            $this->early_departure_minutes = $this->check_out_time->diffInMinutes($this->expected_check_out);
        }
    }

    /**
     * Auto-calculate all time fields
     */
    public function calculateAllTimes()
    {
        $this->calculateWorkTime();
        $this->calculateLateMinutes();
        $this->calculateEarlyDepartureMinutes();
        
        // Update status based on calculations
        $this->updateStatus();
    }

    /**
     * Update attendance status
     */
    public function updateStatus()
    {
        if (!$this->check_in_time) {
            $this->status = 'absent';
        } elseif ($this->late_minutes > 0) {
            $this->status = 'late';
        } elseif ($this->total_work_minutes && $this->total_work_minutes < 240) { // Less than 4 hours
            $this->status = 'half_day';
        } else {
            $this->status = 'present';
        }
    }

    /**
     * Scope for today's attendance
     */
    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    /**
     * Scope for this week's attendance
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope for this month's attendance
     */
    public function scopeThisMonth($query)
    {
        return $query->whereYear('date', now()->year)
                    ->whereMonth('date', now()->month);
    }

    /**
     * Scope for pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->where('requires_approval', true)
                    ->where('is_approved', false);
    }

    /**
     * Get attendance statuses
     */
    public static function getStatuses()
    {
        return [
            'present' => 'Present',
            'absent' => 'Absent',
            'late' => 'Late',
            'half_day' => 'Half Day',
            'on_leave' => 'On Leave',
            'holiday' => 'Holiday'
        ];
    }

    /**
     * Get attendance types
     */
    public static function getAttendanceTypes()
    {
        return [
            'regular' => 'Regular',
            'overtime' => 'Overtime',
            'weekend' => 'Weekend',
            'holiday' => 'Holiday'
        ];
    }
}