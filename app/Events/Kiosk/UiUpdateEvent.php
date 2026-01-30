<?php

declare(strict_types=1);

namespace App\Events\Kiosk;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * UiUpdateEvent
 * 
 * Broadcasted when the kiosk UI needs to update its display.
 * Used for state changes like session authorization, item processing, etc.
 * 
 * @package App\Events\Kiosk
 */
class UiUpdateEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * UI update types.
     */
    public const TYPE_SESSION_AUTHORIZED = 'session_authorized';
    public const TYPE_ITEM_PROCESSED = 'item_processed';
    public const TYPE_SESSION_ENDED = 'session_ended';
    public const TYPE_HARDWARE_STATUS = 'hardware_status';
    public const TYPE_ERROR = 'error';

    /**
     * Create a new event instance.
     *
     * @param string $machineUuid
     * @param string $updateType
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $machineUuid,
        public string $updateType,
        public array $payload = [],
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("rvm.{$this->machineUuid}"),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'ui.update';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->updateType,
            'payload' => $this->payload,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
