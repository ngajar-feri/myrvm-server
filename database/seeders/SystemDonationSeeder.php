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
            $this->command->info("✅ System Donation user already exists");
            $this->command->info("   ID: {$existing->id}");
            $this->command->info("   Email: {$existing->email}");
            
            // Auto-fix ID if not -1
            if ($existing->id != -1) {
                $this->command->warn("⚠️  Fixing ID from {$existing->id} to -1...");
                $existing->id = -1;
                $existing->save();
                $this->command->info("✅ ID updated to -1");
            }
            return;
        }
        
        // Create System Donation user with forced ID -1
        $user = new User();
        $user->id = -1; // FORCE ID
        $user->name = 'System Donation';
        $user->email = 'system.donation@myrvm.local';
        $user->password = Hash::make('SystemDonation2026!');
        $user->role = 'system';
        $user->points_balance = 0;
        $user->email_verified_at = now();
        $user->save();
        
        $this->command->info("✅ System Donation user created successfully");
        $this->command->info("   ID: {$user->id}");
        $this->command->info("   Email: {$user->email}");
        $this->command->warn("⚠️  IMPORTANT: Edge devices must receive this ID (-1) via handshake");
    }
}
