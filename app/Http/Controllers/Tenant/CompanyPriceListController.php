<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreCompanyPriceListRequest;
use App\Http\Requests\Tenant\UpdateCompanyPriceListRequest;
use App\Models\Company;
use App\Models\CompanyPriceList;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CompanyPriceListController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CompanyPriceList::class);

        $company = current_company();
        abort_if($company === null, 403);

        $search = $request->string('search')->trim()->value();
        $status = $request->input('status', 'all');
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $query = CompanyPriceList::query()
            ->where('company_id', $company->getKey())
            ->with('serviceCategories:id,name_ar,name_en')
            ->orderByDesc('is_default')
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

        return Inertia::render('Tenant/Pricing/PriceLists/Index', [
            'priceLists' => $query->paginate($perPage)->withQueryString(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', CompanyPriceList::class);

        return Inertia::render('Tenant/Pricing/PriceLists/Create', [
            'categories' => $this->activeCategories(),
        ]);
    }

    public function store(StoreCompanyPriceListRequest $request): RedirectResponse
    {
        $this->authorize('create', CompanyPriceList::class);

        /** @var Company $company */
        $company = current_company();
        abort_if($company === null, 403);

        $validated = $request->validated();
        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);

        $validated['company_id'] = $company->getKey();
        $validated['is_active'] = $validated['is_active'] ?? true;

        DB::transaction(function () use ($company, $categoryIds, $validated): void {
            $list = CompanyPriceList::query()->create($validated);

            $this->syncDefaultFlag($list, $company);
            $list->serviceCategories()->sync($categoryIds);
        });

        return redirect()
            ->route('price-lists.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    public function edit(CompanyPriceList $price_list): Response
    {
        $this->authorize('update', $price_list);

        $price_list->load('serviceCategories:id');

        return Inertia::render('Tenant/Pricing/PriceLists/Edit', [
            'priceList' => $price_list,
            'categories' => $this->activeCategories(),
            'selectedCategoryIds' => $price_list->serviceCategories->pluck('id')->all(),
        ]);
    }

    public function update(UpdateCompanyPriceListRequest $request, CompanyPriceList $price_list): RedirectResponse
    {
        $this->authorize('update', $price_list);

        /** @var Company $company */
        $company = current_company();
        abort_if($company === null, 403);

        $validated = $request->validated();
        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);

        $validated['company_id'] = $company->getKey();

        DB::transaction(function () use ($categoryIds, $company, $price_list, $validated): void {
            $price_list->update($validated);
            $price_list->refresh();
            $this->syncDefaultFlag($price_list, $company);
            $price_list->serviceCategories()->sync($categoryIds);
        });

        return redirect()
            ->route('price-lists.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    public function destroy(CompanyPriceList $price_list): RedirectResponse
    {
        $this->authorize('delete', $price_list);

        $price_list->delete();

        return redirect()
            ->route('price-lists.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    private function syncDefaultFlag(CompanyPriceList $priceList, Company $company): void
    {
        if ($priceList->is_default) {
            CompanyPriceList::query()
                ->where('company_id', $company->getKey())
                ->whereKeyNot($priceList->getKey())
                ->update(['is_default' => false]);
        }
    }

    private function activeCategories()
    {
        return ServiceCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name_ar', 'name_en']);
    }
}
