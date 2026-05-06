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

        $actor = $request->user();

        $search = $request->string('search')->trim()->value();
        $status = $request->input('status', 'all');

        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 25, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

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

        $categories = $query->paginate($perPage)->withQueryString();

        $categories->through(function (ServiceCategory $category) use ($actor): array {
            return [
                'id' => $category->id,
                'name_ar' => $category->name_ar,
                'name_en' => $category->name_en,
                'slug' => $category->slug,
                'is_active' => (bool) $category->is_active,
                'sort_order' => $category->sort_order,
                'can_update' => $actor !== null && $actor->can('update', $category),
                'can_delete' => $actor !== null && $actor->can('delete', $category),
            ];
        });

        return Inertia::render('Platform/ServiceCatalog/Categories/Index', [
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
            'can' => [
                'create' => $actor !== null && $actor->can('create', ServiceCategory::class),
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
