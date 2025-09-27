<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $table = 'hr_leave_requests';

    protected $fillable = [
        'employee_id',
        'type',
        'start_date',
        'end_date',
        'days_requested',
        'reason',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejected_reason',
        'attachments',
        'is_half_day',
        'half_day_period',
        'emergency_contact',
        'notes',
        'metadata'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days_requested' => 'decimal:1',
        'approved_at' => 'datetime',
        'attachments' => 'array',
        'is_half_day' => 'boolean',
        'metadata' => 'array'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($subQ) use ($startDate, $endDate) {
                  $subQ->where('start_date', '<=', $startDate)
                       ->where('end_date', '>=', $endDate);
              });
        });
    }

    // Accessors
    public function getDurationAttribute()
    {
        if ($this->is_half_day) {
            return 0.5;
        }
        
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'orange',
            'approved' => 'green',
            'rejected' => 'red',
            'cancelled' => 'gray',
            default => 'blue'
        };
    }

    public function getCanCancelAttribute()
    {
        return $this->status === 'pending' && $this->start_date > now();
    }
}