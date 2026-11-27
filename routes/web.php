<?php

use App\Http\Controllers\Admin\AdministrationController;
use App\Http\Controllers\Admin\ReferenceDataController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\StudentLoginCodeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LibraryHomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentDepositAuthorizationController;
use App\Http\Controllers\ThesisAnalysisReportController;
use App\Http\Controllers\ThesisArchiveImportController;
use App\Http\Controllers\ThesisPreviewController;
use App\Http\Controllers\ThesisReviewController;
use App\Http\Controllers\ThesisSubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', LibraryHomeController::class)->name('home');
Route::get('/thesis-versions/{version}/preview', ThesisPreviewController::class)
    ->name('thesis-versions.preview');

Route::middleware('guest')->group(function () {
    Route::get('/login', LibraryHomeController::class)->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
    Route::post('/login/student-code', [StudentLoginCodeController::class, 'send'])
        ->middleware('throttle:5,1')
        ->name('login.student-code.send');
    Route::post('/login/student-code/verify', [StudentLoginCodeController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('login.student-code.verify');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.update');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');
    Route::post('/payments/theses/{thesis}', [PaymentController::class, 'start'])
        ->name('payments.start');
    Route::post('/payments/{payment}/confirm', [PaymentController::class, 'confirm'])
        ->name('payments.confirm');
    Route::post('/deposits', [ThesisSubmissionController::class, 'store'])->name('deposits.store');
    Route::post('/responsable/archives', [ThesisArchiveImportController::class, 'store'])
        ->middleware('permission:archives.import')
        ->name('responsable.archives.store');
    Route::post('/responsable/deposit-authorizations', [StudentDepositAuthorizationController::class, 'store'])
        ->name('responsable.deposit-authorizations.store');
    Route::post('/responsable/students/{student}/deposit-authorization', [StudentDepositAuthorizationController::class, 'destroy'])
        ->name('responsable.students.deposit-authorization.destroy');
    Route::get('/thesis-versions/{version}/viewer', [ThesisSubmissionController::class, 'viewer'])
        ->name('thesis-versions.viewer');
    Route::get('/thesis-versions/{version}/document', [ThesisSubmissionController::class, 'document'])
        ->name('thesis-versions.document');
    Route::get('/responsable/thesis-versions/{version}/analysis-report', ThesisAnalysisReportController::class)
        ->name('responsable.thesis-analysis-reports.show');
    Route::post('/responsable/theses/{thesis}/analysis', [ThesisReviewController::class, 'updateAnalysis'])
        ->name('responsable.theses.analysis');
    Route::post('/responsable/theses/{thesis}/physical-receipt', [ThesisReviewController::class, 'confirmPhysicalReceipt'])
        ->name('responsable.theses.physical-receipt');
    Route::post('/responsable/theses/{thesis}/decision', [ThesisReviewController::class, 'decide'])
        ->name('responsable.theses.decision');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdministrationController::class, 'index'])
            ->middleware('permission:users.manage')
            ->name('index');
        Route::get('/users', [AdministrationController::class, 'users'])
            ->middleware('permission:users.manage')
            ->name('users.index');
        Route::get('/roles', [AdministrationController::class, 'roles'])
            ->middleware('permission:roles.manage')
            ->name('roles.index');
        Route::post('/deposit-authorizations', [StudentDepositAuthorizationController::class, 'store'])
            ->middleware('permission:deposit_authorizations.manage')
            ->name('deposit-authorizations.store');
        Route::post('/students/{student}/deposit-authorization', [StudentDepositAuthorizationController::class, 'destroy'])
            ->middleware('permission:deposit_authorizations.manage')
            ->name('students.deposit-authorization.destroy');
        Route::patch('/users/{user}', [AdministrationController::class, 'updateAccount'])
            ->middleware('permission:users.manage')
            ->name('users.update');
        Route::patch('/users/{user}/status', [AdministrationController::class, 'updateStatus'])
            ->middleware('permission:users.manage')
            ->name('users.status');
        Route::patch('/users/{user}/student-profile', [AdministrationController::class, 'updateStudentProfile'])
            ->middleware('permission:users.manage')
            ->name('users.student-profile');
        Route::put('/users/{user}/role', [AdministrationController::class, 'assignRole'])
            ->middleware('permission:roles.manage')
            ->name('users.role');
        Route::put('/roles/{role}/permissions', [AdministrationController::class, 'updateRolePermissions'])
            ->middleware('permission:roles.manage')
            ->name('roles.permissions');
        Route::get('/references', [ReferenceDataController::class, 'index'])
            ->middleware('permission:references.manage')
            ->name('references.index');
        Route::post('/references/{type}', [ReferenceDataController::class, 'store'])
            ->middleware('permission:references.manage')
            ->name('references.store');
        Route::put('/references/{type}/{id}', [ReferenceDataController::class, 'update'])
            ->middleware('permission:references.manage')
            ->name('references.update');
        Route::patch('/references/{type}/{id}', [ReferenceDataController::class, 'updateStatus'])
            ->middleware('permission:references.manage')
            ->name('references.status');
        Route::get('/settings', [AdministrationController::class, 'settings'])
            ->middleware('permission:platform_settings.manage')
            ->name('settings.index');
        Route::patch('/settings/{setting}', [AdministrationController::class, 'updateSetting'])
            ->middleware('permission:platform_settings.manage')
            ->name('settings.update');
        Route::get('/activity-logs', [AdministrationController::class, 'activityLogs'])
            ->middleware('permission:activity_logs.read')
            ->name('activity-logs.index');
    });
});
