<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateAttendanceNotification extends Notification
{
    use Queueable;
    protected $employee;
    protected $lateHours;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($employee, $lateHours)
    {
        $this->employee = $employee;
        $this->lateHours = $lateHours;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('Hello, ' . $this->employee->name . '!')
            ->line('You have been marked late for your recent attendance.')
            ->line('Late Hours: ' . $this->lateHours)
            ->line('Please ensure to arrive on time in the future.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->name,
            'late_hours' => $this->lateHours,
            'notification_type' => 'late_attendance',
            'message' => 'You have been marked late for your recent attendance. Please ensure to arrive on time in the future.'
        ];
    }
}
