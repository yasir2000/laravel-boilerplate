<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    private const STANDARD_WORK_HOURS = 8;
    private const STANDARD_WORK_DAYS = 5;
    private const OVERTIME_MULTIPLIER = 1.5;

    public function createPayrollPeriod(array $data): PayrollPeriod
    {
        return PayrollPeriod::create([
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'pay_date' => $data['pay_date'],
            'type' => $data['type'],
            'year' => Carbon::parse($data['start_date'])->year,
            'month' => Carbon::parse($data['start_date'])->month,
            'week_number' => Carbon::parse($data['start_date'])->weekOfYear,
            'status' => 'draft'
        ]);
    }

    public function generatePayslips(PayrollPeriod $payrollPeriod, array $employeeIds = []): Collection
    {
        $query = Employee::with(['user', 'department', 'attendances'])
            ->where('status', 'active');

        if (!empty($employeeIds)) {
            $query->whereIn('id', $employeeIds);
        }

        $employees = $query->get();
        $payslips = collect();

        DB::transaction(function () use ($payrollPeriod, $employees, &$payslips) {
            foreach ($employees as $employee) {
                $payslip = $this->generateEmployeePayslip($payrollPeriod, $employee);
                $payslips->push($payslip);
            }

            // Update payroll period totals
            $this->updatePayrollPeriodTotals($payrollPeriod);
        });

        return $payslips;
    }

    private function generateEmployeePayslip(PayrollPeriod $payrollPeriod, Employee $employee): Payslip
    {
        $attendanceData = $this->calculateAttendanceData($employee, $payrollPeriod);
        $salaryData = $this->calculateSalaryData($employee, $attendanceData);
        $deductionsData = $this->calculateDeductions($employee, $salaryData);

        return Payslip::create([
            'payroll_period_id' => $payrollPeriod->id,
            'employee_id' => $employee->id,
            'employee_number' => $employee->employee_id,
            'employee_name' => $employee->user->name,
            'department' => $employee->department->name,
            'position' => $employee->position,
            'basic_salary' => $salaryData['basic_salary'],
            'overtime_hours' => $attendanceData['overtime_hours'],
            'overtime_rate' => $salaryData['overtime_rate'],
            'overtime_pay' => $salaryData['overtime_pay'],
            'allowances' => $salaryData['allowances'],
            'bonuses' => $salaryData['bonuses'],
            'commissions' => $salaryData['commissions'],
            'gross_pay' => $salaryData['gross_pay'],
            'tax_deductions' => $deductionsData['tax_deductions'],
            'insurance_deductions' => $deductionsData['insurance_deductions'],
            'retirement_deductions' => $deductionsData['retirement_deductions'],
            'other_deductions' => $deductionsData['other_deductions'],
            'total_deductions' => $deductionsData['total_deductions'],
            'net_pay' => $salaryData['gross_pay'] - $deductionsData['total_deductions'],
            'bank_account' => $employee->bank_account ?? '',
            'payment_method' => $employee->preferred_payment_method ?? 'bank_transfer',
            'status' => 'generated',
            'generated_at' => now(),
            'metadata' => [
                'calculation_details' => [
                    'attendance' => $attendanceData,
                    'salary' => $salaryData,
                    'deductions' => $deductionsData
                ],
                'generated_by' => auth()->id()
            ]
        ]);
    }

    private function calculateAttendanceData(Employee $employee, PayrollPeriod $payrollPeriod): array
    {
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$payrollPeriod->start_date, $payrollPeriod->end_date])
            ->get();

        $totalDays = $attendances->count();
        $presentDays = $attendances->where('status', 'present')->count();
        $absentDays = $attendances->where('status', 'absent')->count();
        $lateDays = $attendances->where('status', 'late')->count();
        $halfDays = $attendances->where('status', 'half_day')->count();

        // Calculate total hours worked
        $totalHours = $attendances->sum('hours_worked') ?? ($presentDays * self::STANDARD_WORK_HOURS);
        
        // Calculate expected hours for the period
        $workingDays = $this->getWorkingDaysInPeriod($payrollPeriod);
        $expectedHours = $workingDays * self::STANDARD_WORK_HOURS;

        // Calculate overtime
        $overtimeHours = max(0, $totalHours - $expectedHours);

        return [
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'half_days' => $halfDays,
            'total_hours' => $totalHours,
            'expected_hours' => $expectedHours,
            'overtime_hours' => $overtimeHours,
            'attendance_rate' => $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 0
        ];
    }

    private function calculateSalaryData(Employee $employee, array $attendanceData): array
    {
        $basicSalary = $employee->salary ?? 0;
        $hourlyRate = $basicSalary / (self::STANDARD_WORK_DAYS * self::STANDARD_WORK_HOURS * 4.33); // Monthly to hourly
        $overtimeRate = $hourlyRate * self::OVERTIME_MULTIPLIER;
        $overtimePay = $attendanceData['overtime_hours'] * $overtimeRate;

        // Calculate allowances
        $allowances = $this->calculateAllowances($employee);
        
        // Calculate bonuses and commissions
        $bonuses = $this->calculateBonuses($employee);
        $commissions = $this->calculateCommissions($employee);

        // Adjust for attendance
        $attendanceAdjustment = $this->calculateAttendanceAdjustment($employee, $attendanceData);
        $adjustedBasicSalary = $basicSalary * $attendanceAdjustment;

        $grossPay = $adjustedBasicSalary + $overtimePay + $allowances['total'] + $bonuses + $commissions;

        return [
            'basic_salary' => $adjustedBasicSalary,
            'hourly_rate' => $hourlyRate,
            'overtime_rate' => $overtimeRate,
            'overtime_pay' => $overtimePay,
            'allowances' => $allowances,
            'bonuses' => $bonuses,
            'commissions' => $commissions,
            'gross_pay' => $grossPay,
            'attendance_adjustment' => $attendanceAdjustment
        ];
    }

    private function calculateDeductions(Employee $employee, array $salaryData): array
    {
        $grossPay = $salaryData['gross_pay'];

        // Tax deductions (simplified progressive tax)
        $taxDeductions = $this->calculateTaxDeductions($grossPay);

        // Insurance deductions
        $insuranceDeductions = $this->calculateInsuranceDeductions($employee, $grossPay);

        // Retirement/pension deductions
        $retirementDeductions = $this->calculateRetirementDeductions($employee, $grossPay);

        // Other deductions
        $otherDeductions = $this->calculateOtherDeductions($employee);

        $totalDeductions = $taxDeductions + $insuranceDeductions + $retirementDeductions + $otherDeductions['total'];

        return [
            'tax_deductions' => $taxDeductions,
            'insurance_deductions' => $insuranceDeductions,
            'retirement_deductions' => $retirementDeductions,
            'other_deductions' => $otherDeductions,
            'total_deductions' => $totalDeductions
        ];
    }

    private function calculateAllowances(Employee $employee): array
    {
        $allowances = [
            'transportation' => 0,
            'housing' => 0,
            'meal' => 0,
            'communication' => 0,
            'special' => 0
        ];

        // Get allowances from employee record or department settings
        $employeeAllowances = $employee->allowances ?? [];
        
        foreach ($allowances as $type => $amount) {
            $allowances[$type] = $employeeAllowances[$type] ?? 0;
        }

        $allowances['total'] = array_sum($allowances);

        return $allowances;
    }

    private function calculateBonuses(Employee $employee): float
    {
        // Get performance-based bonuses, achievement bonuses, etc.
        return $employee->current_month_bonus ?? 0;
    }

    private function calculateCommissions(Employee $employee): float
    {
        // Calculate sales commissions or other variable pay
        return $employee->current_month_commission ?? 0;
    }

    private function calculateAttendanceAdjustment(Employee $employee, array $attendanceData): float
    {
        // Full salary for 100% attendance, pro-rated for less
        return min(1.0, $attendanceData['attendance_rate'] / 100);
    }

    private function calculateTaxDeductions(float $grossPay): float
    {
        // Simplified progressive tax calculation
        $taxBrackets = [
            ['min' => 0, 'max' => 1000, 'rate' => 0],
            ['min' => 1000, 'max' => 3000, 'rate' => 0.10],
            ['min' => 3000, 'max' => 5000, 'rate' => 0.15],
            ['min' => 5000, 'max' => 10000, 'rate' => 0.20],
            ['min' => 10000, 'max' => PHP_FLOAT_MAX, 'rate' => 0.25]
        ];

        $tax = 0;
        foreach ($taxBrackets as $bracket) {
            if ($grossPay > $bracket['min']) {
                $taxableInBracket = min($grossPay, $bracket['max']) - $bracket['min'];
                $tax += $taxableInBracket * $bracket['rate'];
            }
        }

        return round($tax, 2);
    }

    private function calculateInsuranceDeductions(Employee $employee, float $grossPay): float
    {
        // Health insurance, life insurance, etc.
        $rate = 0.05; // 5% of gross pay
        return round($grossPay * $rate, 2);
    }

    private function calculateRetirementDeductions(Employee $employee, float $grossPay): float
    {
        // Retirement/pension contribution
        $rate = 0.08; // 8% of gross pay
        return round($grossPay * $rate, 2);
    }

    private function calculateOtherDeductions(Employee $employee): array
    {
        $deductions = [
            'loan_repayment' => $employee->loan_deduction ?? 0,
            'advance_salary' => $employee->advance_deduction ?? 0,
            'uniform' => $employee->uniform_deduction ?? 0,
            'parking' => $employee->parking_deduction ?? 0,
            'other' => $employee->other_deductions ?? 0
        ];

        $deductions['total'] = array_sum($deductions);

        return $deductions;
    }

    private function getWorkingDaysInPeriod(PayrollPeriod $payrollPeriod): int
    {
        $start = Carbon::parse($payrollPeriod->start_date);
        $end = Carbon::parse($payrollPeriod->end_date);
        
        $workingDays = 0;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($date->isWeekday()) {
                $workingDays++;
            }
        }

        return $workingDays;
    }

    private function updatePayrollPeriodTotals(PayrollPeriod $payrollPeriod): void
    {
        $payslips = $payrollPeriod->payslips;

        $payrollPeriod->update([
            'total_employees' => $payslips->count(),
            'total_gross_pay' => $payslips->sum('gross_pay'),
            'total_deductions' => $payslips->sum('total_deductions'),
            'total_net_pay' => $payslips->sum('net_pay'),
            'status' => 'processed',
            'processed_by' => auth()->id(),
            'processed_at' => now()
        ]);
    }

    public function approvePayrollPeriod(PayrollPeriod $payrollPeriod): PayrollPeriod
    {
        $payrollPeriod->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now()
        ]);

        // Update all payslips status
        $payrollPeriod->payslips()->update(['status' => 'approved']);

        return $payrollPeriod;
    }

    public function processPayments(PayrollPeriod $payrollPeriod): array
    {
        $payslips = $payrollPeriod->payslips()->where('status', 'approved')->get();
        $results = [
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($payslips as $payslip) {
            try {
                $this->processPayment($payslip);
                $results['successful']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'employee' => $payslip->employee_name,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Update payroll period status if all payments successful
        if ($results['failed'] === 0) {
            $payrollPeriod->update(['status' => 'paid']);
        }

        return $results;
    }

    private function processPayment(Payslip $payslip): void
    {
        // Integration with payment gateway or banking system
        // This is a simplified implementation
        
        switch ($payslip->payment_method) {
            case 'bank_transfer':
                $this->processBankTransfer($payslip);
                break;
            case 'cash':
                $this->processCashPayment($payslip);
                break;
            case 'cheque':
                $this->processChequePayment($payslip);
                break;
            case 'digital_wallet':
                $this->processDigitalWalletPayment($payslip);
                break;
            default:
                throw new \InvalidArgumentException('Invalid payment method');
        }

        $payslip->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $this->generatePaymentReference($payslip)
        ]);
    }

    private function processBankTransfer(Payslip $payslip): void
    {
        // Integrate with banking API
        // For now, we'll just simulate the process
        if (empty($payslip->bank_account)) {
            throw new \Exception('Bank account information missing');
        }

        // Simulate API call
        sleep(1);
        
        // In real implementation, you would:
        // 1. Call banking API
        // 2. Handle authentication
        // 3. Process transfer
        // 4. Handle response and errors
    }

    private function processCashPayment(Payslip $payslip): void
    {
        // Mark as ready for cash pickup
        // No external API needed
    }

    private function processChequePayment(Payslip $payslip): void
    {
        // Generate cheque number and mark for printing
        // No external API needed
    }

    private function processDigitalWalletPayment(Payslip $payslip): void
    {
        // Integrate with digital wallet APIs (PayPal, etc.)
        // Similar to bank transfer but different endpoint
    }

    private function generatePaymentReference(Payslip $payslip): string
    {
        return 'PAY_' . $payslip->payroll_period_id . '_' . $payslip->employee_id . '_' . now()->format('YmdHis');
    }

    public function generatePayrollReport(PayrollPeriod $payrollPeriod): array
    {
        $payslips = $payrollPeriod->payslips()->with(['employee.user', 'employee.department'])->get();

        return [
            'period_info' => [
                'name' => $payrollPeriod->name,
                'start_date' => $payrollPeriod->start_date,
                'end_date' => $payrollPeriod->end_date,
                'pay_date' => $payrollPeriod->pay_date,
                'status' => $payrollPeriod->status
            ],
            'summary' => [
                'total_employees' => $payslips->count(),
                'total_gross_pay' => $payslips->sum('gross_pay'),
                'total_deductions' => $payslips->sum('total_deductions'),
                'total_net_pay' => $payslips->sum('net_pay'),
                'average_gross_pay' => $payslips->avg('gross_pay'),
                'average_net_pay' => $payslips->avg('net_pay')
            ],
            'by_department' => $payslips->groupBy('department')->map(function ($deptPayslips) {
                return [
                    'count' => $deptPayslips->count(),
                    'total_gross' => $deptPayslips->sum('gross_pay'),
                    'total_net' => $deptPayslips->sum('net_pay'),
                    'average_gross' => $deptPayslips->avg('gross_pay'),
                    'average_net' => $deptPayslips->avg('net_pay')
                ];
            }),
            'by_payment_method' => $payslips->groupBy('payment_method')->map->count(),
            'deduction_breakdown' => [
                'tax' => $payslips->sum('tax_deductions'),
                'insurance' => $payslips->sum('insurance_deductions'),
                'retirement' => $payslips->sum('retirement_deductions'),
                'other' => $payslips->sum(function ($payslip) {
                    return array_sum($payslip->other_deductions ?? []);
                })
            ],
            'payslips' => $payslips,
            'generated_at' => now()
        ];
    }

    public function getPayrollStatistics(): array
    {
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        return [
            'current_month' => [
                'periods' => PayrollPeriod::whereMonth('start_date', $currentMonth->month)->count(),
                'total_paid' => PayrollPeriod::whereMonth('start_date', $currentMonth->month)->sum('total_net_pay'),
                'employees_paid' => PayrollPeriod::whereMonth('start_date', $currentMonth->month)->sum('total_employees')
            ],
            'last_month' => [
                'periods' => PayrollPeriod::whereMonth('start_date', $lastMonth->month)->count(),
                'total_paid' => PayrollPeriod::whereMonth('start_date', $lastMonth->month)->sum('total_net_pay'),
                'employees_paid' => PayrollPeriod::whereMonth('start_date', $lastMonth->month)->sum('total_employees')
            ],
            'pending_periods' => PayrollPeriod::where('status', 'pending')->count(),
            'pending_payments' => Payslip::whereIn('status', ['approved', 'pending'])->count(),
            'payment_methods' => Payslip::groupBy('payment_method')
                ->selectRaw('payment_method, count(*) as count')
                ->pluck('count', 'payment_method')
                ->toArray()
        ];
    }
}