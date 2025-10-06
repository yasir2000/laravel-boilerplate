<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\DocumentController;
use App\Http\Controllers\API\PayrollController;
use App\Http\Controllers\API\AdvancedFilterController;

// Reports API Routes
Route::middleware(['auth:sanctum'])->prefix('reports')->group(function () {
    Route::get('/employees', [ReportController::class, 'employeeReport']);
    Route::get('/attendance', [ReportController::class, 'attendanceReport']);
    Route::get('/performance', [ReportController::class, 'performanceReport']);
    Route::get('/departments', [ReportController::class, 'departmentReport']);
    Route::post('/custom', [ReportController::class, 'customReport']);
    Route::get('/dashboard-metrics', [ReportController::class, 'dashboardMetrics']);
});

// Document Management API Routes
Route::middleware(['auth:sanctum'])->prefix('documents')->group(function () {
    Route::get('/', [DocumentController::class, 'index']);
    Route::post('/', [DocumentController::class, 'store']);
    Route::get('/{document}', [DocumentController::class, 'show']);
    Route::put('/{document}', [DocumentController::class, 'update']);
    Route::delete('/{document}', [DocumentController::class, 'destroy']);
    Route::post('/{document}/verify', [DocumentController::class, 'verify']);
    Route::post('/{document}/replace', [DocumentController::class, 'replace']);
    Route::get('/{document}/download', [DocumentController::class, 'download']);
    Route::post('/bulk-verify', [DocumentController::class, 'bulkVerify']);
    Route::get('/statistics', [DocumentController::class, 'statistics']);
    Route::get('/expiring', [DocumentController::class, 'expiringDocuments']);
    Route::get('/unverified', [DocumentController::class, 'unverifiedDocuments']);
    Route::get('/types', [DocumentController::class, 'getDocumentTypes']);
    Route::get('/access-levels', [DocumentController::class, 'getAccessLevels']);
    Route::get('/statuses', [DocumentController::class, 'getStatuses']);
});

// Payroll API Routes
Route::middleware(['auth:sanctum'])->prefix('payroll')->group(function () {
    // Payroll Periods
    Route::get('/periods', [PayrollController::class, 'index']);
    Route::post('/periods', [PayrollController::class, 'store']);
    Route::get('/periods/{period}', [PayrollController::class, 'show']);
    Route::put('/periods/{period}', [PayrollController::class, 'update']);
    Route::delete('/periods/{period}', [PayrollController::class, 'destroy']);
    Route::post('/periods/{period}/generate-payslips', [PayrollController::class, 'generatePayslips']);
    Route::post('/periods/{period}/approve', [PayrollController::class, 'approve']);
    Route::post('/periods/{period}/process-payments', [PayrollController::class, 'processPayments']);
    Route::get('/periods/{period}/report', [PayrollController::class, 'generateReport']);
    
    // Payslips
    Route::get('/payslips', [PayrollController::class, 'payslips']);
    Route::get('/payslips/{payslip}', [PayrollController::class, 'showPayslip']);
    Route::put('/payslips/{payslip}', [PayrollController::class, 'updatePayslip']);
    Route::get('/payslips/{payslip}/download', [PayrollController::class, 'downloadPayslip']);
    
    // Statistics & Reports
    Route::get('/statistics', [PayrollController::class, 'statistics']);
    Route::get('/employee/{employee}/payslips', [PayrollController::class, 'employeePayslips']);
});

// Advanced Filtering API Routes
Route::middleware(['auth:sanctum'])->prefix('filters')->group(function () {
    Route::post('/employees', [AdvancedFilterController::class, 'filterEmployees']);
    Route::post('/attendance', [AdvancedFilterController::class, 'filterAttendance']);
    Route::post('/performance', [AdvancedFilterController::class, 'filterPerformance']);
    Route::post('/documents', [AdvancedFilterController::class, 'filterDocuments']);
    Route::post('/payslips', [AdvancedFilterController::class, 'filterPayslips']);
    Route::get('/options/{model}', [AdvancedFilterController::class, 'getFilterOptions']);
    Route::post('/export', [AdvancedFilterController::class, 'exportData']);
});