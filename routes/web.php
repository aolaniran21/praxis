<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Department\CourseManagementController;
use App\Http\Controllers\Department\ResultReviewController;
use App\Http\Controllers\Lecturer\CourseController as LecturerCourseController;
use App\Http\Controllers\Lecturer\ResultEntryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\Student\CourseRegistrationController;
use App\Http\Controllers\Student\ResultStatementController;
use App\Http\Controllers\SuperAdmin\AcademicSetupController;
use App\Http\Controllers\SuperAdmin\UserController as ManagedUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'active'])->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'active', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/registrations', [CourseRegistrationController::class, 'index'])->name('registrations.index');
    Route::post('/registrations', [CourseRegistrationController::class, 'store'])->name('registrations.store');
    Route::delete('/registrations/{registration}', [CourseRegistrationController::class, 'drop'])
        ->name('registrations.drop');
    Route::get('/results', ResultStatementController::class)->name('results.index');
});

Route::middleware(['auth', 'active', 'role:lecturer'])->prefix('lecturer')->name('lecturer.')->group(function () {
    Route::get('/courses', [LecturerCourseController::class, 'index'])->name('courses.index');
    Route::get('/assignments/{courseAssignment}', [LecturerCourseController::class, 'show'])
        ->name('assignments.show');
    Route::put('/registrations/{registration}/draft', [ResultEntryController::class, 'store'])
        ->name('results.draft');
    Route::post('/results/{result}/submit', [ResultEntryController::class, 'submit'])
        ->name('results.submit');
});

Route::middleware(['auth', 'active', 'role:department-admin'])->prefix('department')->name('department.')->group(function () {
    Route::get('/courses', [CourseManagementController::class, 'index'])->name('courses.index');
    Route::post('/courses', [CourseManagementController::class, 'store'])->name('courses.store');
    Route::patch('/courses/{course}', [CourseManagementController::class, 'update'])->name('courses.update');
    Route::post('/assignments', [CourseManagementController::class, 'storeAssignment'])->name('assignments.store');
    Route::patch('/assignments/{assignment}', [CourseManagementController::class, 'updateAssignment'])
        ->name('assignments.update');
    Route::get('/results', [ResultReviewController::class, 'index'])->name('results.index');
    Route::post('/results/{result}/approve', [ResultReviewController::class, 'approve'])
        ->name('results.approve');
    Route::post('/results/{result}/reject', [ResultReviewController::class, 'reject'])
        ->name('results.reject');
    Route::post('/results/{result}/publish', [ResultReviewController::class, 'publish'])
        ->name('results.publish');
    Route::post('/results/{result}/correction', [ResultReviewController::class, 'requestCorrection'])
        ->name('results.correction');
});

Route::middleware(['auth', 'active', 'role:super-admin|department-admin'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportsController::class, 'index'])->name('index');
    Route::get('/export', [ReportsController::class, 'export'])->name('export');
});

Route::middleware(['auth', 'active', 'role:super-admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/academic', [AcademicSetupController::class, 'index'])->name('academic.index');
    Route::patch('/academic/{entity}/{record}', [AcademicSetupController::class, 'update'])
        ->whereNumber('record')
        ->name('academic.update');
    Route::post('/academic/faculties', [AcademicSetupController::class, 'storeFaculty'])->name('academic.faculties.store');
    Route::post('/academic/departments', [AcademicSetupController::class, 'storeDepartment'])->name('academic.departments.store');
    Route::post('/academic/programs', [AcademicSetupController::class, 'storeProgram'])->name('academic.programs.store');
    Route::post('/academic/sessions', [AcademicSetupController::class, 'storeSession'])->name('academic.sessions.store');
    Route::post('/academic/semesters', [AcademicSetupController::class, 'storeSemester'])->name('academic.semesters.store');
    Route::post('/academic/courses', [AcademicSetupController::class, 'storeCourse'])->name('academic.courses.store');
    Route::post('/academic/assignments', [AcademicSetupController::class, 'storeAssignment'])->name('academic.assignments.store');
    Route::get('/users', [ManagedUserController::class, 'index'])->name('users.index');
    Route::post('/users', [ManagedUserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}', [ManagedUserController::class, 'update'])->name('users.update');
});

require __DIR__.'/auth.php';
