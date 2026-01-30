<?php

declare(strict_types=1);

namespace App\Events\Kiosk;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * HardwareCommandEvent
 * 
 * Broadcasted when a maintenance command is sent from kiosk UI.
 * The Edge device (Python daemon) listens for this event.
 * 
 * @package App\Events\Kiosk
 */
class HardwareCommandEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param string $machineUuid
     * @param string $command
     * @param array<string, mixed> $params
     * @param string $requestId
     */
    public function __construct(
        public string $machineUuid,
        public string $command,
        public array $params = [],
        public string $requestId = '',
    ) {
        if (empty($this->requestId)) {
            $this->requestId = uniqid('cmd_', true);
        }
    }

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
        return 'hardware.command';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'command' => $this->command,
            'params' => $this->params,
            'request_id' => $this->requestId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
