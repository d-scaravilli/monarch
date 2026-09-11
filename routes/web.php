<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CourseEditionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberAreaController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/area', [MemberAreaController::class, 'index'])->name('member.area');

    Route::get('/editions', [CourseEditionController::class, 'index'])->name('editions.index');
    Route::get('/editions/{courseEdition}', [CourseEditionController::class, 'show'])->name('editions.show');
    Route::get('/editions/{courseEdition}/lessons/{lesson}/attendance', [AttendanceController::class, 'edit'])->name('editions.attendance.edit');
    Route::post('/editions/{courseEdition}/lessons/{lesson}/attendance', [AttendanceController::class, 'update'])->name('editions.attendance.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
