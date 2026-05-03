<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreServiceProblemRequest;
use App\Http\Requests\Platform\UpdateServiceProblemRequest;
use App\Models\ServiceCategory;
use App\Models\ServiceProblem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceProblemController extends Controller
{
    public function index(Request $request, ServiceCategory $service_category): Response
    {
        $this->authorize('viewAny', ServiceProblem::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->input('status', 'all');

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
            'problems' => $query->paginate(15)->withQueryString(),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
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

        $service_category->problems()->create($request->validated());

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
            'problem' => $problem,
        ]);
    }

    public function update(UpdateServiceProblemRequest $request, ServiceCategory $service_category, ServiceProblem $problem): RedirectResponse
    {
        $this->authorize('update', $problem);

        $problem->update($request->validated());

        return redirect()
            ->route('platform.service-categories.problems.index', $service_category)
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }

    public function destroy(ServiceCategory $service_category, ServiceProblem $problem): RedirectResponse
    {
        $this->authorize('delete', $problem);

        $problem->delete();

        return redirect()
            ->route('platform.service-categories.problems.index', $service_category)
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }
}
