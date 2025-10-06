<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\CacheService;
use App\Models\Company;

class ProcessHRReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $companyId;
    protected string $reportType;
    protected array $parameters;
    protected string $userId;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(string $companyId, string $reportType, array $parameters, string $userId)
    {
        $this->companyId = $companyId;
        $this->reportType = $reportType;
        $this->parameters = $parameters;
        $this->userId = $userId;
        
        $this->onQueue('reports');
    }

    /**
     * Execute the job.
     */
    public function handle(CacheService $cacheService): void
    {
        $company = Company::find($this->companyId);
        if (!$company) {
            throw new \Exception("Company not found: {$this->companyId}");
        }

        $reportData = $this->generateReport($company);
        
        // Cache the report
        $cacheKey = "hr_report_{$this->companyId}_{$this->reportType}_" . md5(serialize($this->parameters));
        $cacheService->cacheWithTags(
            ['hr_reports', "company_{$this->companyId}"],
            $cacheKey,
            $reportData,
            CacheService::CACHE_LONG
        );

        // Notify user that report is ready
        $user = \App\Models\User::find($this->userId);
        if ($user) {
            $user->notify(new \App\Notifications\ReportReadyNotification($this->reportType, $cacheKey));
        }
    }

    /**
     * Generate report data based on type
     */
    private function generateReport(Company $company): array
    {
        switch ($this->reportType) {
            case 'attendance':
                return $this->generateAttendanceReport($company);
            case 'performance':
                return $this->generatePerformanceReport($company);
            case 'payroll':
                return $this->generatePayrollReport($company);
            case 'employee_summary':
                return $this->generateEmployeeSummaryReport($company);
            default:
                throw new \Exception("Unknown report type: {$this->reportType}");
        }
    }

    private function generateAttendanceReport(Company $company): array
    {
        $startDate = $this->parameters['start_date'] ?? now()->startOfMonth();
        $endDate = $this->parameters['end_date'] ?? now()->endOfMonth();

        return [
            'type' => 'attendance',
            'period' => ['start' => $startDate, 'end' => $endDate],
            'summary' => [
                'total_employees' => $company->users()->count(),
                'total_working_days' => $this->calculateWorkingDays($startDate, $endDate),
                'average_attendance_rate' => $this->calculateAverageAttendance($company, $startDate, $endDate),
            ],
            'departments' => $this->getAttendanceByDepartment($company, $startDate, $endDate),
            'employees' => $this->getEmployeeAttendanceDetails($company, $startDate, $endDate),
            'generated_at' => now(),
        ];
    }

    private function generatePerformanceReport(Company $company): array
    {
        $year = $this->parameters['year'] ?? now()->year;

        return [
            'type' => 'performance',
            'year' => $year,
            'summary' => [
                'total_evaluations' => $company->hrEvaluations()->whereYear('created_at', $year)->count(),
                'average_score' => $company->hrEvaluations()->whereYear('created_at', $year)->avg('overall_score'),
                'improvement_rate' => $this->calculateImprovementRate($company, $year),
            ],
            'departments' => $this->getPerformanceByDepartment($company, $year),
            'top_performers' => $this->getTopPerformers($company, $year),
            'generated_at' => now(),
        ];
    }

    private function generatePayrollReport(Company $company): array
    {
        $month = $this->parameters['month'] ?? now()->month;
        $year = $this->parameters['year'] ?? now()->year;

        return [
            'type' => 'payroll',
            'period' => ['month' => $month, 'year' => $year],
            'summary' => [
                'total_employees' => $company->users()->count(),
                'total_gross_salary' => $this->calculateTotalGrossSalary($company, $month, $year),
                'total_deductions' => $this->calculateTotalDeductions($company, $month, $year),
                'total_net_salary' => $this->calculateTotalNetSalary($company, $month, $year),
            ],
            'departments' => $this->getPayrollByDepartment($company, $month, $year),
            'generated_at' => now(),
        ];
    }

    private function generateEmployeeSummaryReport(Company $company): array
    {
        return [
            'type' => 'employee_summary',
            'summary' => [
                'total_employees' => $company->users()->count(),
                'active_employees' => $company->users()->where('is_active', true)->count(),
                'departments_count' => $company->hrDepartments()->count(),
                'average_tenure' => $this->calculateAverageTenure($company),
            ],
            'demographics' => $this->getEmployeeDemographics($company),
            'departments' => $this->getEmployeesByDepartment($company),
            'recent_hires' => $this->getRecentHires($company),
            'upcoming_anniversaries' => $this->getUpcomingAnniversaries($company),
            'generated_at' => now(),
        ];
    }

    // Helper methods for calculations
    private function calculateWorkingDays(string $startDate, string $endDate): int
    {
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        
        $workingDays = 0;
        while ($start->lte($end)) {
            if (!$start->isWeekend()) {
                $workingDays++;
            }
            $start->addDay();
        }
        
        return $workingDays;
    }

    private function calculateAverageAttendance(Company $company, string $startDate, string $endDate): float
    {
        // This would calculate based on actual attendance records
        // For now, returning a mock value
        return 92.5;
    }

    private function getAttendanceByDepartment(Company $company, string $startDate, string $endDate): array
    {
        return $company->hrDepartments()->with('users')->get()->map(function ($department) {
            return [
                'department' => $department->name,
                'employee_count' => $department->users->count(),
                'attendance_rate' => rand(85, 98) . '%', // Mock data
            ];
        })->toArray();
    }

    private function getEmployeeAttendanceDetails(Company $company, string $startDate, string $endDate): array
    {
        return $company->users()->limit(50)->get()->map(function ($user) {
            return [
                'employee_id' => $user->id,
                'name' => $user->name,
                'department' => $user->hrDepartments->first()?->name ?? 'Unassigned',
                'attendance_rate' => rand(80, 100) . '%', // Mock data
                'days_present' => rand(18, 22),
                'days_absent' => rand(0, 4),
            ];
        })->toArray();
    }

    private function calculateImprovementRate(Company $company, int $year): float
    {
        // Mock calculation - would compare with previous year
        return 15.3;
    }

    private function getPerformanceByDepartment(Company $company, int $year): array
    {
        return $company->hrDepartments()->get()->map(function ($department) {
            return [
                'department' => $department->name,
                'average_score' => rand(65, 95) / 10, // Mock data: 6.5 to 9.5
                'evaluations_count' => rand(5, 20),
            ];
        })->toArray();
    }

    private function getTopPerformers(Company $company, int $year): array
    {
        return $company->users()->limit(10)->get()->map(function ($user) {
            return [
                'employee_id' => $user->id,
                'name' => $user->name,
                'department' => $user->hrDepartments->first()?->name ?? 'Unassigned',
                'score' => rand(85, 95) / 10, // Mock data: 8.5 to 9.5
            ];
        })->toArray();
    }

    private function calculateTotalGrossSalary(Company $company, int $month, int $year): float
    {
        // Mock calculation
        return $company->users()->count() * rand(3000, 8000);
    }

    private function calculateTotalDeductions(Company $company, int $month, int $year): float
    {
        return $this->calculateTotalGrossSalary($company, $month, $year) * 0.15; // 15% deductions
    }

    private function calculateTotalNetSalary(Company $company, int $month, int $year): float
    {
        return $this->calculateTotalGrossSalary($company, $month, $year) - 
               $this->calculateTotalDeductions($company, $month, $year);
    }

    private function getPayrollByDepartment(Company $company, int $month, int $year): array
    {
        return $company->hrDepartments()->get()->map(function ($department) {
            $employeeCount = $department->users->count();
            $grossSalary = $employeeCount * rand(3000, 8000);
            
            return [
                'department' => $department->name,
                'employee_count' => $employeeCount,
                'gross_salary' => $grossSalary,
                'deductions' => $grossSalary * 0.15,
                'net_salary' => $grossSalary * 0.85,
            ];
        })->toArray();
    }

    private function calculateAverageTenure(Company $company): float
    {
        // Mock calculation - would calculate based on hire dates
        return 2.5; // years
    }

    private function getEmployeeDemographics(Company $company): array
    {
        $totalEmployees = $company->users()->count();
        
        return [
            'age_groups' => [
                '20-30' => rand(20, 40),
                '31-40' => rand(30, 50),
                '41-50' => rand(15, 25),
                '51+' => rand(5, 15),
            ],
            'gender_distribution' => [
                'male' => rand(40, 60),
                'female' => rand(40, 60),
            ],
        ];
    }

    private function getEmployeesByDepartment(Company $company): array
    {
        return $company->hrDepartments()->withCount('users')->get()->map(function ($department) {
            return [
                'department' => $department->name,
                'employee_count' => $department->users_count,
                'percentage' => round(($department->users_count / max($department->users_count, 1)) * 100, 1),
            ];
        })->toArray();
    }

    private function getRecentHires(Company $company): array
    {
        return $company->users()
            ->where('created_at', '>=', now()->subMonths(3))
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'name' => $user->name,
                    'department' => $user->hrDepartments->first()?->name ?? 'Unassigned',
                    'hire_date' => $user->created_at->format('Y-m-d'),
                ];
            })->toArray();
    }

    private function getUpcomingAnniversaries(Company $company): array
    {
        // Mock data for upcoming anniversaries in the next 3 months
        return $company->users()
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'name' => $user->name,
                    'department' => $user->hrDepartments->first()?->name ?? 'Unassigned',
                    'anniversary_date' => now()->addDays(rand(1, 90))->format('Y-m-d'),
                    'years_of_service' => rand(1, 10),
                ];
            })->toArray();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('HR report generation failed: ' . $exception->getMessage(), [
            'company_id' => $this->companyId,
            'report_type' => $this->reportType,
            'parameters' => $this->parameters,
            'user_id' => $this->userId,
        ]);
    }
}