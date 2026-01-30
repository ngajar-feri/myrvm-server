<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $modules = ['Auth', 'Device', 'Machine', 'System', 'Transaction'];
        $actions = ['Login', 'Logout', 'Create', 'Update', 'Delete', 'Error', 'Warning'];

        $descriptions = [
            'Auth' => [
                'Login' => 'User logged in successfully',
                'Logout' => 'User logged out',
                'Error' => 'Failed login attempt - invalid credentials',
                'Warning' => 'Multiple failed login attempts detected',
            ],
            'Device' => [
                'Create' => 'New edge device registered',
                'Update' => 'Device firmware updated',
                'Delete' => 'Device deregistered',
                'Error' => 'Device connection lost',
                'Warning' => 'Device temperature above threshold',
            ],
            'Machine' => [
                'Create' => 'New RVM machine added to system',
                'Update' => 'Machine status changed',
                'Delete' => 'RVM machine removed from system',
                'Error' => 'Machine sensor malfunction detected',
                'Warning' => 'Machine capacity above 80%',
            ],
            'System' => [
                'Update' => 'System configuration updated',
                'Error' => 'Database connection error',
                'Warning' => 'High memory usage detected',
            ],
            'Transaction' => [
                'Create' => 'New transaction processed',
                'Update' => 'Transaction status updated',
                'Error' => 'Transaction failed - system error',
                'Warning' => 'Unusual transaction pattern detected',
            ],
        ];

        $ips = ['127.0.0.1', '192.168.1.100', '192.168.1.101', '10.0.0.50', '172.16.0.25'];

        // Generate 50 sample log entries
        for ($i = 0; $i < 50; $i++) {
            $module = $modules[array_rand($modules)];
            $validActions = array_keys($descriptions[$module]);
            $action = $validActions[array_rand($validActions)];
            $description = $descriptions[$module][$action];

            ActivityLog::create([
                'user_id' => $users->isNotEmpty() ? $users->random()->id : null,
                'module' => $module,
                'action' => $action,
                'description' => $description,
                'ip_address' => $ips[array_rand($ips)],
                'created_at' => now()->subHours(rand(0, 168)), // Random time in last 7 days
            ]);
        }

        $this->command->info('Generated 50 activity logs');
    }
}
