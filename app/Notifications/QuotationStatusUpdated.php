<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuotationStatusUpdated extends Notification
{
    use Queueable;

    public $quotation;
    public $status;
    public $message;

    /**
     * Create a new notification instance.
     */
    public function __construct($quotation, $status, $message = null)
    {
        $this->quotation = $quotation;
        $this->status = $status;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Keeping it simple for now
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'quotation_id' => $this->quotation->id,
            'quotation_number' => $this->quotation->quotation_number,
            'status' => $this->status,
            'message' => $this->message ?? "Quotation #{$this->quotation->quotation_number} status updated to {$this->status}.",
            'link' => route('quotations.index'), // Or specific show link
        ];
    }
}
