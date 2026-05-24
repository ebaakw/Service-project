<?php

namespace App\Services\Pricing;

use App\Models\Company;
use App\Models\CompanyPriceItem;
use Illuminate\Support\Facades\DB;

class CompanyPriceItemImportService
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{created: int, updated: int}
     */
    public function import(Company $company, int $priceListId, array $rows): array
    {
        return DB::transaction(function () use ($company, $priceListId, $rows): array {
            $created = 0;
            $updated = 0;

            foreach ($rows as $row) {
                $id = $row['id'] ?? null;
                unset($row['id']);

                $payload = array_merge($row, [
                    'company_id' => $company->getKey(),
                    'company_price_list_id' => $priceListId,
                    'is_active' => $row['is_active'] ?? true,
                    'sort_order' => $row['sort_order'] ?? 0,
                ]);

                if ($id !== null) {
                    CompanyPriceItem::query()
                        ->where('company_id', $company->getKey())
                        ->where('company_price_list_id', $priceListId)
                        ->whereKey((int) $id)
                        ->firstOrFail()
                        ->update($payload);

                    $updated++;

                    continue;
                }

                CompanyPriceItem::query()->create($payload);
                $created++;
            }

            return ['created' => $created, 'updated' => $updated];
        });
    }
}
