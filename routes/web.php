<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// SIMPLE TEST ROUTE - SHOULD WORK
Route::get('/simple-test', function () {
    return "SIMPLE TEST ROUTE WORKING!";
})->name('simple.test');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => app()->version(),
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'stats' => [
            'users' => \App\Models\User::count(),
            'notifications' => 0, // You can implement this later
            'workflows' => 0, // You can implement this later
        ],
    ]);
})->middleware(['auth'])->name('dashboard');

// Alternative HR dashboard route
Route::get('/hr-dashboard', function () {
    return Inertia::render('Dashboard', [
        'stats' => [
            'users' => \App\Models\User::count(),
            'notifications' => 0,
            'workflows' => 0,
        ],
    ]);
})->middleware(['auth'])->name('hr.dashboard.main');

// AI Agents Dashboard route
Route::get('/ai-agents', function () {
    return view('ai-agents.dashboard');
})->middleware(['auth'])->name('ai.agents.dashboard');

// Profile Routes
Route::get('/profile', function () {
    return Inertia::render('Profile/Show');
})->middleware(['auth', 'verified'])->name('profile');

// Authentication Routes
Route::get('/login', function () {
    return Inertia::render('Auth/Login', [
        'canResetPassword' => Route::has('password.request'),
        'status' => session('status'),
    ]);
})->name('login');

Route::get('/register', function () {
    return Inertia::render('Auth/Register');
})->name('register');

// Email Verification Routes
Route::get('/email/verify', function () {
    return Inertia::render('Auth/VerifyEmail', [
        'status' => session('status')
    ]);
})->middleware(['auth'])->name('verification.notice');

// HR Management System Routes - Vue.js Application
Route::middleware(['auth', 'verified'])->prefix('hr-vue')->name('hr.')->group(function () {
    
    // HR Dashboard
    Route::get('/', function () {
        return Inertia::render('HR/Dashboard', [
            'stats' => [
                'totalEmployees' => 50,
                'presentToday' => 47,
                'onLeave' => 3,
                'departments' => 5,
                'avgAttendance' => 94.2,
                'pendingRequests' => 8
            ]
        ]);
    })->name('dashboard');
    
    // Employee Management
    Route::get('/employees', function () {
        return Inertia::render('HR/Employees', [
            'employees' => [
                // Demo data will be loaded by the component
            ]
        ]);
    })->name('employees');
    
    // Department Management
    Route::get('/departments', function () {
        return Inertia::render('HR/Departments', [
            'departments' => [
                // Demo data will be loaded by the component
            ]
        ]);
    })->name('departments');
    
    // Attendance Management
    Route::get('/attendance', function () {
        return Inertia::render('HR/Attendance', [
            'attendance' => [
                // Demo data will be loaded by the component
            ]
        ]);
    })->name('attendance');
    
    // Team Management
    Route::get('/teams', function () {
        return Inertia::render('HR/Teams', [
            'teams' => [
                // Demo data will be loaded by the component
            ]
        ]);
    })->name('teams');
    
    // Reports
    Route::get('/reports', function () {
        return Inertia::render('HR/Reports', [
            'reports' => [
                // Demo data will be loaded by the component
            ]
        ]);
    })->name('reports');
    
    // Settings
    Route::get('/settings', function () {
        return Inertia::render('HR/Settings', [
            'settings' => [
                // Demo data will be loaded by the component
            ]
        ]);
    })->name('settings');
});

// Legacy HR routes for backward compatibility
Route::get('/hr-test', function () {
    return "LARAVEL ROUTE WORKING! Time: " . now() . " - HR Vue.js routes are now available at /hr/";
})->name('hr.test');
