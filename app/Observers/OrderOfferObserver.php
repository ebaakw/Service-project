<?php

namespace App\Observers;

use App\Events\TechnicianOfferedOrder;
use App\Jobs\ExpireOrderOfferJob;
use App\Models\OrderOffer;
use App\Notifications\OrderOfferToTechnician;
use Illuminate\Support\Facades\Notification;

class OrderOfferObserver
{
    public function creating(OrderOffer $offer): void
    {
        if ($offer->expires_at === null) {
            $offer->expires_at = now()->addDay();
        }

        if ($offer->offered_at === null) {
            $offer->offered_at = now();
        }
    }

    public function created(OrderOffer $offer): void
    {
        event(new TechnicianOfferedOrder($offer));

        $offer->loadMissing('technician.user');
        $technicianUser = $offer->technician?->user;
        if ($technicianUser !== null) {
            Notification::send($technicianUser, new OrderOfferToTechnician($offer));
        }

        ExpireOrderOfferJob::dispatch($offer->getKey())
            ->delay($offer->expires_at ?? now()->addDay());
    }
}
