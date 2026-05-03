<?php

namespace App\Notifications;

use App\Models\OrderOffer;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderOfferToTechnician extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public OrderOffer $offer) {}

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
        $offer = $this->offer;

        return [
            'type' => 'order_offer',
            'offer_id' => $offer->getKey(),
            'order_id' => $offer->order_id,
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, mixed>}
     */
    public function toFcm(object $notifiable): array
    {
        $offer = $this->offer;

        return [
            'title' => 'New order offer',
            'body' => 'You have a pending offer.',
            'data' => [
                'type' => 'order_offer',
                'offer_id' => (string) $offer->getKey(),
                'order_id' => (string) $offer->order_id,
            ],
        ];
    }
}
