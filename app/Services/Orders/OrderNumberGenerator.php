<?php

namespace App\Services\Orders;

use App\Models\NumberSequence;
use Illuminate\Support\Facades\DB;

class OrderNumberGenerator
{
    public function next(): string
    {
        return DB::transaction(function (): string {
            $this->ensureSequenceRow('order');

            /** @var NumberSequence $seq */
            $seq = NumberSequence::query()->where('key', 'order')->lockForUpdate()->firstOrFail();
            $seq->increment('last_value');

            return sprintf('ORD-%s-%06d', now()->format('Y'), (int) $seq->fresh()->last_value);
        });
    }

    private function ensureSequenceRow(string $key): void
    {
        NumberSequence::query()->firstOrCreate(
            ['key' => $key],
            ['last_value' => 0],
        );
    }
}
