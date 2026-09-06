<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password', 'email_verified_at' => now()],
        );
        $user->assignRole('Super Admin');

        $demoUsers = [
            ['name' => 'Avery Cole', 'email' => 'demo-admin@flowline.test', 'role' => 'Super Admin'],
            ['name' => 'Morgan Lee', 'email' => 'demo-department@flowline.test', 'role' => 'Department Admin'],
            ['name' => 'Casey Morgan', 'email' => 'demo-manager@flowline.test', 'role' => 'Manager'],
            ['name' => 'Jordan Reed', 'email' => 'demo-employee@flowline.test', 'role' => 'Employee'],
            ['name' => 'Riley Shah', 'email' => 'demo-auditor@flowline.test', 'role' => 'Auditor'],
        ];

        foreach ($demoUsers as $demoUser) {
            $account = User::updateOrCreate(
                ['email' => $demoUser['email']],
                ['name' => $demoUser['name'], 'password' => 'password'],
            );
            $account->syncRoles([$demoUser['role']]);
        }
    }
}
