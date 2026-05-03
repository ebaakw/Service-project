<?php

namespace App\Services\Orders;

use App\Models\NumberSequence;
use Illuminate\Support\Facades\DB;

class InvoiceNumberGenerator
{
    public function next(): string
    {
        return DB::transaction(function (): string {
            $this->ensureSequenceRow('invoice');

            /** @var NumberSequence $seq */
            $seq = NumberSequence::query()->where('key', 'invoice')->lockForUpdate()->firstOrFail();
            $seq->increment('last_value');

            return sprintf('INV-%s-%06d', now()->format('Y'), (int) $seq->fresh()->last_value);
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
