<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\ReorderServiceCategoriesRequest;
use App\Http\Requests\Platform\StoreServiceCategoryRequest;
use App\Http\Requests\Platform\UpdateServiceCategoryRequest;
use App\Models\ServiceCategory;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ServiceCategoryController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $mediaUploadService,
    ) {}

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

        $query = ServiceCategory::query()
            ->with('parent:id,name_ar,name_en')
            ->orderBy('sort_order')
            ->orderBy('id');

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
                'parent_id' => $category->parent_id,
                'parent_name_ar' => $category->parent?->name_ar,
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

    public function reorder(ReorderServiceCategoriesRequest $request): RedirectResponse
    {
        /** @var list<int> $ids */
        $ids = array_map('intval', $request->validated('ids'));

        $categories = ServiceCategory::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($ids as $id) {
            /** @var ServiceCategory $category */
            $category = $categories->get($id);
            $this->authorize('update', $category);
        }

        $sortedOrders = $categories
            ->pluck('sort_order')
            ->map(fn ($order) => (int) $order)
            ->sort()
            ->values()
            ->all();

        DB::transaction(function () use ($ids, $sortedOrders): void {
            foreach ($ids as $index => $id) {
                ServiceCategory::query()
                    ->whereKey($id)
                    ->update(['sort_order' => $sortedOrders[$index]]);
            }
        });

        return back()
            ->with('type', 'success')
            ->with('message', __('dashboard.service_categories.reorder_success'));
    }

    public function create(): Response
    {
        $this->authorize('create', ServiceCategory::class);

        return Inertia::render('Platform/ServiceCatalog/Categories/Create', [
            'parentOptions' => $this->rootParentOptions(),
        ]);
    }

    public function store(StoreServiceCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', ServiceCategory::class);

        $data = $request->safe()->except(['icon']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['parent_id'] = $data['parent_id'] ?? null;

        $category = ServiceCategory::query()->create($data);

        if ($request->hasFile('icon')) {
            $this->mediaUploadService->storeServiceCategoryIcon($category, $request->file('icon'));
        }

        return redirect()
            ->route('platform.service-categories.index')
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }

    public function edit(ServiceCategory $service_category): Response
    {
        $this->authorize('update', $service_category);

        return Inertia::render('Platform/ServiceCatalog/Categories/Edit', [
            'category' => [
                'id' => $service_category->id,
                'parent_id' => $service_category->parent_id,
                'name_ar' => $service_category->name_ar,
                'name_en' => $service_category->name_en,
                'slug' => $service_category->slug,
                'description_ar' => $service_category->description_ar,
                'description_en' => $service_category->description_en,
                'sort_order' => $service_category->sort_order,
                'is_active' => (bool) $service_category->is_active,
                'icon_url' => $service_category->iconUrl(),
            ],
            'parentOptions' => $this->rootParentOptions($service_category->getKey()),
        ]);
    }

    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $service_category): RedirectResponse
    {
        $this->authorize('update', $service_category);

        $data = $request->safe()->except(['icon']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['parent_id'] = $data['parent_id'] ?? null;

        $service_category->update($data);

        if ($request->hasFile('icon')) {
            $this->mediaUploadService->storeServiceCategoryIcon($service_category, $request->file('icon'));
        }

        return redirect()
            ->route('platform.service-categories.index')
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }

    public function destroy(ServiceCategory $service_category): RedirectResponse
    {
        $this->authorize('delete', $service_category);

        if ($service_category->icon_path) {
            Storage::disk('public')->delete($service_category->icon_path);
        }

        $service_category->delete();

        return redirect()
            ->route('platform.service-categories.index')
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }

    /**
     * @return list<array{id: int, name_ar: string, name_en: string|null}>
     */
    private function rootParentOptions(?int $excludeId = null): array
    {
        $query = ServiceCategory::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($excludeId !== null) {
            $query->whereKeyNot($excludeId);
        }

        return $query->get(['id', 'name_ar', 'name_en'])
            ->map(fn (ServiceCategory $category): array => [
                'id' => (int) $category->id,
                'name_ar' => $category->name_ar,
                'name_en' => $category->name_en,
            ])
            ->all();
    }
}
