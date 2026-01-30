<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if ($user->isDirty('role') || $user->isDirty('password')) {
            $actor = Auth::user() ? Auth::user()->email : 'System/Seeder';
            $changes = [];

            if ($user->isDirty('role')) {
                $changes[] = "Role changed from '{$user->getOriginal('role')}' to '{$user->role}'";
            }

            if ($user->isDirty('password')) {
                $changes[] = "Password changed";
            }

            Log::channel('daily')->info('SECURITY AUDIT:', [
                'timestamp' => now()->timezone('Asia/Jakarta')->toDateTimeString(),
                'actor' => $actor,
                'target_user' => $user->email,
                'changes' => implode(', ', $changes),
                'reason' => 'User Update Event' // In real app, might come from request context
            ]);
        }
    }
}
