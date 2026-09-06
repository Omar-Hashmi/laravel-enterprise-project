<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define roles required for the enterprise platform
        $roles = [
            'Super Admin',
            'Department Admin',
            'Manager',
            'Employee',
            'Auditor',
        ];

        $permissions = ['workflow.view', 'workflow.create', 'workflow.update', 'workflow.delete', 'workflow.start', 'workflow.manage', 'workflow.approve', 'form.manage', 'form.submit', 'audit.view'];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        foreach ($roles as $role) {
            $roleModel = Role::firstOrCreate(['name' => $role]);
            $roleModel->syncPermissions(match ($role) {
                'Super Admin' => $permissions,
                'Department Admin' => ['workflow.view', 'workflow.create', 'workflow.update', 'workflow.start', 'workflow.manage', 'form.manage', 'form.submit', 'audit.view'],
                'Manager' => ['workflow.view', 'workflow.start', 'workflow.approve', 'form.submit'],
                'Employee' => ['workflow.view', 'workflow.start', 'form.submit'],
                'Auditor' => ['workflow.view', 'audit.view'],
            });
        }
    }
}
