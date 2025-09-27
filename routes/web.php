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
})->middleware(['auth', 'verified'])->name('dashboard');

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

// HR Management System Route
Route::get('/hr', function () {
    return redirect('/hr-app/');
})->middleware(['auth', 'verified'])->name('hr');

// Serve HR Application (ExtJS)
Route::get('/hr-app/{path?}', function () {
    return view('hr-app');
})->where('path', '.*')->middleware(['auth', 'verified'])->name('hr.app');
