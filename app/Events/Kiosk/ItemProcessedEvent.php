<?php

declare(strict_types=1);

namespace App\Events\Kiosk;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ItemProcessedEvent
 * 
 * Broadcasted when an item (bottle/can) has been processed by the AI.
 * Contains the classification result and point value.
 * 
 * @package App\Events\Kiosk
 */
class ItemProcessedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param string $machineUuid
     * @param string $sessionId
     * @param bool $accepted
     * @param string $itemType
     * @param int $pointsAwarded
     * @param string|null $rejectionReason
     */
    public function __construct(
        public string $machineUuid,
        public string $sessionId,
        public bool $accepted,
        public string $itemType,
        public int $pointsAwarded = 0,
        public ?string $rejectionReason = null,
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
        return 'item.processed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->sessionId,
            'accepted' => $this->accepted,
            'item_type' => $this->itemType,
            'points_awarded' => $this->pointsAwarded,
            'rejection_reason' => $this->rejectionReason,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
