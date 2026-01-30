<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentCreated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $assignment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Assignment $assignment)
    {
        $this->assignment = $assignment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $machineName = $this->assignment->machine->name ?? 'Unknown Machine';
        $assignedByName = $this->assignment->assignedBy->name ?? 'System';
        $location = $this->assignment->address ?? "{$this->assignment->latitude}, {$this->assignment->longitude}";

        return (new MailMessage)
            ->subject('🔧 New RVM Installation Assignment')
            ->greeting("Hello {$notifiable->name}!")
            ->line("You have been assigned to install **{$machineName}**")
            ->line("**Assigned by:** {$assignedByName}")
            ->line("📍 **Location:** {$location}")
            ->when($this->assignment->notes, function ($mail) {
                return $mail->line("**Notes:** {$this->assignment->notes}");
            })
            ->action('View Assignment Details', url('/dashboard/assignments'))
            ->line('Please complete the installation as soon as possible.')
            ->salutation('Best regards, MyRVM Team');
    }

    /**
     * Get the array representation of the notification (for database).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'assignment_created',
            'assignment_id' => $this->assignment->id,
            'machine_id' => $this->assignment->machine_id,
            'machine_name' => $this->assignment->machine->name ?? null,
            'assigned_by_id' => $this->assignment->assigned_by,
            'assigned_by_name' => $this->assignment->assignedBy->name ?? null,
            'location' => $this->assignment->address,
            'latitude' => $this->assignment->latitude,
            'longitude' => $this->assignment->longitude,
            'notes' => $this->assignment->notes,
            'assigned_at' => $this->assignment->assigned_at,
        ];
    }
}
