<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LibraryHomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StudentDepositAuthorizationController;
use App\Http\Controllers\ThesisAnalysisReportController;
use App\Http\Controllers\ThesisArchiveImportController;
use App\Http\Controllers\ThesisReviewController;
use App\Http\Controllers\ThesisSubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', LibraryHomeController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', LibraryHomeController::class)->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
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

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');
    Route::post('/payments/theses/{thesis}', [PaymentController::class, 'start'])
        ->name('payments.start');
    Route::post('/payments/{payment}/confirm', [PaymentController::class, 'confirm'])
        ->name('payments.confirm');
    Route::post('/deposits', [ThesisSubmissionController::class, 'store'])->name('deposits.store');
    Route::post('/responsable/archives', [ThesisArchiveImportController::class, 'store'])
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
});
