<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreServiceIntakeFieldRequest;
use App\Http\Requests\Platform\UpdateServiceIntakeFieldRequest;
use App\Models\ServiceCategory;
use App\Models\ServiceIntakeField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceIntakeFieldController extends Controller
{
    public function index(Request $request, ServiceCategory $service_category): Response
    {
        $this->authorize('viewAny', ServiceIntakeField::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->input('status', 'all');
        $problemId = $request->query('problem_id');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 25, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $query = $service_category->intakeFields()
            ->with('problem')
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($problemId !== null && $problemId !== '') {
            $query->where('service_problem_id', (int) $problemId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('label_ar', 'like', '%'.$search.'%')
                    ->orWhere('label_en', 'like', '%'.$search.'%');
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        return Inertia::render('Platform/ServiceCatalog/IntakeFields/Index', [
            'category' => $service_category,
            'problems' => $service_category->problems()->orderBy('sort_order')->get(['id', 'title_ar']),
            'fields' => $query->paginate($perPage)->withQueryString(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'problem_id' => $problemId,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(ServiceCategory $service_category): Response
    {
        $this->authorize('create', ServiceIntakeField::class);

        return Inertia::render('Platform/ServiceCatalog/IntakeFields/Create', [
            'category' => $service_category,
            'problems' => $service_category->problems()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(StoreServiceIntakeFieldRequest $request, ServiceCategory $service_category): RedirectResponse
    {
        $this->authorize('create', ServiceIntakeField::class);

        $data = $request->validated();
        $data['service_category_id'] = $service_category->getKey();

        ServiceIntakeField::query()->create($data);

        return redirect()
            ->route('platform.service-categories.intake-fields.index', $service_category)
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }

    public function edit(ServiceCategory $service_category, ServiceIntakeField $intake_field): Response
    {
        $this->authorize('update', $intake_field);

        return Inertia::render('Platform/ServiceCatalog/IntakeFields/Edit', [
            'category' => $service_category,
            'field' => $intake_field,
            'problems' => $service_category->problems()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(UpdateServiceIntakeFieldRequest $request, ServiceCategory $service_category, ServiceIntakeField $intake_field): RedirectResponse
    {
        $this->authorize('update', $intake_field);

        $data = $request->validated();
        $data['service_category_id'] = $service_category->getKey();

        $intake_field->update($data);

        return redirect()
            ->route('platform.service-categories.intake-fields.index', $service_category)
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }

    public function destroy(ServiceCategory $service_category, ServiceIntakeField $intake_field): RedirectResponse
    {
        $this->authorize('delete', $intake_field);

        $intake_field->delete();

        return redirect()
            ->route('platform.service-categories.intake-fields.index', $service_category)
            ->with('type', 'success')
            ->with('message', __('Saved.'));
    }
}
