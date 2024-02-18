<?php

namespace App\Events;

use App\Models\Employee;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LateAttendanceDetected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $employee;
    public $lateHours;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Employee $employee, $lateHours)
    {
        $this->employee = $employee;
        $this->lateHours = $lateHours;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('late-attendance');
    }
}
