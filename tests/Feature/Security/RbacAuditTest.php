<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RbacAuditTest extends TestCase
{
    // Note: We use in-memory sqlite or similar if configured, 
    // otherwise we rely on the seeding or manual user creation if RefreshDatabase is active.
    // For this audit script, we'll mock the users.
    use RefreshDatabase;

    public function test_admin_can_access_admin_dashboard()
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin_test@myrvm.com']);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.admin');
    }

    public function test_operator_sees_operator_dashboard()
    {
        $operator = User::factory()->create(['role' => 'operator', 'email' => 'operator_test@myrvm.com']);

        $response = $this->actingAs($operator)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.operator');
        $response->assertSee('Status Operasional Mesin');
    }

    public function test_user_sees_user_dashboard()
    {
        $user = User::factory()->create(['role' => 'user', 'email' => 'user_test@myrvm.com']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.user');
        $response->assertSee('Poin Terkumpul');
    }

    public function test_role_change_is_logged()
    {
        // Mock Log
        Log::shouldReceive('channel')->with('daily')->andReturnSelf();
        Log::shouldReceive('info')->once()->withArgs(function ($message, $context) {
            return $message === 'SECURITY AUDIT:' && 
                   str_contains($context['changes'], "Role changed from 'user' to 'admin'");
        });

        $user = User::factory()->create(['role' => 'user']);
        
        // Act
        $user->role = 'admin';
        $user->save();
    }
}
