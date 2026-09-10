<?php

use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportExportController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\WorkflowAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->name('api.v1.login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('api.v1.me');

        // Task Management Endpoints
        Route::get('/tasks', [TaskController::class, 'index'])->name('api.v1.tasks.index');
        Route::get('/tasks/summary', [TaskController::class, 'summary'])->name('api.v1.tasks.summary');
        Route::post('/tasks', [TaskController::class, 'store'])->name('api.v1.tasks.store');
        Route::get('/tasks/{task:uuid}', [TaskController::class, 'show'])->name('api.v1.tasks.show');
        Route::post('/tasks/{task:uuid}/complete', [TaskController::class, 'complete'])->name('api.v1.tasks.complete');
        Route::post('/tasks/{task:uuid}/delegate', [TaskController::class, 'delegate'])->name('api.v1.tasks.delegate');

        // Notification Center Endpoints
        Route::get('/notifications', [NotificationController::class, 'index'])->name('api.v1.notifications.index');
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('api.v1.notifications.unread-count');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('api.v1.notifications.read-all');
        Route::get('/notifications/preferences', [NotificationController::class, 'getPreferences'])->name('api.v1.notifications.preferences.get');
        Route::put('/notifications/preferences', [NotificationController::class, 'updatePreferences'])->name('api.v1.notifications.preferences.update');
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('api.v1.notifications.read');
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('api.v1.notifications.destroy');

        // Workflow Analytics Endpoints
        Route::get('/analytics/completion-times', [WorkflowAnalyticsController::class, 'completionTimes'])->name('api.v1.analytics.completion-times');
        Route::get('/analytics/bottlenecks', [WorkflowAnalyticsController::class, 'bottlenecks'])->name('api.v1.analytics.bottlenecks');
        Route::get('/analytics/department-performance', [WorkflowAnalyticsController::class, 'departmentPerformance'])->name('api.v1.analytics.department-performance');
        Route::get('/analytics/sla-compliance', [WorkflowAnalyticsController::class, 'slaCompliance'])->name('api.v1.analytics.sla-compliance');
        Route::get('/analytics/efficiency', [WorkflowAnalyticsController::class, 'efficiency'])->name('api.v1.analytics.efficiency');

        // Admin Dashboard Endpoints
        Route::get('/dashboard/kpis', [AdminDashboardController::class, 'kpis'])->name('api.v1.dashboard.kpis');
        Route::get('/dashboard/delayed-processes', [AdminDashboardController::class, 'delayedProcesses'])->name('api.v1.dashboard.delayed-processes');
        Route::get('/dashboard/user-activity', [AdminDashboardController::class, 'userActivity'])->name('api.v1.dashboard.user-activity');
        Route::get('/dashboard/approval-statistics', [AdminDashboardController::class, 'approvalStatistics'])->name('api.v1.dashboard.approval-statistics');

        // Reporting & Export Endpoints
        Route::get('/reports/tasks/excel', [ReportExportController::class, 'tasksExcel'])->name('api.v1.reports.tasks.excel');
        Route::get('/reports/analytics/excel', [ReportExportController::class, 'analyticsExcel'])->name('api.v1.reports.analytics.excel');
        Route::get('/reports/compliance/pdf', [ReportExportController::class, 'compliancePdf'])->name('api.v1.reports.compliance.pdf');
    });
});
