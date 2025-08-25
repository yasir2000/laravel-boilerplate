<?php

namespace App\Events;

use App\Models\User;
use App\Models\WorkflowStep;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowStepAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public WorkflowStep $step;
    public User $assignee;

    /**
     * Create a new event instance.
     */
    public function __construct(WorkflowStep $step, User $assignee)
    {
        $this->step = $step;
        $this->assignee = $assignee;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->assignee->id),
            new PrivateChannel('workflow.' . $this->step->workflow_instance_id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'step' => [
                'id' => $this->step->id,
                'name' => $this->step->name,
                'description' => $this->step->description,
                'type' => $this->step->type,
                'status' => $this->step->status,
                'due_date' => $this->step->due_date?->toISOString(),
                'workflow_instance_id' => $this->step->workflow_instance_id,
            ],
            'assignee' => [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
                'email' => $this->assignee->email,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'workflow.step.assigned';
    }
}
