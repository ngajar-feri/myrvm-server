<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\RvmMachine;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Super Admin User
        User::updateOrCreate(
            ['email' => 'superadmin@myrvm.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'role' => 'super_admin',
                'points_balance' => 0,
            ]
        );

        // Create Admin User
        User::updateOrCreate(
            ['email' => 'admin@myrvm.com'],
            [
                'name' => 'Admin RVM',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'points_balance' => 0,
            ]
        );

        // Create Operator User
        User::updateOrCreate(
            ['email' => 'operator@myrvm.com'],
            [
                'name' => 'Operator RVM',
                'password' => Hash::make('password123'),
                'role' => 'operator',
                'points_balance' => 0,
            ]
        );

        // Create Regular Users
        User::updateOrCreate(
            ['email' => 'john@example.com'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'points_balance' => 500,
                'phone_number' => '+6281234567890',
            ]
        );

        User::updateOrCreate(
            ['email' => 'jane@example.com'],
            [
                'name' => 'Jane Smith',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'points_balance' => 1500,
                'phone_number' => '+6281234567891',
            ]
        );

        User::updateOrCreate(
            ['email' => 'bob@example.com'],
            [
                'name' => 'Bob Wilson',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'points_balance' => 250,
                'phone_number' => '+6281234567892',
            ]
        );

        // Create Tenant User
        $tenant = User::updateOrCreate(
            ['email' => 'tenant@starbucks.com'],
            [
                'name' => 'Starbucks Indonesia',
                'password' => Hash::make('password123'),
                'role' => 'tenan',
                'points_balance' => 0,
            ]
        );

        // Create Technician User
        User::updateOrCreate(
            ['email' => 'tech@myrvm.com'],
            [
                'name' => 'Tech Support',
                'password' => Hash::make('password123'),
                'role' => 'teknisi',
                'points_balance' => 0,
            ]
        );

        // Create RVM Machines
        RvmMachine::updateOrCreate(
            ['serial_number' => 'RVM-GI-001'],
            [
                'name' => 'RVM Mall Grand Indonesia',
                'location' => 'Mall Grand Indonesia, Jakarta Pusat',
                'status' => 'offline',
                'capacity_percentage' => 25,
                'last_ping' => now(),
            ]
        );

        RvmMachine::updateOrCreate(
            ['serial_number' => 'RVM-CP-002'],
            [
                'name' => 'RVM Central Park',
                'location' => 'Central Park Mall, Jakarta Barat',
                'status' => 'offline',
                'capacity_percentage' => 19,
                'last_ping' => now()->subMinutes(5),
            ]
        );

        RvmMachine::updateOrCreate(
            ['serial_number' => 'RVM-PS-003'],
            [
                'name' => 'RVM Plaza Senayan',
                'location' => 'Plaza Senayan, Jakarta Selatan',
                'status' => 'offline',
                'capacity_percentage' => 80,
                'last_ping' => now()->subHours(2),
            ]
        );

        RvmMachine::updateOrCreate(
            ['serial_number' => 'RVM-UI-004'],
            [
                'name' => 'RVM Universitas Indonesia',
                'location' => 'Kampus UI Depok',
                'status' => 'offline',
                'capacity_percentage' => 10,
                'last_ping' => now()->subMinute(),
            ]
        );

        RvmMachine::updateOrCreate(
            ['serial_number' => 'RVM-PIM-005'],
            [
                'name' => 'RVM Pondok Indah Mall',
                'location' => 'Pondok Indah Mall, Jakarta Selatan',
                'status' => 'offline',
                'capacity_percentage' => 0,
                'last_ping' => now()->subDay(),
            ]
        );

        // Call other seeders
        $this->call([
            VoucherSeeder::class,
        ]);

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('');
        $this->command->info('Demo Credentials:');
        $this->command->info('👤 Super Admin: superadmin@myrvm.com / password123');
        $this->command->info('👤 Admin: admin@myrvm.com / password123');
        $this->command->info('👤 Operator: operator@myrvm.com / password123');
        $this->command->info('👤 User: john@example.com / password123');
        $this->command->info('👤 Tenant: tenant@starbucks.com / password123');
        $this->command->info('👤 Technician: tech@myrvm.com / password123');
        $this->command->info('');
        $this->command->info('🏪 Created 5 RVM Machines');
    }
}
