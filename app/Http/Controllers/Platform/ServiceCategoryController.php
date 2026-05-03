<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreServiceCategoryRequest;
use App\Http\Requests\Platform\UpdateServiceCategoryRequest;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ServiceCategory::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->input('status', 'all');

        $query = ServiceCategory::query()->orderBy('sort_order')->orderBy('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name_ar', 'like', '%'.$search.'%')
                    ->orWhere('name_en', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%');
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        return Inertia::render('Platform/ServiceCatalog/Categories/Index', [
            'categories' => $query->paginate(15)->withQueryString(),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ServiceCategory::class);

        return Inertia::render('Platform/ServiceCatalog/Categories/Create');
    }

    public function store(StoreServiceCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', ServiceCategory::class);

        ServiceCategory::query()->create($request->validated());

        return redirect()
            ->route('platform.service-categories.index')
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }

    public function edit(ServiceCategory $service_category): Response
    {
        $this->authorize('update', $service_category);

        return Inertia::render('Platform/ServiceCatalog/Categories/Edit', [
            'category' => $service_category,
        ]);
    }

    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $service_category): RedirectResponse
    {
        $this->authorize('update', $service_category);

        $service_category->update($request->validated());

        return redirect()
            ->route('platform.service-categories.index')
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }

    public function destroy(ServiceCategory $service_category): RedirectResponse
    {
        $this->authorize('delete', $service_category);

        $service_category->delete();

        return redirect()
            ->route('platform.service-categories.index')
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }
}
