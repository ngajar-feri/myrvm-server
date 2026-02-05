<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SystemDonationSeeder
 * 
 * Creates the "System Donation" user account for offline transactions.
 * This user receives all points from donations made during offline mode.
 */
class SystemDonationSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Check if already exists
        $existing = User::where('name', 'System Donation')->first();
        
        if ($existing) {
            $this->command->info("System Donation user already exists (ID: {$existing->id})");
            return;
        }
        
        // Create System Donation user
        $user = User::create([
            'name' => 'System Donation',
            'email' => 'system.donation@myrvm.local',
            'password' => Hash::make('SystemDonation2026!'),
            'role' => 'system',
            'phone_number' => null,
            'points_balance' => 0,
            'email_verified_at' => now(),
        ]);
        
        $this->command->info("System Donation user created with ID: {$user->id}");
    }
}
