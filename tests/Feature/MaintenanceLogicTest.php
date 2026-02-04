<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\RvmMachine;
use App\Models\EdgeDevice;
use App\Models\User;

class MaintenanceLogicTest extends TestCase
{
    use RefreshDatabase;

    protected $machine;
    protected $edgeDevice;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user
        $this->user = User::factory()->create();

        // Create a machine
        $this->machine = RvmMachine::create([
            'name' => 'Test Machine',
            'location' => 'Test Lab',
            'serial_number' => 'TEST-RVM-001',
            'coordinates' => '0,0',
            'status' => 'offline', // Start offline
            'api_key' => 'test_key', // Plaintext as per middleware check
        ]);

        // Create edge device linked to machine
        $this->edgeDevice = EdgeDevice::create([
            'rvm_machine_id' => $this->machine->id,
            'device_id' => 'TEST-001-' . uniqid(),
            'type' => 'NVIDIA Jetson',
            'status' => 'offline',
            // EdgeDevice api_key is separate, but we need RvmMachine's for heartbeat
            'api_key' => hash('sha256', 'edge_test_key'), 
        ]);
    }

    /** @test */
    public function heartbeat_updates_status_from_offline_to_online()
    {
        // Arrange: Machine is offline
        $this->machine->update(['status' => 'offline']);

        // Act: Send Heartbeat to correct Edge endpoint
        $response = $this->postJson("/api/v1/edge/heartbeat", [
            'ip_local' => '192.168.1.100',
            'health_metrics' => ['cpu' => 50]
        ], [
            'X-RVM-API-KEY' => 'test_key' // Key matches the hash created in setUp
        ]);

        // Assert
        $response->assertStatus(200);
        
        $this->machine->refresh();
        $this->assertEquals('online', $this->machine->status, 'Machine should be online after heartbeat');
    }

    /** @test */
    public function heartbeat_preserves_maintenance_status()
    {
        // Arrange: Machine is in maintenance mode
        $this->machine->update(['status' => 'maintenance']);
        
        // Act: Send Heartbeat
        $response = $this->postJson("/api/v1/edge/heartbeat", [
            'ip_local' => '192.168.1.100',
            'health_metrics' => ['cpu' => 50]
        ], [
            'X-RVM-API-KEY' => 'test_key'
        ]);

        // Assert
        $response->assertStatus(200);
        
        $this->machine->refresh();
        $this->assertEquals('maintenance', $this->machine->status, 'Machine MUST remain in maintenance mode despite heartbeat');
    }
}
