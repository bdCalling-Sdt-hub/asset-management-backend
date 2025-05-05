<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InspectionSheetNotification extends Notification
{
    use Queueable;

    protected $inspection_sheet;
    public function __construct($inspectionSheet)
    {
        $this->inspection_sheet = $inspectionSheet;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message'    => 'A new inspection sheet has been created',
            'inspection_id'  => $this->inspection_sheet->id,
            'product'  => $this->inspection_sheet->ticket->asset->product,
            'serial_number'  => $this->inspection_sheet->ticket->asset->serial_number,
            'created_at' => now(),
        ];
    }
}
