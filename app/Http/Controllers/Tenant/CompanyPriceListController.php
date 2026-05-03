<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreCompanyPriceListRequest;
use App\Http\Requests\Tenant\UpdateCompanyPriceListRequest;
use App\Models\Company;
use App\Models\CompanyPriceList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $query = CompanyPriceList::query()
            ->where('company_id', $company->getKey())
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
            'priceLists' => $query->paginate(15)->withQueryString(),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', CompanyPriceList::class);

        return Inertia::render('Tenant/Pricing/PriceLists/Create');
    }

    public function store(StoreCompanyPriceListRequest $request): RedirectResponse
    {
        $this->authorize('create', CompanyPriceList::class);

        /** @var Company $company */
        $company = current_company();
        abort_if($company === null, 403);

        $validated = $request->validated();
        $validated['company_id'] = $company->getKey();

        $list = CompanyPriceList::query()->create($validated);

        if ($list->is_default) {
            CompanyPriceList::query()
                ->where('company_id', $company->getKey())
                ->whereKeyNot($list->getKey())
                ->update(['is_default' => false]);
        }

        return redirect()
            ->route('price-lists.index')
            ->with('type', 'success')
            ->with('message', __('tenant.saved'));
    }

    public function edit(CompanyPriceList $price_list): Response
    {
        $this->authorize('update', $price_list);

        return Inertia::render('Tenant/Pricing/PriceLists/Edit', [
            'priceList' => $price_list,
        ]);
    }

    public function update(UpdateCompanyPriceListRequest $request, CompanyPriceList $price_list): RedirectResponse
    {
        $this->authorize('update', $price_list);

        /** @var Company $company */
        $company = current_company();
        abort_if($company === null, 403);

        $validated = $request->validated();
        $validated['company_id'] = $company->getKey();

        $price_list->update($validated);

        if ($price_list->is_default) {
            CompanyPriceList::query()
                ->where('company_id', $company->getKey())
                ->whereKeyNot($price_list->getKey())
                ->update(['is_default' => false]);
        }

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
}
