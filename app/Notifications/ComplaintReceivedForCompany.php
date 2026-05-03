<?php

namespace App\Notifications;

use App\Models\FeedbackTicket;
use App\Notifications\Channels\FcmChannel;
use App\Support\CompanyNotifiables;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class ComplaintReceivedForCompany extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public FeedbackTicket $ticket) {}

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $ticket = $this->ticket;

        return [
            'type' => 'complaint',
            'ticket_ulid' => $ticket->ulid,
            'company_id' => $ticket->company_id,
            'subject' => $ticket->subject,
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, mixed>}
     */
    public function toFcm(object $notifiable): array
    {
        $ticket = $this->ticket;

        return [
            'title' => 'New complaint',
            'body' => $ticket->subject,
            'data' => [
                'type' => 'complaint',
                'ticket_ulid' => $ticket->ulid,
            ],
        ];
    }

    public static function notifyManagers(FeedbackTicket $ticket): void
    {
        if ($ticket->company_id === null) {
            return;
        }

        $recipients = CompanyNotifiables::complaintManagers((int) $ticket->company_id);
        if ($recipients->isEmpty()) {
            return;
        }

        NotificationFacade::send($recipients, new self($ticket));
    }
}
