<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\DiscountScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreDiscountRequest;
use App\Http\Requests\Tenant\UpdateDiscountRequest;
use App\Models\Company;
use App\Models\CompanyPriceItem;
use App\Models\Discount;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiscountController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Discount::class);

        $company = current_company();
        abort_if($company === null, 403);

        $search = $request->string('search')->trim()->value();
        $status = $request->input('status', 'all');
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $query = Discount::query()
            ->where('company_id', $company->getKey())
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('name_ar', 'like', '%'.$search.'%')
                    ->orWhere('name_en', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('description_ar', 'like', '%'.$search.'%')
                    ->orWhere('description_en', 'like', '%'.$search.'%');
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        return Inertia::render('Tenant/Pricing/Discounts/Index', [
            'discounts' => $query->paginate($perPage)->withQueryString(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Discount::class);

        $company = current_company();
        abort_if($company === null, 403);

        $categories = ServiceCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name_ar', 'name_en']);

        $priceItems = CompanyPriceItem::query()
            ->where('company_id', $company->getKey())
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'company_price_list_id']);

        return Inertia::render('Tenant/Pricing/Discounts/Create', [
            'categories' => $categories,
            'priceItems' => $priceItems,
        ]);
    }

    public function store(StoreDiscountRequest $request): RedirectResponse
    {
        $this->authorize('create', Discount::class);

        /** @var Company $company */
        $company = current_company();
        abort_if($company === null, 403);

        $validated = $request->validated();
        $categoryIds = $validated['category_ids'] ?? [];
        $itemIds = $validated['company_price_item_ids'] ?? [];
        unset($validated['category_ids'], $validated['company_price_item_ids']);

        $validated['company_id'] = $company->getKey();

        $discount = Discount::query()->create($validated);

        $this->syncDiscountRelations($discount, $categoryIds, $itemIds);

        return redirect()
            ->route('discounts.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    public function edit(Discount $discount): Response
    {
        $this->authorize('update', $discount);

        $company = current_company();
        abort_if($company === null, 403);

        $categories = ServiceCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name_ar', 'name_en']);

        $priceItems = CompanyPriceItem::query()
            ->where('company_id', $company->getKey())
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'company_price_list_id']);

        $discount->load(['categories:id', 'priceItems:id']);

        return Inertia::render('Tenant/Pricing/Discounts/Edit', [
            'discount' => $discount,
            'categories' => $categories,
            'priceItems' => $priceItems,
            'selectedCategoryIds' => $discount->categories->pluck('id')->all(),
            'selectedPriceItemIds' => $discount->priceItems->pluck('id')->all(),
        ]);
    }

    public function update(UpdateDiscountRequest $request, Discount $discount): RedirectResponse
    {
        $this->authorize('update', $discount);

        /** @var Company $company */
        $company = current_company();
        abort_if($company === null, 403);

        $validated = $request->validated();
        $categoryIds = $validated['category_ids'] ?? [];
        $itemIds = $validated['company_price_item_ids'] ?? [];
        unset($validated['category_ids'], $validated['company_price_item_ids']);

        $validated['company_id'] = $company->getKey();

        $discount->update($validated);

        $this->syncDiscountRelations($discount->fresh(), $categoryIds, $itemIds);

        return redirect()
            ->route('discounts.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    public function destroy(Discount $discount): RedirectResponse
    {
        $this->authorize('delete', $discount);

        $discount->delete();

        return redirect()
            ->route('discounts.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    /**
     * @param  list<int>  $categoryIds
     * @param  list<int>  $itemIds
     */
    private function syncDiscountRelations(Discount $discount, array $categoryIds, array $itemIds): void
    {
        $scope = $discount->discount_scope;

        if ($scope === DiscountScope::FullMenu) {
            $discount->categories()->detach();
            $discount->priceItems()->detach();

            return;
        }

        if ($scope === DiscountScope::Category) {
            $discount->categories()->sync($categoryIds);
            $discount->priceItems()->detach();

            return;
        }

        if ($scope === DiscountScope::PriceItem) {
            $discount->priceItems()->sync($itemIds);
            $discount->categories()->detach();
        }
    }
}
