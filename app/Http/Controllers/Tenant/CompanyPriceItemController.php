<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ImportCompanyPriceItemsRequest;
use App\Http\Requests\Tenant\StoreCompanyPriceItemRequest;
use App\Http\Requests\Tenant\UpdateCompanyPriceItemRequest;
use App\Models\Company;
use App\Models\CompanyPriceItem;
use App\Models\CompanyPriceList;
use App\Models\ServiceCategory;
use App\Services\Pricing\CompanyPriceItemImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyPriceItemController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CompanyPriceItem::class);

        $company = current_company();
        abort_if($company === null, 403);

        $search = $request->string('search')->trim()->value();
        $status = $request->input('status', 'all');
        $priceListId = $request->query('price_list_id');
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $query = CompanyPriceItem::query()
            ->where('company_id', $company->getKey())
            ->with(['priceList', 'serviceCategory', 'serviceProblem'])
            ->orderBy('sort_order')
            ->orderByDesc('id');

        if ($priceListId !== null && $priceListId !== '') {
            $query->where('company_price_list_id', (int) $priceListId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name_ar', 'like', '%'.$search.'%')
                    ->orWhere('name_en', 'like', '%'.$search.'%')
                    ->orWhere('description_ar', 'like', '%'.$search.'%')
                    ->orWhere('description_en', 'like', '%'.$search.'%');
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $priceLists = CompanyPriceList::query()
            ->where('company_id', $company->getKey())
            ->with('serviceCategories:id,name_ar,name_en')
            ->orderBy('name_ar')
            ->get(['id', 'name', 'name_ar', 'name_en']);

        return Inertia::render('Tenant/Pricing/PriceItems/Index', [
            'priceItems' => $query->paginate($perPage)->withQueryString(),
            'priceLists' => $priceLists,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'price_list_id' => $priceListId,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', CompanyPriceItem::class);

        $company = current_company();
        abort_if($company === null, 403);

        $priceLists = CompanyPriceList::query()
            ->where('company_id', $company->getKey())
            ->with('serviceCategories:id,name_ar,name_en')
            ->orderBy('name_ar')
            ->get();

        $categories = ServiceCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Tenant/Pricing/PriceItems/Create', [
            'priceLists' => $priceLists,
            'categories' => $categories,
            'defaultPriceListId' => $request->query('price_list_id'),
        ]);
    }

    public function store(StoreCompanyPriceItemRequest $request): RedirectResponse
    {
        $this->authorize('create', CompanyPriceItem::class);

        /** @var Company $company */
        $company = current_company();
        abort_if($company === null, 403);

        $validated = $request->validated();
        $validated['company_id'] = $company->getKey();

        CompanyPriceItem::query()->create($validated);

        return redirect()
            ->route('price-items.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    public function import(ImportCompanyPriceItemsRequest $request, CompanyPriceItemImportService $importService): RedirectResponse
    {
        $this->authorize('create', CompanyPriceItem::class);

        /** @var Company $company */
        $company = current_company();
        abort_if($company === null, 403);

        $validated = $request->validated();
        $result = $importService->import($company, (int) $validated['company_price_list_id'], $validated['rows']);

        return redirect()
            ->route('price-items.index', ['price_list_id' => $validated['company_price_list_id']])
            ->with('type', 'success')
            ->with('message', __('tenant.price_items_import_success', $result));
    }

    public function edit(CompanyPriceItem $price_item): Response
    {
        $this->authorize('update', $price_item);

        $company = current_company();
        abort_if($company === null, 403);

        $priceLists = CompanyPriceList::query()
            ->where('company_id', $company->getKey())
            ->with('serviceCategories:id,name_ar,name_en')
            ->orderBy('name_ar')
            ->get();

        $categories = ServiceCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Tenant/Pricing/PriceItems/Edit', [
            'priceItem' => $price_item,
            'priceLists' => $priceLists,
            'categories' => $categories,
        ]);
    }

    public function update(UpdateCompanyPriceItemRequest $request, CompanyPriceItem $price_item): RedirectResponse
    {
        $this->authorize('update', $price_item);

        /** @var Company $company */
        $company = current_company();
        abort_if($company === null, 403);

        $validated = $request->validated();
        $validated['company_id'] = $company->getKey();

        $price_item->update($validated);

        return redirect()
            ->route('price-items.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    public function destroy(CompanyPriceItem $price_item): RedirectResponse
    {
        $this->authorize('delete', $price_item);

        $price_item->delete();

        return redirect()
            ->route('price-items.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }
}
