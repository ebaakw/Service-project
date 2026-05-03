<?php

namespace App\Jobs;

use App\Enums\OrderOfferStatus;
use App\Models\OrderOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpireOrderOfferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $orderOfferId) {}

    public function handle(): void
    {
        $offer = OrderOffer::query()->find($this->orderOfferId);
        if ($offer === null || $offer->status !== OrderOfferStatus::Offered) {
            return;
        }

        $offer->forceFill([
            'status' => OrderOfferStatus::Expired,
            'responded_at' => now(),
        ])->save();
    }
}
