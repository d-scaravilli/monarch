<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MemberAreaController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/modules/{module}', [DashboardController::class, 'enter'])->name('modules.enter');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings/theme', [SettingsController::class, 'updateTheme'])->name('settings.theme');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('module:palestra')->group(function () {
        Route::get('/area', [MemberAreaController::class, 'index'])->name('member.area');

        Route::resource('members', MemberController::class);

        Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
        Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
        Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
        Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
        Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

        Route::post('/courses/{course}/enrollments', [EnrollmentController::class, 'store'])->name('courses.enrollments.store');
        Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');

        Route::get('/lessons', [LessonController::class, 'index'])->name('lessons.index');
        Route::get('/lessons/create', [LessonController::class, 'create'])->name('lessons.create');
        Route::post('/lessons', [LessonController::class, 'store'])->name('lessons.store');
        Route::get('/lessons/generate', [LessonController::class, 'generateForm'])->name('lessons.generate');
        Route::post('/lessons/generate', [LessonController::class, 'generateStore'])->name('lessons.generate.store');
        Route::delete('/lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');

        Route::get('/courses/{course}/lessons/{lesson}/attendance', [AttendanceController::class, 'edit'])->name('courses.lessons.attendance.edit');
        Route::patch('/courses/{course}/lessons/{lesson}/attendance', [AttendanceController::class, 'toggle'])->name('courses.lessons.attendance.toggle');
        Route::post('/courses/{course}/lessons/{lesson}/attendance/mark-all', [AttendanceController::class, 'markAllPresent'])->name('courses.lessons.attendance.mark-all');
    });
});

require __DIR__.'/auth.php';
