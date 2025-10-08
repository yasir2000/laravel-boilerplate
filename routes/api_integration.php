<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Integration\ErpIntegrationController;

/*
|--------------------------------------------------------------------------
| ERP Integration API Routes
|--------------------------------------------------------------------------
|
| API routes for ERP integration management
|
*/

Route::prefix('integration')->group(function () {
    
    // Dashboard and status routes
    Route::get('/dashboard', [ErpIntegrationController::class, 'dashboard'])
        ->name('integration.dashboard');
    
    Route::get('/status', [ErpIntegrationController::class, 'getStatus'])
        ->name('integration.status');
    
    Route::get('/health', [ErpIntegrationController::class, 'health'])
        ->name('integration.health');
    
    // Employee synchronization routes
    Route::prefix('employee')->group(function () {
        Route::post('/sync', [ErpIntegrationController::class, 'syncEmployees'])
            ->name('integration.employee.sync');
        
        Route::get('/status', [ErpIntegrationController::class, 'getEmployeeStatus'])
            ->name('integration.employee.status');
        
        Route::post('/process', [ErpIntegrationController::class, 'processEmployees'])
            ->name('integration.employee.process');
    });
    
    // Payroll synchronization routes
    Route::prefix('payroll')->group(function () {
        Route::post('/sync', [ErpIntegrationController::class, 'syncPayroll'])
            ->name('integration.payroll.sync');
        
        Route::get('/status', [ErpIntegrationController::class, 'getPayrollStatus'])
            ->name('integration.payroll.status');
        
        Route::post('/process', [ErpIntegrationController::class, 'processPayroll'])
            ->name('integration.payroll.process');
    });
    
    // Accounting synchronization routes
    Route::prefix('accounting')->group(function () {
        Route::post('/sync', [ErpIntegrationController::class, 'syncAccounting'])
            ->name('integration.accounting.sync');
        
        Route::get('/status', [ErpIntegrationController::class, 'getAccountingStatus'])
            ->name('integration.accounting.status');
        
        Route::post('/process', [ErpIntegrationController::class, 'processAccounting'])
            ->name('integration.accounting.process');
    });
    
    // Full synchronization routes
    Route::post('/sync-all', [ErpIntegrationController::class, 'syncAll'])
        ->name('integration.sync.all');
    
    // Cache management
    Route::delete('/cache', [ErpIntegrationController::class, 'clearCache'])
        ->name('integration.cache.clear');
});

// Webhook routes for receiving data from ERP systems
Route::prefix('webhook')->group(function () {
    Route::post('/frappe/employee-update', function (Illuminate\Http\Request $request) {
        // Handle employee updates from Frappe
        \Illuminate\Support\Facades\Log::info('Frappe employee webhook received', $request->all());
        return response()->json(['status' => 'received']);
    })->name('webhook.frappe.employee');
    
    Route::post('/frappe/payroll-update', function (Illuminate\Http\Request $request) {
        // Handle payroll updates from Frappe
        \Illuminate\Support\Facades\Log::info('Frappe payroll webhook received', $request->all());
        return response()->json(['status' => 'received']);
    })->name('webhook.frappe.payroll');
    
    Route::post('/frappe/accounting-update', function (Illuminate\Http\Request $request) {
        // Handle accounting updates from Frappe
        \Illuminate\Support\Facades\Log::info('Frappe accounting webhook received', $request->all());
        return response()->json(['status' => 'received']);
    })->name('webhook.frappe.accounting');
    
    Route::post('/generic-erp/update', function (Illuminate\Http\Request $request) {
        // Handle updates from generic ERP systems
        \Illuminate\Support\Facades\Log::info('Generic ERP webhook received', $request->all());
        return response()->json(['status' => 'received']);
    })->name('webhook.generic.update');
});