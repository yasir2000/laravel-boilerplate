<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'hr_attendance';

    protected $fillable = [
        'employee_id',
        'date',
        'clock_in',
        'clock_out',
        'break_start',
        'break_end',
        'break_duration',
        'total_hours',
        'overtime_hours',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'location_in',
        'location_out',
        'ip_address',
        'metadata'
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'break_start' => 'datetime',
        'break_end' => 'datetime',
        'break_duration' => 'integer',
        'total_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'approved_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    // Accessors
    public function getIsLateAttribute()
    {
        if (!$this->clock_in || !$this->employee) {
            return false;
        }
        
        // Assuming standard work start time is 9:00 AM
        $standardStartTime = $this->date->setTime(9, 0);
        return $this->clock_in > $standardStartTime;
    }

    public function getIsEarlyDepartureAttribute()
    {
        if (!$this->clock_out || !$this->employee) {
            return false;
        }
        
        // Assuming standard work end time is 5:00 PM
        $standardEndTime = $this->date->setTime(17, 0);
        return $this->clock_out < $standardEndTime;
    }
}