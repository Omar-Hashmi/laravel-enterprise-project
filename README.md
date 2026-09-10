# Enterprise Workflow Automation & BPM Platform (LV-327)

> High-throughput, event-driven Business Process Management (BPM) and Workflow Automation platform built on **Laravel 12 (PHP 8.4+)**, **Redis**, **Laravel Horizon**, and **Laravel Sanctum**.

---

## Table of Contents

- [1. Architecture Overview](#1-architecture-overview)
  - [Developer 1: Core Workflow & Dynamic Form Engine](#developer-1-core-workflow--dynamic-form-engine)
  - [Developer 2: Operations, Notifications, Analytics & Dashboard](#developer-2-operations-notifications-analytics--dashboard)
  - [Integrated Event-Driven Topology](#integrated-event-driven-topology)
- [2. REST API Reference](#2-rest-api-reference)
  - [Authentication](#authentication)
  - [Task Management](#task-management)
  - [Notification Center](#notification-center)
  - [Workflow Analytics](#workflow-analytics)
  - [Executive Admin Dashboard](#executive-admin-dashboard)
  - [Enterprise Reporting & Exports](#enterprise-reporting--exports)
- [3. Queue & Horizon Architecture](#3-queue--horizon-architecture)
  - [Dedicated Queue Channels](#dedicated-queue-channels)
  - [Worker Pool & Auto-Scaling Configuration](#worker-pool--auto-scaling-configuration)
- [4. Redis Caching Strategy & Invalidation Lifecycle](#4-redis-caching-strategy--invalidation-lifecycle)
  - [Cache Keys and TTL Policies](#cache-keys-and-ttl-policies)
  - [Reactive Event Invalidation](#reactive-event-invalidation)
- [5. Role-Based Access Control (RBAC)](#5-role-based-access-control-rbac)
- [6. Development & Operational Commands](#6-development--operational-commands)
  - [Setup & Seeders](#setup--seeders)
  - [Running the Test Suite](#running-the-test-suite)
  - [Horizon & Telescope Dashboards](#horizon--telescope-dashboards)
  - [Scheduled Tasks & SLA Monitor](#scheduled-tasks--sla-monitor)
  - [Code Quality & Formatting](#code-quality--formatting)

---

## 1. Architecture Overview

The platform is architected as an integrated collaboration between **Developer 1** (Core BPM Engine) and **Developer 2** (Operational Operations, Async Queues, Notification Fabric & Real-Time Analytics).

```
 ┌────────────────────────────────────────────────────────────────────────────┐
 │                               Web / API Clients                            │
 └───────────────────────┬────────────────────────────┬───────────────────────┘
                         │                            │
             Sanctum Auth / Web Session    Sanctum Bearer Token / API
                         │                            │
 ┌───────────────────────▼──────────────┐  ┌──────────▼───────────────────────┐
 │   Developer 1: BPM & Forms Core      │  │ Developer 2: Operations Engine   │
 │                                      │  │                                  │
 │ • Workflow Definitions & Steps       │  │ • Task Operations (UUID-driven)  │
 │ • Finite State Machine Engine        │  │ • Multi-Channel Notifications    │
 │ • Dynamic Form Builder & Schemas     │  │ • Workflow Analytics & Metrics   │
 │ • Conditional Transition Evaluator   │  │ • Executive KPI Dashboard        │
 │ • Immutable Audit Logging            │  │ • Excel & PDF Report Generators  │
 └───────────────────────┬──────────────┘  └──────────▲───────────────────────┘
                         │                            │
                         │ Events & Transitions       │ Reactive Handling
                         ▼                            │
 ┌────────────────────────────────────────────────────┴───────────────────────┐
 │               WorkflowEventListener / Event Subscriber                     │
 │  (WorkflowStepActivated, FormSubmitted, WorkflowStatusChanged, Assignment) │
 └───────────────────────┬────────────────────────────┬───────────────────────┘
                         │                            │
                         ▼                            ▼
 ┌──────────────────────────────┐              ┌──────────────────────────────┐
 │   Redis Caching & Horizon    │              │   Relational Database        │
 │  • Dedicated Queue Workers   │              │  • Users, Roles, Tasks       │
 │  • Predictive Cache & TTL    │              │  • Workflows & Instances     │
 └──────────────────────────────┘              └──────────────────────────────┘
```

### Developer 1: Core Workflow & Dynamic Form Engine
- **Workflow State Machine (`App\Services\WorkflowEngine`, `App\Services\WorkflowStateMachine`)**:
  - Deterministic state transitions (`pending` → `in_progress` → `approved` / `rejected` / `cancelled`).
  - Supports sequential, parallel, conditional, and automated action steps.
  - Role- and user-based approval assignments (`WorkflowAssignment`).
- **Dynamic Form Engine (`App\Services\FormSubmissionService`)**:
  - Configurable form schemas with typed fields (`text`, `number`, `date`, `file`, `multi_select`).
  - Dynamic validation rules and conditional field visibility.
  - Direct association with workflow definitions and instance contexts.
- **Audit Trail (`App\Services\AuditTrailService`)**:
  - Tamper-evident logging of every state transition, form submission, and assignment change.

### Developer 2: Operations, Notifications, Analytics & Dashboard
- **Task Management Module (`App\Models\Task`, `App\Http\Controllers\Api\TaskController`)**:
  - High-concurrency task tracking with UUID identifiers.
  - Role delegation, audit notes, due dates, and automated SLA breach checks (`tasks:check-sla`).
- **Multi-Channel Notification Center (`App\Notifications\TaskAssignedNotification`, `App\Notifications\TaskEscalatedNotification`)**:
  - Notification delivery via database, mail, and broadcast channels.
  - Per-user delivery channel preferences (`UserNotificationPreference`).
- **Workflow Analytics (`App\Services\WorkflowAnalyticsService`)**:
  - Aggregates cycle times (mean, median, min, max), department throughput, bottlenecks, and SLA compliance.
- **Executive Admin Dashboard (`App\Services\AdminDashboardService`)**:
  - Real-time KPIs (active tasks, overdue items, pending approvals, running workflows, 24h completion velocity).
- **Report Generation (`App\Http\Controllers\Api\ReportExportController`)**:
  - Streaming Excel spreadsheets for tasks and analytics; downloadable DomPDF compliance audit reports.

### Integrated Event-Driven Topology
The modules communicate asynchronously and synchronously via `App\Listeners\WorkflowEventListener`:
1. **Step Activation**: Triggering a step instantiates a `Task`, sets SLA deadlines, and dispatches `TaskAssignedEvent`.
2. **Form Submission**: Dynamic form submissions automatically spin up a review `Task` for managers.
3. **Approval / Completion**: When a task is completed via `/api/v1/tasks/{task:uuid}/complete` or approved via the workflow engine, assignments resolve, instance state machine progresses, and analytics/dashboard Redis caches are immediately invalidated.

---

## 2. REST API Reference

All operational endpoints reside under the `/api/v1` namespace. Secured routes require a Bearer token via Laravel Sanctum (`Authorization: Bearer <token>`).

### Authentication
| Method | Endpoint | Description | Auth Required | Request Body / Parameters |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/login` | Authenticate and issue API token | No | `{"email": "...", "password": "..."}` |
| `POST` | `/api/v1/logout` | Revoke current Sanctum token | Yes | None |
| `GET` | `/api/v1/me` | Fetch authenticated user profile & roles | Yes | None |

### Task Management
| Method | Endpoint | Description | Auth Required | Parameters / Body |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/tasks` | Paginated task list | Yes | `assigned_to_me=1`, `status=pending\|completed\|overdue`, `priority=low\|medium\|high\|urgent`, `per_page=15` |
| `GET` | `/api/v1/tasks/summary` | Aggregate task counts by state | Yes | None |
| `POST` | `/api/v1/tasks` | Manually dispatch a new task | Yes | `title`, `description`, `priority`, `due_at`, `user_id` |
| `GET` | `/api/v1/tasks/{task:uuid}` | Retrieve full task metadata | Yes | Route parameter `uuid` |
| `POST` | `/api/v1/tasks/{task:uuid}/complete` | Mark task complete & approve workflow | Yes | `{"action_notes": "Approval commentary"}` |
| `POST` | `/api/v1/tasks/{task:uuid}/delegate` | Delegate task to another user | Yes | `{"target_user_id": 4, "action_notes": "..."}` |

### Notification Center
| Method | Endpoint | Description | Auth Required | Parameters / Body |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/notifications` | Paginated user notifications | Yes | `unread=1`, `page=1` |
| `GET` | `/api/v1/notifications/unread-count` | Count unread notifications | Yes | None |
| `POST` | `/api/v1/notifications/{id}/read` | Mark single notification as read | Yes | Route parameter `id` |
| `POST` | `/api/v1/notifications/read-all` | Mark all notifications as read | Yes | None |
| `DELETE` | `/api/v1/notifications/{id}` | Permanently delete notification | Yes | Route parameter `id` |
| `GET` | `/api/v1/notifications/preferences` | Retrieve user channel preferences | Yes | None |
| `PUT` | `/api/v1/notifications/preferences` | Update delivery channels | Yes | `{"email_enabled": true, "sms_enabled": false, "in_app_enabled": true}` |

### Workflow Analytics
| Method | Endpoint | Description | Auth Required | Query Parameters |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/analytics/completion-times` | Task duration distribution (avg, median, min, max) | Yes | `from=YYYY-MM-DD`, `to=YYYY-MM-DD` |
| `GET` | `/api/v1/analytics/bottlenecks` | Detect users/steps with highest overdue rate | Yes | None |
| `GET` | `/api/v1/analytics/department-performance`| Throughput & SLA compliance grouped by department | Yes | None |
| `GET` | `/api/v1/analytics/sla-compliance` | System-wide SLA compliance rates & breach counts | Yes | None |
| `GET` | `/api/v1/analytics/efficiency` | 7-day rolling velocity & created vs completed ratio | Yes | None |

### Executive Admin Dashboard
| Method | Endpoint | Description | Auth Required | Query Parameters |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/dashboard/kpis` | Real-time counts of active/overdue tasks & velocity | Yes | None |
| `GET` | `/api/v1/dashboard/delayed-processes` | Top delayed tasks exceeding SLA thresholds | Yes | `limit=10` |
| `GET` | `/api/v1/dashboard/user-activity` | Chronological audit stream of task actions | Yes | `limit=15` |
| `GET` | `/api/v1/dashboard/approval-statistics` | Breakdown of approvals, rejections, escalations | Yes | None |

### Enterprise Reporting & Exports
| Method | Endpoint | Description | Auth Required | Output Format |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/reports/tasks/excel` | Export filtered tasks to Excel | Yes | `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` |
| `GET` | `/api/v1/reports/analytics/excel` | Export analytics summary to Excel | Yes | `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` |
| `GET` | `/api/v1/reports/compliance/pdf` | Generate SLA compliance audit report | Yes | `application/pdf` (inline stream) |

---

## 3. Queue & Horizon Architecture

The platform uses **Laravel Horizon** over **Redis** for distributed, resilient job execution.

### Dedicated Queue Channels
Jobs are routed to dedicated queue channels according to SLA priority and processing characteristics:

1. `notifications`: High priority, low latency. Handles outbound email, database notifications, and broadcasting.
2. `tasks`: Operational task creation, delegation, and state synchronization.
3. `analytics`: Heavy aggregation queries, scheduled SLA calculations, and report compilations.
4. `default`: General background tasks, housekeeping, and maintenance.

### Worker Pool & Auto-Scaling Configuration

Configured in `config/horizon.php`:
```php
'defaults' => [
    'supervisor-1' => [
        'connection' => 'redis',
        'queue' => ['default', 'notifications', 'analytics', 'tasks'],
        'balance' => 'auto',
        'autoScalingStrategy' => 'time',
        'minProcesses' => 1,
        'maxProcesses' => 5,
        'balanceMaxShift' => 1,
        'balanceCooldown' => 3,
        'tries' => 3,
        'timeout' => 60,
    ],
],
'environments' => [
    'production' => [
        'supervisor-1' => [
            'minProcesses' => 2,
            'maxProcesses' => 10,
            'balance' => 'auto',
        ],
    ],
],
```

---

## 4. Redis Caching Strategy & Invalidation Lifecycle

To maintain sub-millisecond response times across high-traffic analytics and dashboard endpoints, the platform implements a structured Redis caching layer.

### Cache Keys and TTL Policies

| Cache Key | Service | Default TTL | Description |
| :--- | :--- | :--- | :--- |
| `dashboard:kpis` | `AdminDashboardService` | 5 minutes | Executive counter stats (active, overdue, 24h throughput) |
| `dashboard:delayed_processes:{limit}` | `AdminDashboardService` | 5 minutes | List of tasks with breached SLAs |
| `dashboard:user_activity:{limit}` | `AdminDashboardService` | 2 minutes | Live activity feed of task actions |
| `dashboard:approval_stats` | `AdminDashboardService` | 5 minutes | Aggregated counts of approved, rejected, delegated items |
| `analytics:completion_times:{from}_{to}`| `WorkflowAnalyticsService`| 10 minutes| Distribution of task duration |
| `analytics:bottlenecks` | `WorkflowAnalyticsService`| 10 minutes| High-delay assignees & roles |
| `analytics:department_performance` | `WorkflowAnalyticsService`| 10 minutes| SLA compliance rate by organizational unit |
| `analytics:sla_compliance` | `WorkflowAnalyticsService`| 10 minutes| System-wide compliance percentages |
| `analytics:process_efficiency` | `WorkflowAnalyticsService`| 10 minutes| 7-day velocity and completion ratio |

### Reactive Event Invalidation

Caches are never left stale when operational states change. `WorkflowEventListener::clearCaches()` triggers targeted invalidation upon:
- New step activation (`WorkflowStepActivated`)
- Workflow instance transition (`WorkflowStatusChanged`)
- Form submission (`FormSubmitted`)
- Assignment decision (`WorkflowAssignment` updated)
- Task completion / delegation (`TaskController::complete`, `TaskController::delegate`)

```php
public function clearCaches(): void
{
    app(WorkflowAnalyticsService::class)->clearAnalyticsCache();
    app(AdminDashboardService::class)->clearDashboardCache();
}
```

---

## 5. Role-Based Access Control (RBAC)

The system leverages **Spatie Laravel Permission** to enforce granular role boundaries:

- **Super Admin**: Unrestricted administrative access across all workflows, forms, analytics, reports, and system settings.
- **Department Admin**: Can create and publish workflows, design dynamic forms, inspect department performance, and view audit trails.
- **Manager**: Authority to initiate processes, review form submissions, approve/reject workflow assignments, and reassign tasks.
- **Employee**: Can submit forms, initiate standard self-service workflows, and complete tasks assigned to them.
- **Auditor**: Read-only oversight of workflow instances, audit trails, SLA compliance, and regulatory PDF reports.

---

## 6. Development & Operational Commands

### Setup & Seeders

Run standard setup to prepare database migrations, default permissions, and demonstration accounts:

```powershell
# Run database migrations
php artisan migrate

# Seed roles, permissions, and test accounts
php artisan db:seed
```

Demo Accounts seeded by `DatabaseSeeder`:
- Super Admin: `demo-admin@flowline.test` (password: `password`)
- Department Admin: `demo-department@flowline.test` (password: `password`)
- Manager: `demo-manager@flowline.test` (password: `password`)
- Employee: `demo-employee@flowline.test` (password: `password`)
- Auditor: `demo-auditor@flowline.test` (password: `password`)

### Running the Test Suite

Execute the full suite of unit and feature tests:

```powershell
# Run all tests
php artisan test

# Run End-to-End lifecycle tests
php artisan test tests/Feature/EndToEndWorkflowIntegrationTest.php

# Run with compact output
php artisan test --compact
```

### Horizon & Telescope Dashboards

Access real-time telemetry during development:
- **Horizon**: `http://localhost:8000/horizon`
  - Start Horizon master supervisor: `php artisan horizon`
- **Telescope**: `http://localhost:8000/telescope`
  - Inspect queries, events, notifications, and scheduled commands in real time.

### Scheduled Tasks & SLA Monitor

The system includes automated SLA breach detection and workflow auto-action jobs in `routes/console.php`:

```powershell
# Manually run the SLA breach scanner
php artisan tasks:check-sla

# Run all scheduled jobs locally
php artisan schedule:run
```

### Code Quality & Formatting

This project adheres to Laravel Pint coding standards:

```powershell
vendor/bin/pint --format agent
```
