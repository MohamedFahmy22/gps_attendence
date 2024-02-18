<?php

namespace App\Listeners;

use App\Events\LateAttendanceDetected;
use App\Models\Admin;
use App\Notifications\LateAttendanceNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLateAttendanceNotification
{

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\LateAttendanceDetected  $event
     * @return void
     */
    public function handle(LateAttendanceDetected $event)
    {
        $admin = Admin::first(); // Example retrieval of admin
        if ($admin) {
            $admin->notify(new LateAttendanceNotification($event->employee, $event->lateHours));
        }
    }
}
