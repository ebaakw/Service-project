<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class SequentialSortOrder
{
    /**
     * Assign dense sequential sort_order values (0..n-1) for the given id order.
     *
     * @param  list<int>  $orderedIds
     * @param  callable(int $id, int $index): void  $updater
     */
    public static function assign(array $orderedIds, callable $updater): void
    {
        DB::transaction(function () use ($orderedIds, $updater): void {
            foreach ($orderedIds as $index => $id) {
                $updater((int) $id, $index);
            }
        });
    }
}
