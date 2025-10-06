<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollPeriod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'pay_date',
        'status',
        'type',
        'year',
        'month',
        'week_number',
        'total_employees',
        'total_gross_pay',
        'total_deductions',
        'total_net_pay',
        'processed_by',
        'processed_at',
        'approved_by',
        'approved_at',
        'metadata'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'pay_date' => 'date',
        'processed_at' => 'datetime',
        'approved_at' => 'datetime',
        'total_gross_pay' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net_pay' => 'decimal:2',
        'metadata' => 'array'
    ];

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByMonth($query, int $month)
    {
        return $query->where('month', $month);
    }

    public static function getTypes(): array
    {
        return [
            'weekly' => 'Weekly',
            'bi_weekly' => 'Bi-Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'annual' => 'Annual'
        ];
    }

    public static function getStatuses(): array
    {
        return [
            'draft' => 'Draft',
            'pending' => 'Pending',
            'processing' => 'Processing',
            'processed' => 'Processed',
            'approved' => 'Approved',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled'
        ];
    }
}

class Payslip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'employee_number',
        'employee_name',
        'department',
        'position',
        'basic_salary',
        'overtime_hours',
        'overtime_rate',
        'overtime_pay',
        'allowances',
        'bonuses',
        'commissions',
        'gross_pay',
        'tax_deductions',
        'insurance_deductions',
        'retirement_deductions',
        'other_deductions',
        'total_deductions',
        'net_pay',
        'bank_account',
        'payment_method',
        'payment_reference',
        'status',
        'notes',
        'generated_at',
        'paid_at',
        'metadata'
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'allowances' => 'array',
        'bonuses' => 'decimal:2',
        'commissions' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'tax_deductions' => 'decimal:2',
        'insurance_deductions' => 'decimal:2',
        'retirement_deductions' => 'decimal:2',
        'other_deductions' => 'array',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'generated_at' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array'
    ];

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['generated', 'approved', 'pending']);
    }

    public static function getPaymentMethods(): array
    {
        return [
            'bank_transfer' => 'Bank Transfer',
            'cash' => 'Cash',
            'cheque' => 'Cheque',
            'digital_wallet' => 'Digital Wallet'
        ];
    }

    public static function getStatuses(): array
    {
        return [
            'draft' => 'Draft',
            'generated' => 'Generated',
            'approved' => 'Approved',
            'pending' => 'Pending Payment',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled',
            'on_hold' => 'On Hold'
        ];
    }
}