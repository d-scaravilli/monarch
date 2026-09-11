<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PermissionController as AdminPermissionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MemberAreaController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ModuleSettingsController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PalestraDashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/modules/{module}', [DashboardController::class, 'enter'])->name('modules.enter');
    Route::get('/modules/{module}/settings', [ModuleSettingsController::class, 'edit'])->name('modules.settings.edit');
    Route::put('/modules/{module}/settings', [ModuleSettingsController::class, 'update'])->name('modules.settings.update');
    Route::post('/modules/{module}/settings/access', [ModuleSettingsController::class, 'grantAccess'])->name('modules.settings.access.store');
    Route::delete('/modules/{module}/settings/access/{user}', [ModuleSettingsController::class, 'revokeAccess'])->name('modules.settings.access.destroy');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings/account', [ProfileController::class, 'update'])->name('settings.account.update');
    Route::delete('/settings/account', [ProfileController::class, 'destroy'])->name('settings.account.destroy');
    Route::post('/settings/avatar', [SettingsController::class, 'updateAvatar'])->name('settings.avatar.update');
    Route::patch('/settings/theme', [SettingsController::class, 'updateTheme'])->name('settings.theme');

    Route::middleware('module:palestra')->group(function () {
        Route::get('/palestra/dashboard', [PalestraDashboardController::class, 'index'])->name('palestra.dashboard');
        Route::get('/calendar', [CalendarController::class, 'index'])->name('palestra.calendar');
        Route::get('/team', [MemberController::class, 'team'])->name('members.team');

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
        Route::patch('/courses/{course}/lessons/{lesson}/description', [AttendanceController::class, 'updateDescription'])->name('courses.lessons.description.update');
        Route::post('/courses/{course}/lessons/{lesson}/notes', [NoteController::class, 'store'])->name('courses.lessons.notes.store');

        Route::post('/enrollments/{enrollment}/payments', [PaymentController::class, 'store'])->name('enrollments.payments.store');
        Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

        Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
        Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::put('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
    });

    Route::prefix('admin')->name('admin.')->middleware('module:amministrazione')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/disable', [AdminUserController::class, 'disable'])->name('users.disable');
        Route::post('/users/{user}/enable', [AdminUserController::class, 'enable'])->name('users.enable');
        Route::put('/users/{user}/password', [AdminUserController::class, 'setPassword'])->name('users.password');

        Route::get('/permissions', [AdminPermissionController::class, 'index'])->name('permissions.index');
        Route::post('/permissions', [AdminPermissionController::class, 'update'])->name('permissions.update');
    });
});

require __DIR__.'/auth.php';
