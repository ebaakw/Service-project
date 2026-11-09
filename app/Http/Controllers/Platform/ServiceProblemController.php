<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\ReorderServiceProblemsRequest;
use App\Http\Requests\Platform\StoreServiceProblemRequest;
use App\Http\Requests\Platform\UpdateServiceProblemRequest;
use App\Models\ServiceCategory;
use App\Models\ServiceProblem;
use App\Services\Media\MediaUploadService;
use App\Support\SequentialSortOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ServiceProblemController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $mediaUploadService,
    ) {}

    public function index(Request $request, ServiceCategory $service_category): Response
    {
        $this->authorize('viewAny', ServiceProblem::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->input('status', 'all');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 25, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $query = $service_category->problems()->orderBy('sort_order')->orderBy('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('title_ar', 'like', '%'.$search.'%')
                    ->orWhere('title_en', 'like', '%'.$search.'%');
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        return Inertia::render('Platform/ServiceCatalog/Problems/Index', [
            'category' => $service_category,
            'problems' => $query->paginate($perPage)->withQueryString(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function reorder(ReorderServiceProblemsRequest $request, ServiceCategory $service_category): RedirectResponse
    {
        /** @var list<int> $ids */
        $ids = array_map('intval', $request->validated('ids'));

        $problems = $service_category->problems()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($ids as $id) {
            /** @var ServiceProblem $problem */
            $problem = $problems->get($id);
            $this->authorize('update', $problem);
        }

        SequentialSortOrder::assign($ids, function (int $id, int $index) use ($service_category): void {
            $service_category->problems()
                ->whereKey($id)
                ->update(['sort_order' => $index]);
        });

        return back()
            ->with('type', 'success')
            ->with('message', __('dashboard.service_problems.reorder_success'));
    }

    public function create(ServiceCategory $service_category): Response
    {
        $this->authorize('create', ServiceProblem::class);

        return Inertia::render('Platform/ServiceCatalog/Problems/Create', [
            'category' => $service_category,
        ]);
    }

    public function store(StoreServiceProblemRequest $request, ServiceCategory $service_category): RedirectResponse
    {
        $this->authorize('create', ServiceProblem::class);

        $data = $request->safe()->except(['icon']);
        $problem = $service_category->problems()->create($data);

        if ($request->hasFile('icon')) {
            $this->mediaUploadService->storeServiceProblemIcon($problem, $request->file('icon'));
        }

        return redirect()
            ->route('platform.service-categories.problems.index', $service_category)
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }

    public function edit(ServiceCategory $service_category, ServiceProblem $problem): Response
    {
        $this->authorize('update', $problem);

        return Inertia::render('Platform/ServiceCatalog/Problems/Edit', [
            'category' => $service_category,
            'problem' => [
                'id' => $problem->id,
                'title_ar' => $problem->title_ar,
                'title_en' => $problem->title_en,
                'description_ar' => $problem->description_ar,
                'description_en' => $problem->description_en,
                'is_other' => (bool) $problem->is_other,
                'sort_order' => $problem->sort_order,
                'is_active' => (bool) $problem->is_active,
                'icon_url' => $problem->iconUrl(),
            ],
        ]);
    }

    public function update(UpdateServiceProblemRequest $request, ServiceCategory $service_category, ServiceProblem $problem): RedirectResponse
    {
        $this->authorize('update', $problem);

        $data = $request->safe()->except(['icon']);
        $problem->update($data);

        if ($request->hasFile('icon')) {
            $this->mediaUploadService->storeServiceProblemIcon($problem, $request->file('icon'));
        }

        return redirect()
            ->route('platform.service-categories.problems.index', $service_category)
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }

    public function destroy(ServiceCategory $service_category, ServiceProblem $problem): RedirectResponse
    {
        $this->authorize('delete', $problem);

        if ($problem->icon_path) {
            Storage::disk('public')->delete($problem->icon_path);
        }

        $problem->delete();

        return redirect()
            ->route('platform.service-categories.problems.index', $service_category)
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }
}
