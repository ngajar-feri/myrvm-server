<?php

declare(strict_types=1);

namespace App\Events\Kiosk;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * SessionAuthorizedEvent
 * 
 * Broadcasted when a user successfully scans the QR code
 * and authorizes their session with the RVM machine.
 * 
 * @package App\Events\Kiosk
 */
class SessionAuthorizedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param string $machineUuid
     * @param User $user
     * @param string $sessionId
     */
    public function __construct(
        public string $machineUuid,
        public User $user,
        public string $sessionId,
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
        return 'session.authorized';
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
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'balance' => $this->user->balance ?? 0,
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
