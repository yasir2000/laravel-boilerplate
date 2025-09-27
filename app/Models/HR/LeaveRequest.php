<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Carbon\Carbon;

class LeaveRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hr_leave_requests';

    protected $fillable = [
        'employee_id',
        'request_number',
        'leave_type',
        'start_date',
        'end_date',
        'days_requested',
        'is_half_day',
        'half_day_type',
        'reason',
        'comments',
        'attachments',
        'requested_at',
        'status',
        'reviewed_by',
        'review_comments',
        'reviewed_at',
        'coverage_employee_id',
        'coverage_notes',
        'metadata'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days_requested' => 'integer',
        'is_half_day' => 'boolean',
        'attachments' => 'json',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'metadata' => 'json'
    ];

    /**
     * Get the employee who made the request
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Get the reviewer
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the coverage employee
     */
    public function coverageEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'coverage_employee_id');
    }

    /**
     * Get duration in days
     */
    public function getDurationAttribute()
    {
        if ($this->is_half_day) {
            return 0.5;
        }
        
        return $this->days_requested;
    }

    /**
     * Check if request is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request is approved
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Check if request is rejected
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if request can be cancelled
     */
    public function canBeCancelled()
    {
        return $this->status === 'pending' || 
               ($this->status === 'approved' && $this->start_date->isFuture());
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for current year requests
     */
    public function scopeCurrentYear($query)
    {
        return $query->whereYear('start_date', now()->year);
    }

    /**
     * Get leave types
     */
    public static function getLeaveTypes()
    {
        return [
            'vacation' => 'Vacation',
            'sick' => 'Sick Leave',
            'personal' => 'Personal Leave',
            'maternity' => 'Maternity Leave',
            'paternity' => 'Paternity Leave',
            'emergency' => 'Emergency Leave',
            'bereavement' => 'Bereavement Leave',
            'unpaid' => 'Unpaid Leave',
            'compensatory' => 'Compensatory Leave'
        ];
    }

    /**
     * Get status options
     */
    public static function getStatuses()
    {
        return [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled'
        ];
    }

    /**
     * Generate unique request number
     */
    public static function generateRequestNumber()
    {
        $prefix = 'LR';
        $year = now()->year;
        
        // Get next sequence number
        $lastRequest = self::where('request_number', 'LIKE', "{$prefix}{$year}%")
                          ->orderBy('request_number', 'desc')
                          ->first();
        
        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->request_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . $year . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate working days between dates
     */
    public function calculateWorkingDays()
    {
        if ($this->is_half_day) {
            return 0.5;
        }

        $startDate = $this->start_date;
        $endDate = $this->end_date;
        $workingDays = 0;

        while ($startDate->lte($endDate)) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if (!in_array($startDate->dayOfWeek, [0, 6])) {
                $workingDays++;
            }
            $startDate->addDay();
        }

        return $workingDays;
    }
}