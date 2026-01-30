<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\RvmMachine;
use App\Models\EdgeDevice;
use App\Models\Transaction;

class EdgeApiV2Test extends TestCase
{
    use RefreshDatabase;

    protected $device;
    protected $apiKey = 'test-api-key-123';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Storage
        Storage::fake('public');

        // Setup Data
        $machine = RvmMachine::create([
            'serial_number' => 'RVM-TEST-001',
            'status' => 'online',
            'location' => 'Test Lab'
        ]);

        $this->device = EdgeDevice::create([
             'rvm_machine_id' => $machine->id,
             'device_id' => 'EDGE-TEST-001',
             'type' => 'NVIDIA Jetson',
             'api_key' => hash('sha256', $this->apiKey),
             'status' => 'online',
             'inventory_code' => 'INV-TEST-001'
        ]);
    }

    public function test_deposit_endpoint_stores_image_and_returns_success()
    {
        $payload = [
            'status' => 'ACCEPTED',
            'data' => json_encode([
                'classification' => 'plastic_bottle',
                'confidence' => 0.99,
                'session_id' => 'sess-123',
                'brand' => 'Coca Cola'
            ]),
            'image' => UploadedFile::fake()->image('bottle.jpg')
        ];

        $response = $this->postJson('/api/v1/edge/deposit', $payload, [
            'X-RVM-API-KEY' => $this->apiKey
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'status',
                     'data' => ['image_url', 'ai_result']
                 ]);

        // Verify Storage
        // The controller uses current date in path: deposits/Y-m-d/session_id/...
        $date = now()->format('Y-m-d');
        $files = Storage::disk('public')->allFiles("deposits/{$date}/sess-123");
        $this->assertNotEmpty($files, 'Image should be stored in public disk');
    }

    public function test_sync_offline_endpoint_creates_transactions()
    {
        $payload = [
            'transactions' => [
                [
                    'session_id' => 'offline-tx-1',
                    'timestamp' => now()->subHour()->toIso8601String(),
                    'items' => [
                        ['type' => 'plastic_bottle', 'weight' => 15.5, 'points' => 10],
                        ['type' => 'aluminum_can', 'weight' => 12.0, 'points' => 20]
                    ]
                ],
                [
                    'session_id' => 'offline-tx-2',
                    'timestamp' => now()->toIso8601String(),
                    'items' => [
                        ['type' => 'glass_bottle', 'weight' => 250, 'points' => 50]
                    ]
                ]
            ]
        ];

        $this->withoutExceptionHandling();
        $response = $this->postJson('/api/v1/edge/sync-offline', $payload, [
            'X-RVM-API-KEY' => $this->apiKey
        ]);

        if ($response->status() !== 200) {
            dump($response->json());
        }
        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'synced_count' => 2
                 ]);

        // Verify Database
        $this->assertDatabaseCount('transactions', 2);
        // Total items: 2 in first tx, 1 in second tx = 3 items total
        $this->assertDatabaseCount('transaction_items', 3);
        
        $this->assertDatabaseHas('transactions', [
            'rvm_machine_id' => $this->device->rvm_machine_id, // Linked to RVM, not Edge Device directly
            'total_items' => 2 // First tx
        ]);
    }
    
    public function test_invalid_api_key_is_rejected()
    {
        $response = $this->postJson('/api/v1/edge/sync-offline', [], [
            'X-RVM-API-KEY' => 'wrong-key'
        ]);
        
        // Should return 401 Unauthorized
        $response->assertStatus(401);
    }
}
