<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class SafeEloquent
{
    /**
     * Run a query that depends on an optional table (e.g. newly added migrations).
     * Missing table / schema drift returns an empty collection instead of a 500.
     *
     * @template TKey of array-key
     * @template TValue
     *
     * @param  callable(): Collection<TKey, TValue>  $callback
     * @return Collection<TKey, TValue>
     */
    public static function collectIfTable(string $table, callable $callback): Collection
    {
        if (! Schema::hasTable($table)) {
            return collect();
        }

        try {
            return $callback();
        } catch (QueryException $e) {
            report($e);

            return collect();
        } catch (Throwable $e) {
            // Enum cast / unexpected row shape should not take down guest browse.
            report($e);

            return collect();
        }
    }

    public static function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
