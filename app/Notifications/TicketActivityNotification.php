<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Ticket $ticket,
        private readonly string $event,
        private readonly string $message
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_title' => $this->ticket->title,
            'event' => $this->event,
            'message' => $this->message,
            'url' => route('tickets.show', $this->ticket),
        ];
    }
}
