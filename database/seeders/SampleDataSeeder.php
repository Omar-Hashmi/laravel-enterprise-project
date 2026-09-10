<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskEscalatedNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Roles Exist
        $roles = ['Super Admin', 'Department Admin', 'Manager', 'Employee', 'Auditor'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // 2. Identify / Create Seed Users with password 'password'
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['Super Admin']);

        $manager = User::updateOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Operations Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $manager->syncRoles(['Manager']);

        $employee = User::updateOrCreate(
            ['email' => 'employee@example.com'],
            [
                'name' => 'Jordan Employee',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $employee->syncRoles(['Employee']);

        // Clean up any existing tasks for clean seeding
        Task::query()->delete();

        // 3. Populate Sample Tasks
        // --- For Employee:
        // 3 Pending / in-progress tasks
        Task::create([
            'title' => 'Complete Employee Security Awareness Training',
            'description' => 'Mandatory annual cybersecurity module and quiz for enterprise data handling.',
            'user_id' => $employee->id,
            'creator_id' => $manager->id,
            'status' => 'pending',
            'priority' => 'medium',
            'due_at' => now()->addDays(3),
            'sla_breached' => false,
        ]);

        Task::create([
            'title' => 'Prepare Quarterly Inventory Audit Form',
            'description' => 'Verify asset counts across storage facility and submit discrepancy notes.',
            'user_id' => $employee->id,
            'creator_id' => $manager->id,
            'status' => 'in_progress',
            'priority' => 'high',
            'due_at' => now()->addDay(),
            'sla_breached' => false,
        ]);

        Task::create([
            'title' => 'Review Vendor SLA Contract Documentation',
            'description' => 'Inspect vendor support level terms prior to quarterly review meeting.',
            'user_id' => $employee->id,
            'creator_id' => $manager->id,
            'status' => 'pending',
            'priority' => 'medium',
            'due_at' => now()->addDays(4),
            'sla_breached' => false,
        ]);

        // 2 Completed tasks
        Task::create([
            'title' => 'Submit Health & Safety Certification',
            'description' => 'Upload renewed workplace safety compliance certificate.',
            'user_id' => $employee->id,
            'creator_id' => $manager->id,
            'status' => 'completed',
            'priority' => 'low',
            'due_at' => now()->subDays(2),
            'completed_at' => now()->subDay(),
            'action_notes' => 'Certificate uploaded and verified by operations.',
            'sla_breached' => false,
        ]);

        Task::create([
            'title' => 'Annual Software License Requisition',
            'description' => 'Submit renewal license headcount for IDE and productivity tooling.',
            'user_id' => $employee->id,
            'creator_id' => $admin->id,
            'status' => 'completed',
            'priority' => 'medium',
            'due_at' => now()->subDays(4),
            'completed_at' => now()->subDays(3),
            'action_notes' => 'Headcount verified, purchase order processed.',
            'sla_breached' => false,
        ]);

        // 1 Overdue task for Employee
        Task::create([
            'title' => 'Submit Travel Expense Reimbursement Report',
            'description' => 'Reimbursement documentation for developer conference travel expenses.',
            'user_id' => $employee->id,
            'creator_id' => $manager->id,
            'status' => 'pending',
            'priority' => 'urgent',
            'due_at' => now()->subDays(2),
            'sla_breached' => true,
        ]);

        // --- For Manager:
        // 2 Pending approval review tasks
        Task::create([
            'title' => 'Approve Hardware Purchase Order #1042',
            'description' => 'Purchase requisition for 5 engineering workstations ($14,500).',
            'user_id' => $manager->id,
            'creator_id' => $admin->id,
            'status' => 'pending',
            'priority' => 'high',
            'due_at' => now()->addDays(2),
            'sla_breached' => false,
        ]);

        Task::create([
            'title' => 'Review Contractor Access Request - Cloud Infrastructure',
            'description' => 'Temporary VPN and staging AWS environment permissions for external auditor.',
            'user_id' => $manager->id,
            'creator_id' => $admin->id,
            'status' => 'pending',
            'priority' => 'urgent',
            'due_at' => now()->addDay(),
            'sla_breached' => false,
        ]);

        // 1 Delegated task (delegated by manager to employee)
        Task::create([
            'title' => 'Draft Department Budget Forecast Q4',
            'description' => 'Prepare preliminary departmental expenditure projection.',
            'user_id' => $employee->id,
            'creator_id' => $manager->id,
            'delegated_by_id' => $manager->id,
            'delegated_to_id' => $employee->id,
            'delegated_at' => now()->subHours(6),
            'status' => 'delegated',
            'priority' => 'high',
            'due_at' => now()->addDays(3),
            'action_notes' => 'Delegated to Jordan for initial numbers and spreadsheet modeling.',
            'sla_breached' => false,
        ]);

        // 2 Overdue escalation tasks for Manager
        Task::create([
            'title' => 'Executive Sign-off: Security Compliance Audit',
            'description' => 'Final authorization on ISO27001 vulnerability audit findings.',
            'user_id' => $manager->id,
            'creator_id' => $admin->id,
            'status' => 'pending',
            'priority' => 'urgent',
            'due_at' => now()->subDay(),
            'sla_breached' => true,
        ]);

        Task::create([
            'title' => 'Approve Client Service Level Agreement (SLA)',
            'description' => 'Enterprise customer custom SLA tier agreement sign-off.',
            'user_id' => $manager->id,
            'creator_id' => $admin->id,
            'status' => 'pending',
            'priority' => 'high',
            'due_at' => now()->subDays(3),
            'sla_breached' => true,
        ]);

        // --- For Admin:
        // 2 High/Urgent priority tasks
        Task::create([
            'title' => 'System-wide Database Index Optimization & Redis Migration',
            'description' => 'Review database execution plans and tune high-concurrency queue connection.',
            'user_id' => $admin->id,
            'creator_id' => $admin->id,
            'status' => 'pending',
            'priority' => 'urgent',
            'due_at' => now()->addDays(2),
            'sla_breached' => false,
        ]);

        Task::create([
            'title' => 'Quarterly SOC2 Compliance Verification',
            'description' => 'Perform access review and verify immutable audit trail logs.',
            'user_id' => $admin->id,
            'creator_id' => $admin->id,
            'status' => 'pending',
            'priority' => 'high',
            'due_at' => now()->addDays(5),
            'sla_breached' => false,
        ]);

        // 4. Populate Sample Notifications in `notifications` table
        \DB::table('notifications')->delete();

        $notifications = [
            // Admin Notifications
            [
                'id' => (string) Str::uuid(),
                'type' => TaskAssignedNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $admin->id,
                'data' => json_encode([
                    'type' => 'task_assigned',
                    'title' => 'New Urgent Task: System-wide Database Optimization',
                    'message' => 'You have been assigned task: System-wide Database Index Optimization & Redis Migration',
                    'priority' => 'urgent',
                ]),
                'read_at' => null,
                'created_at' => now()->subMinutes(15),
                'updated_at' => now()->subMinutes(15),
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => TaskEscalatedNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $admin->id,
                'data' => json_encode([
                    'type' => 'task_escalated',
                    'title' => 'SLA Breach Warning: Security Compliance Audit Overdue',
                    'message' => 'Task "Executive Sign-off: Security Compliance Audit" has exceeded SLA deadline.',
                    'priority' => 'urgent',
                ]),
                'read_at' => null,
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => TaskAssignedNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $admin->id,
                'data' => json_encode([
                    'type' => 'report_generated',
                    'title' => 'Quarterly Compliance Report Ready',
                    'message' => 'The executive compliance audit report is available for PDF export.',
                    'priority' => 'medium',
                ]),
                'read_at' => null,
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(5),
            ],

            // Manager Notifications
            [
                'id' => (string) Str::uuid(),
                'type' => TaskAssignedNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $manager->id,
                'data' => json_encode([
                    'type' => 'task_assigned',
                    'title' => 'Approval Required: Hardware Purchase Order #1042',
                    'message' => 'Please review and approve purchase requisition for engineering workstations.',
                    'priority' => 'high',
                ]),
                'read_at' => null,
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(30),
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => TaskEscalatedNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $manager->id,
                'data' => json_encode([
                    'type' => 'task_escalated',
                    'title' => 'Escalation Notice: Client SLA Approval Overdue',
                    'message' => 'Task "Approve Client Service Level Agreement (SLA)" breached SLA by 3 days.',
                    'priority' => 'urgent',
                ]),
                'read_at' => null,
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(3),
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => TaskAssignedNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $manager->id,
                'data' => json_encode([
                    'type' => 'task_assigned',
                    'title' => 'Review Contractor Access Request',
                    'message' => 'New access request submitted for cloud infrastructure.',
                    'priority' => 'urgent',
                ]),
                'read_at' => null,
                'created_at' => now()->subHours(6),
                'updated_at' => now()->subHours(6),
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => TaskAssignedNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $manager->id,
                'data' => json_encode([
                    'type' => 'task_delegated',
                    'title' => 'Task Delegation Confirmed',
                    'message' => 'Draft Department Budget Forecast Q4 has been delegated to Jordan Employee.',
                    'priority' => 'high',
                ]),
                'read_at' => now()->subDay(),
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],

            // Employee Notifications
            [
                'id' => (string) Str::uuid(),
                'type' => TaskAssignedNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $employee->id,
                'data' => json_encode([
                    'type' => 'task_assigned',
                    'title' => 'New Task Assigned: Security Awareness Training',
                    'message' => 'You have been assigned mandatory annual cybersecurity module.',
                    'priority' => 'medium',
                ]),
                'read_at' => null,
                'created_at' => now()->subMinutes(45),
                'updated_at' => now()->subMinutes(45),
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => TaskAssignedNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $employee->id,
                'data' => json_encode([
                    'type' => 'task_assigned',
                    'title' => 'New Task: Prepare Quarterly Inventory Audit',
                    'message' => 'Please verify asset counts across storage facilities.',
                    'priority' => 'high',
                ]),
                'read_at' => null,
                'created_at' => now()->subHours(4),
                'updated_at' => now()->subHours(4),
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => TaskEscalatedNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $employee->id,
                'data' => json_encode([
                    'type' => 'task_escalated',
                    'title' => 'Urgent: Travel Expense Reimbursement Overdue',
                    'message' => 'Your reimbursement report was due 2 days ago and has breached SLA.',
                    'priority' => 'urgent',
                ]),
                'read_at' => null,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => TaskAssignedNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $employee->id,
                'data' => json_encode([
                    'type' => 'workflow_approved',
                    'title' => 'Health & Safety Certification Verified',
                    'message' => 'Your workplace safety certificate was approved and filed.',
                    'priority' => 'low',
                ]),
                'read_at' => now()->subDay(),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
        ];

        \DB::table('notifications')->insert($notifications);

        // 5. Invalidate All Caches
        Cache::flush();
    }
}
