<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\WorkflowInstanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'))->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/login/role', [AuthController::class, 'detectRole'])->middleware('throttle:10,1')->name('login.role');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::apiResource('workflows', WorkflowController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::get('workflows/{workflow}/instances', [WorkflowInstanceController::class, 'index'])->name('workflow-instances.index');
    Route::post('workflows/{workflow}/instances', [WorkflowInstanceController::class, 'store'])->name('workflow-instances.store');
    Route::get('workflow-instances/{workflowInstance}', [WorkflowInstanceController::class, 'show'])->name('workflow-instances.show');
    Route::post('workflow-instances/{workflowInstance}/cancel', [WorkflowInstanceController::class, 'cancel'])->name('workflow-instances.cancel');
    Route::post('workflow-assignments/{assignment}/approve', [WorkflowInstanceController::class, 'approve'])->name('workflow-assignments.approve');
    Route::post('workflow-assignments/{assignment}/reject', [WorkflowInstanceController::class, 'reject'])->name('workflow-assignments.reject');

    Route::get('workflows/{workflow}/forms', [FormController::class, 'index'])->name('forms.index');
    Route::post('workflows/{workflow}/forms', [FormController::class, 'store'])->name('forms.store');
    Route::get('forms/{form}', [FormController::class, 'show'])->name('forms.show');
    Route::put('forms/{form}', [FormController::class, 'update'])->name('forms.update');
    Route::delete('forms/{form}', [FormController::class, 'destroy'])->name('forms.destroy');
    Route::post('forms/{form}/submissions', [FormController::class, 'submit'])->name('forms.submissions.store');
});
